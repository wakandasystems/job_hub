<?php

namespace Botble\JobBoard\Jobs;

use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryPublerPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(
        public int $jobId,
        public int $automationId,
        public ?string $preferredImageField = null,
        public array $excludeNetworks = [],
    ) {
    }

    public function handle(SocialPublisherService $publisher): void
    {
        $job = Job::query()->find($this->jobId);
        $automation = SocialAutomation::query()
            ->where('platform', 'publer')
            ->where('is_active', true)
            ->find($this->automationId);

        if (! $job || ! $automation) {
            return;
        }

        $settings = $automation->settings ?? [];
        $countryId = $settings['country_id'] ?? null;
        if ($countryId && (int) $countryId !== (int) $job->country_id) {
            return;
        }

        $apiKey = trim((string) ($settings['api_key'] ?? ''));
        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }

        $accountIds = array_values(array_filter((array) ($settings['account_ids'] ?? [])));
        $workspaceId = trim((string) ($settings['workspace_id'] ?? ''));

        if ($apiKey === '' || empty($accountIds)) {
            return;
        }

        $success = $publisher->publerPost(
            $job,
            $apiKey,
            $accountIds,
            $workspaceId,
            $this->preferredImageField,
            $this->excludeNetworks,
        );

        if (! $success) {
            Log::warning('Background Publer retry failed', [
                'job_id' => $job->getKey(),
                'automation_id' => $automation->getKey(),
                'error' => $publisher->getLastPublerError(),
            ]);
        }
    }
}
