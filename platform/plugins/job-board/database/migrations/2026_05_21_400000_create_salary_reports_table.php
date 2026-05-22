<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_salary_reports', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('year');
            $table->string('sector')->nullable();
            $table->decimal('price', 10, 2)->unsigned()->default(0);
            $table->string('currency_code', 10)->default('USD');
            $table->string('file_path')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_salary_reports');
    }
};
