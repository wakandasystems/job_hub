<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Casts\SafeContent;
use Botble\Base\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class Currency extends BaseModel
{
    protected $table = 'jb_currencies';

    protected $fillable = [
        'title',
        'symbol',
        'is_prefix_symbol',
        'order',
        'decimals',
        'is_default',
        'exchange_rate',
        'number_format_style',
        'space_between_price_and_currency',
    ];

    protected $casts = [
        'title' => SafeContent::class,
    ];

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('currencies');
        });

        static::deleted(function () {
            Cache::forget('currencies');
        });
    }

    public static function convert(float $price, string $fromCode, string $toCode): float
    {
        if (strtoupper($fromCode) === strtoupper($toCode)) {
            return $price;
        }

        $from = static::query()->where('title', $fromCode)->first();
        $to = static::query()->where('title', $toCode)->first();

        if (! $from || ! $to) {
            return $price;
        }

        $inDefault = (! $from->is_default && $from->exchange_rate > 0)
            ? $price / $from->exchange_rate
            : $price;

        return (! $to->is_default && $to->exchange_rate > 0)
            ? $inDefault * $to->exchange_rate
            : $inDefault;
    }
}
