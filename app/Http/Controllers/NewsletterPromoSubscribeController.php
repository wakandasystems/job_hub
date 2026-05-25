<?php

namespace App\Http\Controllers;

use Botble\Newsletter\Enums\NewsletterStatusEnum;
use Botble\Newsletter\Models\Newsletter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class NewsletterPromoSubscribeController extends Controller
{
    public function subscribe(Request $request)
    {
        abort_unless(URL::hasValidSignature($request), 404);

        $email = $request->query('email');
        $name  = $request->query('name');

        if (! $email) {
            abort(404);
        }

        $record = Newsletter::query()->firstOrNew(['email' => $email]);
        $record->name   = $name ?: $record->name;
        $record->status = NewsletterStatusEnum::SUBSCRIBED;
        $record->save();

        return redirect('/?subscribed=1')->with('success', 'You are now subscribed to the WakandaJobs newsletter!');
    }
}
