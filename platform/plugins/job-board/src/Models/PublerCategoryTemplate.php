<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PublerCategoryTemplate extends BaseModel
{
    protected $table = 'jb_publer_category_templates';

    protected $fillable = [
        'name',
        'template_square',
        'template_vertical',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function hasTemplate(string $format): bool
    {
        return $this->is_active && (bool) ($format === 'vertical' ? $this->template_vertical : $this->template_square);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'jb_publer_category_template_categories',
            'template_id',
            'category_id'
        );
    }
}
