<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\CareerServiceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CareerServiceOrderController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Career Service Orders', route('career-service-orders.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Career Service Orders');

        $query = CareerServiceOrder::query()
            ->with('candidate')
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($deliveryStatus = $request->query('delivery_status')) {
            $query->where('delivery_status', $deliveryStatus);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($query) use ($search): void {
                $query
                    ->where('service_name', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('assigned_coach_name', 'like', "%{$search}%")
                    ->orWhere('charge_id', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(30)->withQueryString();

        $stats = [
            'total' => CareerServiceOrder::query()->count(),
            'paid' => CareerServiceOrder::query()->where('status', 'paid')->count(),
            'unassigned' => CareerServiceOrder::query()->where('delivery_status', 'unassigned')->count(),
            'in_progress' => CareerServiceOrder::query()->where('delivery_status', 'in_progress')->count(),
        ];

        $deliveryStatuses = CareerServiceOrder::deliveryStatuses();

        return view('plugins/job-board::career-service-orders.index', compact('orders', 'stats', 'deliveryStatuses'));
    }

    public function edit(CareerServiceOrder $careerServiceOrder)
    {
        $this->pageTitle('Career Service Order #' . $careerServiceOrder->getKey());

        $deliveryStatuses = CareerServiceOrder::deliveryStatuses();

        $emailLogs = DB::table('jb_career_service_email_logs')
            ->where('order_id', $careerServiceOrder->id)
            ->orderByDesc('created_at')
            ->get();

        return view('plugins/job-board::career-service-orders.edit', [
            'order' => $careerServiceOrder,
            'deliveryStatuses' => $deliveryStatuses,
            'emailLogs' => $emailLogs,
        ]);
    }

    public function update(CareerServiceOrder $careerServiceOrder, Request $request, BaseHttpResponse $response)
    {
        $validated = $request->validate([
            'assigned_coach_name' => ['nullable', 'string', 'max:255'],
            'assigned_coach_email' => ['nullable', 'email', 'max:255'],
            'delivery_status' => ['required', Rule::in(array_keys(CareerServiceOrder::deliveryStatuses()))],
            'status' => ['required', Rule::in(['pending', 'paid', 'cancelled', 'refunded'])],
            'delivered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['delivery_status'] === 'delivered' && empty($validated['delivered_at'])) {
            $validated['delivered_at'] = now();
        }

        $careerServiceOrder->update($validated);

        return $response
            ->setPreviousUrl(route('career-service-orders.index'))
            ->setNextUrl(route('career-service-orders.edit', $careerServiceOrder))
            ->setMessage('Career service order updated successfully.');
    }

    public function bulkDestroy(Request $request, BaseHttpResponse $response)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids)) {
            return $response->setError()->setMessage('No orders selected.');
        }

        $orders = CareerServiceOrder::query()->whereIn('id', $ids)->get();

        foreach ($orders as $order) {
            if ($order->candidate_cv_path) {
                Storage::disk('local')->delete($order->candidate_cv_path);
            }
            if ($order->reviewed_cv_path) {
                Storage::disk('local')->delete($order->reviewed_cv_path);
            }
            $order->delete();
        }

        return $response
            ->setNextUrl(route('career-service-orders.index'))
            ->setMessage(count($orders) . ' order(s) deleted successfully.');
    }

    public function destroy(CareerServiceOrder $careerServiceOrder, BaseHttpResponse $response)
    {
        if ($careerServiceOrder->candidate_cv_path) {
            Storage::disk('local')->delete($careerServiceOrder->candidate_cv_path);
        }

        if ($careerServiceOrder->reviewed_cv_path) {
            Storage::disk('local')->delete($careerServiceOrder->reviewed_cv_path);
        }

        $careerServiceOrder->delete();

        return $response
            ->setNextUrl(route('career-service-orders.index'))
            ->setMessage('Career service order deleted.');
    }

    public function uploadReviewedCv(CareerServiceOrder $careerServiceOrder, Request $request, BaseHttpResponse $response)
    {
        $request->validate([
            'reviewed_cv' => ['required', 'file', 'mimes:docx,doc,pdf', 'max:20480'],
        ]);

        $file = $request->file('reviewed_cv');
        $filename = 'order-' . $careerServiceOrder->id . '-reviewed.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('career-service-cvs/' . $careerServiceOrder->id, $filename, 'local');

        if ($careerServiceOrder->reviewed_cv_path) {
            Storage::disk('local')->delete($careerServiceOrder->reviewed_cv_path);
        }

        $updates = ['reviewed_cv_path' => $path];

        if ($careerServiceOrder->delivery_status !== 'delivered') {
            $updates['delivery_status'] = 'delivered';
            $updates['delivered_at'] = now();
        }

        $careerServiceOrder->update($updates);

        return $response
            ->setPreviousUrl(route('career-service-orders.edit', $careerServiceOrder))
            ->setNextUrl(route('career-service-orders.edit', $careerServiceOrder))
            ->setMessage('Reviewed CV uploaded and order marked as delivered.');
    }

    public function downloadCandidateCv(CareerServiceOrder $careerServiceOrder)
    {
        abort_unless(
            $careerServiceOrder->candidate_cv_path && Storage::disk('local')->exists($careerServiceOrder->candidate_cv_path),
            404
        );

        return Storage::disk('local')->download(
            $careerServiceOrder->candidate_cv_path,
            'candidate-cv-order-' . str_pad($careerServiceOrder->id, 6, '0', STR_PAD_LEFT) . '.' . pathinfo($careerServiceOrder->candidate_cv_path, PATHINFO_EXTENSION)
        );
    }

    public function sendEmail(CareerServiceOrder $careerServiceOrder, Request $request, BaseHttpResponse $response)
    {
        $validated = $request->validate([
            'to_email' => ['required', 'email'],
            'subject'  => ['required', 'string', 'max:255'],
            'body'     => ['required', 'string'],
        ]);

        try {
            Mail::raw($validated['body'], function ($m) use ($validated, $careerServiceOrder): void {
                $m->to($validated['to_email'])
                  ->subject($validated['subject'])
                  ->replyTo(
                      setting('email_from_address', config('mail.from.address')),
                      setting('email_from_name', config('mail.from.name'))
                  );
            });

            DB::table('jb_career_service_email_logs')->insert([
                'order_id'   => $careerServiceOrder->id,
                'to_email'   => $validated['to_email'],
                'subject'    => $validated['subject'],
                'body'       => $validated['body'],
                'sent_by'    => Auth::user()?->name ?? 'Admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            return $response
                ->setError()
                ->setNextUrl(route('career-service-orders.edit', $careerServiceOrder))
                ->setMessage('Failed to send email: ' . $e->getMessage());
        }

        return $response
            ->setNextUrl(route('career-service-orders.edit', $careerServiceOrder))
            ->setMessage('Email sent successfully to ' . $validated['to_email']);
    }

    public function downloadReviewedCv(CareerServiceOrder $careerServiceOrder)
    {
        abort_unless(
            $careerServiceOrder->reviewed_cv_path && Storage::disk('local')->exists($careerServiceOrder->reviewed_cv_path),
            404
        );

        return Storage::disk('local')->download(
            $careerServiceOrder->reviewed_cv_path,
            'reviewed-cv-order-' . str_pad($careerServiceOrder->id, 6, '0', STR_PAD_LEFT) . '.' . pathinfo($careerServiceOrder->reviewed_cv_path, PATHINFO_EXTENSION)
        );
    }
}
