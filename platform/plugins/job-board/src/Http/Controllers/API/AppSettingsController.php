<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Illuminate\Http\Request;

class AppSettingsController extends BaseController
{
    public function index(Request $request)
    {
        $socialLoginEnabled = (bool) setting('social_login_enable', false);

        return $this
            ->httpResponse()
            ->setData([
                'social_login_enabled' => $socialLoginEnabled,
                'social_login_google_enabled' => $socialLoginEnabled && (bool) setting('social_login_google_enable', false),
                'social_login_facebook_enabled' => $socialLoginEnabled && (bool) setting('social_login_facebook_enable', false),
                'social_login_apple_enabled' => $socialLoginEnabled && (bool) setting('social_login_apple_enable', false),
                'social_login_x_enabled' => $socialLoginEnabled && (bool) setting('social_login_x_enable', false),
                'registration_enabled' => JobBoardHelper::isRegisterEnabled(),
                'employer_registration_enabled' => (bool) setting('job_board_enabled_register_as_employer', 1),
                'verify_account_email' => (bool) setting('verify_account_email', 0),
            ])
            ->toApiResponse();
    }
}
