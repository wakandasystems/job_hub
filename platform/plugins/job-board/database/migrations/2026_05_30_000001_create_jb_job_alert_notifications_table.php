<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('jb_job_alert_notifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->unsignedBigInteger('job_alert_id')->nullable()->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'job_id', 'job_alert_id'], 'uniq_account_job_alert');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_job_alert_notifications');
    }
};
