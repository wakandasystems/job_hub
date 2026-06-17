<?php

namespace Botble\JobBoard\Providers;

use Botble\Base\Events\UpdatedContentEvent;
use Botble\JobBoard\Events\AdminApprovedCompanyEvent;
use Botble\JobBoard\Events\AdminApprovedJobEvent;
use Botble\JobBoard\Events\EmployerPostedJobEvent;
use Botble\JobBoard\Events\JobAppliedEvent;
use Botble\JobBoard\Events\JobPublishedEvent;
use Botble\JobBoard\Listeners\AdminApprovedCompanyListener;
use Botble\JobBoard\Listeners\AdminApprovedJobListener;
use Botble\JobBoard\Listeners\EmployerPostedJobListener;
use Botble\JobBoard\Listeners\GenerateSocialImagesListener;
use Botble\JobBoard\Listeners\JobAppliedListener;
use Botble\JobBoard\Listeners\NewApplicationNotification;
use Botble\JobBoard\Listeners\RenderingSiteMapListener;
use Botble\JobBoard\Listeners\SaveFavoriteTagAndSkillsListener;
use Botble\JobBoard\Listeners\SendJobAlertListener;
use Botble\JobBoard\Listeners\SendNewsletterJobAlertListener;
use Botble\JobBoard\Listeners\SendPushNotificationListener;
use Botble\JobBoard\Listeners\SendVipCandidateAlertsListener;
use Botble\JobBoard\Listeners\SocialPublishListener;
use Botble\JobBoard\Listeners\SubscribedPackageListener;
use Botble\JobBoard\Listeners\UpdatedContentListener;
use Botble\Payment\Events\PaymentWebhookReceived;
use Botble\Theme\Events\RenderingSiteMapEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UpdatedContentEvent::class => [
            UpdatedContentListener::class,
            SaveFavoriteTagAndSkillsListener::class,
        ],
        RenderingSiteMapEvent::class => [
            RenderingSiteMapListener::class,
        ],
        JobPublishedEvent::class => [
            SendJobAlertListener::class,
            SendNewsletterJobAlertListener::class,
            SocialPublishListener::class,
            SendPushNotificationListener::class,
            SendVipCandidateAlertsListener::class,
            GenerateSocialImagesListener::class,
        ],
        EmployerPostedJobEvent::class => [
            EmployerPostedJobListener::class,
        ],
        AdminApprovedJobEvent::class => [
            AdminApprovedJobListener::class,
        ],
        AdminApprovedCompanyEvent::class => [
            AdminApprovedCompanyListener::class,
        ],
        JobAppliedEvent::class => [
            JobAppliedListener::class,
            NewApplicationNotification::class,
        ],
        PaymentWebhookReceived::class => [
            SubscribedPackageListener::class,
        ],
    ];
}
