<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_wakanda_verification_requests', function (Blueprint $table) {
            $table->string('charge_id', 120)->nullable()->after('account_id');
            $table->string('payment_method', 60)->nullable()->after('charge_id');
            $table->string('payment_reference', 120)->nullable()->after('payment_method');
            $table->decimal('amount', 15, 2)->nullable()->after('payment_reference');
            $table->string('currency', 10)->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('jb_wakanda_verification_requests', function (Blueprint $table) {
            $table->dropColumn(['charge_id', 'payment_method', 'payment_reference', 'amount', 'currency']);
        });
    }
};
