<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_auto_apply_preferences', function (Blueprint $table): void {
            $table->text('location_keyword')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jb_auto_apply_preferences', function (Blueprint $table): void {
            $table->string('location_keyword')->nullable()->change();
        });
    }
};
