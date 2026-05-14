<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            $table->index('status', 'jb_companies_status_index');
            $table->index('is_featured', 'jb_companies_is_featured_index');
            $table->index('country_id', 'jb_companies_country_id_index');
            $table->index('state_id', 'jb_companies_state_id_index');
            $table->index('city_id', 'jb_companies_city_id_index');
            $table->index('created_at', 'jb_companies_created_at_index');
            $table->index(['status', 'is_featured', 'created_at'], 'jb_companies_published_featured_index');
        });
    }

    public function down(): void
    {
        Schema::table('jb_companies', function (Blueprint $table): void {
            $table->dropIndex('jb_companies_status_index');
            $table->dropIndex('jb_companies_is_featured_index');
            $table->dropIndex('jb_companies_country_id_index');
            $table->dropIndex('jb_companies_state_id_index');
            $table->dropIndex('jb_companies_city_id_index');
            $table->dropIndex('jb_companies_created_at_index');
            $table->dropIndex('jb_companies_published_featured_index');
        });
    }
};
