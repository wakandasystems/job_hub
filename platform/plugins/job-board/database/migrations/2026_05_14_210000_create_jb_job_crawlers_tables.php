<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('jb_job_crawlers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('source_url', 1000);
            $table->string('parser_type', 20)->default('html');
            $table->string('schedule', 120)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('default_company_id')->nullable()->index();
            $table->text('item_selector')->nullable();
            $table->string('title_selector')->nullable();
            $table->string('company_selector')->nullable();
            $table->string('location_selector')->nullable();
            $table->string('description_selector')->nullable();
            $table->string('content_selector')->nullable();
            $table->string('apply_url_selector')->nullable();
            $table->string('published_at_selector')->nullable();
            $table->json('field_mappings')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('last_status', 40)->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('jb_job_crawler_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crawler_id')->constrained('jb_job_crawlers')->cascadeOnDelete();
            $table->string('status', 40)->default('running')->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('jobs_found')->default(0);
            $table->unsignedInteger('jobs_created')->default(0);
            $table->unsignedInteger('jobs_updated')->default(0);
            $table->unsignedInteger('jobs_skipped')->default(0);
            $table->text('error_message')->nullable();
            $table->longText('error_trace')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->foreignId('crawler_id')->nullable()->after('id')->index();
            $table->string('external_source_id')->nullable()->after('crawler_id')->index();
            $table->string('external_source_url', 1000)->nullable()->after('external_source_id');
        });
    }

    public function down(): void
    {
        Schema::table('jb_jobs', function (Blueprint $table): void {
            $table->dropColumn(['crawler_id', 'external_source_id', 'external_source_url']);
        });

        Schema::dropIfExists('jb_job_crawler_runs');
        Schema::dropIfExists('jb_job_crawlers');
    }
};
