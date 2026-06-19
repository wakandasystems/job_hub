<?php

namespace Botble\JobBoard\Services;

use Botble\Base\Events\DeletedContentEvent;
use Botble\JobBoard\Models\AiImageGenerationLog;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\CompanyMergeLog;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\JobCrawler;
use Botble\JobBoard\Models\Review;
use Botble\Slug\Facades\SlugHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class CompanyMergeService
{
    /**
     * Fields copied from the loser onto the winner only when the winner's own value is blank.
     * Identity fields (name, status, verification, unique_id, account_id) are deliberately excluded
     * so the winner's own admin-controlled state is never silently overwritten.
     */
    protected const GAP_FILL_FIELDS = [
        'address',
        'email',
        'phone',
        'year_founded',
        'number_of_offices',
        'number_of_employees',
        'annual_revenue',
        'description',
        'content',
        'website',
        'logo',
        'latitude',
        'longitude',
        'postal_code',
        'cover_image',
        'facebook',
        'twitter',
        'linkedin',
        'instagram',
        'ceo',
        'country_id',
        'state_id',
        'city_id',
        'tax_id',
    ];

    /**
     * Decide which of the two companies should survive a merge, based on which one has
     * a real linked employer login (jb_companies_accounts). Returns null when ambiguous
     * (both or neither have a linked account) — callers must ask an admin to choose.
     *
     * @return array{0: Company, 1: Company}|null [$winner, $loser]
     */
    public function determineWinnerLoser(Company $a, Company $b): ?array
    {
        $aHasAccount = $a->accounts()->exists();
        $bHasAccount = $b->accounts()->exists();

        if ($aHasAccount === $bHasAccount) {
            return null;
        }

        return $aHasAccount ? [$a, $b] : [$b, $a];
    }

    public function merge(Company $winner, Company $loser, ?int $performedByUserId): CompanyMergeLog
    {
        if ($winner->is($loser)) {
            throw new RuntimeException('Cannot merge a company into itself.');
        }

        return DB::transaction(function () use ($winner, $loser, $performedByUserId): CompanyMergeLog {
            $winner = Company::query()->whereKey($winner->getKey())->lockForUpdate()->firstOrFail();
            $loser = Company::query()->whereKey($loser->getKey())->lockForUpdate()->firstOrFail();

            $winnerSnapshot = $winner->toArray();
            $loserSnapshot = $loser->toArray();

            $movedJobIds = Job::query()->where('company_id', $loser->getKey())->pluck('id')->all();
            if ($movedJobIds) {
                Job::query()->whereKey($movedJobIds)->update(['company_id' => $winner->getKey()]);
            }

            $movedReviewIds = Review::query()
                ->where('reviewable_type', Company::class)
                ->where('reviewable_id', $loser->getKey())
                ->pluck('id')
                ->all();
            if ($movedReviewIds) {
                Review::query()->whereKey($movedReviewIds)->update(['reviewable_id' => $winner->getKey()]);
            }

            $loserAccountIds = $loser->accounts()->pluck('jb_accounts.id')->all();
            if ($loserAccountIds) {
                $winner->accounts()->syncWithoutDetaching($loserAccountIds);
            }

            $movedAiImageLogIds = AiImageGenerationLog::query()->where('company_id', $loser->getKey())->pluck('id')->all();
            if ($movedAiImageLogIds) {
                AiImageGenerationLog::query()->whereKey($movedAiImageLogIds)->update(['company_id' => $winner->getKey()]);
            }

            $movedJobCrawlerIds = JobCrawler::query()->where('default_company_id', $loser->getKey())->pluck('id')->all();
            if ($movedJobCrawlerIds) {
                JobCrawler::query()->whereKey($movedJobCrawlerIds)->update(['default_company_id' => $winner->getKey()]);
            }

            $fieldsChanged = [];
            foreach (self::GAP_FILL_FIELDS as $field) {
                if (blank($winner->{$field}) && filled($loser->{$field})) {
                    $fieldsChanged[$field] = $winner->{$field};
                    $winner->{$field} = $loser->{$field};
                }
            }

            $winner->contact_emails = array_values(array_unique(array_merge(
                $winner->contact_emails ?? [],
                $loser->contact_emails ?? [],
            )));
            $winner->contact_numbers = array_values(array_unique(array_merge(
                $winner->contact_numbers ?? [],
                $loser->contact_numbers ?? [],
            )));

            $winner->save();

            $log = CompanyMergeLog::create([
                'winner_company_id' => $winner->getKey(),
                'loser_company_id' => $loser->getKey(),
                'loser_name' => $loser->name,
                'loser_website' => $loser->website,
                'winner_snapshot' => $winnerSnapshot,
                'loser_snapshot' => $loserSnapshot,
                'winner_fields_changed' => $fieldsChanged,
                'moved_job_ids' => $movedJobIds,
                'moved_review_ids' => $movedReviewIds,
                'moved_account_ids' => $loserAccountIds,
                'moved_ai_image_log_ids' => $movedAiImageLogIds,
                'moved_job_crawler_ids' => $movedJobCrawlerIds,
                'merged_by' => $performedByUserId,
            ]);

            // Cascade-cleanup (slug, search index, etc.) the same way a normal admin delete would,
            // since jobs/reviews/accounts have already been moved off the loser above.
            DeletedContentEvent::dispatch(COMPANY_MODULE_SCREEN_NAME, request(), $loser);
            $loser->delete();

            return $log;
        });
    }

    public function undo(CompanyMergeLog $log, ?int $performedByUserId): void
    {
        if ($log->isUndone()) {
            throw new RuntimeException('This merge has already been undone.');
        }

        if (! $log->isUndoableSafely()) {
            throw new RuntimeException(
                'This company has since been merged into another one — undo the more recent merge first.'
            );
        }

        DB::transaction(function () use ($log, $performedByUserId): void {
            $winner = Company::query()->whereKey($log->winner_company_id)->lockForUpdate()->firstOrFail();

            if (Company::query()->whereKey($log->loser_company_id)->exists()) {
                throw new RuntimeException('A company already exists with the original ID — cannot safely restore.');
            }

            // Recreate the deleted company with its original primary key and every original column
            // (not just the fillable ones), so counters like `views` survive the round trip too.
            // Going through Eloquent (rather than a raw DB insert) lets the model's normal casts
            // turn the snapshot's serialized dates/arrays back into the right column format.
            $columns = Schema::getColumnListing('jb_companies');
            $loserAttributes = collect($log->loser_snapshot)->only($columns)->except(['id'])->all();

            $loser = new Company();
            $loser->forceFill($loserAttributes);
            $loser->setAttribute('id', $log->loser_company_id);
            $loser->exists = false;
            // Disable timestamp auto-touching only now, after forceFill has already cast
            // created_at/updated_at correctly — usesTimestamps() gates date-casting too.
            $loser->timestamps = false;
            $loser->save();

            // The original slug was removed when the company was deleted; give it a fresh one.
            SlugHelper::createSlug($loser);

            if ($log->moved_job_ids) {
                Job::query()->whereKey($log->moved_job_ids)->update(['company_id' => $loser->getKey()]);
            }

            if ($log->moved_review_ids) {
                Review::query()
                    ->where('reviewable_type', Company::class)
                    ->whereKey($log->moved_review_ids)
                    ->update(['reviewable_id' => $loser->getKey()]);
            }

            if ($log->moved_account_ids) {
                $loser->accounts()->syncWithoutDetaching($log->moved_account_ids);
                $winner->accounts()->detach($log->moved_account_ids);
            }

            if ($log->moved_ai_image_log_ids) {
                AiImageGenerationLog::query()->whereKey($log->moved_ai_image_log_ids)->update(['company_id' => $loser->getKey()]);
            }

            if ($log->moved_job_crawler_ids) {
                JobCrawler::query()->whereKey($log->moved_job_crawler_ids)->update(['default_company_id' => $loser->getKey()]);
            }

            foreach ((array) $log->winner_fields_changed as $field => $originalValue) {
                $winner->{$field} = $originalValue;
            }
            $winner->contact_emails = $log->winner_snapshot['contact_emails'] ?? [];
            $winner->contact_numbers = $log->winner_snapshot['contact_numbers'] ?? [];
            $winner->save();

            $log->forceFill([
                'undone_at' => now(),
                'undone_by' => $performedByUserId,
            ])->save();
        });
    }

    /**
     * Used by the crawler runner: when no live company matches a scraped name/website,
     * check whether that name/website previously belonged to a company that has since
     * been merged away, and resolve to the surviving company instead of creating a duplicate.
     * Follows chained merges (A merged into B, B later merged into C) up to a sane depth.
     */
    public function resolveByNameOrWebsite(string $name, ?string $website): ?Company
    {
        $normalizedName = $this->normalizeName($name);

        if ($normalizedName === '') {
            return null;
        }

        $log = CompanyMergeLog::query()
            ->whereNull('undone_at')
            ->where(function ($query) use ($website): void {
                $query->whereNotNull('loser_name');

                if ($website) {
                    $query->orWhere('loser_website', $website);
                }
            })
            ->latest('id')
            ->get()
            ->first(fn (CompanyMergeLog $candidate) => ($candidate->loser_website && $candidate->loser_website === $website)
                || $this->normalizeName((string) $candidate->loser_name) === $normalizedName);

        if (! $log) {
            return null;
        }

        $winnerId = $log->winner_company_id;

        for ($hops = 0; $hops < 5; $hops++) {
            $winner = Company::query()->find($winnerId);

            if ($winner) {
                return $winner;
            }

            // The winner itself was later merged away — follow the chain to its winner.
            $nextLog = CompanyMergeLog::query()
                ->whereNull('undone_at')
                ->where('loser_company_id', $winnerId)
                ->latest('id')
                ->first();

            if (! $nextLog) {
                return null;
            }

            $winnerId = $nextLog->winner_company_id;
        }

        return null;
    }

    protected function normalizeName(string $name): string
    {
        return Str::of($name)->replaceMatches('/\s+/', ' ')->trim()->lower()->toString();
    }
}
