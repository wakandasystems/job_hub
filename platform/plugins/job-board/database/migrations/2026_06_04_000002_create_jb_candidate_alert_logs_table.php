<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_candidate_alert_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_alert_id')
                ->constrained('jb_candidate_alerts')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('job_id');
            $table->string('status', 20)->default('sent'); // sent | failed
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['candidate_alert_id', 'job_id']);
            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_candidate_alert_logs');
    }
};
