<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->string('whatsapp_number', 30)->nullable()->after('phone');
            $table->string('telegram_chat_id', 100)->nullable()->after('whatsapp_number');
        });
    }

    public function down(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn(['whatsapp_number', 'telegram_chat_id']);
        });
    }
};
