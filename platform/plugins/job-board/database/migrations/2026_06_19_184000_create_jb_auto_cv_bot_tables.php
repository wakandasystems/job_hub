<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_auto_cv_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('candidate_name', 150)->nullable();
            $table->string('whatsapp_number', 40);
            $table->string('status', 30)->default('collecting');
            $table->json('topics')->nullable();
            $table->json('topics_covered')->nullable();
            $table->json('answers')->nullable();
            $table->json('structured_cv')->nullable();
            $table->longText('conversation_text')->nullable();
            $table->text('last_question_text')->nullable();
            $table->string('docx_path', 500)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->unsignedInteger('ai_total_prompt_tokens')->default(0);
            $table->unsignedInteger('ai_total_completion_tokens')->default(0);
            $table->decimal('ai_total_cost_usd', 10, 6)->default(0);
            $table->json('ai_calls')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('last_question_sent_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('admin_notified_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_question_sent_at'], 'jb_auto_cv_sessions_status_sent_idx');
        });

        Schema::create('jb_auto_cv_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->string('direction', 20);
            $table->text('body');
            $table->string('whapi_message_id', 120)->nullable();
            $table->json('whapi_payload')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'created_at'], 'jb_auto_cv_messages_session_created_idx');
            $table->unique('whapi_message_id', 'jb_auto_cv_messages_whapi_message_id_unique');
            $table->foreign('session_id', 'jb_auto_cv_messages_session_fk')
                ->references('id')
                ->on('jb_auto_cv_sessions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_auto_cv_messages');
        Schema::dropIfExists('jb_auto_cv_sessions');
    }
};
