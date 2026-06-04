<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->unsignedInteger('free_credits')->default(0)->after('credits');
            $table->timestamp('free_credits_refreshed_at')->nullable()->after('free_credits');
        });
    }

    public function down(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn(['free_credits', 'free_credits_refreshed_at']);
        });
    }
};
