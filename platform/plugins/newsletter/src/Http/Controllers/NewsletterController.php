<?php

namespace Botble\Newsletter\Http\Controllers;

use App\Mail\NewsletterMail;
use Botble\Base\Http\Actions\DeleteResourceAction;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Newsletter\Models\Newsletter;
use Botble\Newsletter\Tables\NewsletterTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends BaseController
{
    public function index(NewsletterTable $dataTable)
    {
        $this->pageTitle(trans('plugins/newsletter::newsletter.name'));

        return $dataTable->renderTable();
    }

    public function send()
    {
        $this->pageTitle('Send Newsletter');

        $subscriberCount = DB::table('newsletters')->where('status', 'subscribed')->count();

        return view('plugins/newsletter::send', compact('subscriberCount'));
    }

    public function sendPost(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:20000'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'test_to' => ['nullable', 'email', 'max:180'],
        ]);

        $pdfPath = null;

        if ($request->hasFile('pdf')) {
            $directory = storage_path('app/newsletter-attachments');

            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            $file = $request->file('pdf');
            $filename = now()->format('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
            $file->move($directory, $filename);
            $pdfPath = $directory . '/' . $filename;
        }

        if (! empty($validated['test_to'])) {
            $subscribers = collect([(object) [
                'id' => 0,
                'email' => $validated['test_to'],
                'name' => 'Test',
            ]]);
        } else {
            $subscribers = DB::table('newsletters')
                ->where('status', 'subscribed')
                ->select('id', 'email', 'name')
                ->get();
        }

        if ($subscribers->isEmpty()) {
            return redirect()->back()->withInput()->with('error_msg', 'No subscribed newsletter recipients found.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($subscribers as $subscriber) {
            try {
                Mail::to($subscriber->email, $subscriber->name ?? '')
                    ->send(new NewsletterMail(
                        subject: $validated['subject'],
                        body: $validated['message'],
                        imageUrl: $validated['image_url'] ?? null,
                        subscriberId: (int) $subscriber->id,
                        pdfPath: $pdfPath,
                    ));

                $sent++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $message = $failed > 0
            ? "Newsletter sent to {$sent} recipient(s). Failed: {$failed}."
            : "Newsletter sent to {$sent} recipient(s).";

        return redirect()->route('newsletter.send')->with($failed > 0 ? 'error_msg' : 'success_msg', $message);
    }

    public function destroy(Newsletter $newsletter)
    {
        return DeleteResourceAction::make($newsletter);
    }
}
