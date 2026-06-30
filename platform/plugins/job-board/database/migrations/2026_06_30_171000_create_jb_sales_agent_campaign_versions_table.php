<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jb_sales_agent_campaign_versions')) {
            Schema::create('jb_sales_agent_campaign_versions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('campaign_id')->constrained('jb_sales_agent_campaigns')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedBigInteger('restored_from_version_id')->nullable();
                $table->string('label', 190)->nullable();
                $table->json('snapshot');
                $table->timestamps();

                $table->index('restored_from_version_id', 'jb_sacv_restored_idx');
                $table->foreign('restored_from_version_id', 'jb_sacv_restored_fk')
                    ->references('id')
                    ->on('jb_sales_agent_campaign_versions')
                    ->nullOnDelete();
            });

            return;
        }

        Schema::table('jb_sales_agent_campaign_versions', function (Blueprint $table): void {
            if (! Schema::hasColumn('jb_sales_agent_campaign_versions', 'restored_from_version_id')) {
                $table->unsignedBigInteger('restored_from_version_id')->nullable()->after('created_by');
            }
        });

        Schema::table('jb_sales_agent_campaign_versions', function (Blueprint $table): void {
            $database = DB::getDatabaseName();
            $foreignKeys = collect(DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$database, 'jb_sales_agent_campaign_versions']
            ))->pluck('CONSTRAINT_NAME')->all();
            $indexes = collect(DB::select(
                'SHOW INDEX FROM jb_sales_agent_campaign_versions'
            ))->pluck('Key_name')->unique()->values()->all();

            if (! in_array('jb_sacv_restored_idx', $indexes, true)) {
                $table->index('restored_from_version_id', 'jb_sacv_restored_idx');
            }

            if (! in_array('jb_sacv_restored_fk', $foreignKeys, true)) {
                $table->foreign('restored_from_version_id', 'jb_sacv_restored_fk')
                    ->references('id')
                    ->on('jb_sales_agent_campaign_versions')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_campaign_versions');
    }
};
