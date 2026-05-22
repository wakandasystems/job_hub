<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->unsignedSmallInteger('desired_salary_from')->nullable()->after('cv_score_data');
            $table->unsignedSmallInteger('desired_salary_to')->nullable()->after('desired_salary_from');
            $table->tinyInteger('experience_years')->unsigned()->nullable()->after('desired_salary_to')
                ->comment('0=No exp, 1=<1yr, 2=1-2, 3=3-5, 5=5-10, 10=10+');
            $table->string('education_level', 30)->nullable()->after('experience_years')
                ->comment('high_school|diploma|bachelor|masters|phd');
            $table->string('availability', 30)->nullable()->after('education_level')
                ->comment('immediate|one_week|two_weeks|one_month|not_looking');
            $table->boolean('talent_hub_consent')->default(false)->after('availability')
                ->comment('Explicit consent to share profile with employers');
            $table->timestamp('profile_updated_at')->nullable()->after('talent_hub_consent');
        });

        Schema::create('jb_cv_reveals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employer_id');
            $table->unsignedBigInteger('candidate_id');
            $table->string('reveal_type', 20)->default('subscription')
                ->comment('subscription|credits|free');
            $table->decimal('amount_charged', 8, 2)->default(0);
            $table->string('charge_id', 200)->nullable();
            $table->timestamps();

            $table->unique(['employer_id', 'candidate_id']);
            $table->index('employer_id');
            $table->index('candidate_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_cv_reveals');

        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn([
                'desired_salary_from', 'desired_salary_to', 'experience_years',
                'education_level', 'availability', 'talent_hub_consent', 'profile_updated_at',
            ]);
        });
    }
};
