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
        Schema::table('jb_auto_apply_logs', function (Blueprint $table) {
            $table->string('message_id')->nullable()->after('email_sent_to');
            $table->timestamp('employer_reply_forwarded_at')->nullable()->after('sent_at');

            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jb_auto_apply_logs', function (Blueprint $table) {
            $table->dropIndex(['message_id']);
            $table->dropColumn(['message_id', 'employer_reply_forwarded_at']);
        });
    }
};
