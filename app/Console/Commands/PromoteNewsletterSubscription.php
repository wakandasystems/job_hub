<?php

namespace App\Console\Commands;

use App\Mail\NewsletterPromoMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class PromoteNewsletterSubscription extends Command
{
    protected $signature = 'newsletter:promote-signup
                            {--to=         : Send only to this address (testing)}
                            {--type=       : Limit to account type: employer or job-seeker}
                            {--dry-run     : Show who would be emailed without sending}';

    protected $description = 'Email confirmed accounts who are not newsletter subscribers, encouraging them to subscribe';

    public function handle(): int
    {
        $subscribedEmails = DB::table('newsletters')
            ->where('status', 'subscribed')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->all();

        $unsubscribedEmails = DB::table('newsletters')
            ->where('status', 'unsubscribed')
            ->pluck('email')
            ->map(fn ($e) => strtolower(trim($e)))
            ->all();

        // Confirmed accounts that are not subscribed and haven't explicitly unsubscribed
        $query = DB::table('jb_accounts')
            ->whereNotNull('confirmed_at')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotIn(DB::raw('LOWER(email)'), $subscribedEmails)
            ->whereNotIn(DB::raw('LOWER(email)'), $unsubscribedEmails)
            ->select('id', 'first_name', 'last_name', 'email', 'type');

        if ($type = $this->option('type')) {
            $query->where('type', $type);
        }

        $accounts = $query->get();

        if ($testTo = $this->option('to')) {
            // In test mode, send only to the given address using a sample record
            $sampleType = $this->option('type') ?: 'job-seeker';
            $accounts = collect([(object) [
                'id'         => 0,
                'first_name' => 'Test',
                'last_name'  => 'User',
                'email'      => $testTo,
                'type'       => $sampleType,
            ]]);
        }

        if ($accounts->isEmpty()) {
            $this->info('No unsubscribed accounts to email.');
            echo "SENT:0\nFAILED:0\nEMPLOYERS:0\nJOBSEEKERS:0\n";
            return 0;
        }

        $sent      = 0;
        $failed    = 0;
        $employers = 0;
        $jobseekers = 0;

        foreach ($accounts as $account) {
            $name  = trim("{$account->first_name} {$account->last_name}") ?: 'there';
            $email = $account->email;
            $type  = $account->type ?: 'job-seeker';

            $subscribeUrl = URL::signedRoute('newsletter.promo.subscribe', [
                'email' => $email,
                'name'  => $name,
            ]);

            if ($this->option('dry-run')) {
                $this->line("[DRY RUN] Would email {$email} ({$type})");
                $type === 'employer' ? $employers++ : $jobseekers++;
                $sent++;
                continue;
            }

            try {
                Mail::to($email, $name)->send(new NewsletterPromoMail($name, $type, $subscribeUrl));
                $type === 'employer' ? $employers++ : $jobseekers++;
                $sent++;
            } catch (\Throwable $e) {
                $this->error("Failed {$email}: " . $e->getMessage());
                $failed++;
            }
        }

        $this->info("Sent: {$sent}, Failed: {$failed}, Employers: {$employers}, Job Seekers: {$jobseekers}");
        echo "SENT:{$sent}\nFAILED:{$failed}\nEMPLOYERS:{$employers}\nJOBSEEKERS:{$jobseekers}\n";

        return 0;
    }
}
