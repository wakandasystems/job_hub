<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_credit_orders', function (Blueprint $table) {
            $table->string('payment_reference', 120)->nullable()->after('charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_credit_orders', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });
    }
};
