<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->boolean('wakanda_verified')->default(false)->after('talent_hub_consent');
            $table->unsignedTinyInteger('wakanda_score')->default(0)->after('wakanda_verified');
            $table->timestamp('wakanda_verified_at')->nullable()->after('wakanda_score');
        });

        Schema::create('jb_wakanda_verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedTinyInteger('score')->default(0);
            $table->timestamps();

            $table->foreign('account_id')->references('id')->on('jb_accounts')->onDelete('cascade');
        });

        Schema::create('jb_talent_unlocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('employer_account_id');
            $table->unsignedBigInteger('candidate_account_id');
            $table->unsignedSmallInteger('credits_spent')->default(20);
            $table->timestamps();

            $table->unique(['employer_account_id', 'candidate_account_id'], 'talent_unlocks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_talent_unlocks');
        Schema::dropIfExists('jb_wakanda_verification_requests');
        Schema::table('jb_accounts', function (Blueprint $table): void {
            $table->dropColumn(['wakanda_verified', 'wakanda_score', 'wakanda_verified_at']);
        });
    }
};
