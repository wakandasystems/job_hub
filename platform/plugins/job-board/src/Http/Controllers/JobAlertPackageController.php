<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\JobAlertPackage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobAlertPackageController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Job Alert Packages', route('career-alert-packages.index'));
    }

    public function index()
    {
        $this->pageTitle('Job Alert Packages');
        $packages = JobAlertPackage::query()->orderBy('sort_order')->orderBy('price')->get();
        return view('plugins/job-board::job-alert-packages.index', compact('packages'));
    }

    public function create()
    {
        $this->pageTitle('Create Job Alert Package');
        return view('plugins/job-board::job-alert-packages.create');
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePackage($request);
        JobAlertPackage::query()->create($validated);

        return $response
            ->setPreviousUrl(route('career-alert-packages.index'))
            ->setNextUrl(route('career-alert-packages.index'))
            ->setMessage('Package created successfully.');
    }

    public function edit(JobAlertPackage $careerAlertPackage)
    {
        $this->pageTitle('Edit Job Alert Package');
        return view('plugins/job-board::job-alert-packages.edit', ['package' => $careerAlertPackage]);
    }

    public function update(JobAlertPackage $careerAlertPackage, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePackage($request);
        $careerAlertPackage->update($validated);

        return $response
            ->setPreviousUrl(route('career-alert-packages.index'))
            ->setNextUrl(route('career-alert-packages.edit', $careerAlertPackage))
            ->setMessage('Package updated successfully.');
    }

    public function destroy(JobAlertPackage $careerAlertPackage, BaseHttpResponse $response)
    {
        $careerAlertPackage->delete();
        return $response
            ->setPreviousUrl(route('career-alert-packages.index'))
            ->setMessage('Package deleted successfully.');
    }

    protected function validatePackage(Request $request): array
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:500'],
            'alerts_per_month' => ['required', 'integer', 'min:0'],
            'price'            => ['required', 'numeric', 'min:0'],
            'currency'         => ['required', 'string', 'max:3'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ]);

        // Checkbox not present means unchecked → false
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
