<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        // first, get the language info from db
        $information['language'] = Language::where('code', $request->language)->first();
        $information['languages'] = Language::where('user_id', Auth::guard('web')->user()->id)->get();


        $information['countries'] = Country::where('user_id', $userId)->orderBy('serial_number', 'asc')->get();


        return view('user.realestate_management.property-management.country.index', $information);
    }
    public function getCountries($langId)
    {
        $countries = Country::where([['user_id', Auth::guard('web')->user()->id], ['language_id', $langId]])->get();
        return response()->json($countries);
    }
    public function store(Request $request)
    {

        $userId = Auth::guard('web')->user()->id;
        $rules = [
            'user_language_id' => 'required|numeric',
            'serial_number' => 'required|numeric',
            'name' => 'required'
        ];
        $message = [
            'user_language_id.required' => 'The language field is required',
            'user_language_id.numeric' => 'The language field is required'
        ];

        $validator = Validator::make($request->all(), $rules, $message);
        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }

        try {
            $request['language'] = $request->user_language_id;
            Country::storeCountry($userId, $request);
            Session::flash('success', 'Country added successfully!');
            return  'success';
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');
            return  'success';
        }
    }

    public function update(Request $request)
    {

        $userId = Auth::guard('web')->user()->id;

        $rules = [
            'name' => 'required',
            'serial_number' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }

        try {
            $country = Country::where('user_id', $userId)->find($request->id);
            $country->updateCountry($request->all());
            Session::flash('success', 'Country update successfully!');
            return  'success';
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong!');
            return  'success';
        }
    }


    public function destroy(Request $request)
    {
        $country = Country::where('user_id', Auth::guard('web')->user()->id)->find($request->id);
        $delete = true;

        $properties = $country->propertyContents()->count();
        $cities = $country->cities()->count();
        $states = $country->states()->count();
        if ($properties >  0 || $cities >  0 || $states >  0) {
            $delete = false;
        }
        if ($delete) {
            $country->delete();
        } else {
            return redirect()->back()->with('warning', 'You can not delete Country!! A property, state or city included in this country.');
        }

        return redirect()->back()->with('success', 'Country deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {

        $ids = $request->ids;
        DB::beginTransaction();

        try {
            foreach ($ids as $id) {;
                $country = Country::where('user_id', Auth::guard('web')->user()->id)->find($id);
                $delete = true;

                $properties = $country->propertyContents()->count();
                $cities = $country->cities()->count();
                $states = $country->states()->count();
                if ($properties >  0 || $cities >  0 || $states >  0) {
                    $delete = false;
                }


                if ($delete) {

                    $country->delete();
                } else {
                    Session::flash('warning', 'You can not delete country!! A property,state or city included in this state.');

                    return Response::json(['success'], 200);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('warning', 'You can not delete all country!!  The property, state or city included the state.');
            return Response::json(['success'], 200);
        }

        Session::flash('success', 'Countries deleted successfully!');

        return Response::json(['success'], 200);
    }
}
