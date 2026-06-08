<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jb_publer_country_mappings', function (Blueprint $table): void {
            $table->string('image_mode', 20)->default('none')->after('is_active');         // 'none' | 'template'
            $table->string('template_square', 500)->nullable()->after('image_mode');       // 1080×1080
            $table->string('template_vertical', 500)->nullable()->after('template_square'); // 1080×1920
            $table->string('wm_logo', 500)->nullable()->after('template_vertical');        // logo composited bottom-centre
            $table->string('text_color', 20)->default('#FFFFFF')->after('wm_logo');
            $table->unsignedTinyInteger('overlay_opacity')->default(55)->after('text_color'); // 0-90
        });
    }

    public function down(): void
    {
        Schema::table('jb_publer_country_mappings', function (Blueprint $table): void {
            $table->dropColumn(['image_mode', 'template_square', 'template_vertical', 'wm_logo', 'text_color', 'overlay_opacity']);
        });
    }
};
