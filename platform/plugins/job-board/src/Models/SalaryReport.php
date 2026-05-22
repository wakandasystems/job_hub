<?php

namespace Botble\JobBoard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class SalaryReport extends Model
{
    protected $table = 'jb_salary_reports';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'year',
        'sector',
        'price',
        'currency_code',
        'file_path',
        'cover_image',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'price'        => 'float',
        'year'         => 'integer',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(SalaryReportPurchase::class, 'report_id');
    }

    public static function generateSlug(string $title): string
    {
        return Str::slug($title);
    }
}
