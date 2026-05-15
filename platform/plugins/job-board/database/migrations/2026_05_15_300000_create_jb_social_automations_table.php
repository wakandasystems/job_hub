<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('jb_social_automations', function (Blueprint $table): void {
            $table->id();
            $table->string('platform', 30); // facebook | linkedin | whatsapp
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable(); // page_id, access_token, etc.
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_social_automations');
    }
};
