<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Http\Resources\AccountResource;
use Botble\JobBoard\Models\Account;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends BaseController
{
    public function register(Request $request)
    {
        abort_unless(JobBoardHelper::isRegisterEnabled(), 403, __('Registration is currently disabled.'));

        $rules = [
            'first_name' => ['required', 'string', 'min:2', 'max:120'],
            'last_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:60', 'unique:jb_accounts,email'],
            'phone' => ['nullable', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];

        if (setting('job_board_enabled_register_as_employer', 1)) {
            $rules['account_type'] = ['required', 'string', Rule::in([
                AccountTypeEnum::JOB_SEEKER,
                AccountTypeEnum::EMPLOYER,
            ])];
        }

        $data = $request->validate($rules);

        $accountType = isset($data['account_type']) && $data['account_type'] === (string) AccountTypeEnum::EMPLOYER
            ? AccountTypeEnum::EMPLOYER
            : AccountTypeEnum::JOB_SEEKER;

        $account = Account::query()->forceCreate([
            'type' => $accountType,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => Arr::get($data, 'phone'),
            'password' => Hash::make($data['password']),
            'is_public_profile' => true,
        ]);

        event(new Registered($account));

        if (setting('verify_account_email', 0)) {
            $account->sendEmailVerificationNotification();

            return $this
                ->httpResponse()
                ->setMessage(trans('plugins/job-board::messages.verification_email_sent', [], 'Please check your email to verify your account.'))
                ->toApiResponse();
        }

        $account->confirmed_at = Carbon::now();
        $account->is_public_profile = false;
        $account->save();

        $token = $account->createToken($request->input('device_name', 'wakandajobs-mobile'))->plainTextToken;

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
