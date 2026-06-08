<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublerCountryMapping extends BaseModel
{
    protected $table = 'jb_publer_country_mappings';

    protected $fillable = [
        'country_id',
        'workspace_id',
        'facebook_account_id',
        'linkedin_account_id',
        'twitter_account_id',
        'tiktok_account_id',
        'instagram_account_id',
        'is_active',
        'image_mode',
        'template_square',
        'template_vertical',
        'wm_logo',
        'text_color',
        'overlay_opacity',
    ];

    protected $casts = [
        'is_active'       => 'bool',
        'overlay_opacity' => 'int',
    ];

    public function hasImageGeneration(): bool
    {
        // Backgrounds are sourced from category templates (see SocialImageService::resolveBackgroundPath);
        // this country mapping only needs to opt in via image_mode.
        return $this->image_mode === 'template';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(\Botble\Location\Models\Country::class, 'country_id');
    }

    public function mappedAccountIds(): array
    {
        return array_values(array_filter([
            $this->facebook_account_id,
            $this->linkedin_account_id,
            $this->twitter_account_id,
            $this->tiktok_account_id,
            $this->instagram_account_id,
        ]));
    }

    public function networkToAccountMap(): array
    {
        $map = [];
        if ($this->facebook_account_id)  $map['facebook']  = $this->facebook_account_id;
        if ($this->linkedin_account_id)  $map['linkedin']  = $this->linkedin_account_id;
        if ($this->twitter_account_id)   $map['twitter']   = $this->twitter_account_id;
        if ($this->tiktok_account_id)    $map['tiktok']    = $this->tiktok_account_id;
        if ($this->instagram_account_id) $map['instagram'] = $this->instagram_account_id;
        return $map;
    }
}
