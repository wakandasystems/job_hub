<?php

namespace Botble\JobBoard\BulkActions;

use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Base\Models\BaseModel;
use Botble\JobBoard\Models\Company;
use Botble\JobBoard\Services\CompanyMergeService;
use Botble\Table\Abstracts\TableBulkActionAbstract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class MergeCompaniesBulkAction extends TableBulkActionAbstract
{
    public function __construct()
    {
        $this
            ->label('Merge Selected Companies')
            ->confirmationModalTitle('Merge Companies')
            ->confirmationModalMessage(
                'This will merge the 2 selected companies into one. Whichever company has no linked '
                . 'employer login will be deleted, and its jobs, reviews and other data will be moved '
                . 'to the other. This cannot be undone from here — use the Merge Tool for manual control. Continue?'
            )
            ->confirmationModalButton('Merge');
    }

    public function dispatch(BaseModel|Model $model, array $ids): BaseHttpResponse
    {
        if (count($ids) !== 2) {
            return BaseHttpResponse::make()
                ->setError()
                ->setMessage('Please select exactly 2 companies to merge.');
        }

        $companies = Company::query()->whereKey($ids)->get();

        if ($companies->count() !== 2) {
            return BaseHttpResponse::make()
                ->setError()
                ->setMessage('Could not find both selected companies.');
        }

        $service = app(CompanyMergeService::class);

        $pair = $service->determineWinnerLoser($companies->first(), $companies->last());

        if (! $pair) {
            return BaseHttpResponse::make()
                ->setError()
                ->setMessage(
                    'Could not automatically tell which company should stay — both (or neither) have a '
                    . 'linked employer login. Use the "Merge Tool" button above to choose manually.'
                );
        }

        [$winner, $loser] = $pair;

        try {
            $service->merge($winner, $loser, Auth::id());
        } catch (RuntimeException $exception) {
            return BaseHttpResponse::make()
                ->setError()
                ->setMessage($exception->getMessage());
        }

        return BaseHttpResponse::make()
            ->setMessage("Merged \"{$loser->name}\" into \"{$winner->name}\".");
    }
}
