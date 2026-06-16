<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_publer_category_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('category_id')->unique();
            $table->string('template_square')->nullable();
            $table->string('template_vertical')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_publer_category_templates');
    }
};
