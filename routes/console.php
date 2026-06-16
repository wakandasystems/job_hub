<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('blog:publish-scheduled', function () {
    $posts = DB::table('posts')
        ->where('status', 'draft')
        ->whereNotNull('publish_at')
        ->where('publish_at', '<=', now())
        ->get(['id', 'publish_at']);

    foreach ($posts as $post) {
        DB::table('posts')->where('id', $post->id)->update([
            'status'     => 'published',
            'created_at' => $post->publish_at, // set created_at = publish_at so listing order is correct
            'updated_at' => now(),
        ]);
    }

    $this->info("Published {$posts->count()} scheduled blog post(s).");
})->purpose('Publish draft blog posts whose publish_at has arrived');

