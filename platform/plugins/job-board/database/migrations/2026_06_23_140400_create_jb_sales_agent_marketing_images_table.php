<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_marketing_images', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('sales_agent_id');
            $table->unsignedBigInteger('campaign_id');
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('generating'); // generating, completed, failed
            $table->text('error_message')->nullable();
            $table->decimal('cost_usd', 8, 4)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('sales_agent_id');
            $table->index('campaign_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_marketing_images');
    }
};
