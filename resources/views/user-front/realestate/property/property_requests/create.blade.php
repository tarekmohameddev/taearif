@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')

@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))
@section('pageHeading', __('سجل طلبك العقاري'))
@include('user-front.realestate.partials.header.header-pages')
@else
@section('tab-title') {{ __('سجل طلبك العقاري') }} @endsection
@section('page-name') {{ __('سجل طلبك العقاري') }} @endsection
@section('br-name') {{ __('Property Request') }} @endsection
@section('styles')
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/partials.css') }}">
@if ($userCurrentLang->rtl == 1)
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/rtl.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/responsive.css') }}">
@endsection
@endif

@section('content')

@php
    $fs  = $formSettings ?? [];
    $vis = fn(string $k) => (bool)($fs[$k]['is_visible'] ?? true);
    $req = fn(string $k) => (bool)($fs[$k]['is_required'] ?? false);
    $lbl = function(string $k, string $fallback) use ($fs) {
        $isAr = app()->getLocale() === 'ar';
        $custom = $fs[$k][$isAr ? 'label_ar' : 'label_en'] ?? null;
        return $custom ?: $fallback;
    };
@endphp

<div class="product-single pt-100 border-top header-next">
    <div style="margin-top: 3% !important;">
        <div>
            <div class="form-header">
                <h1><i class="fas fa-home"></i> سجل طلبك العقاري</h1>
                <p>ابحث عن العقار المثالي بسهولة ويسر</p>
            </div>

            <div class="form-wrapper">
            <form id="propertyRequestForm" method="POST" action="{{ route('front.user.property-requests.store', getParam()) }}">
    @csrf

    @php
        $secProperty = $vis('category_id') || $vis('property_type') || $vis('city_id') || $vis('districts_id') || $vis('area_from') || $vis('area_to');
        $secBudget   = $vis('purchase_method') || $vis('budget_from') || $vis('budget_to');
        $secExtra    = $vis('seriousness') || $vis('purchase_goal') || $vis('wants_similar_offers');
        $secContact  = $vis('full_name') || $vis('phone') || $vis('contact_on_whatsapp') || $vis('notes');
    @endphp

    {{-- معلومات العقار --}}
    @if($secProperty)
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-building"></i>
            {{ $lbl('section_property_info', 'معلومات العقار المطلوب') }}
        </div>

        <div class="form-row">
            @if($vis('category_id'))
            <div class="form-group">
                <label class="{{ $req('category_id') ? 'required' : '' }}">{{ $lbl('category_id', 'نوع العقار') }}</label>

                <select name="category_id" class="form-select" {{ $req('category_id') ? 'required' : '' }}>
                    <option value="">{{ $lbl('category_id_placeholder','اختر نوع العقار') }}</option>
                    @forelse($allCategories as $cat)
                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                    {{ app()->getLocale()==='ar' ? ($cat->name_ar ?? $cat->name) : ($cat->name_en ?? $cat->name) }}

                    </option>
                    @empty
                    <option value="" disabled>{{ $lbl('category_id_empty','لا توجد أنواع متاحة حاليًا') }}</option>
                    @endforelse
                </select>
                @error('category_id') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif

            @if($vis('property_type'))
            <div class="form-group">
                <label class="{{ $req('property_type') ? 'required' : '' }}">{{ $lbl('property_type','تصنيف العقار') }}</label>
                <div class="radio-group">
                    @php $types = $fs['property_type']['meta']['options'] ?? ['سكني','تجاري','صناعي','زراعي']; @endphp
                    @foreach($types as $t)
                    <div class="radio-item {{ old('property_type') == $t ? 'selected' : '' }}" data-radio="property_type" data-value="{{ $t }}">
                        <input type="radio" name="property_type" value="{{ $t }}"
                               {{ old('property_type') == $t ? 'checked' : '' }}
                               {{ $req('property_type') && $loop->first ? 'required' : '' }}>
                        {{ $t }}
                    </div>
                    @endforeach
                </div>
                @error('property_type') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>

        <div class="form-row">
        @php
            $citiesList = $cities ?? collect([]);
            $isAr = app()->getLocale() === 'ar';
        @endphp

        @if($vis('city_id'))
        <div class="form-group">
            <label class="{{ $req('city_id') ? 'required' : '' }}">{{ $lbl('city_id','المدينة') }}</label>

            {{-- Hidden input to ensure city_id is sent when disabled --}}
            @if(isset($disableCitySelection) && $disableCitySelection && isset($defaultCityId))
                <input type="hidden" name="city_id" value="{{ $defaultCityId }}">
            @endif

            <select
                class="form-select"
                id="citySelect"
                name="city_id"
                data-districts-base="{{ url('/geo/districts/by-city') }}"
                data-old-city="{{ old('city_id') }}"
                data-old-district="{{ old('districts_id') }}"
                {{ $req('city_id') ? 'required' : '' }}
                {{ isset($disableCitySelection) && $disableCitySelection ? 'disabled' : '' }}
            >
                <option value="">{{ $lbl('city_id_placeholder','اختر المدينة') }}</option>

                @foreach($citiesList as $row)
                    <option value="{{ $row->id }}" {{ 
                        (string)old('city_id') === (string)$row->id ? 'selected' : 
                        ((isset($defaultCityId) && $defaultCityId == $row->id && !old('city_id')) ? 'selected' : '')
                    }}>
                        {{ $isAr ? ($row->name_ar ?? $row->name_en) : ($row->name_en ?? $row->name_ar) }}
                    </option>
                @endforeach
            </select>

            @error('city_id') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif


        @if($vis('districts_id'))
        <div class="form-group">
            <label class="{{ $req('districts_id') ? 'required' : '' }}">{{ $lbl('districts_id','الحي') }}</label>
            <select
                class="form-select"
                id="districtSelect"
                name="districts_id"
                {{ $req('districts_id') ? 'required' : '' }}
                disabled
            >
                <option value="">{{ $isAr ? 'اختر الحي' : 'Select District' }}</option>
            </select>
            @error('districts_id') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif
    </div>

        <div class="form-row">
            @if($vis('area_from'))
            <div class="form-group">
                <label class="{{ $req('area_from') ? 'required' : '' }}">{{ $lbl('area_from','المساحة من (م²)') }}</label>
                <input type="number" name="area_from" value="{{ old('area_from') }}" placeholder="{{ $lbl('area_from_placeholder','مثال: 100') }}" {{ $req('area_from') ? 'required' : '' }}>
                @error('area_from') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif

            @if($vis('area_to'))
            <div class="form-group">
                <label class="{{ $req('area_to') ? 'required' : '' }}">{{ $lbl('area_to','المساحة إلى (م²)') }}</label>
                <input type="number" name="area_to" value="{{ old('area_to') }}" placeholder="{{ $lbl('area_to_placeholder','مثال: 200') }}" {{ $req('area_to') ? 'required' : '' }}>
                @error('area_to') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- معلومات الميزانية --}}
    @if($secBudget)
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-money-bill-wave"></i>
            {{ $lbl('section_budget','معلومات الميزانية والدفع') }}
        </div>

        @if($vis('purchase_method'))
        <div class="form-group">
            <label class="{{ $req('purchase_method') ? 'required' : '' }}">{{ $lbl('purchase_method','طريقة الشراء') }}</label>
            <div class="radio-group">
                @php $methods = $fs['purchase_method']['meta']['options'] ?? ['كاش','تمويل بنكي']; @endphp
                @foreach($methods as $m)
                <div class="radio-item {{ old('purchase_method') == $m ? 'selected' : '' }}" data-radio="purchase_method" data-value="{{ $m }}">
                    <input type="radio" name="purchase_method" value="{{ $m }}"
                           {{ old('purchase_method') == $m ? 'checked' : '' }}
                           {{ $req('purchase_method') && $loop->first ? 'required' : '' }}>
                    {{ $m }}
                </div>
                @endforeach
            </div>
            @error('purchase_method') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif

        <div class="form-row">
            @if($vis('budget_from'))
            <div class="form-group">
                <label class="{{ $req('budget_from') ? 'required' : '' }}">{{ $lbl('budget_from','الميزانية من (ر.س)') }}</label>
                <input type="number" name="budget_from" value="{{ old('budget_from') }}" placeholder="{{ $lbl('budget_from_placeholder','مثال: 500000') }}" {{ $req('budget_from') ? 'required' : '' }}>
                @error('budget_from') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif

            @if($vis('budget_to'))
            <div class="form-group">
                <label class="{{ $req('budget_to') ? 'required' : '' }}">{{ $lbl('budget_to','الميزانية إلى (ر.س)') }}</label>
                <input type="number" name="budget_to" value="{{ old('budget_to') }}" placeholder="{{ $lbl('budget_to_placeholder','مثال: 800000') }}" {{ $req('budget_to') ? 'required' : '' }}>
                @error('budget_to') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- تفاصيل إضافية --}}
    @if($secExtra)
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-clipboard-check"></i>
            {{ $lbl('section_extra','تفاصيل إضافية') }}
        </div>

        @if($vis('seriousness'))
        <div class="form-group">
            <label class="{{ $req('seriousness') ? 'required' : '' }}">{{ $lbl('seriousness','ما مدى جديتك في الشراء؟') }}</label>
            <div class="radio-group">
                @php $ser = $fs['seriousness']['meta']['options'] ?? ['مستعد فورًا','خلال شهر','خلال 3 أشهر','لاحقًا / استكشاف فقط']; @endphp
                @foreach($ser as $s)
                <div class="radio-item {{ old('seriousness') == $s ? 'selected' : '' }}" data-radio="seriousness" data-value="{{ $s }}">
                    <input type="radio" name="seriousness" value="{{ $s }}"
                           {{ old('seriousness') == $s ? 'checked' : '' }}
                           {{ $req('seriousness') && $loop->first ? 'required' : '' }}>
                    {{ $s }}
                </div>
                @endforeach
            </div>
            @error('seriousness') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif

        @if($vis('purchase_goal'))
        <div class="form-group">
            <label class="{{ $req('purchase_goal') ? 'required' : '' }}">{{ $lbl('purchase_goal','هدف الشراء') }}</label>
            <div class="radio-group">
                @php $goals = $fs['purchase_goal']['meta']['options'] ?? ['سكن خاص','استثمار وتأجير','بناء وبيع','مشروع تجاري']; @endphp
                @foreach($goals as $g)
                <div class="radio-item {{ old('purchase_goal') == $g ? 'selected' : '' }}" data-radio="purchase_goal" data-value="{{ $g }}">
                    <input type="radio" name="purchase_goal" value="{{ $g }}"
                           {{ old('purchase_goal') == $g ? 'checked' : '' }}
                           {{ $req('purchase_goal') && $loop->first ? 'required' : '' }}>
                    {{ $g }}
                </div>
                @endforeach
            </div>
            @error('purchase_goal') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif

        @if($vis('wants_similar_offers'))
        <div class="form-group">
            <label class="{{ $req('wants_similar_offers') ? 'required' : '' }}">{{ $lbl('wants_similar_offers','هل ترغب باستقبال عروض مشابهة؟') }}</label>
            <div class="radio-group">
                <div class="radio-item {{ old('wants_similar_offers','1') == '1' ? 'selected' : '' }}" data-radio="wants_similar_offers" data-value="1">
                    <input type="radio" name="wants_similar_offers" value="1" {{ old('wants_similar_offers','1') == '1' ? 'checked' : '' }} {{ $req('wants_similar_offers') ? 'required' : '' }}>
                    {{ $lbl('yes','نعم') }}
                </div>
                <div class="radio-item {{ old('wants_similar_offers') == '0' ? 'selected' : '' }}" data-radio="wants_similar_offers" data-value="0">
                    <input type="radio" name="wants_similar_offers" value="0" {{ old('wants_similar_offers') == '0' ? 'checked' : '' }}>
                    {{ $lbl('no','لا') }}
                </div>
            </div>
            @error('wants_similar_offers') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif
    </div>
    @endif

    {{-- بيانات التواصل --}}
    @if($secContact)
    <div class="form-section">
        <div class="section-title">
            <i class="fas fa-user"></i>
            {{ $lbl('section_contact','بيانات التواصل') }}
        </div>

        <div class="form-row">
            @if($vis('full_name'))
            <div class="form-group">
                <label class="{{ $req('full_name') ? 'required' : '' }}">{{ $lbl('full_name','الاسم الكامل') }}</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="{{ $lbl('full_name_placeholder','أدخل اسمك الكامل') }}" {{ $req('full_name') ? 'required' : '' }}>
                @error('full_name') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif

            @if($vis('phone'))
            <div class="form-group">
                <label class="{{ $req('phone') ? 'required' : '' }}">{{ $lbl('phone','رقم الجوال') }}</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="{{ $lbl('phone_placeholder','05xxxxxxxx') }}" {{ $req('phone') ? 'required' : '' }}>
                @error('phone') <p class="text-danger">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>

        @if($vis('contact_on_whatsapp'))
        <div class="form-group">
            <label class="{{ $req('contact_on_whatsapp') ? 'required' : '' }}">{{ $lbl('contact_on_whatsapp','هل ترغب بالتواصل عبر واتساب؟') }}</label>
            <div class="radio-group">
                <div class="radio-item {{ old('contact_on_whatsapp','1') == '1' ? 'selected' : '' }}" data-radio="contact_on_whatsapp" data-value="1">
                    <input type="radio" name="contact_on_whatsapp" value="1" {{ old('contact_on_whatsapp','1') == '1' ? 'checked' : '' }} {{ $req('contact_on_whatsapp') ? 'required' : '' }}>
                    {{ $lbl('yes','نعم') }}
                </div>
                <div class="radio-item {{ old('contact_on_whatsapp') == '0' ? 'selected' : '' }}" data-radio="contact_on_whatsapp" data-value="0">
                    <input type="radio" name="contact_on_whatsapp" value="0" {{ old('contact_on_whatsapp') == '0' ? 'checked' : '' }}>
                    {{ $lbl('no','لا') }}
                </div>
            </div>
            @error('contact_on_whatsapp') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif

        @if($vis('notes'))
        <div class="form-group">
            <label class="{{ $req('notes') ? 'required' : '' }}">{{ $lbl('notes','تفاصيل إضافية أو ملاحظات') }}</label>
            <textarea name="notes" placeholder="{{ $lbl('notes_placeholder','أي متطلبات أو ملاحظات إضافية تود إضافتها...') }}" {{ $req('notes') ? 'required' : '' }}>{{ old('notes') }}</textarea>
            @error('notes') <p class="text-danger">{{ $message }}</p> @enderror
        </div>
        @endif
    </div>
    @endif

    <button type="submit" class="btn-submit">
        <i class="fas fa-paper-plane"></i>
        {{ $lbl('submit_btn','إرسال الطلب') }}
    </button>
