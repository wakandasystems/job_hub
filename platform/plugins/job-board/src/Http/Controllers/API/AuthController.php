<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Http\Resources\AccountResource;
use Botble\JobBoard\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'account_type' => ['required', 'string', Rule::in([
                AccountTypeEnum::JOB_SEEKER,
                AccountTypeEnum::EMPLOYER,
            ])],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $account = Account::query()
            ->where('email', $data['email'])
            ->first();

        if (! $account || ! Hash::check($data['password'], $account->password)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(422)
                ->setMessage(trans('auth.failed'));
        }

        if ((string) $account->type->getValue() !== $data['account_type']) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(__('This account is not registered as the selected account type.'));
        }

        if (setting('verify_account_email', 0) && empty($account->confirmed_at)) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(403)
                ->setMessage(trans('plugins/job-board::account.not_confirmed', [
                    'resend_link' => route('public.account.resend_confirmation', ['email' => $account->email]),
                ]));
        }

        $account->tokens()->where('name', $data['device_name'] ?? 'wakandajobs-mobile')->delete();
        $token = $account->createToken($data['device_name'] ?? 'wakandajobs-mobile')->plainTextToken;

        $with = ['companies', 'educations', 'experiences'];
        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }
        $account->load($with);

        return $this
            ->httpResponse()
            ->setData([
                'token' => $token,
                'token_type' => 'Bearer',
                'account_type' => (string) $account->type->getValue(),
                'account' => new AccountResource($account),
            ])
            ->toApiResponse();
    }

    public function me(Request $request)
    {
        $account = auth('account')->user() ?: $request->user();

        $with = ['companies', 'educations', 'experiences'];
        if (is_plugin_active('location')) {
            $with = array_merge($with, ['country', 'state', 'city']);
        }
        $account->load($with);

        return $this
            ->httpResponse()
            ->setData(new AccountResource($account))
            ->toApiResponse();
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this
            ->httpResponse()
            ->setMessage(__('Logged out successfully.'))
            ->toApiResponse();
    }
}
