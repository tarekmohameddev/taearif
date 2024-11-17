<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\WorkProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Purifier;

class WorkProcessController extends Controller
{
    public function create()
    {
        $data['langs'] = Language::where('user_id', Auth::user()->id)->get();
        return view('user.home.work_process_section.create', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'icon' => 'required',
            'text' => 'required',
            'serial_number' => 'required',
            'user_language_id' => 'required'
        ],[
            'title.required' => 'The title field is required',
            'icon.required' => 'The icon field is required',
            'text.required' => 'The content field is required',
            'serial_number.required' => 'The serial number field is required',
            'user_language_id.required' => 'The language field is required',
        ]);
        WorkProcess::create($request->except('language_id','user_id') + [
                'language_id' => $request->user_language_id,
                'user_id' => Auth::id()
        ]);
        $request->session()->flash('success', 'Work process added successfully!');
        return redirect()->back();
    }

    public function edit(Request $request, $id)
    {
        // first, get the language info from db
        $information['language'] = Language::where('code', $request->language)->where('user_id',Auth::id())->first();
        $information['workProcessInfo'] = WorkProcess::findOrFail($id);
        return view('user.home.work_process_section.edit', $information);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'icon' => 'required',
            'text' => 'required',
            'serial_number' => 'required'
        ]);
        $skill = WorkProcess::findOrFail($id);
        $input = $request->all();
        $input['text'] = Purifier::clean($request->text);
        $skill->update($input);
        $request->session()->flash('success', 'Work process updated successfully!');
        return redirect()->back();
    }

    public function delete(Request $request)
    {
        WorkProcess::findOrFail($request->work_process_id)->delete();
        $request->session()->flash('success', 'Work process deleted successfully!');
        return redirect()->back();
    }
}

