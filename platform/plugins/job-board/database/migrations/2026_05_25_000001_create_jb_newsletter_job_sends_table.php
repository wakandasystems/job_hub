<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('jb_newsletter_job_sends', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('email', 120);
            $table->timestamp('sent_at')->useCurrent();

            $table->unique(['job_id', 'email']);
            $table->index('job_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_newsletter_job_sends');
    }
};
