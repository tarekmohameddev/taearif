<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use Illuminate\Http\Request;

class ProjectController extends Controller
{

    public function index($username, Request $request)
    {
        $userId = getUser()->id;
        // $misc = new MiscellaneousController();
        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $userId)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where('is_default', 1)->where('user_id', $userId)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $userId)->first();
        }

        // $information['seoInfo'] = $language->seoInfo()->select('meta_keyword_projects', 'meta_description_projects')->first();
        // $information['breadcrumb'] = $misc->getBreadcrumb($userId);
        // $queryResult['pageHeading'] = $this->pageHeading($userId);

        $title = $location =   null;
        if ($request->filled('title') && $request->filled('title')) {
            $title =  $request->title;
        }
        if ($request->filled('location') && $request->filled('location')) {
            $location =  $request->location;
        }
        if ($request->filled('sort')) {
            if ($request['sort'] == 'new') {
                $order_by_column = 'user_projects.id';
                $order = 'desc';
            } elseif ($request['sort'] == 'old') {
                $order_by_column = 'user_projects.id';
                $order = 'asc';
            } elseif ($request['sort'] == 'high-to-low') {
                $order_by_column = 'user_projects.min_price';
                $order = 'desc';
            } elseif ($request['sort'] == 'low-to-high') {
                $order_by_column = 'user_projects.min_price';
                $order = 'asc';
            } else {
                $order_by_column = 'user_projects.id';
                $order = 'desc';
            }
        } else {
            $order_by_column = 'user_projects.id';
            $order = 'desc';
        }

        $projects  = Project::where('user_projects.user_id', $userId)->join('user_project_contents', 'user_projects.id', 'user_project_contents.project_id')


            ->where('user_project_contents.language_id', $userCurrentLang->id)
            ->when($title, function ($query) use ($title) {
                return $query->where('user_project_contents.title', 'LIKE', '%' . $title . '%');
            })
            ->when($location, function ($query) use ($location) {
                return $query->where('user_project_contents.address', 'LIKE', '%' . $location . '%');
            })
            ->select('user_projects.*',  'user_project_contents.title', 'user_project_contents.slug', 'user_project_contents.address')
            ->orderBy($order_by_column, $order)
            ->paginate(9);
        $information['projects'] = $projects;
        $information['contents'] = $projects;


        return view('user-front.realestate.project.index', $information);
    }

    public function details($username, Request $request, $slug)
    {
        $userId = getUser()->id;
        // $misc = new MiscellaneousController();
        // $language = $this->currentLang($tenantId);
        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $userId)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where([['is_default', 1], ['user_id', $userId]])->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where([['is_default', 1], ['user_id', $userId]])->first();
        }
        // $queryResult['pageHeading'] = $this->pageHeading($userId);
        // $information['breadcrumb'] = $misc->getBreadcrumb($userId);
        $projectContent = ProjectContent::where([['slug', $slug], ['user_id', $userId]])->firstOrFail();
        $project = Project::query()
            // ->where('user_projects.approve_status', 1)
            ->where('user_projects.id', $projectContent->project_id)
            ->where('user_project_contents.language_id', $userCurrentLang->id)
            ->join('user_project_contents', 'user_projects.id', 'user_project_contents.project_id')
            // ->leftJoin('user_agents', function ($join) {
            //     $join->on('user_projects.agent_id', '=', 'user_agents.id')
            //         ->where(function ($query) {
            //             $query->whereNotNull('user_projects.agent_id')
            //                 ->where('user_projects.agent_id', '!=', 0)
            //                 ->where('user_agents.status', '=', 1);
            //         });
            // })
            ->select('user_projects.*', 'user_project_contents.id as contentId', 'user_project_contents.title', 'user_project_contents.slug', 'user_project_contents.address', 'user_project_contents.language_id', 'user_project_contents.description', 'user_project_contents.meta_keyword', 'user_project_contents.meta_description')

            ->with(['galleryImages', 'projectTypes' => function ($q) use ($userCurrentLang) {
                $q->where('language_id', $userCurrentLang->id);
            }, 'floorplanImages', 'specifications'])
            ->firstOrFail();

        $information['project'] = $project;
        $information['floorPlanImages'] = $information['project']->floorplanImages;
        $information['galleryImages'] =  $information['project']->galleryImages;

        return view('user-front.realestate.project.details', $information);
    }
}
