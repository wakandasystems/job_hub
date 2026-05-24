<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Enums\AccountTypeEnum;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Forms\Fronts\AccountLanguageForm;
use Botble\JobBoard\Forms\Fronts\AccountSettingForm;
use Botble\JobBoard\Http\Requests\AvatarRequest;
use Botble\JobBoard\Http\Requests\SettingRequest;
use Botble\JobBoard\Http\Requests\UpdatePasswordRequest;
use Botble\JobBoard\Http\Requests\UploadResumeRequest;
use Botble\JobBoard\Http\Resources\ActivityLogResource;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\AccountActivityLog;
use Botble\JobBoard\Models\AccountEducation;
use Botble\JobBoard\Models\AccountExperience;
use Botble\JobBoard\Models\AccountLanguage;
use Botble\JobBoard\Models\CareerServiceOrder;
use Botble\JobBoard\Models\Currency;
use Botble\JobBoard\Services\CvScoringService;
use Botble\Media\Facades\RvMedia;
use Botble\Media\Models\MediaFile;
use Botble\Media\Services\ThumbnailService;
use Botble\Optimize\Facades\OptimizerHelper;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends BaseController
{
    public function __construct()
    {
        OptimizerHelper::disable();
    }

    public function getOverview()
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        SeoHelper::setTitle(__('Overview'));
        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.my_profile'), route('public.account.overview'))
            ->add($account->name);

        $educations = AccountEducation::query()
            ->where('account_id', $account->id)
            ->get();

        $experiences = AccountExperience::query()
            ->where('account_id', $account->id)
            ->get();

        $data = compact('account', 'educations', 'experiences');

        return view(JobBoardHelper::viewPath('account.overview'), $data);
    }

    public function getCareerServices()
    {
        SeoHelper::setTitle(__('Career Services'));

        /** @var Account $account */
        $account = auth('account')->user();

        $services = CareerServiceOrder::services();
        $servicePrices = collect($services)
            ->mapWithKeys(fn (array $service, string $key): array => [$key => $this->careerServicePricing($service)]);

        $myOrders = CareerServiceOrder::where('customer_email', $account->email)
            ->latest()
            ->limit(10)
            ->get();

        return JobBoardHelper::scope('account.career-services', compact('account', 'services', 'servicePrices', 'myOrders'));
    }

    protected function careerServicePricing(array $service): array
    {
        $originCode = strtoupper((string) ($service['currency'] ?? setting('career_service_currency', 'USD')));
        $originCurrency = Currency::query()->where('title', $originCode)->first();
        $targetCurrency = get_application_currency();
        $amount = round((float) format_price($service['price'], $originCurrency, true, true, true), (int) ($targetCurrency->decimals ?? 2));
        $originMeta = function_exists('wakanda_currency_meta') ? wakanda_currency_meta($originCode) : null;
        $targetMeta = $targetCurrency && function_exists('wakanda_currency_meta') ? wakanda_currency_meta($targetCurrency->title) : null;

        return [
            'amount' => $amount,
            'display' => $originCurrency ? format_price($service['price'], $originCurrency, fullNumber: true) : number_format($service['price'], 2) . ' ' . $originCode,
            'currency_code' => strtoupper((string) ($targetCurrency->title ?? $originCode)),
            'target_country' => $targetMeta['country'] ?? null,
            'origin_country' => $originMeta['country'] ?? null,
            'origin_currency_code' => $originCode,
            'origin_display' => number_format($service['price'], 2) . ' ' . $originCode,
        ];
    }

    public function getSettings()
    {
        SeoHelper::setTitle(trans('plugins/job-board::messages.my_profile'));
        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        $form = AccountSettingForm::createFromModel($account);

        $languages = AccountLanguage::query()
            ->where('account_id', $account->id)
            ->with('languageLevel')
            ->latest('id')
            ->get();

        $languageForm = AccountLanguageForm::create();

        return view(
            JobBoardHelper::viewPath('account.settings.index'),
            compact('account', 'languages', 'form', 'languageForm')
        );
    }

    public function postSettings(SettingRequest $request)
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();
        $data = $request->validated();
        Arr::forget($data, ['resume', 'cover_letter']);

        if ($request->hasFile('resume')) {
            $result = RvMedia::handleUpload($request->file('resume'), 0, $account->upload_folder);

            if (! $result['error']) {
                if ($path = $account->resume) {
                    Storage::disk('public')->delete($path);
                }

                $data['resume'] = $result['data']->url;

                try {
                    $uploadedFile  = $request->file('resume');
                    $cvScoreResult = app(CvScoringService::class)->scoreFile(
                        $uploadedFile->getRealPath(),
                        $uploadedFile->getClientOriginalExtension()
                    );
                    if ($cvScoreResult) {
                        $data['cv_score']      = $cvScoreResult['score'];
                        $data['cv_score_data'] = $cvScoreResult;
                    }
                } catch (\Throwable) {
                    // Non-fatal
                }
            }
        }

        if ($request->hasFile('cover_letter')) {
            $result = RvMedia::handleUpload($request->file('cover_letter'), 0, $account->upload_folder);

            if (! $result['error']) {
                if ($path = $account->cover_letter) {
                    Storage::disk('public')->delete($path);
                }

                $data['cover_letter'] = $result['data']->url;
            }
        }

        // Touch profile_updated_at so staleness tracking resets
        $data['profile_updated_at'] = now();

        AccountSettingForm::createFromModel($account)
            ->saving(function (AccountSettingForm $form) use ($data): void {
                $model = $form->getModel();

                $model->fill($data);
                $model->save();
            });

        AccountActivityLog::query()->create(['action' => 'update_setting']);

        return $this
            ->httpResponse()
            ->setNextUrl(route('public.account.settings'))
            ->setMessage(trans('plugins/job-board::messages.update_profile_successfully'));
    }

    public function getChooseType()
    {
        /** @var Account $account */
        $account = auth('account')->user();

        // Already has a confirmed type, so skip the chooser.
        if (in_array($account->type?->getValue(), [AccountTypeEnum::JOB_SEEKER, AccountTypeEnum::EMPLOYER], true)) {
            if ($account->isEmployer()) {
                return redirect()->route('public.account.dashboard');
            }

            return redirect()->route('public.account.dashboard');
        }

        SeoHelper::setTitle(__('Welcome! Tell us about yourself'));

        return Theme::scope('job-board.auth.choose-type', compact('account'))->render();
    }

    public function postChooseType(Request $request)
    {
        $request->validate([
            'type' => ['required', 'in:job-seeker,employer'],
        ]);

        /** @var Account $account */
        $account = auth('account')->user();
        $account->update(['type' => $request->input('type') === 'employer' ? AccountTypeEnum::EMPLOYER : AccountTypeEnum::JOB_SEEKER]);

        if ($account->isEmployer()) {
            return redirect()->route('public.account.dashboard')
                ->with('success_msg', __('Welcome! You can now manage your employer account.'));
        }

        return redirect()->route('public.account.dashboard')
            ->with('success_msg', __('Welcome! Start exploring jobs.'));
    }

    public function getSecurity()
    {
        SeoHelper::setTitle(trans('plugins/job-board::messages.security'));

        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        return view(JobBoardHelper::viewPath('account.settings.security'), compact('account'));
    }

    public function postSecurity(UpdatePasswordRequest $request)
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        if (! Hash::check($request->input('old_password'), $account->getAuthPassword())) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage(trans('plugins/job-board::dashboard.current_password_incorrect'));
        }

        $account->update([
            'password' => Hash::make($request->input('password')),
        ]);

        AccountActivityLog::query()->create(['action' => 'update_security']);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::dashboard.password_update_success'));
    }

    public function postAvatar(AvatarRequest $request, ThumbnailService $thumbnailService)
    {
        try {
            $account = auth('account')->user();

            $result = RvMedia::handleUpload($request->file('avatar_file'), 0, $account->upload_folder);

            if ($result['error']) {
                return $this
                    ->httpResponse()->setError()->setMessage($result['message']);
            }

            $avatarData = json_decode($request->input('avatar_data'));

            $file = $result['data'];

            $fileUpload = RvMedia::getRealPath($file->url);

            if (RvMedia::isUsingCloud()) {
                $fileUpload = @file_get_contents($fileUpload);
            }

            $thumbnailService
                ->setImage($fileUpload)
                ->setSize((int) $avatarData->width, (int) $avatarData->height)
                ->setCoordinates((int) $avatarData->x, (int) $avatarData->y)
                ->setDestinationPath(File::dirname($file->url))
                ->setFileName(File::name($file->url) . '.' . File::extension($file->url))
                ->save('crop');

            $avatar = MediaFile::query()->find($account->avatar_id);

            if ($avatar) {
                $avatar->forceDelete();
            }

            $account->avatar_id = $file->id;
            $account->save();

            AccountActivityLog::query()->create([
                'action' => 'changed_avatar',
            ]);

            return $this
                ->httpResponse()
                ->setMessage(trans('plugins/job-board::dashboard.update_avatar_success'))
                ->setData(['url' => RvMedia::url($file->url)]);
        } catch (Exception $ex) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($ex->getMessage());
        }
    }

    public function deleteAvatar()
    {
        try {
            $account = auth('account')->user();

            if (! $account->avatar_id) {
                return $this
                    ->httpResponse()
                    ->setError()
                    ->setMessage(trans('plugins/job-board::dashboard.avatar_not_found'));
            }

            $avatar = MediaFile::query()->find($account->avatar_id);

            if ($avatar) {
                $avatar->forceDelete();
            }

            $account->update([
                'avatar_id' => null,
            ]);

            return $this
                    ->httpResponse()
                    ->withDeletedSuccessMessage();

        } catch (Exception $ex) {
            return $this
                ->httpResponse()
                ->setError()
                ->setMessage($ex->getMessage());
        }
    }

    public function getActivityLogs()
    {
        $activities = AccountActivityLog::query()
            ->where('account_id', auth('account')->id())
            ->latest('created_at')
            ->paginate(10);

        return $this
            ->httpResponse()
            ->setData(ActivityLogResource::collection($activities))
            ->toApiResponse();
    }

    public function postUpload(UploadResumeRequest $request)
    {
        $account = auth('account')->user();

        $result = RvMedia::handleUpload($request->file('file'), 0, $account->upload_folder);

        if ($result['error']) {
            return $this
                ->httpResponse()
                ->setError();
        }

        return $this
            ->httpResponse()
            ->setData($result['data']);
    }

    public function postUploadFromEditor(Request $request)
    {
        $account = auth('account')->user();

        return RvMedia::uploadFromEditor($request, 0, $account->upload_folder);
    }

    public function postUploadResume(UploadResumeRequest $request)
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();

        $result = RvMedia::handleUpload($request->file('file'), 0, $account->upload_folder);

        if ($result['error']) {
            return $this
                ->httpResponse()->setError();
        }

        $account->update(['resume' => $result['data']->url]);

        $url = null;
        if (! $account->phone) {
            $url = route('public.account.settings');
        }

        return $this
            ->httpResponse()
            ->setData(compact('url'));
    }

    public function postUploadResumeScore(UploadResumeRequest $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        $result = RvMedia::handleUpload($request->file('file'), 0, $account->upload_folder);

        if ($result['error']) {
            return $this->httpResponse()->setError()->setMessage($result['message'] ?? __('Upload failed.'));
        }

        if ($old = $account->resume) {
            Storage::disk('public')->delete($old);
        }

        // Archive the previous score into history before overwriting
        if ($account->cv_score) {
            $history = (array) ($account->cv_score_history ?: []);
            array_unshift($history, [
                'score'     => $account->cv_score,
                'data'      => $account->cv_score_data,
                'archived_at' => now()->toIso8601String(),
            ]);
            // Keep at most 20 historical entries
            $history = array_slice($history, 0, 20);
        }

        $data = [
            'resume'           => $result['data']->url,
            'cv_score'         => null,
            'cv_score_data'    => null,
            'cv_score_history' => $history ?? $account->cv_score_history,
        ];

        $uploadedFile = $request->file('file');
        try {
            $cvResult = app(CvScoringService::class)->scoreFile(
                $uploadedFile->getRealPath(),
                $uploadedFile->getClientOriginalExtension()
            );
            if ($cvResult) {
                $data['cv_score']      = $cvResult['score'];
                $data['cv_score_data'] = $cvResult;
            }
        } catch (\Throwable) {
            // Non-fatal: upload succeeded even if scoring fails
        }

        $account->update($data);

        return $this->httpResponse()->setData([
            'resume'    => $account->resume,
            'score'     => $account->cv_score,
            'feedback'  => $account->cv_score_data['feedback'] ?? [],
            'scored_at' => $account->cv_score_data['scored_at'] ?? null,
        ]);
    }

    public function getCvScoreHistory(Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        $history = (array) ($account->cv_score_history ?: []);

        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 5;
        $total   = count($history);
        $items   = array_slice($history, ($page - 1) * $perPage, $perPage);

        return $this->httpResponse()->setData([
            'items'        => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage) ?: 1,
        ]);
    }

    public function deleteResume(Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        if ($path = $account->resume) {
            Storage::disk('public')->delete($path);
        }

        $account->update(['resume' => null, 'cv_score' => null, 'cv_score_data' => null]);

        return $this->httpResponse()->setMessage(__('CV removed successfully.'));
    }
}
