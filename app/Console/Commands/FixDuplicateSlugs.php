<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FixDuplicateSlugs extends Command
{
    protected $signature = 'slugs:fix-duplicates {--dry-run : Show what would change without writing}';
    protected $description = 'Rename duplicate slug keys by appending a unique 6-char suffix (keeps the oldest slug unchanged)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Find every (key, prefix, reference_type) group with more than one entry
        $groups = DB::table('slugs')
            ->select('key', 'prefix', 'reference_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('key', 'prefix', 'reference_type')
            ->having('cnt', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate slugs found.');
            return 0;
        }

        $this->info("Found {$groups->count()} duplicate slug groups. Processing...");
        $fixed = 0;

        foreach ($groups as $group) {
            // Get all duplicates for this group, ordered oldest first (keep the first one)
            $rows = DB::table('slugs')
                ->where('key', $group->key)
                ->where('prefix', $group->prefix)
                ->where('reference_type', $group->reference_type)
                ->orderBy('id')
                ->get();

            // Skip the oldest (first) row — rename all subsequent ones
            foreach ($rows->skip(1) as $row) {
                $newKey = $this->generateUniqueKey($group->key, $group->prefix);

                if ($dryRun) {
                    $this->line("  [dry-run] slug #{$row->id} ({$row->reference_type} #{$row->reference_id}): '{$group->key}' → '{$newKey}'");
                } else {
                    DB::table('slugs')->where('id', $row->id)->update(['key' => $newKey]);
                    $this->line("  Fixed slug #{$row->id} ({$row->reference_type} #{$row->reference_id}): '{$group->key}' → '{$newKey}'");
                }

                $fixed++;
            }
        }

        $action = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("{$action} {$fixed} duplicate slug(s).");

        return 0;
    }

    private function generateUniqueKey(string $baseKey, ?string $prefix): string
    {
        do {
            $candidate = $baseKey . '-' . strtolower(Str::random(6));
        } while (DB::table('slugs')->where('key', $candidate)->where('prefix', $prefix)->exists());

        return $candidate;
    }
}
