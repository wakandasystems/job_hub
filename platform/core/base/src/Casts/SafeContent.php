<?php

namespace Botble\Base\Casts;

use Botble\Base\Facades\BaseHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Facades\Cache;

class SafeContent implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes)
    {
        return BaseHelper::clean($value);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if (! $value) {
            return $value;
        }

        // $value is already cleaned by set() at write time, so re-running
        // HTMLPurifier on every read is redundant work for identical content.
        // Memoize by content hash since clean() is a pure function of $value.
        return Cache::remember(
            'safe_content_' . md5($value),
            3600,
            fn () => html_entity_decode(BaseHelper::clean($value))
        );
    }
}
