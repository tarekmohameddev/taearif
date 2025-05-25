<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use Log;
use App\Models\User\Region;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\UploadFile;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use App\Http\Requests\PropertyStoreRequest;
use Http\Message\UriFactory\SlimUriFactory;
use App\Http\Requests\PropertyUpdateRequest;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertySpecification;

class PropertyController extends Controller
{
    public function settings()
    {
        $content = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->select('property_country_status', 'property_state_status')->first();
        return view('user.realestate_management.property-management.settings', compact('content'));
    }
    //update_setting
    public function update_settings(Request $request)
    {
        $status = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        $status->property_country_status = $request->property_country_status;
        $status->property_state_status = $request->property_state_status;
        $status->save();
        Session::flash('success', 'Property Settings Updated Successfully!');
        return back();
    }

    public function type()
    {
        $userId = Auth::guard('web')->user()->id;
        $data['commertialCount'] = Property::where('user_id', $userId)->where('type', 'commercial')->where('status', 1)->count();
        $data['residentialCount'] = Property::where('user_id', $userId)->where('type', 'residential')->where('status', 1)->count();
        return view('user.realestate_management.property-management.type', $data);
    }
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;

        if ($request->has('language')) {
            $lang = Language::where([
                ['code', $request->language],
                ['user_id', $userId]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            $lang = Language::where([
                ['is_default', 1],
                ['user_id', $userId]
            ])
                ->first();
            Session::put('currentLangCode', $lang->code);
        }


        $data['language'] = $lang;

        $language_id = 1;
        $title = null;


        if (request()->filled('title')) {
            $title = $request->title;
        }

        $data['properties'] = Property::where('user_properties.user_id', $userId)
            ->join('user_property_contents', 'user_properties.id', 'user_property_contents.property_id')
            ->where('user_property_contents.language_id', $language_id)

            ->when($title, function ($query) use ($title) {
                return $query->where('user_property_contents.title', 'LIKE', '%' . $title . '%');
            })
            ->when($request->filled('city_id'), function($query) use ($request) {
                return $query->where('user_property_contents.city_id', $request->city_id);
            })
            ->join('user_cities', 'user_property_contents.city_id', 'user_cities.id')
            ->select('user_properties.id', 'user_properties.type', 'user_properties.featured', 'user_properties.status',  'user_property_contents.title', 'user_property_contents.slug', 'user_cities.name as cityName')
            ->orderBy('user_properties.id', 'desc')
            ->paginate(10);

        return view('user.realestate_management.property-management.index', $data);
    }

    public function create(Request $request)
    {

        if (!request()->has('type') || !in_array(request()->type, ['commercial', 'residential'])) {
            abort(404);
        }

        // $user  = Auth::guard('web')->user();
        $user = auth()->user();
        // $information = [];
        // $information['lang'] = Language::where('code', $request->language)->where('user_id', $user->id)->first();
        // $information['languages'] = Language::where('user_id', $user->id)->get();
        // $information['propertySettings'] = BasicSetting::select('property_state_status', 'property_country_status')->first();
        // $information['regions'] = Region::with('governorates')->get();
        $information = [
            'lang' => Language::where('code', $request->language)->where('user_id', $user->id)->first(),
            'languages' => Language::where('user_id', $user->id)->get(),
            'propertySettings' => BasicSetting::select('property_state_status', 'property_country_status')->first(),
            'regions' => \App\Models\User\Region::with('governorates')->get(), // Fetch regions with governorates
        ];

        // dd($information['regions']);
        return view('user.realestate_management.property-management.create', $information);
    }

    public function updateFeatured(Request $request)
    {
        $property = Property::findOrFail($request->requestId);

        if ($request->featured == 1) {
            $property->update(['featured' => 1]);
            Session::flash('success', 'Property featured successfully!');
        } else {
            $property->update(['featured' => 0]);
            Session::flash('success', 'Property remove from featured!');
        }

        return redirect()->back();
    }

    //imagedbrmv
    public function imagedbrmv(Request $request)
    {

        $pi = PropertySliderImg::findOrFail($request->fileid);
        $imageCount = PropertySliderImg::where('property_id', $pi->property_id)->get()->count();
        if ($imageCount > 1) {
            @unlink(public_path('assets/img/property/slider-images/') . $pi->image);
            $pi->delete();
            return $pi->id;
        } else {
            return 'false';
        }
    }

    public function videoImgrmv(Request $request)
    {
        $pi = Property::select('video_image', 'id')->findOrFail($request->fileid);

        if (!empty($pi->video_image)) {
            @unlink(public_path('assets/img/property/video/') . $pi->video_image);
            $pi->video_image = null;
            $pi->save();
            return 'success';
        } else {
            return 'false';
        }
    }

    public function floorImgrmv(Request $request)
    {
        $pi = Property::select('floor_planning_image', 'id')->findOrFail($request->fileid);

        if (!empty($pi->floor_planning_image)) {
            @unlink(public_path('assets/img/property/plannings/') . $pi->floor_planning_image);
            $pi->floor_planning_image = null;
            $pi->save();
            return 'success';
        } else {
            return 'false';
        }
    }
    public function store(PropertyStoreRequest $request)
    {

      //  dd($request->all());
      Log::info('PropertyStoreRequest');
      \Log::info($request->all());


        DB::transaction(function () use ($request) {
            // $user = Auth::guard('web')->user();
            $user = auth()->user();
            $featuredImgURL = $request->featured_image;
            if (request()->hasFile('featured_image')) {
                $featuredImgName = UploadFile::store('assets/img/property/featureds', $featuredImgURL);
            }

            $languages = Language::where('user_id', $user->id)->get();

            $floorPlanningImage = null;
            $videoImage = null;
            if (request()->hasFile('floor_planning_image')) {
                $floorPlanningImage = UploadFile::store('assets/img/property/plannings', $request->floor_planning_image);
            }

            if ($request->hasFile('video_image')) {
                $videoImage = UploadFile::store('assets/img/property/video/', $request->video_image);
            }

            $property = Property::storeProperty($user->id, $request->all(), $featuredImgName, $floorPlanningImage, $videoImage);

            if ($request->has('slider_images')) {
                foreach ($request->file('slider_images') as $key => $image) {
                    $imageName = UploadFile::store('assets/img/property/slider-images/', $image);
                    PropertySliderImg::storeSliderImage($user->id, $property->id, $imageName);
                }
            }

            foreach ($languages as $language) {

                if ($request->has($language->code . '_amenities')) {
                    foreach ($request[$language->code . '_amenities'] as $amenity) {

                        PropertyAmenity::sotreAmenity($user->id, $property->id, $amenity);
                    }
                }

                $contentRequest = [
                    'language_id' => $language->id,
                    'category_id' => $request[$language->code . '_category_id'],
                    'country_id' => $request[$language->code . '_country_id'],
                    'state_id' => $request[$language->code . '_state_id'],
                    'city_id' => $request[$language->code . '_city_id'],
                    'title' => $request[$language->code . '_title'],
                    'slug' => $request[$language->code . '_title'],
                    'address' => $request[$language->code . '_address'],
                    'description' => $request[$language->code . '_description'],
                    'meta_keyword' => $request[$language->code . '_meta_keyword'],
                    'meta_description' => $request[$language->code . '_meta_description'],
                ];

                PropertyContent::storePropertyContent($user->id, $property->id, $contentRequest);

                $label_datas = $request->input($language->code . '_label', []);
                // $label_datas = $request[$language->code . '_label'];
                foreach ($label_datas as $key => $data) { // line 267
                    if (!empty($request[$language->code . '_value'][$key])) {

                        $specificationData = [
                            'language_id' => $language->id,
                            'key' => $key,
                            'label' => $data,
                            'value' => $request[$language->code . '_value'][$key],
                        ];
                        PropertySpecification::storeSpecification($user->id, $property->id, $specificationData);
                    }
                }
            }
        });
        Session::flash('success', 'New Property added successfully!');

        return Response::json(['status' => 'success'], 200);
    }

    public function updateStatus(Request $request)
    {
        $property = Property::findOrFail($request->propertyId);

        if ($request->status == 1) {
            $property->update(['status' => 1]);

            Session::flash('success', 'Property Active successfully!');
        } else {
            $property->update(['status' => 0]);

            Session::flash('success', 'Property Deactive successfully!');
        }

        return redirect()->back();
    }
    public function edit($id)
    {
        $userID = Auth::guard('web')->user()->id;
        $property = Property::where('user_id', $userID)->with('galleryImages')->findOrFail($id);

        $information['property'] = $property;
        $information['propertyContents'] = $property->contents()->get();
        $information['galleryImages'] = $property->galleryImages;

        $information['languages'] = Language::where('user_id', $userID)->get();

        $information['propertyAmenities'] = PropertyAmenity::where([['user_id', $userID], ['property_id', $property->id]])->get();


        $information['propertySettings'] = BasicSetting::where('user_id', $userID)->select('property_state_status', 'property_country_status')->first();
        $information['specifications'] = PropertySpecification::where('user_id', $userID)->where('property_id', $property->id)->get();

        return view('user.realestate_management.property-management.edit', $information);
    }

    public function update(PropertyUpdateRequest $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $userID = Auth::guard('web')->user()->id;

            $languages = Language::where('user_id', $userID)->get();
            $property = Property::where('user_id', $userID)->findOrFail($request->property_id);

            $featuredImgName = $property->featured_image;
            $floorPlanningImage = $property->floor_planning_image;
            $videoImage = $property->video_image;
            if ($request->hasFile('featured_image')) {
                $featuredImgName = UploadFile::update('assets/img/property/featureds/', $request->featured_image, $property->featured_image);
            }
            if ($request->hasFile('floor_planning_image')) {
                $floorPlanningImage = UploadFile::update('assets/img/property/plannings/', $request->floor_planning_image, $property->floor_planning_image);
            }
            if ($request->hasFile('video_image')) {
                $videoImage = UploadFile::update('assets/img/property/video/', $request->video_image, $property->video_image);
            }
            $requestData = $request->all();
            $requestData['featured_image'] = $featuredImgName;
            $requestData['floor_planning_image'] = $floorPlanningImage;
            $requestData['video_image'] = $videoImage;
            $property->updateProperty($requestData);

            if ($request->has('slider_images')) {
                foreach ($request->file('slider_images') as $key => $image) {
                    $imageName = UploadFile::store('assets/img/property/slider-images/', $image);
                    PropertySliderImg::storeSliderImage($userID, $property->id, $imageName);
                }
            }

            if ($request->has('amenities')) {

                $currentAmenities = $property->propertyAmenities()->pluck('amenity_id')->toArray();
                $amenitiesToDelete = array_diff($currentAmenities, $request->amenities);
                $amenitiesToAdd = array_diff($request->amenities, $currentAmenities);

                if (!empty($amenitiesToDelete)) {
                    $property->propertyAmenities()
                        ->whereIn('amenity_id', $amenitiesToDelete)
                        ->delete();
                }
                // Add new amenities
                foreach ($amenitiesToAdd as $amenity) {
                    PropertyAmenity::sotreAmenity($userID, $property->id, $amenity);
                }
            }

            $d_property_specifications = PropertySpecification::where('property_id', $request->property_id)->get();
            foreach ($d_property_specifications as $d_property_specification) {
                $d_property_specification->delete();
            }

            foreach ($languages as $language) {
                $propertyContent =  PropertyContent::where('property_id', $request->property_id)->where('language_id', $language->id)->first();
                if (empty($propertyContent)) {
                    $propertyContent = new PropertyContent();
                }
                $propertyContent->user_id = $userID;
                $propertyContent->language_id = $language->id;
                $propertyContent->property_id = $property->id;

                $propertyContent->category_id = $request->input($language->code . '_category_id', null);
                $propertyContent->country_id = $request->input($language->code . '_country_id', null);
                $propertyContent->state_id = $request->input($language->code . '_state_id', null);
                $propertyContent->city_id = $request->input($language->code . '_city_id', null);
                $propertyContent->title = $request->input($language->code . '_title', null);
                $propertyContent->slug = $request->input($language->code . '_title', null);
                $propertyContent->address = $request->input($language->code . '_address', null);
                $propertyContent->description = $request->input($language->code . '_description', null);
                $propertyContent->meta_keyword = $request->input($language->code . '_meta_keyword', null);
                $propertyContent->meta_description = $request->input($language->code . '_meta_description', null);
                $propertyContent->save();

                $label_datas = $request->input($language->code . '_label', []);
                $value_datas = $request->input($language->code . '_value', []);
                // $label_datas = $request[$language->code . '_label'];
                foreach ($label_datas as $key => $data) {
                    if (!empty($request[$language->code . '_value'][$key])) {
                        $property_specification = PropertySpecification::where([['property_id', $property->id], ['key', $key]])->first();
                        if (is_null($property_specification)) {

                            $specificationData = [
                                'language_id' => $language->id,
                                'key' => $key,
                                'label' => $data,
                                'value' => $request[$language->code . '_value'][$key],
                            ];
                            PropertySpecification::storeSpecification($userID, $property->id, $specificationData);
                        }
                    }
                }
            }
        });
        Session::flash('success', 'Property Updated successfully!');

