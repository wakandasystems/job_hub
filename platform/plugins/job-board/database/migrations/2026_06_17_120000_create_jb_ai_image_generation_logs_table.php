<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_ai_image_generation_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('job_id')->nullable()->index();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('slot_type', 50)->index();
            $table->string('status', 20)->index();
            $table->string('model', 50)->nullable()->index();
            $table->string('quality', 20)->nullable()->index();
            $table->string('background', 20)->nullable();
            $table->string('output_format', 20)->nullable();
            $table->unsignedTinyInteger('output_compression')->nullable();
            $table->string('request_size', 30)->nullable();
            $table->unsignedInteger('target_width')->nullable();
            $table->unsignedInteger('target_height')->nullable();
            $table->string('stored_path')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('input_text_tokens')->nullable();
            $table->unsignedInteger('input_image_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable()->index();
            $table->string('api_request_id', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->json('response_meta')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
            $table->index(['slot_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_ai_image_generation_logs');
    }
};
