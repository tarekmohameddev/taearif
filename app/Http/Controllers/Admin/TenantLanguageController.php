<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class TenantLanguageController extends Controller
{
    public function defaultLanguage(){

        $data['languages'] = Language::orderby('id','asc')->take(1)->get();
        return view('admin.language.tenant.index', $data);
    }
    public function defaultLanguageEdit(){

        $data['language'] = Language::first();
        return view('admin.language.tenant.edit', $data);
    }
    public function defaultLanguageUpdate(Request $request)
    {
        $language = Language::where('id', $request->language_id)->firstOrFail();
        $rules = [
            'name' => 'required|max:255',
            'code' => [
                'required'
            ],
            'direction' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $language->name = $request->name;
        $language->code = $request->code;
        $language->rtl = $request->direction;
        $language->save();

        Session::flash('success', 'Language updated successfully!');
        return "success";
    }

    public function editKeyword()
    {
        $data['la'] = Language::orderby('id','asc')->first();
        $data['languageKeywords'] = json_decode($data['la']->keywords, true);
        return view('admin.language.tenant.edit-keyword', $data);
    }
    public function updateKeyword(Request $request, $id)
    {
        $lang = Language::findOrFail($id);
        $keywords = $request->except('_token');
        $lang->keywords = json_encode($keywords);
        $lang->save();
        return back()->with('success', 'Updated Successfully');
    }
    // public function addKeyword(Request $request, $id)
    // {
    //     $request->validate([
    //         'keyword' => 'required'
    //     ]);
    //     $language = Language::orderby('id','desc')->first();

    //     dd()

    //     // return response()->json(['status' => 'success'], 200);
    //     return 'success';
    // }
}