        return Response::json(['status' => 'success'], 200);
    }



    public function specificationDelete(Request $request)
    {
        $d_project_specification = PropertySpecification::where('user_id', Auth::guard('web')->user()->id)->find($request->spacificationId);
        $d_project_specification->delete();

        return Response::json(['status' => 'success'], 200);
    }

    public function delete(Request $request)
    {

        try {
            $this->deleteProperty($request->property_id);
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');

            return redirect()->back();
        }

        Session::flash('success', 'Property deleted successfully!');
        return redirect()->back();
    }

    public function deleteProperty($id)
    {

        $property = Property::find($id);

        if (!is_null($property->featured_image)) {
            @unlink(public_path('assets/img/property/featureds/' . $property->featured_image));
        }

        if (!is_null($property->floor_planning_image)) {
            @unlink(public_path('assets/img/property/plannings/' . $property->floor_planning_image));
        }
        if (!is_null($property->video_image)) {
            @unlink(public_path('assets/img/property/video/' . $property->video_image));
        }
        $property->proertyAmenities()->delete();
        $propertySliderImages  = $property->galleryImages()->get();
        foreach ($propertySliderImages  as  $image) {

            @unlink(public_path('assets/img/property/slider-images/' . $image->image));
            $image->delete();
        }


        $specifications = $property->specifications()->get();
        foreach ($specifications as $specification) {
            $specification->delete();
        }

        $propertyContents = $property->contents()->get();

        foreach ($propertyContents as $content) {

            $content->delete();
        }
        // delete wishlists
        $property->wishlists()->delete();
        $property->delete();


        return;
    }

    public function bulkDelete(Request $request)
    {

        $propertyIds = $request->ids;
        try {
            foreach ($propertyIds as $id) {
                $this->deleteProperty($id);
            }
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');

            return redirect()->back();
        }
        Session::flash('success', 'Properties deleted successfully!');
        return response()->json(['status' => 'success'], 200);
    }
}
