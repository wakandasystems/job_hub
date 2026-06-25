<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_sales_agents', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 30);
            $table->string('email', 150)->nullable();
            $table->string('code', 30)->unique();
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->string('status', 20)->default('active'); // active, inactive
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_sales_agents');
    }
};
