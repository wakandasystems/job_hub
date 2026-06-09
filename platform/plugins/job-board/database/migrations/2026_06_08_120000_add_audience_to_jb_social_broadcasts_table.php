<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_social_broadcasts', function (Blueprint $table): void {
            $table->string('audience', 30)->default('channels')->after('image_path');
            $table->unsignedInteger('recipient_count')->default(0)->after('audience');
            $table->unsignedInteger('sent_count')->default(0)->after('recipient_count');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
        });
    }

    public function down(): void
    {
        Schema::table('jb_social_broadcasts', function (Blueprint $table): void {
            $table->dropColumn(['audience', 'recipient_count', 'sent_count', 'failed_count']);
        });
    }
};
