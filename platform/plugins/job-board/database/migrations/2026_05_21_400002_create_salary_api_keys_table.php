<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_salary_api_keys', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('key_prefix', 12)->unique();
            $table->string('key_hash');
            $table->enum('plan', ['basic', 'pro', 'enterprise'])->default('basic');
            $table->unsignedInteger('requests_per_month')->default(500);
            $table->unsignedInteger('requests_this_month')->default(0);
            $table->timestamp('last_reset_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_salary_api_keys');
    }
};
