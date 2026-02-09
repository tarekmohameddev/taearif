<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;


use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\UserCity;
use Illuminate\Validation\Rule;
use App\Models\User\UserDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Auth;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Services\PropertyRequestFormSettings;

class PropertyRequestController extends Controller
{
    public function create($website, Request $request, PropertyRequestFormSettings $frSettings)
    {
        $user = getUser();
        $tenantId = $user->id ?? null;

        // Arabic default أولًا، ثم الديفولت بتاع اليوزر، ثم أي لغة متاحة
        $userCurrentLang = Language::where('user_id', $tenantId)->where('code', 'ar')->first()
            ?? Language::where('user_id', $tenantId)->where('is_default', 1)->first()
            ?? Language::where('user_id', $tenantId)->first();

        if ($userCurrentLang) {
            session()->put('user_lang', $userCurrentLang->code);
            app()->setLocale($userCurrentLang->code);
        } else {
            session()->put('user_lang', 'ar');
            app()->setLocale('ar');
        }

        $cities = UserCity::query()
        ->select('id', 'name_ar', 'name_en')
        // ->orderBy(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')
        ->get();

        // (Optional) only needed if you want a preloaded list somewhere:
        $districts = UserDistrict::query()
            ->select('id', 'city_id', 'name_ar', 'name_en')
            // ->orderBy(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')
            ->get();

        // Get all categories instead of filtered ones
        $allCategories = \App\Models\User\RealestateManagement\ApiUserCategory::query()
            ->where('is_active', 1)
            ->when(
                $request->filled('type') && in_array($request->type, ['commercial', 'residential']),
                fn ($q) => $q->where('type', $request->type)
            )
            ->orderBy('name')
            ->get();
        $formSettings = $frSettings->forTenant($tenantId);

        // Set default city for specific user
        $defaultCityId = null;
        $disableCitySelection = false;
        if ($user && $user->id == 1000) {
            $defaultCityId = 3;
            $disableCitySelection = true;
        }

        // dd($cities);
        return view('user-front.realestate.property.property_requests.create', [
            'cities'              => $cities,
            'districts'           => $districts,
            'allCategories'       => $allCategories,
            'userCurrentLang'     => $userCurrentLang,
            'website'             => $website,
            'formSettings'        => $formSettings,
            'defaultCityId'       => $defaultCityId,
            'disableCitySelection' => $disableCitySelection,
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            'category_id'        => [
                'nullable', 'integer',
                Rule::exists('api_user_categories', 'id')->where('is_active', 1)
            ],
            'property_type'      => ['nullable', Rule::in(['سكني', 'تجاري', 'صناعي', 'زراعي'])],
            'city_id'            => ['nullable', 'integer'],
            'districts_id'       => ['nullable', 'integer'],
            'area_from'          => ['nullable', 'integer', 'min:0', 'lte:area_to'],
            'area_to'            => ['nullable', 'integer', 'min:0'],
            'purchase_method'    => ['nullable', Rule::in(['كاش', 'تمويل بنكي'])],
            'budget_from'        => ['nullable', 'numeric', 'min:0', 'lte:budget_to'],
            'budget_to'          => ['nullable', 'numeric', 'min:0'],
            'seriousness'        => ['nullable', Rule::in(['مستعد فورًا', 'خلال شهر', 'خلال 3 أشهر', 'لاحقًا / استكشاف فقط'])],
            'purchase_goal'      => ['nullable', Rule::in(['سكن خاص', 'استثمار وتأجير', 'بناء وبيع', 'مشروع تجاري'])],
            'wants_similar_offers' => ['nullable', 'boolean'],
            'full_name'          => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:20'],
            'contact_on_whatsapp' => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:5000'],
        ];


        $messages = [
            'required' => 'حقل :attribute مطلوب.',
            'integer'  => 'حقل :attribute يجب أن يكون رقمًا صحيحًا.',
            'numeric'  => 'حقل :attribute يجب أن يكون رقمًا.',
            'min'      => 'قيمة :attribute يجب ألا تقل عن :min.',
            'max'      => 'قيمة :attribute يجب ألا تزيد عن :max.',
            'in'       => 'القيمة المختارة لـ :attribute غير صحيحة.',
            'lte'      => ':attribute يجب أن يكون أقل من أو يساوي :value.',
            'exists'   => ':attribute غير موجود أو غير متاح.',
        ];
        // change neighborhood_id to districts_id
        $attributes = [
            'full_name'            => 'الاسم الكامل',
            'phone'                => 'رقم الجوال',
            'category_id'          => 'نوع العقار',
            'property_type'        => 'تصنيف العقار',
            'city_id'              => 'المدينة',
            'districts_id'         => 'الحي',
            'area_from'            => 'المساحة من',
            'area_to'              => 'المساحة إلى',
            'purchase_method'      => 'طريقة الشراء',
            'budget_from'          => 'الميزانية من',
            'budget_to'            => 'الميزانية إلى',
            'seriousness'          => 'مدى الجدية',
            'purchase_goal'        => 'هدف الشراء',
            'wants_similar_offers' => 'استقبال عروض مشابهة',
            'contact_on_whatsapp'  => 'التواصل عبر واتساب',
            'notes'                => 'الملاحظات',
        ];

        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        $validator->after(function ($v) use ($request) {
            if ($request->filled('city_id') && $request->filled('districts_id')) {
                $ok = \App\Models\User\UserDistrict::where('id', $request->districts_id)
                    ->where('city_id', $request->city_id)
                    ->exists();
                if (!$ok) {
                    $v->errors()->add('districts_id', 'الحي لا ينتمي للمدينة المختارة.');
                }
            }
        });

        $validated = $validator->validate();
        $tenant = getUser();
        $validated['user_id']   = $tenant->id;

        // Derive region from city_id if provided
        if ($request->filled('city_id')) {
            $city = \App\Models\User\UserCity::with('region')->find($request->city_id);
            $validated['region'] = $city && $city->region ?
                (app()->getLocale() === 'ar' ? $city->region->name_ar : $city->region->name_en) :
                'الرياض'; // fallback to default
        } else {
            $validated['region'] = 'الرياض'; // default region
        }

        $validated['is_read']   = false;
        $validated['is_active'] = true;

        $validated['wants_similar_offers'] = $request->has('wants_similar_offers') ? ($request->boolean('wants_similar_offers') ? 1 : 0) : null;

        $validated['contact_on_whatsapp']  = $request->boolean('contact_on_whatsapp', true);

        $validated['source'] = 'manual';

        UserPropertyRequest::create($validated);

        return back()->with('success', 'تم إرسال الطلب بنجاح، سنقوم بالتواصل معك قريبًا.');
    }
}
