<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_packages', function (Blueprint $table): void {
            $table->string('billing_cycle', 20)->default('one_time')->after('status')
                ->comment('one_time | monthly | annual');
            $table->unsignedSmallInteger('posts_per_cycle')->default(0)->after('billing_cycle')
                ->comment('0 = unlimited; only used for monthly/annual');
            $table->boolean('can_search_candidates')->default(false)->after('posts_per_cycle');
            $table->boolean('is_recruiter_plan')->default(false)->after('can_search_candidates');
        });
    }

    public function down(): void
    {
        Schema::table('jb_packages', function (Blueprint $table): void {
            $table->dropColumn(['billing_cycle', 'posts_per_cycle', 'can_search_candidates', 'is_recruiter_plan']);
        });
    }
};
