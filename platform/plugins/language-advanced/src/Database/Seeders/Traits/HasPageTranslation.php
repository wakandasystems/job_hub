<?php

namespace Botble\LanguageAdvanced\Database\Seeders\Traits;

use Botble\Page\Models\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

trait HasPageTranslation
{
    /**
     * Seed page translations for all locales
     * Loads basic translations from JSON, merges with custom content from pageTranslations() if exists
     *
     * @param array $locales
     */
    protected function seedPageTranslations(array $locales): void
    {
        if (! Schema::hasTable('pages_translations')) {
            return;
        }

        $pages = Page::query()->get(['id', 'name', 'description', 'content']);

        // Get custom translations if method exists (for complex content generation)
        $customTranslations = method_exists($this, 'pageTranslations') ? $this->pageTranslations() : [];

        foreach ($locales as $locale) {
            // Load basic translations from JSON
            $jsonTranslations = $this->loadPageTranslationsFromJson($locale);

            // Merge with custom translations (custom overrides JSON)
            $localeTranslations = array_merge(
                $jsonTranslations,
                $customTranslations[$locale] ?? []
            );

            foreach ($pages as $page) {
                $translation = $localeTranslations[$page->name] ?? [];

                DB::table('pages_translations')->updateOrInsert(
                    [
                        'lang_code' => $locale,
                        'pages_id' => $page->id,
                    ],
                    [
                        'lang_code' => $locale,
                        'pages_id' => $page->id,
                        'name' => $translation['name'] ?? $page->name,
                        'description' => $translation['description'] ?? $page->description,
                        'content' => $translation['content'] ?? $page->content,
                    ]
                );
            }
        }
    }

    /**
     * Load page translations from JSON file
     *
     * @param string $locale
     * @return array
     */
    protected function loadPageTranslationsFromJson(string $locale): array
    {
        $path = database_path("seeders/translations/{$locale}/pages.json");

        if (! File::exists($path)) {
            return [];
        }

        $content = File::get($path);
        $data = json_decode($content, true);

        return $data ?: [];
    }

    protected function translatePageContent(string $pageName, string $locale): string
    {
        $page = Page::query()->where('name', $pageName)->first();

        if (! $page) {
            return '';
        }

        $translations = $this->getPageTranslations($pageName, $locale);

        if (empty($translations)) {
            return $page->content;
        }

        $content = $page->content;

        foreach ($translations as $english => $translated) {
            $pattern = '/' . preg_quote($english, '/') . '/su';
            $pattern = str_replace(' ', '\s+', $pattern);
            $content = preg_replace($pattern, $translated, $content);
        }

        return $content;
    }

    protected function getPageTranslations(string $pageName, string $locale): array
    {
        $methodName = 'get' . str_replace(' ', '', ucwords($pageName)) . 'Translations';

        if (method_exists($this, $methodName)) {
            $translations = $this->$methodName();

            return $translations[$locale] ?? [];
        }

        return [];
    }
}
