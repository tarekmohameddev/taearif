<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Http\Helpers\UploadFile;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // first, get the language info from db
        $user = Auth::guard('web')->user();
        $information['language'] = Language::where('code', $request->language)->first();
        $information['languages'] = Language::query()->where('user_id', Auth::guard('web')->user()->id)->get();

        // then, get the equipment categories of that language from db

        $information['categories'] = Category::where('user_id', $user->id)->orderBy('serial_number', 'asc')->get();

        // also, get all the languages from db
        // $information['userLangs'] = Language::where('user_id', $user->id)->get();
        return view('user.realestate_management.property-management.category.index', $information);
    }

    public function store(Request $request)
    {
        $img = $request->file('image');


        $rules = [
            'type' => "required",
            'name' => "required",
            'image' => "required",
            'user_language_id' => 'required|numeric',
            'status' => 'required|numeric',
            'serial_number' => 'required|numeric'
        ];

        $message = [
            'user_language_id.required' => 'The language field is required.',
            'user_language_id.numeric' => 'The language field is required.'
        ];

        $userId = Auth::guard('web')->user()->id;

        $validator = Validator::make($request->all(), $rules, $message);


        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        if ($request->hasFile('image')) {
            $filename = UploadFile::store('assets/img/property-category/', $img);
        }

        DB::beginTransaction();
        $request['language'] = $request->user_language_id;
        try {
            Category::storeCategory($userId, $request, $filename);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('warning', 'Something went wrong!');

            return 'success';
        }
        Session::flash('success', 'New Property category added successfully!');

        return 'success';
    }
    public function updateFeatured(Request $request)
    {
        $category = Category::findOrFail($request->categoryId);

        if ($request->featured == 1) {
            $category->update(['featured' => 1]);

            Session::flash('success', 'Category featured successfully!');
        } else {
            $category->update(['featured' => 0]);

            Session::flash('success', 'Category Unfeatured successfully!');
        }

        return redirect()->back();
    }
    public function update(Request $request)
    {
        $rules = [
            'status' => 'required|numeric',
            'name' => 'required',
            // 'language' => 'required|numeric',
            'serial_number' => 'required|numeric'
        ];
        $userId = Auth::guard('web')->user()->id;

        $validator = Validator::make($request->all(), $rules);



        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()
            ], 400);
        }

        $category = Category::find($request->id);

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $filename = UploadFile::update('assets/img/property-category/', $img, $category->image);
        } else {
            $filename = $category->image;
        }
        $category->update([

            'image' => $filename,
            'status' => $request->status,
            'serial_number' => $request->serial_number
        ]);

        try {
            $category = Category::where([['id', $request->id], ['user_id', $userId]])->firstOrFail();
            $category->updateCategory($request, $filename);
            Session::flash('success', 'Property category updated successfully!');
        } catch (\Exception $e) {

            Session::flash('warning', 'Something went wrong!');
        }



        return 'success';
    }

    public function destroy(Request $request)
    {
        $category = Category::where('user_id', Auth::guard('web')->user()->id)->find($request->id);

        if ($category->properties()->count() ==  0) {
            @unlink(public_path('assets/img/property-category/') . $category->image);

            $category->delete();
        } else {
            return redirect()->back()->with('warning', 'You can not delete category!! A property included in this category.');
        }


        return redirect()->back()->with('success', 'Category deleted successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->ids;
        DB::beginTransaction();

        try {
            foreach ($ids as $id) {
                $category = Category::where('user_id', Auth::guard('web')->user()->id)->find($id);

                if ($category->properties()->count() == 0) {
                    @unlink(public_path('assets/img/property-category/') . $category->image);
                    $category->delete();
                } else {
                    Session::flash('warning', 'You can not delete all category!!  The category included in the property.');
                    return Response::json(['success'], 200);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Session::flash('warning', 'You can not delete all category!!  The category included in the property.');
            return Response::json(['status' => 'error'], 400);
        }

        Session::flash('success', 'Property categories deleted successfully!');

        return Response::json(['success'], 200);
    }
}
