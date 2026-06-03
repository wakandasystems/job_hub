<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table) {
            $table->string('tiktok_image')->nullable()->after('cover_image');
            $table->string('facebook_image')->nullable()->after('tiktok_image');
            $table->string('linkedin_image')->nullable()->after('facebook_image');
            $table->string('whatsapp_image')->nullable()->after('linkedin_image');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table) {
            $table->dropColumn(['tiktok_image', 'facebook_image', 'linkedin_image', 'whatsapp_image']);
        });
    }
};
