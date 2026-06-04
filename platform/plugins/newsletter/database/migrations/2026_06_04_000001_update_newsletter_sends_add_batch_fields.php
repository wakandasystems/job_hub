<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->string('batch_id', 255)->nullable()->after('id');
            $table->string('status', 20)->default('completed')->after('batch_id');
            $table->mediumText('body')->nullable()->after('subject');
            $table->string('image_url', 500)->nullable()->after('body');
            $table->string('pdf_path', 500)->nullable()->after('image_url');
            $table->unsignedInteger('sent_count')->default(0)->after('recipient_count');
            $table->unsignedInteger('failed_count')->default(0)->after('sent_count');
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');
            $table->unsignedSmallInteger('dedup_minutes')->default(0)->after('scheduled_at');

            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('newsletter_sends', function (Blueprint $table) {
            $table->dropColumn([
                'batch_id', 'status', 'body', 'image_url', 'pdf_path',
                'sent_count', 'failed_count', 'scheduled_at', 'dedup_minutes',
            ]);
        });
    }
};
