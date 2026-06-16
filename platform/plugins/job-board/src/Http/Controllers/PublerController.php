<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Models\PublerCountryMapping;
use Botble\JobBoard\Services\SocialImageService;
use Botble\JobBoard\Services\SocialPublisherService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublerController extends BaseController
{
    private const PUBLER_COUNTRIES = [
        'Zambia', 'South Africa', 'Nigeria', 'Kenya', 'Ghana', 'Uganda',
        'Tanzania', 'Rwanda', 'Zimbabwe', 'Mauritius', 'Morocco', 'Tunisia',
        'Cameroon', 'Malawi', 'Botswana', 'Namibia',
    ];

    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add(trans('plugins/job-board::job-board.name'))
            ->add('Publer', route('job-board.publer.index'));
    }

    public function index()
    {
        $this->pageTitle('Publer — Country Social Account Mapping');

        // Countries that have jobs or are in the target list, with job counts
        $jobCounts = DB::table('jb_jobs')
            ->select('country_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('country_id')
            ->groupBy('country_id')
            ->pluck('total', 'country_id');

        $countries = DB::table('countries')
            ->whereIn('name', self::PUBLER_COUNTRIES)
            ->orWhereIn('id', $jobCounts->keys()->all())
            ->orderByDesc(DB::raw('(SELECT COUNT(*) FROM jb_jobs WHERE jb_jobs.country_id = countries.id)'))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $mappings = PublerCountryMapping::query()
            ->whereIn('country_id', $countries->pluck('id')->all())
            ->get()
            ->keyBy('country_id');

        $apiKey      = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        $workspaceId = setting('publer_workspace_id', '6a245fe41714e74d655cf271');

        return view('plugins/job-board::publer.index', compact(
            'countries', 'mappings', 'jobCounts', 'apiKey', 'workspaceId'
        ));
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'country_id'          => ['required', 'integer'],
            'workspace_id'        => ['nullable', 'string', 'max:100'],
            'facebook_account_id' => ['nullable', 'string', 'max:100'],
            'linkedin_account_id' => ['nullable', 'string', 'max:100'],
            'twitter_account_id'  => ['nullable', 'string', 'max:100'],
            'tiktok_account_id'   => ['nullable', 'string', 'max:100'],
            'instagram_account_id'=> ['nullable', 'string', 'max:100'],
            'is_active'           => ['nullable', 'boolean'],
        ]);

        PublerCountryMapping::updateOrCreate(
            ['country_id' => $validated['country_id']],
            [
                'workspace_id'         => $validated['workspace_id'] ?? null,
                'facebook_account_id'  => $validated['facebook_account_id']  ?: null,
                'linkedin_account_id'  => $validated['linkedin_account_id']  ?: null,
                'twitter_account_id'   => $validated['twitter_account_id']   ?: null,
                'tiktok_account_id'    => $validated['tiktok_account_id']    ?: null,
                'instagram_account_id' => $validated['instagram_account_id'] ?: null,
                'is_active'            => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return $this->httpResponse()->setMessage('Country mapping saved.');
    }

    public function toggle(PublerCountryMapping $mapping)
    {
        $mapping->is_active = ! $mapping->is_active;
        $mapping->save();

        return $this->httpResponse()->setData(['is_active' => $mapping->is_active]);
    }

    public function destroy(PublerCountryMapping $mapping)
    {
        $mapping->delete();

        return $this->httpResponse()->setMessage('Mapping cleared.');
    }

    public function fetchAccounts(Request $request)
    {
        $apiKey      = trim((string) $request->input('api_key', ''));
        $workspaceId = trim((string) $request->input('workspace_id', ''));

        if ($apiKey === '') {
            $apiKey = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        }

        if ($apiKey === '') {
            return response()->json(['error' => 'No Publer API key configured. Add PUBLER_API_KEY to .env.'], 422);
        }

        try {
            $publisher = app(SocialPublisherService::class);

            if ($workspaceId === '') {
                $workspaces  = $publisher->fetchPublerWorkspaces($apiKey);
                $workspaceId = $workspaces[0]['id'] ?? '';
            }

            $accounts = $publisher->fetchPublerAccounts($apiKey, $workspaceId);

            // Store workspace ID as a setting for convenience
            if ($workspaceId) {
                setting()->set('publer_workspace_id', $workspaceId)->save();
            }

            return response()->json([
                'accounts'     => $accounts,
                'workspace_id' => $workspaceId,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Publer API error: ' . $e->getMessage()], 422);
        }
    }

    public function saveImageSettings(Request $request, PublerCountryMapping $mapping)
    {
        $request->validate([
            'image_mode'      => ['required', 'in:none,template'],
            'text_color'      => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'overlay_opacity' => ['nullable', 'integer', 'min:0', 'max:90'],
            'wm_logo'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data = [
            'image_mode'      => $request->input('image_mode', 'none'),
            'text_color'      => $request->input('text_color', '#FFFFFF') ?: '#FFFFFF',
            'overlay_opacity' => (int) $request->input('overlay_opacity', 55),
        ];

        $dir = public_path('social-templates');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if ($request->hasFile('wm_logo') && $request->file('wm_logo')->isValid()) {
            $file    = $request->file('wm_logo');
            $newName = $mapping->country_id . '_wm_logo.' . $file->extension();

            if ($mapping->wm_logo) {
                $old = public_path(ltrim($mapping->wm_logo, '/'));
                if (file_exists($old)) {
                    @unlink($old);
                }
            }

            $file->move($dir, $newName);
            $data['wm_logo'] = 'social-templates/' . $newName;
        }

        $mapping->update($data);

        return $this->httpResponse()->setMessage('Image settings saved.');
    }

    public function previewImage(PublerCountryMapping $mapping, SocialImageService $imageService)
    {
        if ($mapping->image_mode !== 'template') {
            return response()->json(['error' => 'Image mode is set to "none". Enable Template mode first.'], 422);
        }

        // Pick the most recent published job from this country
        $job = Job::with(['company', 'slugable', 'country', 'currency'])
            ->where('status', 'published')
            ->where('country_id', $mapping->country_id)
            ->latest()
            ->first();

        if (! $job) {
            return response()->json(['error' => 'No published jobs found for this country to preview with.'], 422);
        }

        $format = request('format', 'square');
        $result = $imageService->generateForJob($job, $mapping, $format);

        if (! $result) {
            return response()->json(['error' => 'Image generation failed. Check that a template file is uploaded and is a valid image.'], 422);
        }

        [$localPath] = $result;

        // Stream the file directly rather than redirecting to its public URL — a redirect
        // would let the shutdown-based cleanup delete the file before the browser's
        // follow-up GET for the image arrives, producing a 404.
        return response()->file($localPath, ['Content-Type' => 'image/jpeg'])->deleteFileAfterSend(true);
    }

    public function testPost(PublerCountryMapping $mapping, SocialPublisherService $publisher)
    {
        $mapping->load('country');

        $apiKey      = trim((string) (setting('publer_api_key') ?: env('PUBLER_API_KEY', '')));
        $workspaceId = $mapping->workspace_id ?: setting('publer_workspace_id', '');

        if ($apiKey === '') {
            return $this->httpResponse()->setError()->setMessage('No Publer API key configured.');
        }

        $networkMap = $mapping->networkToAccountMap();
        if (empty($networkMap)) {
            return $this->httpResponse()->setError()->setMessage('No accounts mapped for this country. Save at least one account first.');
        }

        $countryName = $mapping->country?->name ?? 'this country';
        $testText    = "🧪 Test post from Wakanda Jobs — {$countryName} social accounts are connected and ready! wakandajobs.com";

        try {
            $ok = $publisher->publerPostText($testText, $apiKey, $workspaceId, $networkMap);
            return $ok
                ? $this->httpResponse()->setMessage("Test post sent to {$countryName} accounts via Publer.")
                : $this->httpResponse()->setError()->setMessage('Publer returned an error. Check the API key and account IDs.');
        } catch (\Throwable $e) {
            return $this->httpResponse()->setError()->setMessage('Error: ' . $e->getMessage());
        }
    }
}
