<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;

class SocialAutomation extends BaseModel
{
    protected $table = 'jb_social_automations';

    protected $fillable = [
        'platform',
        'name',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'settings'  => 'array',
    ];

    public static array $platforms = [
        'facebook'  => 'Facebook Page',
        'linkedin'  => 'LinkedIn Company Page',
        'whatsapp'  => 'WhatsApp',
        'telegram'  => 'Telegram',
        'whapi'     => 'WhatsApp Channel (Whapi)',
        'publer'    => 'Publer',
    ];

    public function getPlatformLabelAttribute(): string
    {
        return static::$platforms[$this->platform] ?? $this->platform;
    }
}
