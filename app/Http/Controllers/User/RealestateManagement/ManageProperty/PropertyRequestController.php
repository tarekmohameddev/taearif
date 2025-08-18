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
use App\Services\CategoryVisibility;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\UserPropertyRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Services\PropertyRequestFormSettings;

class PropertyRequestController extends Controller
{
    public function create($website, Request $request, CategoryVisibility $visibility, PropertyRequestFormSettings $frSettings)
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

        // $districts = UserDistrict::whereHas('propertyContent', function ($q) use ($user) {
        //     // propertyContent مربوط بـ state_id على مستوى الحي
        //     $q->whereHas('property', function ($qq) use ($user) {
        //         $qq->where('user_id', $user->id)->where('status', 1);
        //     });
        // })
        // ->select('id', 'city_name_ar', 'name_en', 'city_id', 'city_name_ar', 'city_name_en')
        // ->orderBy(app()->getLocale() === 'ar' ? 'city_name_ar' : 'city_name_en')
        // ->get();

        // ✅ All cities from user_districts (distinct)
        $cities = UserDistrict::query()
            ->select('city_id', 'city_name_ar', 'city_name_en')
            ->groupBy('city_id', 'city_name_ar', 'city_name_en')
            ->orderBy(app()->getLocale() === 'ar' ? 'city_name_ar' : 'city_name_en')
            ->get();

        // (Optional) only needed if you want a preloaded list somewhere:
        $districts = UserDistrict::query()
            ->select('id', 'city_id', 'name_ar', 'name_en')
            ->orderBy(app()->getLocale() === 'ar' ? 'name_ar' : 'name_en')
            ->get();

        $availableCategories = $visibility->forTenant(
            $tenantId,
            $request,
            (bool) $user->show_even_if_empty,
            $userCurrentLang?->id
        );
        $formSettings = $frSettings->forTenant($tenantId);

        // dd($cities);
        return view('user-front.realestate.property.property_requests.create', [
            'cities'              => $cities,
            'districts'           => $districts,
            'availableCategories' => $availableCategories,
            'userCurrentLang'     => $userCurrentLang,
            'website'             => $website,
            'formSettings'        => $formSettings,
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
            'districts_id'    => ['nullable', 'integer'],
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
        $validated['region'] = $request->filled('region') ? $request->string('region')->toString() : null;

        $validated['is_read']   = false;
        $validated['is_active'] = true;

        $validated['wants_similar_offers'] = $request->has('wants_similar_offers') ? ($request->boolean('wants_similar_offers') ? 1 : 0) : null;

        $validated['contact_on_whatsapp']  = $request->boolean('contact_on_whatsapp', true);

        UserPropertyRequest::create($validated);

        return back()->with('success', 'تم إرسال الطلب بنجاح، سنقوم بالتواصل معك قريبًا.');
    }
}
