<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_featured_packages', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_days')->default(7)->comment('0 = no expiry');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('badge_label', 50)->default('Featured')->comment('Text shown on the job card');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('jb_featured_orders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->string('charge_id', 200)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('account_id');
            $table->index('job_id');
            $table->index('status');
        });

        if (! Schema::hasColumn('jb_jobs', 'featured_until')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->timestamp('featured_until')->nullable()->after('is_featured');
            });
        }

        // Seed default packages
        $currency = DB::table('jb_currencies')->where('is_default', 1)->value('title') ?? 'USD';

        DB::table('jb_featured_packages')->insert([
            [
                'name'         => 'Standard Featured',
                'description'  => 'Pin your job at the top of search results for 7 days with a Featured badge.',
                'duration_days'=> 7,
                'price'        => 49.00,
                'currency'     => strtoupper($currency),
                'badge_label'  => 'Featured',
                'is_active'    => true,
                'sort_order'   => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Premium Featured',
                'description'  => 'Maximum visibility for 30 days — bold listing, Featured badge, and top placement.',
                'duration_days'=> 30,
                'price'        => 149.00,
                'currency'     => strtoupper($currency),
                'badge_label'  => 'Featured',
                'is_active'    => true,
                'sort_order'   => 2,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'name'         => 'Sponsored Post',
                'description'  => 'Highlighted as Sponsored across category and search pages for 14 days.',
                'duration_days'=> 14,
                'price'        => 89.00,
                'currency'     => strtoupper($currency),
                'badge_label'  => 'Sponsored',
                'is_active'    => true,
                'sort_order'   => 3,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_featured_orders');
        Schema::dropIfExists('jb_featured_packages');

        if (Schema::hasColumn('jb_jobs', 'featured_until')) {
            Schema::table('jb_jobs', function (Blueprint $table): void {
                $table->dropColumn('featured_until');
            });
        }
    }
};
