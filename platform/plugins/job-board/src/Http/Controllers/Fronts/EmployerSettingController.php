<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Forms\Fronts\AccountSettingForm;
use Botble\JobBoard\Http\Requests\SettingRequest;
use Botble\JobBoard\Models\AccountActivityLog;
use Botble\Optimize\Facades\OptimizerHelper;

class EmployerSettingController extends BaseController
{
    public function __construct()
    {
        OptimizerHelper::disable();
    }

    public function edit()
    {
        return redirect()->route('public.account.settings');
    }

    public function update(SettingRequest $request)
    {
        /**
         * @var Account $account
         */
        $account = auth('account')->user();
        $data = $request->validated();

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
            ->setMessage(trans('plugins/job-board::messages.update_settings_successfully'));
    }
}
