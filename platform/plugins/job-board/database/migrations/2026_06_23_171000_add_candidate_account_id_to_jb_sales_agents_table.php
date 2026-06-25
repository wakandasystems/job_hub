<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_sales_agents', function (Blueprint $table): void {
            $table->unsignedBigInteger('candidate_account_id')->nullable()->after('id');
            $table->index('candidate_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_sales_agents', function (Blueprint $table): void {
            $table->dropIndex(['candidate_account_id']);
            $table->dropColumn('candidate_account_id');
        });
    }
};
