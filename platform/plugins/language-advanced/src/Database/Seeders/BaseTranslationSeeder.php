<?php

namespace Botble\LanguageAdvanced\Database\Seeders;

use Botble\Base\Supports\BaseSeeder;
use Botble\LanguageAdvanced\Database\Seeders\Traits\HasTranslationLoader;
use Botble\LanguageAdvanced\Supports\LanguageAdvancedManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class BaseTranslationSeeder extends BaseSeeder
{
    use HasTranslationLoader;

    /**
     * Seed translations for an entity from JSON files
     *
     * @param string $entity Table name (e.g., 'categories', 're_features')
     * @param string $tableName Translation table name (e.g., 'categories_translations')
     * @param string $modelClass Model class name
     * @param array $locales Array of locale codes
     * @param array $columns Translatable columns
     */
    protected function seedEntityFromJson(
        string $entity,
        string $tableName,
        string $modelClass,
        array $locales,
        array $columns = ['name']
    ): void {
        $translations = $this->loadAllTranslations($entity, $locales);

        if (empty($translations)) {
            return;
        }

        $sourceTable = (new $modelClass())->getTable();
        $existingColumns = DB::getSchemaBuilder()->getColumnListing($sourceTable);

        // Filter columns to only those that exist in source table
        $validColumns = array_filter($columns, fn ($col) => in_array($col, $existingColumns));

        if (empty($validColumns)) {
            return;
        }

        // Get all records from source table
        $records = DB::table($sourceTable)->get(['id', ...$validColumns]);

        if ($records->isEmpty()) {
            return;
        }

        $foreignKey = $sourceTable . '_id';

        foreach ($locales as $locale) {
            $localeTranslations = $translations[$locale] ?? [];

            if (empty($localeTranslations)) {
                continue;
            }

            foreach ($records as $record) {
                $data = [
                    'lang_code' => $locale,
                    $foreignKey => $record->id,
                ];

                foreach ($validColumns as $column) {
                    $originalValue = $record->{$column};
                    $data[$column] = $this->translateValue($localeTranslations, $originalValue);
                }

                try {
                    DB::table($tableName)->updateOrInsert(
                        ['lang_code' => $locale, $foreignKey => $record->id],
                        $data
                    );
                } catch (\Throwable) {
                    // Skip if insert fails
                }
            }
        }
    }

    /**
     * Auto-seed all translatable models from JSON files
     *
     * @param array $locales Array of locale codes
     */
    protected function seedAllTranslatableModelsFromJson(array $locales): void
    {
        $models = array_unique(array_merge(
            LanguageAdvancedManager::supportedModels()
        ));

        // Tables that are handled manually by specific seeder methods
        $skipTables = $this->getSkippedTables();

        foreach ($models as $modelClass) {
            try {
                $model = new $modelClass();
                $tableName = $model->getTable();
                $translationTable = $tableName . '_translations';

                // Skip tables that have custom seeding logic
                if (in_array($tableName, $skipTables, true)) {
                    continue;
                }

                if (! Schema::hasTable($translationTable)) {
                    continue;
                }

                $columns = LanguageAdvancedManager::getTranslatableColumns($modelClass);

                if (empty($columns) || ! $this->translationFileExists($tableName, $locales[0])) {
                    continue;
                }

                $this->seedEntityFromJson(
                    entity: $tableName,
                    tableName: $translationTable,
                    modelClass: $modelClass,
                    locales: $locales,
                    columns: $columns
                );

                $this->command->info("✓ Seeded {$tableName} translations from JSON");
            } catch (\Throwable $e) {
                $this->command->warn("⚠ Skipped {$modelClass}: " . $e->getMessage());
            }
        }
    }

    /**
     * Get tables that should be skipped from auto-seeding
     * Override this method in your seeder to skip specific tables
     *
     * @return array
     */
    protected function getSkippedTables(): array
    {
        return ['pages']; // Pages have custom seeding logic with nested JSON structure
    }

    /**
     * Translate a value using the translation array
     *
     * @param array $translations Translation key-value pairs
     * @param string|null $value Original value to translate
     * @return string|null Translated value or original if not found
     */
    protected function translateValue(array $translations, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        // Try exact match first, then trimmed value, then return original
        return $translations[$value]
            ?? $translations[$trimmed]
            ?? $value;
    }
}