</form>

            </div>
        </div>
    </div>
</div>


<style>
    /* ألوان المستخدم كمتغيرات CSS */
    :root{
        --base-color: {{ $userBs->base_color ?? '#1e40af' }};
        --secondary-color: {{ $userBs->secondary_color ?? '#3b82f6' }};
        --accent-color: {{ $userBs->accent_color ?? '#93c5fd' }};

        /* درجات مشتقة بسيطة لثبات المظهر */
        --accent-bg: var(--accent-color);
        --border-color: color-mix(in srgb, var(--secondary-color) 25%, #ffffff);
        --danger: #e74c3c;
        --text-900:#111827; --text-700:#374151; --text-600:#4b5563; --text-500:#6b7280;
        --white:#ffffff;
    }

    *{box-sizing:border-box}
    body{
        font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
        direction:rtl;
        color:var(--text-900);
        background:#fff;
    }

    .modern-form-container{
        margin:0 auto;background:var(--white);border-radius:20px;
        box-shadow:0 20px 60px rgba(0,0,0,.1);overflow:hidden;animation:fadeInUp .8s ease-out
    }
    @keyframes fadeInUp{from{opacity:0;transform:translateY(30px)}to{opacity:1;transform:translateY(0)}}

    /* رأس النموذج */
    .form-header{
        background:linear-gradient(135deg,var(--base-color) 0%,var(--secondary-color) 100%);
        color:var(--white);padding:30px 30px;text-align:center;position:relative;overflow:hidden
    }
    .form-header::before{
        content:'';position:absolute;top:-50%;left:-50%;width:200%;height:200%;
        background:radial-gradient(circle,rgba(255,255,255,.12) 0%,transparent 70%);
        animation:float 6s ease-in-out infinite
    }
    @keyframes float{0%,100%{transform:translateY(0) rotate(0)}50%{transform:translateY(-20px) rotate(180deg)}}
    .form-header h1{font-size:2.5rem;font-weight:700;margin-bottom:10px;position:relative;z-index:2}
    .form-header p{font-size:1.1rem;opacity:.95;position:relative;z-index:2}

    .form-wrapper{padding:40px 30px}

    /* أقسام */
    .form-section{
        margin-bottom:40px;padding:30px;background:var(--accent-bg);
        border-radius:15px;border:1px solid var(--border-color);transition:all .3s ease
    }
    .form-section:hover{transform:translateY(-2px);box-shadow:0 8px 25px color-mix(in srgb, var(--secondary-color) 25%, transparent)}

    .section-title{
        display:flex;align-items:center;margin-bottom:25px;font-size:1.3rem;
        font-weight:600;color:var(--text-900)
    }
    .section-title i{
        background:linear-gradient(135deg,var(--base-color) 0%,var(--secondary-color) 100%);
        color:var(--white);width:35px;height:35px;border-radius:50%;
        display:flex;align-items:center;justify-content:center;margin-left:12px;font-size:.9rem
    }

    /* الشبكة */
    .form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin-bottom:20px}

    .form-group{display:flex;flex-direction:column}
    label{font-weight:600;color:var(--text-700);margin-bottom:8px;font-size:.95rem}
    .required::after{content:' *';color:var(--danger)}

    /* الحقول */
    input[type="text"],input[type="number"],select,textarea{
        padding:12px 15px;border:2px solid var(--border-color);border-radius:10px;
        font-size:1rem;transition:all .25s ease;background:var(--white);color:var(--text-900)
    }
    input[type="text"]::placeholder,input[type="number"]::placeholder,textarea::placeholder{color:var(--text-500)}
    input[type="text"]:focus,input[type="number"]:focus,select:focus,textarea:focus{
        outline:none;border-color:var(--secondary-color);
        box-shadow:0 0 0 3px color-mix(in srgb, var(--secondary-color) 25%, transparent);
        transform:translateY(-1px)
    }

    /* اختيارات */
    .radio-group,.checkbox-group{display:flex;flex-wrap:wrap;gap:15px;margin-top:10px}
    .radio-item,.checkbox-item{
        display:flex;align-items:center;padding:10px 15px;background:var(--white);
        border:2px solid var(--border-color);border-radius:25px;cursor:pointer;
        transition:all .25s ease;min-width:120px;justify-content:center;position:relative;color:var(--text-700)
    }
    .radio-item:hover,.checkbox-item:hover{
        border-color:var(--secondary-color);background:color-mix(in srgb, var(--accent-color) 25%, #fff);
        transform:translateY(-2px)
    }
    .radio-item.selected,.checkbox-item.selected{
        background:linear-gradient(135deg,var(--base-color) 0%,var(--secondary-color) 100%);
        color:var(--white);border-color:transparent
    }
    input[type="radio"],input[type="checkbox"]{display:none}

    /* زر الإرسال */
    .btn-submit{
        background:linear-gradient(135deg,var(--base-color) 0%,var(--secondary-color) 100%);
        color:var(--white);padding:15px 40px;border:none;border-radius:50px;
        font-size:1.1rem;font-weight:600;cursor:pointer;transition:all .25s ease;
        display:block;margin:40px auto 0;min-width:200px
    }
    .btn-submit:hover{transform:translateY(-2px);box-shadow:0 10px 30px color-mix(in srgb, var(--secondary-color) 40%, transparent)}
    .btn-submit:active{transform:translateY(0)}

    textarea{resize:vertical;min-height:100px}
    .text-danger{color:var(--danger);font-size:.875rem;margin-top:5px}

    @media (max-width:768px){
        .modern-form-container{margin:10px;border-radius:15px}
        .form-header{padding:30px 20px}
        .form-header h1{font-size:2rem}
        .form-wrapper{padding:30px 20px}
        .form-section{padding:20px}
        .radio-group,.checkbox-group{flex-direction:column}
        .radio-item,.checkbox-item{min-width:100%}
    }

    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-5px)}75%{transform:translateX(5px)}}
    .shake{animation:shake .5s ease-in-out}
