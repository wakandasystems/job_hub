<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Facades\Assets;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\AccountActivityLog;
use Botble\JobBoard\Models\Review;
use Botble\JobBoard\Tables\Fronts\ReviewTable;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Exception;
use Illuminate\Http\Request;

class AccountReviewController extends BaseController
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            abort_unless(JobBoardHelper::isEnabledReview(), 404);

            return $next($request);
        });
    }

    public function index(ReviewTable $table)
    {
        $this->pageTitle(trans('plugins/job-board::messages.reviews'));

        SeoHelper::setTitle(trans('plugins/job-board::messages.reviews'));

        Assets::addStylesDirectly('vendor/core/plugins/job-board/css/review.css');

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.my_profile'), route('public.account.dashboard'))
            ->add(trans('plugins/job-board::messages.reviews'));

        return $table->render(JobBoardHelper::viewPath('dashboard.table.base'));
    }

    public function destroy(int|string $id, Request $request, BaseHttpResponse $response)
    {
        try {
            $review = Review::query()
                ->where([
                    'id' => $id,
                    'created_by_id' => auth('account')->id(),
                ])
                ->firstOrFail();

            $review->delete();

            event(new DeletedContentEvent(REVIEW_MODULE_SCREEN_NAME, $request, $review));

            AccountActivityLog::query()->create([
                'action' => 'delete_review',
                'reference_name' => $review->reviewable->name ?? '',
                'reference_url' => route('public.account.reviews.index'),
            ]);

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }
}
