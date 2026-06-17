<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_ai_image_generation_logs', function (Blueprint $table): void {
            $table->string('source_type', 50)->nullable()->after('company_id')->index();
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type')->index();
            $table->string('source_title')->nullable()->after('source_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_ai_image_generation_logs', function (Blueprint $table): void {
            $table->dropColumn(['source_type', 'source_id', 'source_title']);
        });
    }
};
