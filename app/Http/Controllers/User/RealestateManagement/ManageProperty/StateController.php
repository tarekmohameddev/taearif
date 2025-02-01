<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Models\User\BasicSetting;
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

class StateController extends Controller
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


        $information['countries'] = Country::where([['user_id', $userId], ['language_id', $information['language']->id]])->orderByDesc('id')->get();
        $information['states'] = State::where([['user_id', $userId], ['language_id', $information['language']->id]])->orderBy('serial_number', 'asc')->get();


        return view('user.realestate_management.property-management.state.index', $information);
    }
    public function getState(Country $country)
    {
        $userId = Auth::guard('web')->user()->id;

        $states = State::where([['country_id', $country->id], ['user_id', $userId]])->select('id', 'name')->get();
        return Response::json($states, 200);
    }

    public function langStates($langId)
    {
        $state = State::where([['user_id', Auth::guard('web')->user()->id], ['language_id', $langId]])->select('id', 'name')->orderBy('serial_number', 'asc')->get();
        return response()->json($state);
    }
    public function getStateCities(Country $country)
    {
        $userId = Auth::guard('web')->user()->id;
        $states = State::where([['country_id', $country->id], ['user_id', $userId]])->select('id', 'name')->orderBy('serial_number', 'ASC')->get();

        $cities = City::where([['country_id', $country->id], ['user_id', $userId], ['status', 1]])->select('id', 'name')->orderBy('serial_number', 'ASC')->get();

        return Response::json(['states' => $states, 'cities' => $cities], 200);
    }
    public function store(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;

        $rules = [
            'name' => 'required',
            'language' => 'required',
            'serial_number' => 'required'
        ];

        $basicSettings = BasicSetting::where('user_id', $userId)->select('property_state_status', 'property_country_status')->first();
        if ($basicSettings->property_country_status == 1 && request()->has('country')) {
            $country = Country::where('user_id', $userId)->findOrFail($request->country);
            $countryId = $country->id;
            $rules['country'] = 'required|integer';
        } elseif ($basicSettings->property_country_status == 1 && !request()->has('country')) {
            // $country = Country::where('user_id', $userId)->findOrFail($request->country);
            // $countryId = $country->id;
            $rules['country'] = 'required|integer';
        } else {
            $countryId = null;
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }

        try {
            State::storeState($userId, $request->all(), $countryId);
        } catch (\Exception $e) {

            Session::flash('warning', $e->getMessage());
            // Session::flash('warning', 'Something went wrong!');
            return   'success';
        }
        Session::flash('success', 'State added successfully!');
        return  'success';
    }

    public function update(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;

        $rules = [
            'name' => 'required',
            'serial_number' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }

        $state = State::where('user_id', $userId)->find($request->id);
        $state->updateState($request->all());
        Session::flash('success', 'Country update successfully!');
        return  'success';
    }

    public function destroy(Request $request)
    {
        $state = State::where('user_id', Auth::guard('web')->user()->id)->find($request->id);
        $delete = true;

        $propertyContents = $state->propertyContents()->count();
        $cities = $state->cities()->count();
        if ($propertyContents > 0  || $cities >  0) {
            $delete = false;
        }

        if ($delete) {
            $state->delete();
        } else {
            return redirect()->back()->with('warning', 'You can not delete state!! A property or city included in this state.');
        }


        return redirect()->back()->with('success', 'State deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {

        $ids = $request->ids;
        DB::beginTransaction();

        try {
            foreach ($ids as $id) {;
                $state = State::where('user_id', Auth::guard('web')->user()->id)->find($id);

                $delete = true;
                $propertyContents = $state->propertyContents()->count();
                $cities = $state->cities()->count();
                if ($propertyContents > 0  || $cities >  0) {
                    $delete = false;
                }

                if ($delete) {
                    $state->delete();
                } else {
                    Session::flash('warning', 'You can not delete all state!! A property or city included in this state.');

                    return Response::json(['success'], 200);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('warning', 'You can not delete all state!!  The property or city included the state.');
            return Response::json(['success'], 200);
        }

        Session::flash('success', 'States deleted successfully!');

        return Response::json(['success'], 200);
    }
}
