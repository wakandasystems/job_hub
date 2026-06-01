<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Language;
use Botble\JobBoard\Models\AccountLanguage;
use Botble\JobBoard\Models\LanguageLevel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountLanguageController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'account_language' => ['required', 'string', Rule::in(array_keys(Language::getLocales()))],
            'language_level_id' => ['nullable', 'integer', Rule::exists(LanguageLevel::class, 'id')],
            'is_native' => ['sometimes', 'boolean'],
        ]);

        $accountId = auth('account')->id();
        $language = $request->input('account_language');

        $existing = AccountLanguage::query()
            ->where('account_id', $accountId)
            ->where('language', $language)
            ->exists();

        if ($existing) {
            return response()->json([
                'error'   => true,
                'message' => __('This language has already been added.'),
            ], 422);
        }

        $levelId = $request->integer('language_level_id')
            ?: LanguageLevel::query()
                ->where('is_default', 1)
                ->value('id')
            ?: LanguageLevel::query()
                ->oldest('order')
                ->oldest('id')
                ->value('id');

        if (! $levelId) {
            return response()->json([
                'error' => true,
                'message' => __('No language levels are configured.'),
            ], 422);
        }

        AccountLanguage::query()->create([
            'account_id' => $accountId,
            'language' => $language,
            'language_level_id' => $levelId,
            'is_native' => $request->boolean('is_native'),
        ]);

        return response()->json([
            'error'   => false,
            'message' => __('Language added successfully.'),
        ]);
    }

    public function destroy(string $id)
    {
        $language = AccountLanguage::query()
            ->where('account_id', auth('account')->id())
            ->findOrFail($id);

        return DeleteResourceAction::make($language);
    }
}
