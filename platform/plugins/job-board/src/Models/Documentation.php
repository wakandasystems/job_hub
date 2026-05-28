<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;

class Documentation extends BaseModel
{
    protected $table = 'jb_documentation';

    protected $fillable = [
        'title',
        'category',
        'content',
        'sort_order',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public static function categories(): array
    {
        return self::query()->distinct()->orderBy('category')->pluck('category')->all();
    }
}
