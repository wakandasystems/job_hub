<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Models\WakandaVerificationRequest;
use Illuminate\Http\Request;

class WakandaVerificationAdminController extends BaseController
{
    public function index(Request $request)
    {
        $this->pageTitle(__('Wakanda Verification Requests'));

        $status    = $request->input('status', 'pending');
        $requests  = WakandaVerificationRequest::with('account')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20);

        return view('plugins/job-board::verification.index', compact('requests', 'status'));
    }

    public function approve(Request $request, WakandaVerificationRequest $verificationRequest)
    {
        $score = (int) $request->input('score', 3);
        $notes = $request->input('notes');

        $verificationRequest->approve(max(1, min(5, $score)), $notes);

        return $this->httpResponse()->setMessage(__('Candidate approved and Wakanda badge awarded.'));
    }

    public function reject(Request $request, WakandaVerificationRequest $verificationRequest)
    {
        $verificationRequest->reject($request->input('notes'));

        return $this->httpResponse()->setMessage(__('Verification request rejected.'));
    }
}
