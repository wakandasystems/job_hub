<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_company_merge_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('winner_company_id');
            $table->unsignedBigInteger('loser_company_id');
            $table->string('loser_name', 120)->nullable();
            $table->string('loser_website', 120)->nullable();
            $table->json('winner_snapshot')->nullable();
            $table->json('loser_snapshot')->nullable();
            $table->json('winner_fields_changed')->nullable();
            $table->json('moved_job_ids')->nullable();
            $table->json('moved_review_ids')->nullable();
            $table->json('moved_account_ids')->nullable();
            $table->json('moved_ai_image_log_ids')->nullable();
            $table->json('moved_job_crawler_ids')->nullable();
            $table->unsignedBigInteger('merged_by')->nullable();
            $table->timestamp('undone_at')->nullable();
            $table->unsignedBigInteger('undone_by')->nullable();
            $table->timestamps();

            $table->index('winner_company_id', 'jb_company_merge_logs_winner_idx');
            $table->index('loser_company_id', 'jb_company_merge_logs_loser_idx');
            $table->index('loser_name', 'jb_company_merge_logs_loser_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_company_merge_logs');
    }
};
