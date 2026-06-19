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

    protected $hasOperations = false; // disable legacy operations column — we define our own

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
            ->with('account')
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
                    $avatar = $alert->account?->avatar_thumb_url ?: $alert->account?->avatar_url;
                    $badge = $alert->account?->wakanda_verified
                        ? '<span class="d-inline-flex align-items-center justify-content-center rounded-circle ms-1" title="Wakanda Verified" style="width:16px;height:16px;background:#6f42c1;color:#fff;font-size:10px;line-height:1;"><i class="ti ti-star-filled"></i></span>'
                        : '';

                    $name = '<div class="fw-semibold d-flex align-items-center gap-2">'
                        . ($avatar ? '<img src="' . e($avatar) . '" alt="" style="width:24px;height:24px;border-radius:999px;object-fit:cover;border:1px solid #e5e7eb">' : '')
                        . '<span>' . e($alert->candidate_name) . '</span>'
                        . $badge
                        . '</div>';

                    $html = $name;
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
                ->width(160)
                ->alignCenter()
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->printable(false)
                ->getValueUsing(function (FormattedColumn $column) {
                    $alert = $column->getItem();
                    $iconStyle = 'width:16px;height:16px;stroke:#fff;stroke-width:2;fill:none;vertical-align:middle';
                    $editIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4Z"/></svg>';
                    $historyIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><path d="M3 3v5h5"/><path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/><path d="M12 7v5l4 2"/></svg>';
                    $eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
                    $refreshIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><path d="M21 12a9 9 0 0 1-15.5 6.36L3 16"/><path d="M3 12A9 9 0 0 1 18.5 5.64L21 8"/><path d="M21 3v5h-5"/><path d="M3 21v-5h5"/></svg>';
                    $mailIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>';
                    $trashIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" style="' . $iconStyle . '"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>';

                    $inviteButton = (! $alert->account_id && $alert->candidate_email)
                        ? '<button type="button" class="btn btn-sm btn-icon btn-secondary text-white btn-send-account-invite" style="color:#fff !important" data-bs-toggle="tooltip" title="Invite candidate to create a Wakanda Jobs account" data-url="' . route('job-board.candidate-alerts.send-account-invite', $alert->id) . '" data-name="' . e($alert->candidate_name) . '">' . $mailIcon . '</button>'
                        : '';

                    $welcomeButton = '<button type="button" class="btn btn-sm btn-icon btn-warning text-white btn-send-welcome" style="color:#fff !important" data-bs-toggle="tooltip" title="Resend VIP welcome message" data-url="' . route('job-board.candidate-alerts.send-welcome', $alert->id) . '" data-name="' . e($alert->candidate_name) . '">' . $refreshIcon . '</button>';

                    return '<div class="table-actions">'
                        . '<button type="button" class="btn btn-sm btn-icon btn-primary text-white btn-edit-alert-modal" style="color:#fff !important" data-bs-toggle="tooltip" title="Edit" data-url="' . route('job-board.candidate-alerts.edit-modal', $alert->id) . '">' . $editIcon . '</button>'
                        . '<button type="button" class="btn btn-sm btn-icon btn-info text-white btn-view-logs" style="color:#fff !important" data-bs-toggle="tooltip" title="View send logs" data-url="' . route('job-board.candidate-alerts.logs', $alert->id) . '" data-name="' . e($alert->candidate_name) . '">' . $historyIcon . '</button>'
                        . '<button type="button" class="btn btn-sm btn-icon btn-success text-white btn-preview-jobs" style="color:#fff !important" data-bs-toggle="tooltip" title="Preview &amp; send matching jobs" data-url="' . route('job-board.candidate-alerts.preview', $alert->id) . '" data-send-url="' . route('job-board.candidate-alerts.send-now', $alert->id) . '" data-name="' . e($alert->candidate_name) . '">' . $eyeIcon . '</button>'
                        . $welcomeButton
                        . $inviteButton
                        . '<a href="' . route('job-board.candidate-alerts.destroy', $alert->id) . '" class="btn btn-sm btn-icon btn-danger text-white" style="color:#fff !important" data-dt-single-action data-method="DELETE" data-confirmation-modal="true" data-confirmation-modal-title="Delete Alert" data-confirmation-modal-message="Delete alert for ' . e($alert->candidate_name) . '? This will also delete all send logs." data-confirmation-modal-button="Delete" data-confirmation-modal-cancel-button="Cancel" data-bs-toggle="tooltip" title="Delete">' . $trashIcon . '</a>'
                        . '</div>';
                }),
        ];
    }

    public function buttons(): array
    {
        return [
            'add_alert' => [
                'link' => '#',
                'text' => '<span class="me-1">+</span> Add Alert',
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
