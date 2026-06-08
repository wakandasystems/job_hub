<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\PublerCategoryTemplate;
use Botble\JobBoard\Models\PublerCategoryTemplateCategory;
use Botble\JobBoard\Models\PublerCountryMapping;
use Botble\JobBoard\Services\SocialImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublerCategoryTemplateController extends BaseController
{
    // Only the highest-volume categories are worth offering for mapping — the long tail
    // would mean scrolling through hundreds of entries for little gain.
    private const TOP_CATEGORIES_LIMIT = 60;

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Publer', route('job-board.publer.index'))
            ->add('Category Templates', route('job-board.publer.category-templates.index'));
    }

    public function index(SocialImageService $imageService)
    {
        $this->pageTitle('Publer — Category Background Templates');

        $jobCounts = DB::table('jb_jobs_categories')
            ->select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        // Coverage table — every category that has at least one job, paginated, most jobs first.
        $coverageCategories = DB::table('jb_categories')
            ->whereIn('id', $jobCounts->keys())
            ->orderByDesc(DB::raw('(SELECT COUNT(*) FROM jb_jobs_categories WHERE jb_jobs_categories.category_id = jb_categories.id)'))
            ->orderBy('name')
            ->paginate(50, ['id', 'name'])
            ->withQueryString();

        // Editor "Mapped Categories" dropdown — capped to the highest-volume categories
        // (plus already-mapped ones) so the multi-select stays usable in the modal.
        $topCategoryIds = $jobCounts->sortDesc()->keys()->take(self::TOP_CATEGORIES_LIMIT)->all();
        $mappedCategoryIds = PublerCategoryTemplateCategory::query()->pluck('category_id')->all();
        $selectableCategoryIds = array_unique(array_merge($topCategoryIds, $mappedCategoryIds));

        $selectableCategories = DB::table('jb_categories')
            ->whereIn('id', $selectableCategoryIds)
            ->orderByDesc(DB::raw('(SELECT COUNT(*) FROM jb_jobs_categories WHERE jb_jobs_categories.category_id = jb_categories.id)'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $links = PublerCategoryTemplateCategory::query()
            ->whereIn('category_id', $coverageCategories->pluck('id')->all())
            ->get()
            ->keyBy('category_id');

        $templates = PublerCategoryTemplate::query()
            ->withCount('categories')
            ->with('categories')
            ->orderBy('name')
            ->get();

        // Ready-to-paste AI image-generation prompts, one pair (square + vertical) per template.
        $prompts = $templates->mapWithKeys(fn (PublerCategoryTemplate $template) => [
            $template->id => [
                'square'   => $imageService->buildCategoryBackgroundPrompt($template->name, 'square'),
                'vertical' => $imageService->buildCategoryBackgroundPrompt($template->name, 'vertical'),
            ],
        ]);

        return view('plugins/job-board::publer.category-templates', compact(
            'coverageCategories', 'selectableCategories', 'templates', 'links', 'jobCounts', 'prompts'
        ));
    }

    public function save(Request $request, ?PublerCategoryTemplate $template = null)
    {
        $template ??= new PublerCategoryTemplate();

        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:120'],
            'is_active'         => ['nullable', 'boolean'],
            'category_ids'      => ['nullable', 'array'],
            'category_ids.*'    => ['integer'],
            'template_square'   => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'template_vertical' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $template->name      = $validated['name'];
        $template->is_active = (bool) ($validated['is_active'] ?? true);

        $dir = public_path('social-templates');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        foreach (['template_square', 'template_vertical'] as $field) {
            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                $file    = $request->file($field);
                $newName = 'category_template_' . ($template->id ?: 'new_' . uniqid()) . '_' . $field . '.' . $file->extension();

                if ($template->{$field}) {
                    $old = public_path(ltrim($template->{$field}, '/'));
                    if (file_exists($old)) {
                        @unlink($old);
                    }
                }

                $file->move($dir, $newName);
                $template->{$field} = 'social-templates/' . $newName;
            }
        }

        $template->save();

        $categoryIds = array_map('intval', $validated['category_ids'] ?? []);

        // A category can only belong to one template — drop links this template no longer
        // claims, then claim the requested ones (stealing them from any other template).
        PublerCategoryTemplateCategory::query()
            ->where('template_id', $template->id)
            ->whereNotIn('category_id', $categoryIds)
            ->delete();

        foreach ($categoryIds as $categoryId) {
            PublerCategoryTemplateCategory::query()->updateOrCreate(
                ['category_id' => $categoryId],
                ['template_id' => $template->id]
            );
        }

        return $this->httpResponse()->setMessage('Category template saved.');
    }

    public function toggle(PublerCategoryTemplate $template)
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        return $this->httpResponse()->setData(['is_active' => $template->is_active]);
    }

    public function destroy(PublerCategoryTemplate $template)
    {
        $template->delete();

        return $this->httpResponse()->setMessage('Template deleted.');
    }

    public function previewImage(PublerCategoryTemplate $template, SocialImageService $imageService)
    {
        if (! $template->is_active) {
            return response()->json(['error' => 'This template is disabled. Enable it first.'], 422);
        }

        $categoryIds = $template->categories()->pluck('jb_categories.id')->all();

        if (! $categoryIds) {
            return response()->json(['error' => 'Map at least one category to this template first.'], 422);
        }

        $job = Job::with(['company', 'slugable', 'country', 'currency'])
            ->where('status', 'published')
            ->whereHas('categories', fn ($q) => $q->whereIn('jb_categories.id', $categoryIds))
            ->latest()
            ->first();

        if (! $job) {
            return response()->json(['error' => 'No published jobs found in the mapped categories to preview with.'], 422);
        }

        $format = request('format', 'square');

        if (! $template->hasTemplate($format)) {
            return response()->json(['error' => 'Upload a ' . $format . ' template first.'], 422);
        }

        // Source branding (logo/colors) from the job's country mapping, with sane defaults.
        $mapping = PublerCountryMapping::query()->where('country_id', $job->country_id)->first()
            ?? new PublerCountryMapping([
                'image_mode'      => 'template',
                'text_color'      => '#FFFFFF',
                'overlay_opacity' => 55,
            ]);

        $result = $imageService->generateForJob($job, $mapping, $format);

        if (! $result) {
            return response()->json(['error' => 'Image generation failed. Check that the template file is a valid image.'], 422);
        }

        [$localPath] = $result;

        // Stream the file directly rather than redirecting to its public URL — a redirect
        // would let the shutdown-based cleanup delete the file before the browser's
        // follow-up GET for the image arrives, producing a 404.
        return response()->file($localPath, ['Content-Type' => 'image/jpeg'])->deleteFileAfterSend(true);
    }
}
