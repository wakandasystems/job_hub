<?php

namespace Botble\JobBoard\Http\Controllers\API;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Http\Resources\CurrencyResource;
use Botble\JobBoard\Models\Currency;
use Illuminate\Http\Request;

class CurrencyController extends BaseController
{
    public function index(Request $request)
    {
        $currencies = Currency::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->when($request->input('keyword'), function ($query, $keyword): void {
                $query->where('title', 'LIKE', "%{$keyword}%")
                      ->orWhere('symbol', 'LIKE', "%{$keyword}%");
            })
            ->oldest('order')
            ->latest()
            ->paginate(min($request->integer('per_page', 50), 100));

        return $this
            ->httpResponse()
            ->setData(CurrencyResource::collection($currencies))
            ->toApiResponse();
    }

    public function show(int $id)
    {
        $currency = Currency::query()
            ->where('status', BaseStatusEnum::PUBLISHED)
            ->find($id);

        if (! $currency) {
            return $this
                ->httpResponse()
                ->setError()
                ->setCode(404)
                ->setMessage(trans('plugins/job-board::messages.currency_not_found'));
        }

        return $this
            ->httpResponse()
            ->setData(new CurrencyResource($currency))
            ->toApiResponse();
    }
}
