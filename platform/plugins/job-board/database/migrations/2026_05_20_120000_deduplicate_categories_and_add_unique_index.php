<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Build the canonical ID map (lowest ID wins per name) ─────
        $rows = DB::select("SELECT id, name FROM jb_categories ORDER BY id ASC");

        $byName = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row->name));
            $byName[$key][] = (int) $row->id;
        }

        $canonicalMap = [];  // duplicate_id -> canonical_id
        $allDupIds    = [];

        foreach ($byName as $ids) {
            if (count($ids) < 2) {
                continue;
            }
            $canonical = $ids[0];
            foreach (array_slice($ids, 1) as $dupId) {
                $canonicalMap[$dupId] = $canonical;
                $allDupIds[]          = $dupId;
            }
        }

        if (empty($allDupIds)) {
            // No duplicates — just add the index below.
            $this->addUniqueIndex();
            return;
        }

        // ── Step 2: Remap pivot rows to canonical category IDs ────────────────
        // Process in chunks to avoid huge IN clauses.
        foreach (array_chunk($allDupIds, 200) as $chunk) {
            // For each duplicate ID that points to a canonical, update the pivot.
            // We must also handle the case where a job already has BOTH the
            // canonical and the duplicate assigned — updating would create a
            // unique-key collision, so we delete those pivot rows instead.
            foreach ($chunk as $dupId) {
                $canonical = $canonicalMap[$dupId];

                // Find job_ids that already have the canonical category assigned.
                $alreadyHaveCanonical = DB::table('jb_jobs_categories')
                    ->where('category_id', $canonical)
                    ->pluck('job_id')
                    ->toArray();

                if (! empty($alreadyHaveCanonical)) {
                    // Delete pivot rows where the job already has the canonical.
                    DB::table('jb_jobs_categories')
                        ->where('category_id', $dupId)
                        ->whereIn('job_id', $alreadyHaveCanonical)
                        ->delete();
                }

                // Update remaining pivot rows to point to the canonical ID.
                DB::table('jb_jobs_categories')
                    ->where('category_id', $dupId)
                    ->update(['category_id' => $canonical]);
            }
        }

        // ── Step 3: Delete slug records for duplicate categories ──────────────
        foreach (array_chunk($allDupIds, 200) as $chunk) {
            DB::table('slugs')
                ->where('reference_type', 'Botble\\JobBoard\\Models\\Category')
                ->whereIn('reference_id', $chunk)
                ->delete();
        }

        // ── Step 4: Delete meta_boxes records for duplicate categories ────────
        foreach (array_chunk($allDupIds, 200) as $chunk) {
            DB::table('meta_boxes')
                ->where('reference_type', 'Botble\\JobBoard\\Models\\Category')
                ->whereIn('reference_id', $chunk)
                ->delete();
        }

        // ── Step 5: Delete the duplicate category rows ────────────────────────
        foreach (array_chunk($allDupIds, 200) as $chunk) {
            DB::table('jb_categories')->whereIn('id', $chunk)->delete();
        }

        // ── Step 6: Add unique index to prevent future duplicates ─────────────
        $this->addUniqueIndex();
    }

    protected function addUniqueIndex(): void
    {
        // Check the collation — utf8mb4_unicode_ci is case-insensitive, so a
        // regular unique index on `name` will correctly block "Foo" and "foo".
        if (! $this->indexExists('jb_categories', 'jb_categories_name_unique')) {
            Schema::table('jb_categories', function (Blueprint $table): void {
                $table->unique('name', 'jb_categories_name_unique');
            });
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return ! empty($indexes);
    }

    public function down(): void
    {
        Schema::table('jb_categories', function (Blueprint $table): void {
            $table->dropUnique('jb_categories_name_unique');
        });
        // Data cleanup cannot be reversed.
    }
};
