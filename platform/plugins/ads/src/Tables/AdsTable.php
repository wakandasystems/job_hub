<?php

namespace Botble\Ads\Tables;

use Botble\Ads\Models\Ads;
use Botble\Base\Facades\Html;
use Botble\Media\Facades\RvMedia;
use Botble\Table\Abstracts\TableAbstract;
use Botble\Table\Actions\DeleteAction;
use Botble\Table\Actions\EditAction;
use Botble\Table\BulkActions\DeleteBulkAction;
use Botble\Table\BulkChanges\DateBulkChange;
use Botble\Table\BulkChanges\NameBulkChange;
use Botble\Table\BulkChanges\StatusBulkChange;
use Botble\Table\Columns\Column;
use Botble\Table\Columns\DateColumn;
use Botble\Table\Columns\FormattedColumn;
use Botble\Table\Columns\IdColumn;
use Botble\Table\Columns\NameColumn;
use Botble\Table\Columns\StatusColumn;
use Botble\Table\HeaderActions\CreateHeaderAction;

class AdsTable extends TableAbstract
{
    public function setup(): void
    {
        $this
            ->model(Ads::class)
            ->addColumns([
                IdColumn::make(),
                FormattedColumn::make('image')
                    ->title(trans('core/base::tables.image'))
                    ->width(180)
                    ->orderable(false)
                    ->searchable(false)
                    ->renderUsing(function (FormattedColumn $column) {
                        $item     = $column->getItem();
                        $imgUrl   = RvMedia::getImageUrl($item->image, null, false, RvMedia::getDefaultImage());
                        $editUrl  = route('ads.edit', $item->getKey());
                        $hasImage = (bool) $item->image;

                        $placeholder = $hasImage ? '' : 'background:#f1f3f5;display:flex;align-items:center;justify-content:center;';

                        return <<<HTML
                            <a href="{$editUrl}" style="display:inline-block;position:relative;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.13);text-decoration:none;width:160px;height:90px;{$placeholder}">
                                <img src="{$imgUrl}" alt="" style="width:160px;height:90px;object-fit:cover;display:block;" loading="lazy">
                                <span style="position:absolute;bottom:6px;left:6px;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);color:#fff;font-size:9.5px;font-weight:700;letter-spacing:.08em;padding:2px 7px;border-radius:3px;line-height:1.5;font-family:system-ui,sans-serif;">AD</span>
                                <span style="position:absolute;top:0;left:0;right:0;bottom:0;border-radius:8px;box-shadow:inset 0 0 0 1px rgba(0,0,0,.08);pointer-events:none;"></span>
                            </a>
                        HTML;
                    }),
                NameColumn::make()->route('ads.edit'),
                FormattedColumn::make('key')
                    ->title(trans('plugins/ads::ads.shortcode'))
                    ->alignStart()
                    ->getValueUsing(function (FormattedColumn $column) {
                        $value = $column->getItem()->key;

                        if (! function_exists('shortcode')) {
                            return $value;
                        }

                        return shortcode()->generateShortcode('ads', ['key' => $value]);
                    })
                    ->renderUsing(fn (FormattedColumn $column) => Html::tag('code', $column->getValue()))
                    ->copyable()
                    ->copyableState(fn (FormattedColumn $column) => $column->getValue()),
                Column::make('clicked')
                    ->title(trans('plugins/ads::ads.clicked'))
                    ->alignStart(),
                DateColumn::make('expired_at')->title(trans('plugins/ads::ads.expired_at')),
                StatusColumn::make(),
            ])
            ->addHeaderAction(CreateHeaderAction::make()->route('ads.create'))
            ->addActions([
                EditAction::make()->route('ads.edit'),
                DeleteAction::make()->route('ads.destroy'),
            ])
            ->addBulkAction(DeleteBulkAction::make()->permission('ads.destroy'))
            ->addBulkChanges([
                NameBulkChange::make(),
                StatusBulkChange::make(),
                DateBulkChange::make()->name('expired_at')->title(trans('plugins/ads::ads.expired_at')),
            ])
            ->queryUsing(function ($query): void {
                $query->select([
                    'id',
                    'image',
                    'key',
                    'name',
                    'clicked',
                    'expired_at',
                    'status',
                ]);
            });
    }
}
