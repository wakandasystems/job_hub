<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_social_broadcasts', function (Blueprint $table): void {
            // null = one-time (existing behaviour). Otherwise one of:
            // fixed_daily | daily_around | random_per_day
            $table->string('recurrence_type', 20)->nullable()->after('scheduled_at');
            $table->time('recurrence_time')->nullable()->after('recurrence_type');
            $table->unsignedSmallInteger('recurrence_jitter_minutes')->nullable()->after('recurrence_time');
            $table->unsignedTinyInteger('recurrence_times_per_day')->nullable()->after('recurrence_jitter_minutes');
            $table->unsignedTinyInteger('recurrence_window_start')->nullable()->after('recurrence_times_per_day');
            $table->unsignedTinyInteger('recurrence_window_end')->nullable()->after('recurrence_window_start');
            $table->unsignedInteger('max_occurrences')->nullable()->after('recurrence_window_end');
            $table->unsignedInteger('occurrence_count')->default(0)->after('max_occurrences');
            $table->unsignedTinyInteger('today_occurrences')->default(0)->after('occurrence_count');
            $table->date('today_date')->nullable()->after('today_occurrences');
            $table->timestamp('next_run_at')->nullable()->after('today_date');
            $table->boolean('ai_spice')->default(false)->after('next_run_at');
            $table->text('last_sent_message')->nullable()->after('ai_spice');

            $table->index('next_run_at');
        });
    }

    public function down(): void
    {
        Schema::table('jb_social_broadcasts', function (Blueprint $table): void {
            $table->dropColumn([
                'recurrence_type',
                'recurrence_time',
                'recurrence_jitter_minutes',
                'recurrence_times_per_day',
                'recurrence_window_start',
                'recurrence_window_end',
                'max_occurrences',
                'occurrence_count',
                'today_occurrences',
                'today_date',
                'next_run_at',
                'ai_spice',
                'last_sent_message',
            ]);
        });
    }
};
