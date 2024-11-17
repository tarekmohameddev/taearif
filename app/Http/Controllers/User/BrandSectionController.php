<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Helpers\Uploader;
use App\Models\User\Brand;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class BrandSectionController extends Controller
{
    public function brandSection(Request $request)
    {

        // also, get the brand info of that language from db
        $information['brands'] = Brand::where('user_id', Auth::guard('web')->user()->id)
            ->orderBy('id', 'desc')
            ->get();

        return view('user.home.brand_section.index', $information);
    }

    public function storeBrand(Request $request)
    {
        $request->validate(
            [
                'brand_img' => 'required|mimes:jpeg,jpg,png|max:1000',
                'brand_url' => 'required',
                'serial_number' => 'required'
            ],
            [
                'brand_img.required' => 'The brand image field is required.',
                'brand_url.required' => 'The brand url field is required.',
                'serial_number.required' => 'The serial number field is required.'
            ]
        );

        if ($request->hasFile('brand_img')) {
            $request['image_name'] = Uploader::upload_picture('assets/front/img/user/brands', $request->file('brand_img'));
        }
        Brand::create($request->except('brand_img', 'user_id') + [
            'user_id' => Auth::guard('web')->user()->id,
            'brand_img' => $request->image_name
        ]);
        $request->session()->flash('success', 'New brand added successfully!');
        return 'success';
    }

    public function updateBrand(Request $request)
    {
        $brand = Brand::where('user_id', Auth::guard('web')->user()->id)->where('id', $request->brand_id)->firstOrFail();
        $rules = [
            'brand_url' => 'required',
            'serial_number' => 'required'
        ];
        $messages = [
            'brand_url.required' => 'The brand url field is required.',
            'serial_number.required' => 'The serial number field is required.'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $request['image_name'] = $brand->brand_img;
        if ($request->hasFile('brand_img')) {
            $request['image_name'] = Uploader::update_picture('assets/front/img/user/brands', $request->file('brand_img'), $brand->brand_img);
        }
        $brand->update($request->except('brand_img') + [
            'brand_img' => $request->image_name
        ]);
        $request->session()->flash('success', 'Brand info updated successfully!');
        return 'success';
    }

    public function deleteBrand(Request $request)
    {
        $brand = Brand::where('user_id', Auth::guard('web')->user()->id)->where('id', $request->brand_id)->firstOrFail();
        @unlink(public_path('assets/img/brands/' . $brand->brand_img));
        $brand->delete();
        $request->session()->flash('success', 'Brand deleted successfully!');
        return redirect()->back();
    }
}
