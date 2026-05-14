<?php

namespace Botble\JobBoard\Repositories\Eloquent;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\JobBoard\Repositories\Interfaces\CompanyInterface;
use Botble\Language\Facades\Language;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;

class CompanyRepository extends RepositoriesAbstract implements CompanyInterface
{
    public function getSearch($query, $limit = 10, $paginate = 10)
    {
        $data = $this->model->with('slugable')->where('jb_companies.status', BaseStatusEnum::PUBLISHED);

        if (
            is_plugin_active('language') &&
            is_plugin_active('language-advanced') &&
            Language::getCurrentLocale() != Language::getDefaultLocale()
        ) {
            foreach (explode(' ', $query) as $term) {
                $data = $data->where(function ($query) use ($term): void {
                    $query->where('jb_companies.name', 'LIKE', '%' . $term . '%')
                        ->orWhereHas('translations', function ($query) use ($term): void {
                            $query->where('name', 'LIKE', '%' . $term . '%');
                        });
                });
            }
        } else {
            foreach (explode(' ', $query) as $term) {
                $data = $data->where('jb_companies.name', 'LIKE', '%' . $term . '%');
            }
        }

        $data = $data->select('jb_companies.*')->oldest('jb_companies.name');

        if ($limit) {
            $data = $data->limit($limit);
        }

        if ($paginate) {
            return $this->applyBeforeExecuteQuery($data)->paginate($paginate);
        }

        return $this->applyBeforeExecuteQuery($data)->get();
    }
}
