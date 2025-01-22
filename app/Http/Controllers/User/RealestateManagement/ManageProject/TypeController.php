<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProject;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class TypeController extends Controller
{
    public function index(Request $request, $id)
    {
        $userId = Auth::guard('web')->user()->id;
        $data['languages'] = Language::where('user_id', $userId)->get();

        if ($request->has('language')) {
            $language = Language::where('user_id', $userId)->where('code', $request->language)->first();
        } else {

            $language = Language::where('user_id', $userId)->where('is_default', 1)->first();
        }

        $data['language'] = $language;



        $project = Project::findOrFail($id);
        $data['project_id'] = $id;
        $data['vendor_id'] = $project->vendor_id;
        $data['types'] = ProjectType::where([['user_project_types.project_id', $id], ['user_project_types.user_id', $userId]])
            // ->join('user_project_type_contents', 'user_project_types.id', 'user_project_type_contents.project_type_id')
            ->where('user_project_types.language_id', $language->id)
            ->select('user_project_types.*')
            ->paginate(10);
        return view('user.realestate_management.project-management.type.index', $data);
    }

    public function store(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $languages  = Language::where('user_id', $userId)->get();
        foreach ($languages as $language) {
            $rules[$language->code . '_name'] = 'required|max:255';
            $rules[$language->code . '_total_unit'] = 'required|numeric';
            $rules[$language->code . '_min_price'] = 'required|numeric';
            $rules[$language->code . '_min_area'] = 'required|numeric';

            $messages[$language->code . '_name.required'] = "The name field is required for " . $language->name . " language";
            $messages[$language->code . '_total_unit.required'] = "The total unit field is required for " . $language->name . " language";
            $messages[$language->code . '_min_price.required'] = "The min price field is required for " . $language->name . " language";
            $messages[$language->code . '_min_area.required'] = "The min area field is required for " . $language->name . " language";
        }


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        try {


            foreach ($languages as $language) {

                $requestData = [
                    'project_id' => $request->project_id,
                    'language_id' => $language->id,
                    'title' =>  $request[$language->code . '_name'],
                    'min_area' => $request[$language->code . '_min_area'],
                    'max_area' => $request[$language->code . '_max_area'],
                    'min_price' => $request[$language->code . '_min_price'],
                    'max_price' => $request[$language->code . '_max_price'],
                    'unit' => $request[$language->code . '_total_unit'],
                ];
                ProjectType::storeProjectType($userId, $requestData);
            }
        } catch (\Exception $e) {


            Session::flash('warning', 'Something went wrong!');
            return Response::json(['status' => 'success'], 200);
        }
        Session::flash('success', 'New Property Type successfully!');

        return Response::json(['status' => 'success'], 200);
    }


    public function update(Request $request)
    {

        $userId = Auth::guard('web')->user()->id;
        $languages  = Language::where('user_id', $userId)->get();
        foreach ($languages as $language) {
            $rules[$language->code . '_name'] = 'required|max:255';
            $rules[$language->code . '_total_unit'] = 'required|numeric';
            $rules[$language->code . '_min_price'] = 'required|numeric';
            $rules[$language->code . '_min_area'] = 'required|numeric';

            $messages[$language->code . '_name.required'] = "The name field is required for " . $language->name . " language";
            $messages[$language->code . '_total_unit.required'] = "The total unit field is required for " . $language->name . " language";
            $messages[$language->code . '_min_price.required'] = "The min price field is required for " . $language->name . " language";
            $messages[$language->code . '_min_area.required'] = "The min area field is required for " . $language->name . " language";
        }


        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }


        foreach ($languages as $language) {

            $projectType =  ProjectType::where('id', $request->type_id)->where('language_id', $language->id)->first();
            $projectType->title = $request[$language->code . '_name'];
            $projectType->unit = $request[$language->code . '_total_unit'];
            $projectType->min_area =  $request[$language->code . '_min_area'];
            $projectType->max_area =  $request[$language->code . '_max_area'];
            $projectType->min_price =  $request[$language->code . '_min_price'];
            $projectType->max_price =  $request[$language->code . '_max_price'];
            $projectType->save();
        }

        Session::flash('success', 'Project Type  Updated successfully!');

        return Response::json(['status' => 'success'], 200);
    }

    public function delete(Request $request)
    {
        try {
            $this->deleteType($request->id);
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');

            return redirect()->back();
        }

        Session::flash('success', 'Project type deleted successfully!');
        return redirect()->back();
    }

    public function deleteType($id)
    {
        $type = ProjectType::where('user_id', Auth::guard('web')->user()->id)->find($id);
        $type->delete();
        return;
    }

    public function bulkDelete(Request $request)
    {
        $propertyIds = $request->ids;
        try {
            foreach ($propertyIds as $id) {
                $this->deleteType($id);
            }
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');

            return redirect()->back();
        }
        Session::flash('success', 'Project type deleted successfully!');
        return response()->json([ 'success'], 200);
    }
}
