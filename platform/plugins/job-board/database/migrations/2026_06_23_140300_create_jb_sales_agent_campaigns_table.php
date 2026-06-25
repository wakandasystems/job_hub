<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agent_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->longText('prompt_template');
            $table->string('aspect_ratio', 20)->default('portrait_4_5'); // portrait_4_5, square_1_1, landscape_16_9
            $table->string('promo_price', 30)->nullable();
            $table->string('promo_original_price', 30)->nullable();
            $table->date('promo_end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agent_campaigns');
    }
};
