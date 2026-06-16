<?php

namespace Botble\JobBoard\BulkActions;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Models\BaseModel;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\SocialAutomation;
use Botble\JobBoard\Services\SocialPublisherService;
use Botble\Media\Facades\RvMedia;
use Botble\Table\Abstracts\TableBulkActionAbstract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendToWhapiChannelBulkAction extends TableBulkActionAbstract
{
    public function __construct()
    {
        $this->label('Send to WhatsApp Channel');
    }

    public function dispatch(BaseModel|Model $model, array $ids): BaseHttpResponse
    {
        $automations = SocialAutomation::query()
            ->where('platform', 'whapi')
            ->where('is_active', true)
            ->get();

        if ($automations->isEmpty()) {
            return BaseHttpResponse::make()
                ->setError()
                ->setMessage('No active Whapi WhatsApp Channel automation configured.');
        }

        $publisher = app(SocialPublisherService::class);
        $jobs      = Job::query()
            ->with(['company', 'slugable', 'country', 'currency', 'jobTypes'])
            ->whereKey($ids)
            ->get();

        $totalSent = 0;

        foreach ($automations as $automation) {
            $settings   = $automation->settings ?? [];
            $token      = SocialAutomation::whapiToken($automation);
            $channelId  = trim((string) ($settings['channel_id'] ?? ''));
            $gatewayUrl = rtrim(trim((string) ($settings['gateway_url'] ?? '')), '/') ?: 'https://gate.whapi.cloud';

            if ($token === '' || $channelId === '') {
                continue;
            }

            if (! str_ends_with($channelId, '@newsletter')) {
                $channelId .= '@newsletter';
            }

            foreach ($jobs as $job) {
                try {
                    $posts    = $publisher->buildPlatformPosts($job);
                    $msg      = $posts['whatsapp'] ?? $job->name;
                    $imgField = trim((string) ($job->whatsapp_image ?? ''));
                    $sent     = false;

                    if ($imgField !== '') {
                        $imageUrl = RvMedia::getImageUrl($imgField);
                        $resp     = Http::timeout(30)->withToken($token)->post("{$gatewayUrl}/messages/image", [
                            'to'      => $channelId,
                            'media'   => $imageUrl,
                            'caption' => $msg,
                        ]);
                        $sent = $resp->successful();
                    }

                    if (! $sent) {
                        $resp = Http::timeout(20)->withToken($token)->post("{$gatewayUrl}/messages/text", [
                            'to'   => $channelId,
                            'body' => $msg,
                        ]);
                        $sent = $resp->successful();
                    }

                    if ($sent) {
                        $totalSent++;
                    }
                } catch (Throwable) {
                    // continue on individual failure
                }
                usleep(600000);
            }
        }

        return BaseHttpResponse::make()
            ->setMessage("Sent {$totalSent} of " . count($ids) . " job(s) to WhatsApp Channel.");
    }
}
