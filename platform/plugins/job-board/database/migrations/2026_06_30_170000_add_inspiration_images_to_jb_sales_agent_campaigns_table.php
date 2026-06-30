<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            if (! Schema::hasColumn('jb_sales_agent_campaigns', 'inspiration_images')) {
                $table->json('inspiration_images')->nullable()->after('prompt_template');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agent_campaigns', function (Blueprint $table): void {
            if (Schema::hasColumn('jb_sales_agent_campaigns', 'inspiration_images')) {
                $table->dropColumn('inspiration_images');
            }
        });
    }
};
