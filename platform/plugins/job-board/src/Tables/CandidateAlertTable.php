<?php

namespace Botble\JobBoard\Tables;

use Botble\JobBoard\Models\CandidateAlert;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\Columns\FormattedColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class CandidateAlertTable extends TableAbstract
{
    protected bool $bStateSave = false;

    protected int $defaultSortColumn = 2; // candidate_name column (after checkbox + active)

    public function setup(): void
    {
        $this
            ->model(CandidateAlert::class)
            ->setView('plugins/job-board::candidate-alerts.table-view');
    }

    public function html()
    {
        return parent::html()->ajax([
            'url'    => $this->getAjaxUrl(),
            'method' => 'GET',
        ]);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this
            ->getModel()
            ->query()
            ->withCount('logs')
            ->latest();

        return $this->applyScopes($query);
    }

    public function columns(): array
    {
        return [
            FormattedColumn::make('active')
                ->title('')
                ->width(42)
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->printable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    return '<div class="form-check form-switch mb-0">
                        <input class="form-check-input alert-toggle" type="checkbox" role="switch"
                            data-url="' . route('job-board.candidate-alerts.toggle', $alert->id) . '"
                            ' . ($alert->is_active ? 'checked' : '') . '
                            title="' . ($alert->is_active ? 'Active — click to disable' : 'Inactive — click to enable') . '">
                    </div>';
                }),

            FormattedColumn::make('candidate_name')
                ->title('Candidate')
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    $html = '<div class="fw-semibold">' . e($alert->candidate_name) . '</div>';
                    $html .= '<div class="text-muted small"><i class="ti ti-brand-whatsapp me-1" style="color:#25D366"></i>' . e($alert->candidate_phone) . '</div>';
                    if ($alert->candidate_email) {
                        $html .= '<div class="text-muted small"><i class="ti ti-mail me-1"></i>' . e($alert->candidate_email) . '</div>';
                    }
                    return $html;
                }),

            FormattedColumn::make('filters')
                ->title('Alert / Filters')
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert  = $column->getItem();
                    $f      = $alert->filters ?? [];
                    $html   = '<div class="fw-semibold text-truncate" style="max-width:200px" title="' . e($alert->label) . '">' . e($alert->candidate_name) . '</div>';
                    $html  .= '<div class="d-flex flex-wrap gap-1 mt-1">';

                    $keywords = array_filter(array_map('trim', (array) ($f['keywords'] ?? (($f['keyword'] ?? null) ? [$f['keyword']] : []))));
                    if ($keywords) {
                        $kwText = implode(', ', $keywords);
                        if (strlen($kwText) > 28) $kwText = mb_substr($kwText, 0, 25) . '...';
                        $html .= '<span class="badge bg-light text-dark border small"><i class="fas fa-search me-1"></i>' . e($kwText) . '</span>';
                    }

                    $companies = array_filter(array_map('trim', (array) ($f['company_keywords'] ?? [])));
                    if ($companies) {
                        $html .= '<span class="badge bg-light text-dark border small"><i class="fas fa-building me-1"></i>' . e(implode(', ', array_slice($companies, 0, 2))) . '</span>';
                    }

                    if (!empty($f['country_ids'])) {
                        $names = DB::table('countries')->whereIn('id', $f['country_ids'])->pluck('name');
                        $html .= '<span class="badge bg-light text-dark border small"><i class="fas fa-globe me-1"></i>' . e($names->implode(', ')) . '</span>';
                    }

                    if (!empty($f['location_keyword'])) {
                        $html .= '<span class="badge bg-light text-dark border small"><i class="fas fa-map-marker-alt me-1"></i>' . e($f['location_keyword']) . '</span>';
                    }

                    $html .= '</div>';
                    return $html;
                }),

            FormattedColumn::make('duration_days')
                ->title('Package')
                ->width(120)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert   = $column->getItem();
                    $durInfo = CandidateAlert::$durations[$alert->duration_days] ?? ['label' => $alert->duration_days . 'd', 'badge' => 'bg-secondary text-white'];
                    return '<span class="badge ' . $durInfo['badge'] . ' fw-semibold">' . e($durInfo['label']) . '</span>'
                         . '<div class="text-muted small mt-1">K' . number_format($alert->price, 0) . '</div>';
                }),

            FormattedColumn::make('status')
                ->title('Status')
                ->width(100)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    if ($alert->status === 'expired') {
                        $badge = '<span class="badge bg-danger-subtle text-danger alert-status-badge">Expired</span>';
                    } elseif ($alert->status === 'active' && $alert->is_active) {
                        $badge = '<span class="badge bg-success-subtle text-success alert-status-badge">Active</span>';
                    } else {
                        $badge = '<span class="badge bg-secondary-subtle text-secondary alert-status-badge">Inactive</span>';
                    }
                    $expiry = $alert->expires_at
                        ? '<div class="text-muted" style="font-size:.7rem">' . $alert->expires_at->format('d M Y') . '</div>'
                        : '';
                    return $badge . $expiry;
                }),

            FormattedColumn::make('expires_at')
                ->title('Days Left')
                ->width(90)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    if ($alert->status === 'expired') {
                        return '<span class="text-danger fw-semibold text-center d-block">Exp</span>';
                    }
                    $days  = $alert->daysRemaining();
                    $color = $days <= 2 ? 'danger' : ($days <= 7 ? 'warning' : 'success');
                    return '<span class="text-' . $color . ' fw-semibold text-center d-block">' . $days . 'd</span>';
                }),

            FormattedColumn::make('logs_count')
                ->title('Sent')
                ->width(80)
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    return '<span class="badge bg-info-subtle text-info fw-semibold d-block text-center">'
                         . $column->getItem()->logs_count . '</span>';
                }),

            FormattedColumn::make('operations')
                ->title('Actions')
                ->width(200)
                ->alignCenter()
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->printable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    return '<div class="table-actions">'
                        . '<a href="' . route('job-board.candidate-alerts.edit', $alert->id) . '" class="btn btn-sm btn-icon btn-primary" title="Edit"><svg class="icon svg-icon-ti-ti-edit" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path><path d="M16 5l3 3"></path></svg><span class="sr-only">Edit</span></a>'
                        . '<button type="button" class="btn btn-sm btn-icon btn-info btn-view-logs" title="View send logs" data-url="' . route('job-board.candidate-alerts.logs', $alert->id) . '" data-name="' . e($alert->candidate_name) . '"><svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6 -3a9 9 0 1 1 -18 0a9 9 0 0 1 18 0z"></path></svg><span class="sr-only">Logs</span></button>'
                        . '<button type="button" class="btn btn-sm btn-icon btn-success btn-preview-jobs" title="Preview &amp; send matching jobs" data-url="' . route('job-board.candidate-alerts.preview', $alert->id) . '" data-send-url="' . route('job-board.candidate-alerts.send-now', $alert->id) . '" data-name="' . e($alert->candidate_name) . '"><svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 14l11 -11m0 0l-6.5 18a.55 .55 0 0 1 -1 0l-3.5 -7l-7 -3.5a.55 .55 0 0 1 0 -1l18 -6.5"></path></svg><span class="sr-only">Send</span></button>'
                        . '<button type="button" class="btn btn-sm btn-icon btn-warning btn-send-welcome" title="Resend VIP welcome message" data-url="' . route('job-board.candidate-alerts.send-welcome', $alert->id) . '" data-name="' . e($alert->candidate_name) . '"><svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"></path></svg><span class="sr-only">Welcome</span></button>'
                        . '<a href="' . route('job-board.candidate-alerts.destroy', $alert->id) . '" class="btn btn-sm btn-icon btn-danger" data-dt-single-action data-method="DELETE" data-confirmation-modal="true" data-confirmation-modal-title="Delete Alert" data-confirmation-modal-message="Delete alert for ' . e($alert->candidate_name) . '? This will also delete all send logs." data-confirmation-modal-button="Delete" data-confirmation-modal-cancel-button="Cancel" title="Delete"><svg class="icon svg-icon-ti-ti-trash" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7l16 0"></path><path d="M10 11l0 6"></path><path d="M14 11l0 6"></path><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path></svg><span class="sr-only">Delete</span></a>'
                        . '</div>';
                }),
        ];
    }

    public function buttons(): array
    {
        return [
            'add_alert' => [
                'link' => '#',
                'text' => '<svg class="icon svg-icon-ti-ti-plus" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5l0 14"></path><path d="M5 12l14 0"></path></svg> Add Alert',
                'class' => 'btn-primary',
            ],
        ];
    }

    public function bulkActions(): array
    {
        return [
            DeleteBulkAction::make()->permission('job-board.candidate-alerts.destroy'),
        ];
    }
}
