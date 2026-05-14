<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('jb_categories', function (Blueprint $table): void {
            $table->index('status', 'jb_categories_status_index');
            $table->index('is_featured', 'jb_categories_is_featured_index');
            $table->index('parent_id', 'jb_categories_parent_id_index');
            $table->index('order', 'jb_categories_order_index');
            $table->index(['status', 'is_featured', 'order'], 'jb_categories_published_featured_index');
        });
    }

    public function down(): void
    {
        Schema::table('jb_categories', function (Blueprint $table): void {
            $table->dropIndex('jb_categories_status_index');
            $table->dropIndex('jb_categories_is_featured_index');
            $table->dropIndex('jb_categories_parent_id_index');
            $table->dropIndex('jb_categories_order_index');
            $table->dropIndex('jb_categories_published_featured_index');
        });
    }
};
