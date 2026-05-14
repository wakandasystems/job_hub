<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Invoice;
use Botble\JobBoard\Supports\InvoiceHelper;
use Botble\JobBoard\Tables\Fronts\InvoiceTable;
use Botble\SeoHelper\Facades\SeoHelper;
use Botble\Theme\Facades\Theme;
use Illuminate\Http\Request;

class InvoiceController extends BaseController
{
    public function index(InvoiceTable $invoiceTable)
    {
        $this->pageTitle(trans('plugins/job-board::messages.invoices'));

        Theme::breadcrumb()
            ->add(trans('plugins/job-board::messages.my_profile'), route('public.account.dashboard'))
            ->add(trans('plugins/job-board::messages.manage_invoices'));

        SeoHelper::setTitle(trans('plugins/job-board::messages.invoices'));

        return $invoiceTable->render(JobBoardHelper::viewPath('dashboard.table.base'));
    }

    public function show(Invoice $invoice)
    {
        abort_unless($this->canViewInvoice($invoice), 404);

        $title = trans('plugins/job-board::messages.invoice_detail', ['code' => $invoice->code]);

        $this->pageTitle($title);

        SeoHelper::setTitle($title);

        return JobBoardHelper::view('dashboard.invoices.detail', compact('invoice'));
    }

    public function getGenerateInvoice(Invoice $invoice, Request $request, InvoiceHelper $invoiceHelper)
    {
        abort_unless($this->canViewInvoice($invoice), 404);

        if ($request->input('type') === 'print') {
            return $invoiceHelper->streamInvoice($invoice);
        }

        return $invoiceHelper->downloadInvoice($invoice);
    }

    protected function canViewInvoice(Invoice $invoice): bool
    {
        return auth('account')->id() == $invoice->payment->customer_id;
    }
}
