<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\FeaturedPackage;
use Illuminate\Http\Request;

class FeaturedPackageController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Featured Job Packages', route('featured-packages.index'));
    }

    public function index()
    {
        $this->pageTitle('Featured Job Packages');
        $packages = FeaturedPackage::query()->orderBy('sort_order')->orderBy('price')->get();
        return view('plugins/job-board::featured-packages.index', compact('packages'));
    }

    public function create()
    {
        $this->pageTitle('Create Featured Package');
        return view('plugins/job-board::featured-packages.create');
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePackage($request);
        FeaturedPackage::query()->create($validated);

        return $response
            ->setPreviousUrl(route('featured-packages.index'))
            ->setNextUrl(route('featured-packages.index'))
            ->setMessage('Package created successfully.');
    }

    public function edit(FeaturedPackage $featuredPackage)
    {
        $this->pageTitle('Edit Featured Package');
        return view('plugins/job-board::featured-packages.edit', ['package' => $featuredPackage]);
    }

    public function update(FeaturedPackage $featuredPackage, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validatePackage($request);
        $featuredPackage->update($validated);

        return $response
            ->setPreviousUrl(route('featured-packages.index'))
            ->setNextUrl(route('featured-packages.edit', $featuredPackage))
            ->setMessage('Package updated successfully.');
    }

    public function destroy(FeaturedPackage $featuredPackage, BaseHttpResponse $response)
    {
        $featuredPackage->delete();
        return $response
            ->setPreviousUrl(route('featured-packages.index'))
            ->setMessage('Package deleted successfully.');
    }

    protected function validatePackage(Request $request): array
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string', 'max:500'],
            'duration_days' => ['required', 'integer', 'min:0'],
            'price'         => ['required', 'numeric', 'min:0'],
            'currency'      => ['required', 'string', 'max:3'],
            'badge_label'   => ['required', 'string', 'max:50'],
            'is_active'     => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }
}
