<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_job_alerts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->string('keyword', 255)->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_whatsapp')->default(false);
            $table->boolean('notify_telegram')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('jb_accounts')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('jb_categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_job_alerts');
    }
};
