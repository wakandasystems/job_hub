<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('jb_auto_apply_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('jb_accounts')->cascadeOnDelete();
            $table->foreignId('job_id')->constrained('jb_jobs')->cascadeOnDelete();
            $table->string('ai_model', 50);
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('reasons')->nullable();
            $table->text('subject')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->timestamp('account_profile_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'job_id', 'ai_model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_auto_apply_previews');
    }
};
