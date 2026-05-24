<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_credit_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('package_id');
            $table->integer('credits')->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('ZMW');
            $table->string('payment_method', 60)->nullable();
            $table->string('charge_id', 120)->nullable()->index();
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('account_id')->references('id')->on('jb_accounts')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('jb_packages')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_credit_orders');
    }
};