</style>


@endsection

@if (!in_array($userBs->theme, ['home13', 'home14', 'home15']))
@section('scripts')
@endif
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.radio-item').forEach(item => {
        item.addEventListener('click', function() {
            const radioGroup = this.dataset.radio;
            document.querySelectorAll(`[data-radio="${radioGroup}"]`).forEach(el => el.classList.remove('selected'));
            this.classList.add('selected');
            const radioInput = this.querySelector('input[type="radio"]');
            if (radioInput) radioInput.checked = true;
        });
    });

    document.querySelectorAll('.checkbox-item').forEach(item => {
        item.addEventListener('click', function() {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('selected', checkbox.checked);
        });
    });

    document.getElementById('propertyRequestForm').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;
        requiredFields.forEach(field => {
            if (!String(field.value || '').trim()) {
                isValid = false;
                field.style.borderColor = '#e74c3c';
                field.classList.add('shake');
                setTimeout(() => field.classList.remove('shake'), 500);
            } else {
                field.style.borderColor = '#e1e8ff';
            }
        });
        if (!isValid) {
            e.preventDefault();
            alert('يرجى ملء جميع الحقول المطلوبة');
        }
    });

    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('focus', function() {
            const section = this.closest('.form-section');
            if (section) section.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const citySelect = document.getElementById('citySelect');
    const districtSelect = document.getElementById('districtSelect');

    // CHANGED: use data-districts-base instead of data-states-base
    const base = citySelect ? citySelect.dataset.districtsBase : null;

    const OLD_CITY_ID = @json(old('city_id'));
    const OLD_DISTRICT_ID = @json(old('districts_id'));
    const DEFAULT_CITY_ID = @json($defaultCityId ?? null);
    const IS_AR = @json(app()->getLocale() === 'ar');

    function resetDistricts(disabled = true) {
        if (!districtSelect) return;
        districtSelect.innerHTML =
          `<option value="">${IS_AR ? 'اختر الحي' : 'Select District'}</option>`;
        districtSelect.disabled = disabled;
    }

    async function loadDistricts(cityId, selectedId = null) {
        if (!base || !cityId || !districtSelect) { resetDistricts(true); return; }
        const baseClean = base.replace(/\/$/, '');
        const url = `${baseClean}/${encodeURIComponent(cityId)}`; // -> /geo/districts/by-city/3
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const list = await res.json();
            resetDistricts(false);
            list.forEach(item => {
                const opt = document.createElement('option');
                opt.value = String(item.id);
                const name = IS_AR ? (item.name_ar || item.name_en) : (item.name_en || item.name_ar);
                opt.textContent = name || ('#' + item.id);
                if (selectedId && String(selectedId) === String(item.id)) opt.selected = true;
                districtSelect.appendChild(opt);
            });
            districtSelect.disabled = list.length === 0;
        } catch (e) {
            console.error('Failed to load districts:', e);
            resetDistricts(true);
        }
    }

    if (citySelect && districtSelect) {
        citySelect.addEventListener('change', function() {
            resetDistricts();
            if (this.value) loadDistricts(this.value, null);
        });
        // Load districts for old city or default city
        const cityToLoad = OLD_CITY_ID || DEFAULT_CITY_ID;
        if (cityToLoad) loadDistricts(cityToLoad, OLD_DISTRICT_ID);
    }
});
</script>


@if (!in_array($userBs->theme, ['home13', 'home14', 'home15']))
@endsection
@endif
