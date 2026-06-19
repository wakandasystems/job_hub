<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_applications', function (Blueprint $table) {
            $table->text('cover_letter')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jb_applications', function (Blueprint $table) {
            $table->string('cover_letter')->nullable()->change();
        });
    }
};
