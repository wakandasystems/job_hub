<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_publer_category_templates', function (Blueprint $table): void {
            $table->dropUnique(['category_id']);
            $table->dropColumn('category_id');
            $table->string('name')->after('id');
        });

        Schema::create('jb_publer_category_template_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('template_id');
            $table->unsignedInteger('category_id')->unique();
            $table->timestamps();

            $table->foreign('template_id')
                ->references('id')->on('jb_publer_category_templates')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_publer_category_template_categories');

        Schema::table('jb_publer_category_templates', function (Blueprint $table): void {
            $table->dropColumn('name');
            $table->unsignedInteger('category_id')->nullable()->unique()->after('id');
        });
    }
};
