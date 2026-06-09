<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_vip_alert_orders', function (Blueprint $table): void {
            $table->string('public_token', 64)->nullable()->unique()->after('id');
        });

        DB::table('jb_vip_alert_orders')
            ->whereNull('public_token')
            ->orderBy('id')
            ->eachById(function (object $order): void {
                DB::table('jb_vip_alert_orders')
                    ->where('id', $order->id)
                    ->update(['public_token' => Str::random(64)]);
            });
    }

    public function down(): void
    {
        Schema::table('jb_vip_alert_orders', function (Blueprint $table): void {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
