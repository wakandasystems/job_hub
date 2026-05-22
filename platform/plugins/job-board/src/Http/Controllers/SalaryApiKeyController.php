<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\SalaryApiKey;
use Illuminate\Http\Request;

class SalaryApiKeyController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Salary API Keys', route('salary-api-keys.index'));
    }

    public function index()
    {
        $this->pageTitle('Salary API Keys');

        $keys = SalaryApiKey::query()->latest()->paginate(20);

        $stats = [
            'total'      => SalaryApiKey::query()->count(),
            'active'     => SalaryApiKey::query()->where('is_active', true)->count(),
            'total_reqs' => SalaryApiKey::query()->sum('requests_this_month'),
        ];

        return view('plugins/job-board::salary-api-keys.index', compact('keys', 'stats'));
    }

    public function create()
    {
        $this->pageTitle('New API Key');

        return view('plugins/job-board::salary-api-keys.edit', ['key' => null]);
    }

    public function store(Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateKey($request);

        $generated = SalaryApiKey::generate();
        $validated['key_prefix'] = $generated['prefix'];
        $validated['key_hash']   = $generated['hash'];

        SalaryApiKey::query()->create($validated);

        return $response
            ->setPreviousUrl(route('salary-api-keys.index'))
            ->setNextUrl(route('salary-api-keys.index'))
            ->setMessage('API key created. Raw key: ' . $generated['raw'] . ' — copy it now, it will not be shown again.');
    }

    public function edit(SalaryApiKey $salaryApiKey)
    {
        $this->pageTitle('Edit API Key: ' . $salaryApiKey->name);

        return view('plugins/job-board::salary-api-keys.edit', ['key' => $salaryApiKey]);
    }

    public function update(SalaryApiKey $salaryApiKey, Request $request, BaseHttpResponse $response)
    {
        $validated = $this->validateKey($request, $salaryApiKey);

        $salaryApiKey->update($validated);

        return $response
            ->setPreviousUrl(route('salary-api-keys.index'))
            ->setNextUrl(route('salary-api-keys.edit', $salaryApiKey))
            ->setMessage('API key updated.');
    }

    public function destroy(SalaryApiKey $salaryApiKey, BaseHttpResponse $response)
    {
        $salaryApiKey->delete();

        return $response
            ->setNextUrl(route('salary-api-keys.index'))
            ->setMessage('API key revoked and deleted.');
    }

    protected function validateKey(Request $request, ?SalaryApiKey $key = null): array
    {
        return $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'plan'               => ['required', 'in:basic,pro,enterprise'],
            'requests_per_month' => ['required', 'integer', 'min:1'],
            'is_active'          => ['boolean'],
            'expires_at'         => ['nullable', 'date'],
            'contact_name'       => ['nullable', 'string', 'max:255'],
            'contact_email'      => ['nullable', 'email', 'max:255'],
            'notes'              => ['nullable', 'string'],
        ]);
    }
}
