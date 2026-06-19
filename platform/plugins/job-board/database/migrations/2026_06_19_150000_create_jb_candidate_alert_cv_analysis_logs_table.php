<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_candidate_alert_cv_analysis_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_alert_id')->nullable()->constrained('jb_candidate_alerts')->nullOnDelete();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->string('ai_provider', 40)->nullable();
            $table->string('ai_model', 120)->nullable();
            $table->string('status', 30)->default('success');
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->unsignedInteger('processing_ms')->nullable();
            $table->unsignedInteger('extracted_characters')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['candidate_alert_id', 'created_at'], 'jb_candidate_alert_cv_logs_alert_created_idx');
            $table->index(['status', 'created_at'], 'jb_candidate_alert_cv_logs_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_candidate_alert_cv_analysis_logs');
    }
};
