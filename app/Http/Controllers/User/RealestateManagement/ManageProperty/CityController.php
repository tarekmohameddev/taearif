<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\CityStoreRequest;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Country;
use App\Models\User\RealestateManagement\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        // first, get the language info from db 
        $information['languages'] = Language::where('user_id', Auth::guard('web')->user()->id)->get();
        if ($request->language) {
            $information['language'] =  $information['languages']->where('code', $request->language)->first();
        } else {
            $information['language'] = $information['languages']->where('is_default', 1)->first();
        }

        $information['cities'] = City::where([['user_id', $userId], ['language_id', $information['language']->id]])->orderBy('serial_number', 'asc')->get();

        return view('user.realestate_management.property-management.city.index', $information);
    }

    public function getCities(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $cities = City::where([['state_id', $request->state_id], ['user_id', $userId], ['status', 1]])->select('id', 'name')->orderBy('serial_number', 'ASC')->get();
        return Response::json(['cities' => $cities], 200);
    }

    public function store(CityStoreRequest $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $img = $request->file('image');
        $filename = null;
        if ($request->hasFile('image')) {
            $filename = UploadFile::store('assets/img/property-city/', $img);
        }

        try {
            if ($request->has('state') && $request->has('country')) {

                $state = State::where('user_id', $userId)->findOrFail($request->state);
                $stateId = $state->id;
                $countryId = $state->country->id;
            } elseif ($request->has('country') && !$request->has('state')) {

                $country = Country::findOrFail($request->country);
                $stateId = null;
                $countryId = $country->id;
            } elseif (!$request->has('country') && !$request->has('state')) {
                $stateId = null;
                $countryId = null;
            }

            City::storeCity($userId, $request->all(), $filename, $countryId, $stateId);
            Session::flash('success', 'New City added successfully!');
        } catch (\Exception $e) {

            Session::flash('warning', 'Something went wrong!');
            return 'success';
        }


        return 'success';
    }

    public function update(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;

        $rules = [
            'status' => 'required|numeric',
            'serial_number' => 'required|numeric'
        ];
        if ($request->hasFile('image')) {
            $rules['image'] = "nullable|mimes:jpg,jpeg,svg,png,webp";
        }

        $rules['name'] =
            [
                'required',
                Rule::unique('user_cities', 'name')->ignore($request->id, 'id')->where('user_id', $userId)
            ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        try {
            $city = City::find($request->id);
            $filename = $city->image;
            if ($request->hasFile('image')) {
                $filename = UploadFile::update('assets/img/property-city/', $request->file('image'), $city->image);
            }
            $city->updateCity($request->all(), $filename);

            Session::flash('success', 'Property category updated successfully!');
        } catch (\Exception $e) {

            Session::flash('warning', 'Something went wrong!');
            return   'success';
        }

        return  'success';
    }

    public function updateFeatured(Request $request)
    {
        $city = City::findOrFail($request->cityId);

        if ($request->featured == 1) {
            $city->update(['featured' => 1]);

            Session::flash('success', 'City featured successfully!');
        } else {
            $city->update(['featured' => 0]);

            Session::flash('success', 'City Unfeatured successfully!');
        }

        return redirect()->back();
    }



    public function destroy(Request $request)
    {
        $city = City::where('user_id', Auth::guard('web')->user()->id)->find($request->id);
        $delete = true;
        $propertyContents = $city->propertyContent()->count();
        if ($propertyContents >  0) {
            $delete = false;
        }

        if ($delete) {
            @unlink(public_path('assets/img/property-city/') . $city->image);
            $city->delete();
        } else {
            return redirect()->back()->with('warning', 'You can not delete city!! A property included in this city.');
        }


        return redirect()->back()->with('success', 'City deleted successfully!');
    }


    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;
        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                $city = City::where('user_id', Auth::guard('web')->user()->id)->find($id);
                $delete = true;

                $properties = $city->propertyContent()->count();
                if ($properties >  0) {
                    $delete = false;
                }

                if ($delete) {
                    @unlink(public_path('assets/img/property-city/') . $city->image);
                    $city->delete();
                } else {
                    Session::flash('warning', 'You can not delete city!! A property included in this city.');
                    return Response::json(['success'], 200);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('warning', 'You can not delete all city!!  The property included the city.');
            return Response::json(['success'], 200);
        }

        Session::flash('success', 'Cities deleted successfully!');

        return Response::json(['success'], 200);
    }
}
