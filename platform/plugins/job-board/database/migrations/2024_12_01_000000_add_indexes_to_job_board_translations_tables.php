<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        // table => [fk_column, fk_index_name, composite_index_name]
        $tables = [
            'jb_accounts_translations' => ['jb_accounts_id', 'idx_jb_accounts_trans_fk', 'idx_jb_accounts_trans_fk_lang'],
            'jb_career_levels_translations' => ['jb_career_levels_id', 'idx_jb_career_lvl_trans_fk', 'idx_jb_career_lvl_trans_fk_lang'],
            'jb_categories_translations' => ['jb_categories_id', 'idx_jb_categories_trans_fk', 'idx_jb_categories_trans_fk_lang'],
            'jb_companies_translations' => ['jb_companies_id', 'idx_jb_companies_trans_fk', 'idx_jb_companies_trans_fk_lang'],
            'jb_custom_field_options_translations' => ['jb_custom_field_options_id', 'idx_jb_cfo_trans_fk', 'idx_jb_cfo_trans_fk_lang'],
            'jb_custom_field_values_translations' => ['jb_custom_field_values_id', 'idx_jb_cfv_trans_fk', 'idx_jb_cfv_trans_fk_lang'],
            'jb_custom_fields_translations' => ['jb_custom_fields_id', 'idx_jb_cf_trans_fk', 'idx_jb_cf_trans_fk_lang'],
            'jb_degree_levels_translations' => ['jb_degree_levels_id', 'idx_jb_degree_lvl_trans_fk', 'idx_jb_degree_lvl_trans_fk_lang'],
            'jb_degree_types_translations' => ['jb_degree_types_id', 'idx_jb_degree_type_trans_fk', 'idx_jb_degree_type_trans_fk_lang'],
            'jb_functional_areas_translations' => ['jb_functional_areas_id', 'idx_jb_func_area_trans_fk', 'idx_jb_func_area_trans_fk_lang'],
            'jb_job_experiences_translations' => ['jb_job_experiences_id', 'idx_jb_job_exp_trans_fk', 'idx_jb_job_exp_trans_fk_lang'],
            'jb_job_shifts_translations' => ['jb_job_shifts_id', 'idx_jb_job_shift_trans_fk', 'idx_jb_job_shift_trans_fk_lang'],
            'jb_job_skills_translations' => ['jb_job_skills_id', 'idx_jb_job_skill_trans_fk', 'idx_jb_job_skill_trans_fk_lang'],
            'jb_job_types_translations' => ['jb_job_types_id', 'idx_jb_job_type_trans_fk', 'idx_jb_job_type_trans_fk_lang'],
            'jb_jobs_translations' => ['jb_jobs_id', 'idx_jb_jobs_trans_fk', 'idx_jb_jobs_trans_fk_lang'],
            'jb_language_levels_translations' => ['jb_language_levels_id', 'idx_jb_lang_lvl_trans_fk', 'idx_jb_lang_lvl_trans_fk_lang'],
            'jb_packages_translations' => ['jb_packages_id', 'idx_jb_packages_trans_fk', 'idx_jb_packages_trans_fk_lang'],
            'jb_tags_translations' => ['jb_tags_id', 'idx_jb_tags_trans_fk', 'idx_jb_tags_trans_fk_lang'],
        ];

        foreach ($tables as $table => $config) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            [$foreignKey, $fkIndex, $compositeIndex] = $config;

            Schema::table($table, function (Blueprint $blueprint) use ($foreignKey, $fkIndex, $compositeIndex) {
                $blueprint->index($foreignKey, $fkIndex);
                $blueprint->index([$foreignKey, 'lang_code'], $compositeIndex);
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'jb_accounts_translations' => ['idx_jb_accounts_trans_fk', 'idx_jb_accounts_trans_fk_lang'],
            'jb_career_levels_translations' => ['idx_jb_career_lvl_trans_fk', 'idx_jb_career_lvl_trans_fk_lang'],
            'jb_categories_translations' => ['idx_jb_categories_trans_fk', 'idx_jb_categories_trans_fk_lang'],
            'jb_companies_translations' => ['idx_jb_companies_trans_fk', 'idx_jb_companies_trans_fk_lang'],
            'jb_custom_field_options_translations' => ['idx_jb_cfo_trans_fk', 'idx_jb_cfo_trans_fk_lang'],
            'jb_custom_field_values_translations' => ['idx_jb_cfv_trans_fk', 'idx_jb_cfv_trans_fk_lang'],
            'jb_custom_fields_translations' => ['idx_jb_cf_trans_fk', 'idx_jb_cf_trans_fk_lang'],
            'jb_degree_levels_translations' => ['idx_jb_degree_lvl_trans_fk', 'idx_jb_degree_lvl_trans_fk_lang'],
            'jb_degree_types_translations' => ['idx_jb_degree_type_trans_fk', 'idx_jb_degree_type_trans_fk_lang'],
            'jb_functional_areas_translations' => ['idx_jb_func_area_trans_fk', 'idx_jb_func_area_trans_fk_lang'],
            'jb_job_experiences_translations' => ['idx_jb_job_exp_trans_fk', 'idx_jb_job_exp_trans_fk_lang'],
            'jb_job_shifts_translations' => ['idx_jb_job_shift_trans_fk', 'idx_jb_job_shift_trans_fk_lang'],
            'jb_job_skills_translations' => ['idx_jb_job_skill_trans_fk', 'idx_jb_job_skill_trans_fk_lang'],
            'jb_job_types_translations' => ['idx_jb_job_type_trans_fk', 'idx_jb_job_type_trans_fk_lang'],
            'jb_jobs_translations' => ['idx_jb_jobs_trans_fk', 'idx_jb_jobs_trans_fk_lang'],
            'jb_language_levels_translations' => ['idx_jb_lang_lvl_trans_fk', 'idx_jb_lang_lvl_trans_fk_lang'],
            'jb_packages_translations' => ['idx_jb_packages_trans_fk', 'idx_jb_packages_trans_fk_lang'],
            'jb_tags_translations' => ['idx_jb_tags_trans_fk', 'idx_jb_tags_trans_fk_lang'],
        ];

        foreach ($tables as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($indexes) {
                $blueprint->dropIndex($indexes[0]);
                $blueprint->dropIndex($indexes[1]);
            });
        }
    }
};
