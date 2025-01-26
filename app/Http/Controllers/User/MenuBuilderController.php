<?php

namespace App\Http\Controllers\User;

use Auth;
use App\Models\UserStep;
use App\Models\User\Menu;
use App\Models\User\Page;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Controllers\Controller;

class MenuBuilderController extends Controller
{

    public function index(Request $request) {
        $lang = Language::where('code', $request->language)->where('user_id', Auth::user()->id)->firstOrFail();
        $data['lang_id'] = $lang->id;

        $data['keywords'] = json_decode($lang->keywords, true);

        // get previous menus
        $menu = Menu::where('language_id', $lang->id)->where('user_id', Auth::user()->id)->first();
        $data['prevMenu'] = '';
        if (!empty($menu)) {
            $data['prevMenu'] = $menu->menus;
        }
        $data['apages'] = Page::where('language_id', $lang->id)->where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();

        return view('user.menu_builder.index', $data);
    }

    public function update(Request $request) {
        Menu::where('language_id', $request->language_id)->where('user_id', Auth::user()->id)->delete();
        $menu = new Menu;
        $menu->language_id = $request->language_id;
        $menu->user_id = Auth::user()->id;
        $menu->menus = $request->str;
        $menu->save();

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['menu_builder' => true]
        );
        return response()->json(['status' => 'success', 'message' => 'Menu updated successfully!']);
    }
}
