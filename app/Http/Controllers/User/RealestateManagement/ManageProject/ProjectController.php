<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProject;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\ProjectStoreRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Models\User\BasicSetting;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\ProjectFloorplanImg;
use App\Models\User\RealestateManagement\ProjectGalleryImg;
use App\Models\User\RealestateManagement\ProjectSpecification;
use App\Models\User\RealestateManagement\PropertySpecification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class ProjectController extends Controller
{

    // public function settings()
    // {
    //     $content = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->select('project_approval_status')->first();
    //     return view('user.realestate_management.project-management.settings', compact('content'));
    // }
    // //  update_setting
    // public function updateSettings(Request $request)
    // {
    //     $status = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();

    //     $status->project_approval_status = $request->project_approval_status;
    //     $status->save();
    //     Session::flash('success', 'Project Settings Updated Successfully!');
    //     return back();
    // }

    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $data['langs'] = Language::where('user_id', $userId)->get();

        if ($request->has('language')) {
            $language = Language::where('user_id', $userId)->where('code', $request->language)->first();
        } else {

            $language = Language::where('user_id', $userId)->where('is_default', 1)->first();
        }

        $data['language'] = $language;
        $language_id = $language->id;

        $title = null;


        $title = null;

        if (request()->filled('title')) {
            $title = $request->title;
        }

        $data['projects'] = Project::join('user_project_contents', 'user_projects.id', 'user_project_contents.project_id')
            ->where('user_project_contents.language_id', $language_id)
            ->when($title, function ($query) use ($title) {
                return $query->where('user_project_contents.title', 'LIKE', '%' . $title . '%');
            })
            ->select('user_projects.*', 'user_project_contents.title')
            ->orderBy('user_projects.id', 'desc')
            ->paginate(10);


        return view('user.realestate_management.project-management.index', $data);
    }

    public function create(Request $request)
    {
        $information = [];
        $userId = Auth::guard('web')->user()->id;
        $languages = Language::where('user_id', $userId)->get();
        $information['languages'] = $languages;
        return view('user.realestate_management.project-management.create', $information);
    }

    // public function galleryImagesStore(Request $request)
    // {
    //     $userId = Auth::guard('web')->user()->id;
    //     $img = $request->file('file');
    //     $allowedExts = array('jpg', 'png', 'jpeg', 'svg', 'webp');
    //     $rules = [
    //         'file' => [
    //             function ($attribute, $value, $fail) use ($img, $allowedExts) {
    //                 $ext = $img->getClientOriginalExtension();
    //                 if (!in_array($ext, $allowedExts)) {
    //                     return $fail("Only png, jpg, jpeg images are allowed");
    //                 }
    //             },
    //         ]
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         $validator->getMessageBag()->add('error', 'true');
    //         return response()->json($validator->errors());
    //     }
    //     $imageName = UploadFile::store('assets/img/project/gallery-images/', $request->file('file'));

    //     $pi = new ProjectGalleryImage();
    //     if (!empty($request->project_id)) {
    //         $pi->project_id = $request->project_id;
    //     }
    //     $pi->user_id = $userId;
    //     $pi->image = $imageName;
    //     $pi->save();
    //     return response()->json(['status' => 'success', 'file_id' => $pi->id]);
    // }

    // public function galleryImageRmv(Request $request)
    // {
    //     $pi = ProjectGalleryImage::findOrFail($request->fileid);
    //     $imageCount = ProjectGalleryImage::where('project_id', $pi->project_id)->get()->count();
    //     if ($imageCount > 1) {
    //         @unlink(public_path('assets/img/project/gallery-images/') . $pi->image);
    //         $pi->delete();
    //         return $pi->id;
    //     } else {
    //         return 'false';
    //     }
    // }

    //imagedbrmv
    public function galleryImageDbrmv(Request $request)
    {
        $pi = ProjectGalleryImg::findOrFail($request->fileid);
        $imageCount = ProjectGalleryImg::where('project_id', $pi->project_id)->get()->count();
        if ($imageCount > 1) {
            @unlink(public_path('assets/img/project/gallery-images/') . $pi->image);
            $pi->delete();
            return $pi->id;
        } else {
            return 'false';
        }
    }


    // public function floorPlanImagesStore(Request $request)
    // {
    //     $userId = Auth::guard('web')->user()->id;
    //     $img = $request->file('file');
    //     $allowedExts = array('jpg', 'png', 'jpeg', 'svg', 'webp');
    //     $rules = [
    //         'file' => [
    //             function ($attribute, $value, $fail) use ($img, $allowedExts) {
    //                 $ext = $img->getClientOriginalExtension();
    //                 if (!in_array($ext, $allowedExts)) {
    //                     return $fail("Only png, jpg, jpeg images are allowed");
    //                 }
    //             },
    //         ]
    //     ];
    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         $validator->getMessageBag()->add('error', 'true');
    //         return response()->json($validator->errors());
    //     }
    //     $imageName = UploadFile::store('assets/img/project/floor-paln-images/', $request->file('file'));

    //     $pi = new ProjectFloorplanImage();
    //     if (!empty($request->project_id)) {
    //         $pi->project_id = $request->project_id;
    //     }
    //     $pi->user_id = $userId;
    //     $pi->image = $imageName;
    //     $pi->save();
    //     return response()->json(['status' => 'success', 'file_id' => $pi->id]);
    // }
    // public function floorPlanImageRmv(Request $request)
    // {
    //     $pi = ProjectFloorplanImage::findOrFail($request->fileid);
    //     $imageCount = ProjectFloorplanImage::where('project_id', $pi->project_id)->get()->count();
    //     if ($imageCount > 1) {
    //         @unlink(public_path('assets/img/project/floor-paln-images/') . $pi->image);
    //         $pi->delete();
    //         return $pi->id;
    //     } else {
    //         return 'false';
    //     }
    // }


    //imagedbrmv
    public function floorPlanImageDbrmv(Request $request)
    {
        $pi = ProjectFloorplanImg::findOrFail($request->fileid);
        $imageCount = ProjectFloorplanImg::where('project_id', $pi->project_id)->get()->count();
        if ($imageCount > 1) {
            @unlink(public_path('assets/img/project/floor-paln-images/') . $pi->image);
            $pi->delete();
            return $pi->id;
        } else {
            return 'false';
        }
    }


    public function store(ProjectStoreRequest $request)
    {

        DB::transaction(function () use ($request) {
            $userId = Auth::guard('web')->user()->id;
            $featuredImgURL = $request->featured_image;
            if (request()->hasFile('featured_image')) {
                $featuredImgName = UploadFile::store('assets/img/project/featured/', $featuredImgURL);
            }

            $languages = Language::where('user_id', $userId)->get();
            $requestData = $request->all();
            $requestData['featured_image'] = $featuredImgName;
            $project = Project::storeProject($userId, $requestData);

            if ($request->has('gallery_images')) {
                foreach ($request->file('gallery_images') as $key => $image) {
                    $imageName = UploadFile::store('assets/img/project/gallery-images/', $image);
                    ProjectGalleryImg::storeGalleryImage($userId, $project->id, $imageName);
                }
            }

            if ($request->has('floor_plan_images')) {
                foreach ($request->file('floor_plan_images') as $key => $image) {
                    $imageName = UploadFile::store('assets/img/project/floor-paln-images/', $image);
                    ProjectFloorplanImg::storeFloorplanImage($userId, $project->id, $imageName);
                }
            }

            foreach ($languages as $language) {
                $requstData = [
                    'project_id' => $project->id,
                    'language_id' => $language->id,
                    'title' => $request[$language->code . '_title'],
                    'address' => $request[$language->code . '_address'],
                    'description' => $request[$language->code . '_description'],
                    'meta_keyword' => $request[$language->code . '_meta_keyword'],
                    'meta_description' => $request[$language->code . '_meta_description'],
                ];
                ProjectContent::storeProjectContent($userId,  $requstData);


                $label_datas = $request[$language->code . '_label'] ?? [];
              
                foreach ($label_datas as $key => $data) {
                    if (!empty($request[$language->code . '_value'][$key])) {

                        $specificationData = [
                            'language_id' => $language->id,
                            'project_id' => $project->id,
                            'key' => $key,
                            'label' => $data,
                            'value' => $request[$language->code . '_value'][$key],
                        ];
                        ProjectSpecification::storeSpecification($userId,  $specificationData);
                    }
                }
            }
        });
        Session::flash('success', 'New Property added successfully!');

        return Response::json(['status' => 'success'], 200);
    }

    public function updateFeatured(Request $request)
    {
        $property = Project::findOrFail($request->projectId);

        if ($request->featured == 1) {
            $property->update(['featured' => 1]);

            Session::flash('success', 'Project featured successfully!');
        } else {
            $property->update(['featured' => 0]);

            Session::flash('success', 'Project Unfeatured successfully!');
        }

        return redirect()->back();
    }

    public function updateStatus(Request $request)
    {
        $project = Project::findOrFail($request->projectId);
        $project->update(['complete_status' => $request->status]);

        Session::flash('success', 'Successfully chaged project status!');
        return redirect()->back();
    }

    // public function approveStatus(Request $request)
    // {
    //     $property = Project::findOrFail($request->project);

    //     $property->update(['approve_status' => $request->approve_status]);

    //     Session::flash('success', 'Successfully change approval status!');

    //     return redirect()->back();
    // }

    public function edit($id)
    {
        $userId = Auth::guard('web')->user()->id;
        $project = Project::where('user_id', $userId)->findOrFail($id);
        $information['project'] = $project;
        $information['projectContents'] = ProjectContent::where('project_id', $project->id)->get();
        $information['gallery_images'] = $project->galleryImages;
        $information['floor_plan_images'] = $project->floorplanImages;
        $information['languages'] = Language::where('user_id', $userId)->get();
        $information['specifications'] = ProjectSpecification::where('user_id', $userId)->where('project_id', $project->id)->get();

        return view('user.realestate_management.project-management.edit', $information);
    }


    public function update(ProjectUpdateRequest $request, $id)
    {

        $userId = Auth::guard('web')->user()->id;
        $languages = Language::where('user_id', $userId)->get();

        $project = Project::findOrFail($request->project_id);


        $featuredImgName = $project->featured_image;


        if ($request->hasFile('featured_image')) {
            $featuredImgName = UploadFile::update('assets/img/project/featured/', $request->featured_image, $project->featured_image);
        }

        $requestData = $request->all();
        $requestData['featured_image'] = $featuredImgName;
        $project->updateProject($requestData);

        if ($request->has('gallery_images')) {
            foreach ($request->file('gallery_images') as $key => $image) {
                $imageName = UploadFile::store('assets/img/project/gallery-images/', $image);
                ProjectGalleryImg::storeGalleryImage($userId, $project->id, $imageName);
            }
        }

        if ($request->has('floor_plan_images')) {
            foreach ($request->file('floor_plan_images') as $key => $image) {
                $imageName = UploadFile::store('assets/img/project/floor-paln-images/', $image);
                ProjectFloorplanImg::storeFloorplanImage($userId, $project->id, $imageName);
            }
        }

        $d_project_specifications = ProjectSpecification::where('project_id', $request->project_id)->get();
        foreach ($d_project_specifications as $d_project_specification) {
            $d_project_specification->delete();
        }

        foreach ($languages as $language) {
            $projectContent =  ProjectContent::where('project_id', $request->project_id)->where('language_id', $language->id)->first();
            if (empty($projectContent)) {
                $projectContent = new ProjectContent();
            }
            $projectContent->language_id = $language->id;
            $projectContent->project_id = $project->id;
            $projectContent->title = $request[$language->code . '_title'];
            $projectContent->slug = $request[$language->code . '_title'];

            $projectContent->address = $request[$language->code . '_address'];
            $projectContent->description = $request[$language->code . '_description'];
            $projectContent->meta_keyword = $request[$language->code . '_meta_keyword'];
            $projectContent->meta_description = $request[$language->code . '_meta_description'];
            $projectContent->save();

            $label_datas = $request[$language->code . '_label'];
            foreach ($label_datas as $key => $data) {
                if (!empty($request[$language->code . '_value'][$key])) {
                    $project_specification = ProjectSpecification::where([['project_id', $project->id], ['key', $key]])->first();

                    if (is_null($project_specification)) {

                        $specificationData = [
                            'language_id' => $language->id,
                            'project_id' => $project->id,
                            'key' => $key,
                            'label' => $data,
                            'value' => $request[$language->code . '_value'][$key],
                        ];
                        ProjectSpecification::storeSpecification($userId,   $specificationData);
                    }
                }
            }
        }

        Session::flash('success', 'Project Updated successfully!');

        return Response::json(['status' => 'success'], 200);
    }
    public function specificationDelete(Request $request)
    {
        $d_project_specification = ProjectSpecification::where('user_id', Auth::guard('web')->user()->id)->find($request->spacificationId);

        $d_project_specification->delete();

        return Response::json(['status' => 'success'], 200);
    }


    public function destroy(Request $request)
    {
        try {
            $this->deleteProject($request->project_id);
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');
            return redirect()->back();
        }

        Session::flash('success', 'Project deleted successfully!');
        return redirect()->back();
    }

    public function deleteProject($id)
    {
        $project = Project::where('user_id', Auth::guard('web')->user()->id)->find($id);

        if (!is_null($project->featured_image)) {
            @unlink(public_path('assets/img/project/featured/' . $project->featured_image));
        }

        $propertyGalleryImages  = $project->galleryImages()->get();
        foreach ($propertyGalleryImages  as  $image) {
            @unlink(public_path('assets/img/project/gallery-images/' . $image->image));
            $image->delete();
        }

        $projectFloorplanImages  = $project->floorplanImages()->get();
        foreach ($projectFloorplanImages  as  $image) {
            @unlink(public_path('assets/img/project/floor-paln-images/' . $image->image));
            $image->delete();
        }

        $projectTypes =  $project->projectTypes()->get();
        foreach ($projectTypes as $type) {
            $type->delete();
        }


        $specifications = $project->specifications()->get();
        foreach ($specifications as $specification) {
            $specification->delete();
        }

        $projectContents = $project->contents()->get();
        foreach ($projectContents as $content) {
            $content->delete();
        }
        $project->delete();

        return;
    }

    public function bulkDestroy(Request $request)
    {
        $propertyIds = $request->ids;

        try {
            foreach ($propertyIds as $id) {
                $this->deleteProject($id);
            }
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');

            return redirect()->back();
        }

        Session::flash('success', 'Projects deleted successfully!');
        return response()->json(['success'], 200);
    }
}
