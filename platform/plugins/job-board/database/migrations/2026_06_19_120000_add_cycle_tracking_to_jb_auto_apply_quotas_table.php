<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('jb_auto_apply_quotas', 'order_id')) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                $table->foreignId('order_id')->nullable()->after('account_id')->constrained('jb_auto_apply_orders')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('jb_auto_apply_quotas', 'quota_key')) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                $table->string('quota_key', 50)->nullable()->after('period');
            });
        }

        if (! Schema::hasColumn('jb_auto_apply_quotas', 'cycle_started_at') || ! Schema::hasColumn('jb_auto_apply_quotas', 'cycle_ends_at')) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                if (! Schema::hasColumn('jb_auto_apply_quotas', 'cycle_started_at')) {
                    $table->timestamp('cycle_started_at')->nullable()->after('plan');
                }

                if (! Schema::hasColumn('jb_auto_apply_quotas', 'cycle_ends_at')) {
                    $table->timestamp('cycle_ends_at')->nullable()->after('cycle_started_at');
                }
            });
        }

        DB::table('jb_auto_apply_quotas')->whereNull('quota_key')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('jb_auto_apply_quotas')
                    ->where('id', $row->id)
                    ->update(['quota_key' => 'legacy-' . $row->id]);
            }
        });

        $indexes = collect(DB::select('SHOW INDEX FROM jb_auto_apply_quotas'))->pluck('Key_name')->unique()->all();

        if (! in_array('jb_auto_apply_quotas_account_id_idx', $indexes, true)) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                $table->index('account_id', 'jb_auto_apply_quotas_account_id_idx');
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM jb_auto_apply_quotas'))->pluck('Key_name')->unique()->all();

        if (in_array('jb_auto_apply_quotas_account_id_period_plan_unique', $indexes, true)) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                $table->dropUnique('jb_auto_apply_quotas_account_id_period_plan_unique');
            });
        }

        $indexes = collect(DB::select('SHOW INDEX FROM jb_auto_apply_quotas'))->pluck('Key_name')->unique()->all();

        if (! in_array('jb_auto_apply_quotas_account_id_quota_key_unique', $indexes, true)) {
            Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
                $table->unique(['account_id', 'quota_key'], 'jb_auto_apply_quotas_account_id_quota_key_unique');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM jb_auto_apply_quotas'))->pluck('Key_name')->unique()->all();

        Schema::table('jb_auto_apply_quotas', function (Blueprint $table) use ($indexes): void {
            if (in_array('jb_auto_apply_quotas_account_id_quota_key_unique', $indexes, true)) {
                $table->dropUnique('jb_auto_apply_quotas_account_id_quota_key_unique');
            }

            if (! in_array('jb_auto_apply_quotas_account_id_period_plan_unique', $indexes, true)) {
                $table->unique(['account_id', 'period', 'plan'], 'jb_auto_apply_quotas_account_id_period_plan_unique');
            }
        });

        Schema::table('jb_auto_apply_quotas', function (Blueprint $table): void {
            if (Schema::hasColumn('jb_auto_apply_quotas', 'order_id')) {
                $table->dropConstrainedForeignId('order_id');
            }

            $dropColumns = array_values(array_filter([
                Schema::hasColumn('jb_auto_apply_quotas', 'quota_key') ? 'quota_key' : null,
                Schema::hasColumn('jb_auto_apply_quotas', 'cycle_started_at') ? 'cycle_started_at' : null,
                Schema::hasColumn('jb_auto_apply_quotas', 'cycle_ends_at') ? 'cycle_ends_at' : null,
            ]));

            if ($dropColumns) {
                $table->dropColumn($dropColumns);
            }

            if (in_array('jb_auto_apply_quotas_account_id_idx', collect(DB::select('SHOW INDEX FROM jb_auto_apply_quotas'))->pluck('Key_name')->unique()->all(), true)) {
                $table->dropIndex('jb_auto_apply_quotas_account_id_idx');
            }
        });
    }
};
