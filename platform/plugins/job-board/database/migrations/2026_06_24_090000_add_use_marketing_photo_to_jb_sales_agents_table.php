<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agents', function (Blueprint $table): void {
            $table->boolean('use_marketing_photo')->default(false)->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agents', function (Blueprint $table): void {
            $table->dropColumn('use_marketing_photo');
        });
    }
};
