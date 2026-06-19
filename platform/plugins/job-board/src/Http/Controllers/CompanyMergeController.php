<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Models\CompanyMergeLog;
use Botble\JobBoard\Services\CompanyMergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class CompanyMergeController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add(trans('plugins/job-board::company.name'), route('companies.index'))
            ->add('Merge Tool', route('companies.merge.picker'));
    }

    public function picker(Request $request)
    {
        $this->pageTitle('Merge Companies');

        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))));
        $preselected = Company::query()->whereKey($ids)->limit(2)->get();

        return view('plugins/job-board::companies.merge-picker', [
            'preselected' => $preselected,
            'recentLogs' => CompanyMergeLog::query()
                ->with(['winner', 'mergedBy', 'undoneBy'])
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->input('q', ''));

        if ($term === '' || mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $companies = Company::query()
            ->where(function ($query) use ($term): void {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('email', 'like', '%' . $term . '%')
                    ->orWhere('website', 'like', '%' . $term . '%');
            })
            ->withCount(['jobs', 'accounts'])
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn (Company $company) => $this->companyPayload($company));

        return response()->json(['data' => $companies]);
    }

    public function compare(Request $request): JsonResponse
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $request->query('ids', ''))));

        if (count($ids) !== 2) {
            return response()->json(['error' => true, 'message' => 'Select exactly 2 companies to compare.']);
        }

        $companies = Company::query()->withCount(['jobs', 'accounts'])->whereKey($ids)->get();

        if ($companies->count() !== 2) {
            return response()->json(['error' => true, 'message' => 'Could not find both selected companies.']);
        }

        $a = $companies->first();
        $b = $companies->last();

        $pair = app(CompanyMergeService::class)->determineWinnerLoser($a, $b);

        return response()->json([
            'error' => false,
            'companies' => [$this->companyPayload($a), $this->companyPayload($b)],
            'recommended_winner_id' => $pair ? $pair[0]->getKey() : null,
        ]);
    }

    public function merge(Request $request)
    {
        $request->validate([
            'winner_id' => ['required', 'integer', 'different:loser_id'],
            'loser_id' => ['required', 'integer'],
        ]);

        $winner = Company::query()->findOrFail($request->integer('winner_id'));
        $loser = Company::query()->findOrFail($request->integer('loser_id'));

        try {
            $log = app(CompanyMergeService::class)->merge($winner, $loser, Auth::id());
        } catch (RuntimeException $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage())
                ->withInput();
        }

        return $this
            ->httpResponse()
            ->setNextUrl(route('companies.merge.picker'))
            ->setMessage(
                "Merged \"{$log->loser_name}\" into \"{$winner->name}\". "
                . count($log->moved_job_ids) . ' job(s), '
                . count($log->moved_review_ids) . ' review(s) and '
                . count($log->moved_account_ids) . ' account(s) moved.'
            );
    }

    public function undo(Request $request, CompanyMergeLog $companyMergeLog)
    {
        try {
            app(CompanyMergeService::class)->undo($companyMergeLog, Auth::id());
        } catch (RuntimeException $exception) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($exception->getMessage());
        }

        return $this
            ->httpResponse()
            ->setNextUrl(route('companies.merge.picker'))
            ->setMessage('Merge undone — the company has been restored.');
    }

    protected function companyPayload(Company $company): array
    {
        return [
            'id' => $company->getKey(),
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'website' => $company->website,
            'address' => $company->address,
            'logo_thumb' => $company->logo_thumb,
            'is_verified' => (bool) $company->is_verified,
            'has_account' => ($company->accounts_count ?? $company->accounts()->count()) > 0,
            'jobs_count' => $company->jobs_count ?? $company->jobs()->count(),
            'completed_profile' => $company->completedProfile(),
            'created_at' => optional($company->created_at)->format('d M Y'),
            'edit_url' => route('companies.edit', $company->getKey()),
        ];
    }
}
