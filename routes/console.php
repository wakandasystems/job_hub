<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('blog:publish-scheduled', function () {
    $count = DB::table('posts')
        ->where('status', 'draft')
        ->whereDate('created_at', '<=', now())
        ->update(['status' => 'published', 'updated_at' => now()]);
    $this->info("Published {$count} scheduled blog post(s).");
})->purpose('Publish draft blog posts whose scheduled date has arrived');

