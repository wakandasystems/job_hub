<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->json('custom_questions')->nullable()->after('topics');
            $table->unsignedBigInteger('linked_account_id')->nullable()->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_cv_sessions', function (Blueprint $table): void {
            $table->dropColumn(['custom_questions', 'linked_account_id']);
        });
    }
};
