<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // Candidate preferences / filters for auto-apply
        Schema::create('jb_auto_apply_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jb_accounts')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->json('keywords')->nullable();
            $table->json('category_ids')->nullable();
            $table->json('country_ids')->nullable();
            $table->string('location_keyword')->nullable();
            $table->unsignedBigInteger('job_experience_id')->nullable();
            $table->json('blacklisted_company_ids')->nullable();
            $table->unsignedTinyInteger('match_score_threshold')->default(60);
            $table->timestamps();

            $table->unique('account_id');
        });

        // Purchase orders for auto-apply packages
        Schema::create('jb_auto_apply_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jb_accounts')->cascadeOnDelete();
            $table->string('plan', 50);
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('applications_allowed')->default(0);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('charge_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('admin_status', 20)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // Monthly quota tracking
        Schema::create('jb_auto_apply_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jb_accounts')->cascadeOnDelete();
            $table->string('period', 7);  // YYYY-MM
            $table->integer('applications_allowed')->default(0); // -1 = unlimited
            $table->unsignedInteger('applications_sent')->default(0);
            $table->boolean('is_approved')->nullable();
            $table->string('charge_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('plan', 50)->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'period', 'plan']);
        });

        // Log of every auto-application sent
        Schema::create('jb_auto_apply_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jb_accounts')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('jb_jobs')->cascadeOnDelete();
            $table->string('email_sent_to');
            $table->text('ai_email_subject')->nullable();
            $table->text('ai_email_body')->nullable();
            $table->string('ai_model_used', 50)->nullable();
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->json('match_reasons')->nullable();
            $table->string('status', 20)->default('sent'); // sent, failed, bounced
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'job_id']); // prevent double-sending
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_auto_apply_logs');
        Schema::dropIfExists('jb_auto_apply_quotas');
        Schema::dropIfExists('jb_auto_apply_orders');
        Schema::dropIfExists('jb_auto_apply_preferences');
    }
};
