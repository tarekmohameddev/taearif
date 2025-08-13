<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;


use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\User\RealestateManagement\ApiUserCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PropertyRequestController extends Controller
{
    public function create($website, Request $request, \App\Services\CategoryVisibility $visibility)
    {
        $user = getUser();
        $tenantId = $user->id;

        $userCurrentLang = session()->has('user_lang')
            ? \App\Models\User\Language::where('code', session('user_lang'))->where('user_id', $tenantId)->first()
            : null;
        if (!$userCurrentLang) {
            $userCurrentLang = \App\Models\User\Language::where('is_default', 1)->where('user_id', $tenantId)->first();
            if ($userCurrentLang) session()->put('user_lang', $userCurrentLang->code);
        }

        $cities = \App\Models\User\UserCity::whereHas('propertyContent', function ($q) use ($user) {
                $q->whereHas('property', function ($qq) use ($user) {
                    $qq->where('user_id', $user->id)->where('status', 1);
                });
            })
            ->select('id', 'name_ar', 'name_en')
            ->orderBy($userCurrentLang->code === 'ar' ? 'name_ar' : 'name_en')
            ->get();

        $availableCategories = $visibility->forTenant(
            $tenantId,
            $request,
            (bool) $user->show_even_if_empty,
            $userCurrentLang?->id
        );

        return view('user-front.realestate.property.property_requests.create', [
            'cities'              => $cities,
            'availableCategories' => $availableCategories,
            'userCurrentLang'     => $userCurrentLang,
            'website'             => $website,
        ]);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['required','integer',
                Rule::exists('api_user_categories','id')->where('is_active',1)
            ],
            'property_type' => ['required', Rule::in(['سكني','تجاري','صناعي','زراعي'])],
            'city_id'         => ['required','integer', Rule::exists('user_cities','id')],
            'neighborhood_id' => ['required','integer', Rule::exists('user_districts','id')],
            'area_from' => ['nullable','integer','min:0','lte:area_to'],
            'area_to'   => ['nullable','integer','min:0'],
            'purchase_method' => ['required', Rule::in(['كاش','تمويل بنكي'])],
            'budget_from'     => ['required','numeric','min:0','lte:budget_to'],
            'budget_to'       => ['required','numeric','min:0'],
            'seriousness'     => ['nullable', Rule::in(['مستعد فورًا','خلال شهر','خلال 3 أشهر','لاحقًا / استكشاف فقط'])],
            'purchase_goal'   => ['nullable', Rule::in(['سكن خاص','استثمار وتأجير','بناء وبيع','مشروع تجاري'])],
            'wants_similar_offers' => ['nullable','boolean'],
            'full_name'       => ['required','string','max:255'],
            'phone'           => ['required','string','max:20'],
            'contact_on_whatsapp' => ['nullable','boolean'],
            'notes'           => ['nullable','string','max:5000'],
        ]);

        $validator->after(function($v) use ($request) {
            if ($request->filled('city_id') && $request->filled('neighborhood_id')) {
                $ok = UserDistrict::where('id', $request->neighborhood_id)
                    ->where('city_id', $request->city_id)
                    ->exists();
                if (! $ok) {
                    $v->errors()->add('neighborhood_id', 'الحي لا ينتمي للمدينة المختارة.');
                }
            }
        });

        $validated = $validator->validate();
        $tenant = getUser();
        $validated['user_id'] = $tenant->id;
        $validated['region']   = UserCity::find($validated['city_id'])?->name_ar ?? '---';
        $validated['is_read']  = false;
        $validated['is_active']= true;

        $validated['wants_similar_offers'] = $request->boolean('wants_similar_offers');
        $validated['contact_on_whatsapp']  = $request->boolean('contact_on_whatsapp', true);

        UserPropertyRequest::create($validated);

        return back()->with('success','تم إرسال الطلب بنجاح، سنقوم بالتواصل معك قريبًا.');
    }


}

