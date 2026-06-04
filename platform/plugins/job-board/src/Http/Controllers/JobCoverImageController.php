<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Job;
use Botble\JobBoard\Services\JobCoverImageGeneratorService;
use Botble\Media\Facades\RvMedia;
use Illuminate\Support\Facades\Storage;

class JobCoverImageController extends BaseController
{
    public function generate(int $id)
    {
        $job = Job::with(['company', 'country', 'slugable'])->findOrFail($id);

        $tmpPath = app(JobCoverImageGeneratorService::class)->generate($job);

        if (! $tmpPath) {
            return back()->with('error_msg', 'Image generation failed — GD extension may not be available.');
        }

        // Store in public media under job-covers/
        $filename  = 'job-cover-' . $job->id . '-' . time() . '.jpg';
        $storagePath = 'job-covers/' . $filename;

        Storage::disk('public')->put($storagePath, file_get_contents($tmpPath));
        @unlink($tmpPath);

        // Save path on the job record
        $job->update(['cover_image' => $storagePath]);

        return back()->with('success_msg', 'Cover image generated and saved successfully.');
    }
}
