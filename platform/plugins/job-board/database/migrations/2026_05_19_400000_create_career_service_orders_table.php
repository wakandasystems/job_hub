<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_career_service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('service_type', 50);
            $table->string('service_name');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30)->nullable();
            $table->unsignedBigInteger('candidate_id')->nullable()->comment('Candidate whose page triggered the order');
            $table->string('charge_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('candidate_id')->references('id')->on('jb_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_career_service_orders');
    }
};
