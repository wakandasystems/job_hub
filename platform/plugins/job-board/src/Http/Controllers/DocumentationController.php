<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\Documentation;
use Illuminate\Http\Request;

class DocumentationController extends BaseController
{
    public function index(Request $request)
    {
        $this->pageTitle(__('Documentation'));

        $category = $request->input('category');
        $docs = Documentation::query()
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->paginate(30);

        $categories = Documentation::query()->distinct()->orderBy('category')->pluck('category');

        return view('plugins/job-board::documentation.index', compact('docs', 'categories', 'category'));
    }

    public function create()
    {
        $this->pageTitle(__('Create Documentation'));

        $categories = Documentation::query()->distinct()->orderBy('category')->pluck('category');

        return view('plugins/job-board::documentation.form', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'content'      => 'required|string',
            'sort_order'   => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $data['is_published'] = $request->boolean('is_published', true);
        $data['sort_order']   = $data['sort_order'] ?? 0;

        $doc = Documentation::query()->create($data);

        return $this->httpResponse()
            ->setPreviousUrl(route('documentation.index'))
            ->setNextUrl(route('documentation.edit', $doc->id))
            ->setMessage(__('Documentation entry created.'));
    }

    public function edit(Documentation $documentation)
    {
        $this->pageTitle(__('Edit Documentation: :title', ['title' => $documentation->title]));

        $categories = Documentation::query()->distinct()->orderBy('category')->pluck('category');

        return view('plugins/job-board::documentation.form', compact('documentation', 'categories'));
    }

    public function update(Documentation $documentation, Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'content'      => 'required|string',
            'sort_order'   => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
        ]);

        $data['is_published'] = $request->boolean('is_published', true);
        $data['sort_order']   = $data['sort_order'] ?? 0;

        $documentation->fill($data)->save();

        return $this->httpResponse()
            ->setPreviousUrl(route('documentation.index'))
            ->setMessage(__('Documentation updated.'));
    }

    public function destroy(Documentation $documentation)
    {
        $documentation->delete();

        return $this->httpResponse()->setMessage(__('Documentation entry deleted.'));
    }
}
