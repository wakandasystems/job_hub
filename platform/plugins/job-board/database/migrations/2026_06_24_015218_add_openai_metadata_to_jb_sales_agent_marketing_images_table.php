<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agent_marketing_images', function (Blueprint $table): void {
            $table->unsignedInteger('generation_ms')->nullable()->after('cost_usd');
            $table->unsignedInteger('input_tokens')->nullable()->after('generation_ms');
            $table->unsignedInteger('output_tokens')->nullable()->after('input_tokens');
            $table->unsignedInteger('total_tokens')->nullable()->after('output_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agent_marketing_images', function (Blueprint $table): void {
            $table->dropColumn(['generation_ms', 'input_tokens', 'output_tokens', 'total_tokens']);
        });
    }
};
