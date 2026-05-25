<?php

namespace Botble\Slug\Services;

use Botble\Slug\Facades\SlugHelper;
use Botble\Slug\Models\Slug;
use Illuminate\Support\Str;

class SlugService
{
    public function create(?string $name, int|string|null $slugId = 0, $model = null): ?string
    {
        $slug = Str::slug($name, '-', ! SlugHelper::turnOffAutomaticUrlTranslationIntoLatin() ? 'en' : false);

        $baseSlug = $slug;

        $prefix = null;
        if (! empty($model)) {
            $prefix = SlugHelper::getPrefix($model);
        }

        while ($this->checkIfExistedSlug($slug, $slugId, $prefix)) {
            $suffix = strtolower(Str::random(6));
            $slug = apply_filters(FILTER_SLUG_EXISTED_STRING, $baseSlug . '-' . $suffix, $baseSlug, 0, $model);
        }

        if (empty($slug)) {
            $slug = time();
        }

        return apply_filters(FILTER_SLUG_STRING, $slug, $model);
    }

    protected function checkIfExistedSlug(?string $slug, int|string|null $slugId, ?string $prefix): bool
    {
        return Slug::query()
            ->where([
                'key' => $slug,
                'prefix' => $prefix,
            ])
            ->where('id', '!=', $slugId)
            ->exists();
    }
}
