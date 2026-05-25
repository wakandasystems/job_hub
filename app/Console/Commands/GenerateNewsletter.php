<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateNewsletter extends Command
{
    protected $signature = 'newsletter:generate
                            {--output=       : File path to write body (default: /tmp/tgbot-nl-message)}
                            {--output-title= : File path to write title (default: /tmp/tgbot-nl-title)}';

    protected $description = 'Auto-generate a marketing newsletter from current job listings';

    public function handle(): int
    {
        $outputBody  = $this->option('output')        ?: '/tmp/tgbot-nl-message';
        $outputTitle = $this->option('output-title') ?: '/tmp/tgbot-nl-title';

        $weekLabel = now()->format('F j, Y');

        // Top 5 featured or newest jobs
        $jobs = DB::table('jb_jobs as j')
            ->leftJoin('jb_companies as c', 'c.id', '=', 'j.company_id')
            ->leftJoin('slugs as s', function ($join) {
                $join->on('s.reference_id', '=', 'j.id')
                     ->where('s.reference_type', 'Botble\\JobBoard\\Models\\Job');
            })
            ->where('j.status', 'published')
            ->where(function ($q) {
                $q->whereNull('j.expire_date')->orWhere('j.expire_date', '>=', now());
            })
            ->orderByDesc('j.is_featured')
            ->orderByDesc('j.created_at')
            ->select('j.name', 'c.name as company', 'j.address', 's.key as slug', 'j.expire_date')
            ->limit(5)
            ->get();

        // Top categories by active job count
        $categories = DB::table('jb_jobs as j')
            ->join('jb_jobs_categories as jc', 'jc.job_id', '=', 'j.id')
            ->join('jb_categories as cat', 'cat.id', '=', 'jc.category_id')
            ->where('j.status', 'published')
            ->where(function ($q) {
                $q->whereNull('j.expire_date')->orWhere('j.expire_date', '>=', now());
            })
            ->where('cat.name', '!=', 'Unspecified')
            ->select('cat.name', DB::raw('count(*) as cnt'))
            ->groupBy('cat.name')
            ->orderByDesc('cnt')
            ->limit(4)
            ->get();

        $total = DB::table('jb_jobs')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expire_date')->orWhere('expire_date', '>=', now());
            })
            ->count();

        // Build the newsletter
        $subject = "🌟 This Week's Top Jobs on WakandaJobs — {$weekLabel}";
        $lines = [];

        $lines[] = "Hello,";
        $lines[] = '';
        $lines[] = "Here are this week's standout opportunities from WakandaJobs — your gateway to careers across Africa and beyond.";
        $lines[] = '';
        $lines[] = "──────────────────────────────";
        $lines[] = "🔥 FEATURED OPPORTUNITIES";
        $lines[] = "──────────────────────────────";
        $lines[] = '';

        foreach ($jobs as $job) {
            $title    = html_entity_decode($job->name, ENT_QUOTES);
            $company  = html_entity_decode($job->company ?? 'Leading Company', ENT_QUOTES);
            $location = $job->address ? trim($job->address) : 'Africa';
            $url      = $job->slug
                ? "https://www.wakandajobs.com/jobs/{$job->slug}"
                : 'https://www.wakandajobs.com/jobs';

            $lines[] = "📌 {$title}";
            $lines[] = "   🏢 {$company}  |  📍 {$location}";
            $lines[] = "   🔗 {$url}";
            $lines[] = '';
        }

        $lines[] = "──────────────────────────────";
        $lines[] = "📂 TRENDING CATEGORIES";
        $lines[] = "──────────────────────────────";
        $lines[] = '';

        $catLine = $categories->map(fn ($c) => html_entity_decode($c->name, ENT_QUOTES) . " ({$c->cnt})")->implode(' • ');
        $lines[] = $catLine;
        $lines[] = '';

        $lines[] = "──────────────────────────────";
        $lines[] = '';
        $lines[] = "📊 {$total} active jobs are waiting for the right candidate right now.";
        $lines[] = '';
        $lines[] = "👉 Explore all openings: https://www.wakandajobs.com/jobs";
        $lines[] = '';
        $lines[] = "Good luck with your job search!";
        $lines[] = '';
        $lines[] = "— The WakandaJobs Team";

        $body = implode("\n", $lines);

        file_put_contents($outputTitle, $subject);
        file_put_contents($outputBody, $body);

        $this->info("Title written to {$outputTitle}");
        $this->info("Body written to {$outputBody}");
        echo "GENERATED\n";

        return 0;
    }
}
