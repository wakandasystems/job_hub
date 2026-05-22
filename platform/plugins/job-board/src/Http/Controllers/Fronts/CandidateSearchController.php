<?php

namespace Botble\JobBoard\Http\Controllers\Fronts;

use Botble\Base\Facades\PageTitle;
use Botble\Base\Http\Controllers\BaseController;
use Botble\JobBoard\Facades\JobBoardHelper;
use Botble\JobBoard\Models\Account;
use Botble\JobBoard\Models\CvReveal;
use Botble\JobBoard\Supports\CvRevealService;
use Botble\SeoHelper\Facades\SeoHelper;
use Illuminate\Http\Request;

class CandidateSearchController extends BaseController
{
    public function __construct(
        protected CvRevealService $revealService,
    ) {
    }

    public function index(Request $request)
    {
        /** @var Account $account */
        $account = auth('account')->user();

        SeoHelper::setTitle(__('Search Candidates'));
        PageTitle::setTitle(__('Search Candidates'));

        $query = Account::query()
            ->where('type', 'job_seeker')
            ->where('is_public_profile', true)
            ->with(['favoriteSkills']);

        if (setting('talent_hub_require_consent', true)) {
            $query->where('talent_hub_consent', true);
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($skill = $request->input('skill')) {
            $query->whereHas('favoriteSkills', function ($q) use ($skill): void {
                $q->where('jb_job_skills.name', 'like', "%{$skill}%");
            });
        }

        if ($location = $request->input('location')) {
            $query->where(function ($q) use ($location): void {
                $q->where('address', 'like', "%{$location}%");
                if (is_plugin_active('location')) {
                    $q->orWhereHas('city', fn ($c) => $c->where('name', 'like', "%{$location}%"))
                      ->orWhereHas('state', fn ($s) => $s->where('name', 'like', "%{$location}%"));
                }
            });
        }

        if ($request->filled('experience_years')) {
            $query->where('experience_years', $request->input('experience_years'));
        }

        if ($educationLevel = $request->input('education_level')) {
            $query->where('education_level', $educationLevel);
        }

        if ($availability = $request->input('availability')) {
            $query->where('availability', $availability);
        }

        if ($salaryMin = $request->input('salary_min')) {
            $query->where('desired_salary_to', '>=', (int) $salaryMin);
        }

        if ($salaryMax = $request->input('salary_max')) {
            $query->where('desired_salary_from', '<=', (int) $salaryMax);
        }

        if ($request->boolean('open_to_work')) {
            $query->where('available_for_hiring', true);
        }

        $candidates = $query->latest()->paginate(20)->withQueryString();

        $revealedIds = CvReveal::query()
            ->where('employer_id', $account->getKey())
            ->pluck('candidate_id')
            ->all();

        $canRevealFree = $this->revealService->canReveal($account)['can'];
        $revealCost    = (int) setting('cv_reveal_credit_cost', 1);

        return JobBoardHelper::view('dashboard.candidates.search', compact(
            'candidates', 'account', 'revealedIds', 'canRevealFree', 'revealCost'
        ));
    }
}
