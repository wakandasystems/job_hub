<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            $table->json('contact_emails')->nullable()->after('email');
            $table->json('contact_numbers')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            $table->dropColumn(['contact_emails', 'contact_numbers']);
        });
    }
};
