<?php

namespace Botble\JobBoard\Models;

use Botble\Base\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublerCategoryTemplateCategory extends BaseModel
{
    protected $table = 'jb_publer_category_template_categories';

    protected $fillable = [
        'template_id',
        'category_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(PublerCategoryTemplate::class, 'template_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
