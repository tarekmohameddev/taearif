@extends(in_array($userBs->theme, ['home13', 'home14', 'home15']) ? 'user-front.realestate.layout' : 'user-front.layout')

@if (in_array($userBs->theme, ['home13', 'home14', 'home15']))
@section('pageHeading', __('سجل طلبك العقاري'))
@include('user-front.realestate.partials.header.header-pages')
@else
@section('tab-title')
{{ __('سجل طلبك العقاري') }}
@endsection

@section('page-name')
{{ __('سجل طلبك العقاري') }}
@endsection
@section('br-name')
{{ __('Property Request') }}
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/partials.css') }}">
@if ($userCurrentLang->rtl == 1)
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/rtl.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/front/user/realestate/css/responsive.css') }}">
@endsection
@endif

@section('content')

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

                    <!-- معلومات العقار -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-building"></i>
                            معلومات العقار المطلوب
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">نوع العقار</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">اختر نوع العقار</option>
                                    @forelse($availableCategories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ app()->getLocale()==='ar' ? ($cat->name_ar ?? $cat->name) : ($cat->name_en ?? $cat->name) }}
                                    </option>
                                    @empty
                                    <option value="" disabled>لا توجد أنواع متاحة حاليًا</option>
                                    @endforelse
                                </select>
                                @error('category_id') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-group">
                                <label>تصنيف العقار</label>
                                <div class="radio-group">
                                    @foreach(['سكني', 'تجاري', 'صناعي', 'زراعي'] as $property_type)
                                    <div class="radio-item {{ old('property_type') == $property_type ? 'selected' : '' }}" data-radio="property_type" data-value="{{ $property_type }}">
                                        <input type="radio" name="property_type" value="{{ $property_type }}" {{ old('property_type') == $property_type ? 'checked' : '' }}>
                                        {{ $property_type }}
                                    </div>
                                    @endforeach
                                </div>
                                @error('property_type')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">المدينة</label>
                                <select class="form-select" id="citySelect" name="city_id" required data-states-base="{{ url('/get-states') }}">
                                    <option value="">اختر المدينة</option>
                                    @foreach($cities as $city)
                                    <option value="{{ $city->id }}" {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                        {{ app()->getLocale() === 'ar' ? ($city->name_ar ?? $city->name_ar) : ($city->name_ar ?? $city->name_ar) }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('city_id') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-group">
                                <label class="required">الحي</label>
                                <select class="form-select" id="districtSelect" name="neighborhood_id" required disabled>
                                    <option value="">اختر الحي</option>
                                </select>
                                @error('neighborhood_id') <p class="text-danger">{{ $message }}</p> @enderror
                            </div>
                        </div>


                        <div class="form-row">
                            <div class="form-group">
                                <label>المساحة من (م²)</label>
                                <input type="number" name="area_from" value="{{ old('area_from') }}" placeholder="مثال: 100">
                                @error('area_from')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label>المساحة إلى (م²)</label>
                                <input type="number" name="area_to" value="{{ old('area_to') }}" placeholder="مثال: 200">
                                @error('area_to')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- معلومات الميزانية -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-money-bill-wave"></i>
                            معلومات الميزانية والدفع
                        </div>

                        <div class="form-group">
                            <label class="required">طريقة الشراء</label>
                            <div class="radio-group">
                                @foreach(['كاش', 'تمويل بنكي'] as $method)
                                <div class="radio-item {{ old('purchase_method') == $method ? 'selected' : '' }}" data-radio="purchase_method" data-value="{{ $method }}">
                                    <input type="radio" name="purchase_method" value="{{ $method }}" {{ old('purchase_method') == $method ? 'checked' : '' }} required>
                                    {{ $method }}
                                </div>
                                @endforeach
                            </div>
                            @error('purchase_method')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">الميزانية من (ر.س)</label>
                                <input type="number" name="budget_from" value="{{ old('budget_from') }}" placeholder="مثال: 500000" required>
                                @error('budget_from')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="required">الميزانية إلى (ر.س)</label>
                                <input type="number" name="budget_to" value="{{ old('budget_to') }}" placeholder="مثال: 800000" required>
                                @error('budget_to')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل إضافية -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-clipboard-check"></i>
                            تفاصيل إضافية
                        </div>

                        <div class="form-group">
                            <label>ما مدى جديتك في الشراء؟</label>
                            <div class="radio-group">
                                @foreach(['مستعد فورًا', 'خلال شهر', 'خلال 3 أشهر', 'لاحقًا / استكشاف فقط'] as $seriousness)
                                <div class="radio-item {{ old('seriousness') == $seriousness ? 'selected' : '' }}" data-radio="seriousness" data-value="{{ $seriousness }}">
                                    <input type="radio" name="seriousness" value="{{ $seriousness }}" {{ old('seriousness') == $seriousness ? 'checked' : '' }}>
                                    {{ $seriousness }}
                                </div>
                                @endforeach
                            </div>
                            @error('seriousness')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>هدف الشراء</label>
                            <div class="radio-group">
                                @foreach(['سكن خاص', 'استثمار وتأجير', 'بناء وبيع', 'مشروع تجاري'] as $goal)
                                <div class="radio-item {{ old('purchase_goal') == $goal ? 'selected' : '' }}" data-radio="purchase_goal" data-value="{{ $goal }}">
                                    <input type="radio" name="purchase_goal" value="{{ $goal }}" {{ old('purchase_goal') == $goal ? 'checked' : '' }}>
                                    {{ $goal }}
                                </div>
                                @endforeach
                            </div>
                            @error('purchase_goal')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>هل ترغب باستقبال عروض مشابهة؟</label>
                            <div class="radio-group">
                                <div class="radio-item {{ old('wants_similar_offers') == '1' ? 'selected' : '' }}" data-radio="wants_similar_offers" data-value="1">
                                    <input type="radio" name="wants_similar_offers" value="1" {{ old('wants_similar_offers') == '1' ? 'checked' : '' }}>
                                    نعم
                                </div>
                                <div class="radio-item {{ old('wants_similar_offers') == '0' ? 'selected' : '' }}" data-radio="wants_similar_offers" data-value="0">
                                    <input type="radio" name="wants_similar_offers" value="0" {{ old('wants_similar_offers') == '0' ? 'checked' : '' }}>
                                    لا
                                </div>
                            </div>
                            @error('wants_similar_offers')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- بيانات التواصل -->
                    <div class="form-section">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            بيانات التواصل
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="required">الاسم الكامل</label>
                                <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="أدخل اسمك الكامل" required>
                                @error('full_name')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label class="required">رقم الجوال</label>
                                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="05xxxxxxxx" required>
                                @error('phone')
                                <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>هل ترغب بالتواصل عبر واتساب؟</label>
                            <div class="radio-group">
                                <div class="radio-item {{ old('contact_on_whatsapp', '1') == '1' ? 'selected' : '' }}" data-radio="contact_on_whatsapp" data-value="1">
                                    <input type="radio" name="contact_on_whatsapp" value="1" {{ old('contact_on_whatsapp', '1') == '1' ? 'checked' : '' }}>
                                    نعم
                                </div>
                                <div class="radio-item {{ old('contact_on_whatsapp') == '0' ? 'selected' : '' }}" data-radio="contact_on_whatsapp" data-value="0">
                                    <input type="radio" name="contact_on_whatsapp" value="0" {{ old('contact_on_whatsapp') == '0' ? 'checked' : '' }}>
                                    لا
                                </div>
                            </div>
                            @error('contact_on_whatsapp')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>تفاصيل إضافية أو ملاحظات</label>
                            <textarea name="notes" placeholder="أي متطلبات أو ملاحظات إضافية تود إضافتها...">{{ old('notes') }}</textarea>
                            @error('notes')
                            <p class="text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        إرسال الطلب
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    * {
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        direction: rtl;
    }

    .modern-form-container {
        /* max-width: 900px; */
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        animation: fadeInUp 0.8s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 30px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .form-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px) rotate(0deg);
        }

        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }

    .form-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        z-index: 2;
    }

    .form-header p {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 2;
    }

    .form-wrapper {
        padding: 40px 30px;
    }

    .form-section {
        margin-bottom: 40px;
        padding: 30px;
        background: #f8f9ff;
        border-radius: 15px;
        border: 1px solid #e1e8ff;
        transition: all 0.3s ease;
    }

    .form-section:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }

    .section-title {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
    }

    .section-title i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 12px;
        font-size: 0.9rem;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    label {
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .required::after {
        content: ' *';
        color: #e74c3c;
    }

    input[type="text"],
    input[type="number"],
    select,
    textarea {
        padding: 12px 15px;
        border: 2px solid #e1e8ff;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: white;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    select:focus,
    textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }

    .radio-group,
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 10px;
    }

    .radio-item,
    .checkbox-item {
        display: flex;
        align-items: center;
        padding: 10px 15px;
        background: white;
        border: 2px solid #e1e8ff;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 120px;
        justify-content: center;
        position: relative;
    }

    .radio-item:hover,
    .checkbox-item:hover {
        border-color: #667eea;
        background: #f8f9ff;
        transform: translateY(-2px);
    }

    .radio-item.selected,
    .checkbox-item.selected {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }

    input[type="radio"],
    input[type="checkbox"] {
        display: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 40px;
        border: none;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        margin: 40px auto 0;
        min-width: 200px;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .btn-submit:active {
        transform: translateY(0);
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    .text-danger {
        color: #e74c3c;
        font-size: 0.875rem;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .modern-form-container {
            margin: 10px;
            border-radius: 15px;
        }

        .form-header {
            padding: 30px 20px;
        }

        .form-header h1 {
            font-size: 2rem;
        }

        .form-wrapper {
            padding: 30px 20px;
        }

        .form-section {
            padding: 20px;
        }

        .radio-group,
        .checkbox-group {
            flex-direction: column;
        }

        .radio-item,
        .checkbox-item {
            min-width: 100%;
        }
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        25% {
            transform: translateX(-5px);
        }

        75% {
            transform: translateX(5px);
        }
    }

    .shake {
        animation: shake 0.5s ease-in-out;
    }
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
                const value = this.dataset.value;

                document.querySelectorAll(`[data-radio="${radioGroup}"]`).forEach(el => {
                    el.classList.remove('selected');
                });

                this.classList.add('selected');

                const radioInput = this.querySelector('input[type="radio"]');
                if (radioInput) {
                    radioInput.checked = true;
                }
            });
        });

        document.querySelectorAll('.checkbox-item').forEach(item => {
            item.addEventListener('click', function() {
                const checkbox = this.querySelector('input[type="checkbox"]');

                if (checkbox.checked) {
                    checkbox.checked = false;
                    this.classList.remove('selected');
                } else {
                    checkbox.checked = true;
                    this.classList.add('selected');
                }
            });
        });

        document.getElementById('propertyRequestForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
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
                if (section) {
                    section.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });
        });
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const citySelect = document.getElementById('citySelect');
        const districtSelect = document.getElementById('districtSelect');
        const base = citySelect ? citySelect.dataset.statesBase : null;

        const OLD_CITY_ID = @json(old('city_id'));
        const OLD_DISTRICT_ID = @json(old('neighborhood_id'));
        const IS_AR = @json(app()-> getLocale() === 'ar');

        function resetDistricts(disabled = true) {
            districtSelect.innerHTML = '<option value="">اختر الحي</option>';
            districtSelect.disabled = disabled;
        }

        async function loadDistricts(cityId, selectedId = null) {
            if (!base || !cityId) {
                resetDistricts(true);
                return;
            }
            const url = `${base}/${encodeURIComponent(cityId)}`;

            try {
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const list = await res.json();

                resetDistricts(false);

                list.forEach(item => {
                    const opt = document.createElement('option');
                    opt.value = String(item.id);
                    const name = IS_AR ? (item.name_ar || item.name_ar) : (item.name_ar || item.name_ar);
                    opt.textContent = name || ('حي #' + item.id);
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

            if (OLD_CITY_ID) {
                loadDistricts(OLD_CITY_ID, OLD_DISTRICT_ID);
            }
        }
    });
</script>

@if (!in_array($userBs->theme, ['home13', 'home14', 'home15']))
@endsection
@endif
