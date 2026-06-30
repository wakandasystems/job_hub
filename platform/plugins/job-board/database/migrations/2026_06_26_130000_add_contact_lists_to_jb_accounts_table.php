<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->json('call_numbers')->nullable()->after('phone');
            $table->json('whatsapp_numbers')->nullable()->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn(['call_numbers', 'whatsapp_numbers']);
        });
    }
};
