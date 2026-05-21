<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->string('assigned_coach_name')->nullable()->after('candidate_id');
            $table->string('assigned_coach_email')->nullable()->after('assigned_coach_name');
            $table->string('delivery_status', 30)->default('unassigned')->after('payment_method');
            $table->timestamp('delivered_at')->nullable()->after('delivery_status');
            $table->unsignedTinyInteger('ai_cv_score')->nullable()->after('delivered_at');
            $table->json('ai_cv_feedback')->nullable()->after('ai_cv_score');
        });
    }

    public function down(): void
    {
        Schema::table('jb_career_service_orders', function (Blueprint $table): void {
            $table->dropColumn([
                'assigned_coach_name',
                'assigned_coach_email',
                'delivery_status',
                'delivered_at',
                'ai_cv_score',
                'ai_cv_feedback',
            ]);
        });
    }
};
