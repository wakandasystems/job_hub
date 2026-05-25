<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\JobBoard\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint'   => 'required|string|max:700',
            'p256dh'     => 'required|string|max:512',
            'auth'       => 'required|string|max:256',
            'country_id' => 'nullable|integer',
        ]);

        $accountId = auth('account')->id();
        $countryId = $this->resolveCountryId() ?? ($request->integer('country_id') ?: null);

        PushSubscription::updateOrCreate(
            ['endpoint' => $request->endpoint],
            [
                'account_id' => $accountId,
                'country_id' => $countryId,
                'p256dh'     => $request->p256dh,
                'auth'       => $request->auth,
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('endpoint', $request->endpoint)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function vapidKey(): JsonResponse
    {
        return response()->json([
            'vapid_public_key' => config('services.vapid.public_key'),
            'country_id'       => $this->resolveCountryId(),
        ]);
    }

    protected function resolveCountryId(): ?int
    {
        if (function_exists('wakanda_selected_country')) {
            $country = wakanda_selected_country();
            return $country ? (int) $country->id : null;
        }

        return null;
    }
}
