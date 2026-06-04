<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_candidate_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('label', 150);
            $table->string('candidate_name', 100);
            $table->string('candidate_phone', 30);
            $table->string('candidate_email', 150)->nullable();
            $table->json('filters')->nullable();
            $table->unsignedTinyInteger('duration_days');
            $table->decimal('price', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->string('status', 20)->default('active'); // active | expired | disabled
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('expiry_warning_sent')->default(false);
            $table->boolean('expiry_notice_sent')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_candidate_alerts');
    }
};
