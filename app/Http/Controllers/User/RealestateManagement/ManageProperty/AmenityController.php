<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class AmenityController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;

        $information['language'] = Language::where('code', $request->language)->first();
        $information['languages'] = Language::where('user_id', Auth::guard('web')->user()->id)->get();
        // first, get the language info from db
        // $language = Language::where([['code', $request->language], ['user_id', $userId]])->firstOrFail();
        // $information['language'] = $language;

        // then, get the equipment categories of that language from db
        $information['amenities'] = Amenity::where('user_id', $userId)->orderBy('serial_number', 'ASC')->get();

        // also, get all the languages from db
        // $information['languages'] = Language::where('user_id', $userId)->get();

        return view('user.realestate_management.property-management.amenity.index', $information);
    }

    public function store(Request $request)
    {
        $rules = [
            'icon' => 'required',
            'user_language_id' => 'required|numeric',
            'status' => 'required|numeric',
            'serial_number' => 'required|numeric'
        ];

        $userId = Auth::guard('web')->user()->id;
        $message = [
            'user_language_id.required' => 'The language field is required',
            'user_language_id.numeric' => 'The language field is required'
        ];


        $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        try {
            $request['language'] = $request->user_language_id;
            Amenity::storeAmenity($userId, $request);
            Session::flash('success', 'New Amenity added successfully!');
            return 'success';
        } catch (\Exception $e) {

            Session::flash('warning', 'Something went wrong!');
            return 'success';
        }
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => 'required',
            'status' => 'required|numeric',
            'serial_number' => 'required|numeric'
        ];

        $userId = Auth::guard('web')->user()->id;

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        try {
            $amenity =  Amenity::where('user_id', $userId)->findOrFail($request->id);
            $amenity->updateAmenity($request->all());
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');
            return 'success';
        }
        Session::flash('success', 'Amenity updated successfully!');

        return 'success';
    }

    public function destroy(Request $request)
    {
        $amenity = Amenity::where('user_id', Auth::guard('web')->user()->id)->find($request->id);
        $propertyAmenities = $amenity->propertyAmenities()->count();
        if ($propertyAmenities == 0) {
            $amenity->delete();
        } else {
            return redirect()->back()->with('warning', 'You can not delete this amenity!! A property included in this amenity.');
        }

        return redirect()->back()->with('success', 'Amenity deleted successfully!');
    }


    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;

        foreach ($ids as $id) {
            $amenity = Amenity::where('user_id', Auth::guard('web')->user()->id)->find($id);
            $propertyAmenities = $amenity->propertyAmenities()->count();
            if ($propertyAmenities == 0) {
                $amenity->delete();
            } else {
                Session::flash('warning', 'You can not delete all amenity!! A property included in this amenity.');
                return Response::json(['success'], 200);
            }
        }
        Session::flash('success', 'All amenities delete successfully!');

        return Response::json(['success'], 200);
    }
}
