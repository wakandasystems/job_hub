<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_employer_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('status', 20)->default('pending')
                ->comment('pending | active | expired | cancelled');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method', 50)->nullable();
            $table->string('charge_id', 200)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('last_renewed_at')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->unsignedSmallInteger('posts_used_this_cycle')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('status');
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_employer_subscriptions');
    }
};
