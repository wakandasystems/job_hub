<?php

namespace Botble\JobBoard\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Supports\Breadcrumb;
use Botble\JobBoard\Models\AutoApplyLog;
use Illuminate\Http\Request;

class AutoApplyLogController extends BaseController
{
    protected function breadcrumb(): Breadcrumb
    {
        return parent::breadcrumb()
            ->add('Auto Apply Logs', route('auto-apply-logs.index'));
    }

    public function index(Request $request)
    {
        $this->pageTitle('Auto Apply Logs');

        $query = AutoApplyLog::query()->with(['account', 'job.company', 'job.country'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('email_sent_to', 'like', "%{$search}%")
                  ->orWhereHas('account', function ($aq) use ($search): void {
                      $aq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('job', function ($jq) use ($search): void {
                      $jq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($accountId = $request->query('account_id')) {
            $query->where('account_id', $accountId);
        }

        $logs = $query->paginate(10)->withQueryString();

        $stats = [
            'total'        => AutoApplyLog::count(),
            'sent'         => AutoApplyLog::where('status', 'sent')->count(),
            'failed'       => AutoApplyLog::where('status', 'failed')->count(),
            'skipped'      => AutoApplyLog::where('status', 'skipped_low_score')->count(),
            'total_cost'   => (float) AutoApplyLog::sum('ai_cost_usd'),
            'total_tokens' => (int) AutoApplyLog::sum('total_tokens'),
        ];

        return view('plugins/job-board::auto-apply-logs.index', compact('logs', 'stats'));
    }

    public function destroy(AutoApplyLog $autoApplyLog, BaseHttpResponse $response)
    {
        $autoApplyLog->delete();

        return $response
            ->setNextUrl(route('auto-apply-logs.index'))
            ->setMessage('Auto Apply log deleted.');
    }

    public static function countryFlagEmoji(string $code): string
    {
        $code = strtoupper(trim($code));

        if (! preg_match('/^[A-Z]{2}$/', $code)) {
            return '';
        }

        $flag = '';

        foreach (str_split($code) as $char) {
            $flag .= mb_chr(127397 + ord($char), 'UTF-8');
        }

        return $flag;
    }
}
