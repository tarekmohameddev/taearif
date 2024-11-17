<?php

namespace App\Http\Controllers\User;

use App\Models\User\Jcategory;
use App\Models\User\Language;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Validator;
use Session;

class JcategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('language')) {
            $lang = Language::where([
                ['code', $request->language],
                ['user_id', Auth::id()]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            $lang = Language::where([
                ['is_default', 1],
                ['user_id', Auth::id()]
            ])
            ->first();
            Session::put('currentLangCode', $lang->code);
        }

        $lang_id = $lang->id;
        $data['jcategorys'] = Jcategory::where([
            ['language_id', $lang_id],
            ['user_id', Auth::id()]
        ])
         ->orderBy('id', 'DESC')
         ->paginate(10);

        return view('user.job.jcategory.index', $data);
    }

    public function edit($id)
    {
        $data['jcategory'] = Jcategory::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        return view('user.job.jcategory.edit', $data);
    }

    public function store(Request $request)
    {
        $messages = [
            'user_language_id.required' => 'The language field is required',
        ];

        $rules = [
            'user_language_id' => 'required',
            'name' => 'required|max:255',
            'status' => 'required',
            'serial_number' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $jcategory = new Jcategory;
        $jcategory->language_id = $request->user_language_id;
        $jcategory->name = $request->name;
        $jcategory->status = $request->status;
        $jcategory->serial_number = $request->serial_number;
        $jcategory->user_id = Auth::id();
        $jcategory->save();

        Session::flash('success', 'Category added successfully!');
        return "success";
    }

    public function update(Request $request)
    {
        $rules = [
            'name' => 'required|max:255',
            'status' => 'required',
            'serial_number' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $jcategory = Jcategory::where('user_id', Auth::user()->id)->where('id', $request->jcategory_id)->firstOrFail();
        $jcategory->name = $request->name;
        $jcategory->status = $request->status;
        $jcategory->serial_number = $request->serial_number;
        $jcategory->save();

        Session::flash('success', 'Category updated successfully!');
        return "success";
    }

    public function delete(Request $request)
    {
        $jcategory = Jcategory::where('user_id', Auth::user()->id)->where('id', $request->jcategory_id)->firstOrFail();
        if ($jcategory->jobs()->count() > 0) {
            Session::flash('warning', 'First, delete all the jobs under this category!');
            return back();
        }
        $jcategory->delete();

        Session::flash('success', 'Category deleted successfully!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        foreach ($ids as $id) {
            $jcategory = Jcategory::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            if ($jcategory->jobs()->count() > 0) {
                Session::flash('warning', 'First, delete all the jobs under the selected categories!');
                return "success";
            }
        }
        foreach ($ids as $id) {
            $jcategory = Jcategory::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            $jcategory->delete();
        }
        Session::flash('success', 'Job categories deleted successfully!');
        return "success";
    }
}
