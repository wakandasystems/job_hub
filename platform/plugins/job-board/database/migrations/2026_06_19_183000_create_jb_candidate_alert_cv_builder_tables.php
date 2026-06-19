<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_candidate_alert_cv_builder_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidate_alert_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('candidate_name', 150)->nullable();
            $table->string('whatsapp_number', 40);
            $table->string('status', 30)->default('collecting');
            $table->unsignedSmallInteger('current_question_index')->default(0);
            $table->json('questions')->nullable();
            $table->longText('conversation_text')->nullable();
            $table->json('structured_cv')->nullable();
            $table->string('docx_path', 500)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['candidate_alert_id', 'created_at'], 'jb_cv_builder_sessions_alert_created_idx');
            $table->index(['status', 'created_at'], 'jb_cv_builder_sessions_status_created_idx');
            $table->foreign('candidate_alert_id', 'jb_cv_builder_sessions_alert_fk')
                ->references('id')
                ->on('jb_candidate_alerts')
                ->nullOnDelete();
        });

        Schema::create('jb_candidate_alert_cv_builder_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('direction', 20);
            $table->unsignedSmallInteger('question_index')->nullable();
            $table->text('body');
            $table->json('whapi_response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at'], 'jb_cv_builder_messages_session_created_idx');
            $table->foreign('session_id', 'jb_cv_builder_messages_session_fk')
                ->references('id')
                ->on('jb_candidate_alert_cv_builder_sessions')
                ->cascadeOnDelete();
        });

        Schema::create('jb_candidate_alert_cv_builder_ai_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('ai_provider', 40)->default('openai');
            $table->string('ai_model', 120)->nullable();
            $table->string('endpoint', 180)->nullable();
            $table->string('status', 30)->default('success');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('response_headers')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 10, 6)->nullable();
            $table->unsignedInteger('processing_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at'], 'jb_cv_builder_ai_logs_session_created_idx');
            $table->index(['status', 'created_at'], 'jb_cv_builder_ai_logs_status_created_idx');
            $table->foreign('session_id', 'jb_cv_builder_ai_logs_session_fk')
                ->references('id')
                ->on('jb_candidate_alert_cv_builder_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_candidate_alert_cv_builder_ai_logs');
        Schema::dropIfExists('jb_candidate_alert_cv_builder_messages');
        Schema::dropIfExists('jb_candidate_alert_cv_builder_sessions');
    }
};
