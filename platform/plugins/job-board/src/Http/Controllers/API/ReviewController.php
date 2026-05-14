<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Requests\AjaxReviewRequest;
use Botble\JobBoard\Http\Resources\ReviewResource;
use Botble\JobBoard\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends BaseController
{
    public function index(Request $request)
    {
        $reviews = Review::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['reviewable', 'account'])
            ->when($request->input('reviewable_type'), function ($query, $type): void {
                $query->where('reviewable_type', $type);
            })
            ->when($request->input('reviewable_id'), function ($query, $id): void {
                $query->where('reviewable_id', $id);
            })
            ->when($request->input('rating'), function ($query, $rating): void {
                $query->where('star', $rating);
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 20), 50));

        return $this
            ->httpResponse()
            ->setData(ReviewResource::collection($reviews))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $review = Review::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->with(['reviewable', 'account'])
            ->find($id);

        if (! $review) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.review_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new ReviewResource($review))
            ->toApiResponse();
    }

    /**
     * Submit a review
     *
     * Submit a review for a company or candidate. Requires authentication.
     *
     * @authenticated
     * @group Reviews
     */
    public function store(AjaxReviewRequest $request)
    {
        $data = $request->validated();

        // Check if user is authenticated
        $account = auth('account')->user();
        if (! $account) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(401)
                ->setMessage(trans('plugins/job-board::messages.must_login_to_review'));
        }

        // Check if user already reviewed this item
        $existingReview = Review::query()
            ->where('account_id', $account->id)
            ->where('reviewable_type', $data['reviewable_type'])
            ->where('reviewable_id', $data['reviewable_id'])
            ->exists();

        if ($existingReview) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(400)
                ->setMessage(trans('plugins/job-board::messages.already_reviewed'));
        }

        $review = Review::create([
            'account_id' => $account->id,
            'reviewable_type' => $data['reviewable_type'],
            'reviewable_id' => $data['reviewable_id'],
            'star' => $request->input('rating', 5),
            'comment' => $request->input('comment', ''),
            'status' => BaseStatusEnum::PENDING,
        ]);

        return $this
            ->httpResponse()
            ->setMessage(trans('plugins/job-board::messages.review_submitted_pending'))
            ->setData(new ReviewResource($review))
            ->toApiResponse();
    }
}
