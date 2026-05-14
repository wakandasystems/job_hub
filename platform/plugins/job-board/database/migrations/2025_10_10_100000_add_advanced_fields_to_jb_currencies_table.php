<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_currencies', function (Blueprint $table): void {
            $table->string('number_format_style', 20)->default('western')->after('exchange_rate');
            $table->tinyInteger('space_between_price_and_currency')->unsigned()->default(0)->after('number_format_style');
        });
    }

    public function down(): void
    {
        Schema::table('jb_currencies', function (Blueprint $table): void {
            $table->dropColumn(['number_format_style', 'space_between_price_and_currency']);
        });
    }
};
