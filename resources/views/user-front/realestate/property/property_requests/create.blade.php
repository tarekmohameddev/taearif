@extends('user-front.realestate.layout')

@section('pageHeading', __('سجل طلبك العقاري'))

@section('content')
<div class="container mt-5 mb-5">
    <h2 class="text-center mb-4">📝 سجل طلبك العقاري</h2>

    <form method="POST" action="{{ route('front.user.property-requests.store', getParam()) }}">
        @csrf

        <div class="row">
            <!-- نوع العقار -->
            <div class="col-md-6 mb-3">
                <label for="property_type">نوع العقار المطلوب *</label>
                <select name="property_type" class="form-select" required>
                    <option value="">اختر نوع العقار</option>
                    <option value="شقة">شقة</option>
                    <option value="دور">دور</option>
                    <option value="تاون هاوس">تاون هاوس</option>
                    <option value="فيلا">فيلا</option>
                    <option value="أرض">أرض</option>
                    <option value="عمارة">عمارة</option>
                </select>
            </div>

            <!-- التصنيف -->
            <div class="col-md-6 mb-3">
                <label>تصنيف العقار</label><br>
                @foreach(['سكني', 'تجاري', 'صناعي', 'زراعي'] as $cat)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="category" value="{{ $cat }}">
                        <label class="form-check-label">{{ $cat }}</label>
                    </div>
                @endforeach
            </div>

            <!-- الأحياء -->
            <div class="col-12 mb-3">
                <label>الأحياء المطلوبة</label><br>
                @php $neighborhoods = ['الملز', 'العليا', 'النرجس', 'الياسمين', 'الازدهار']; @endphp
                @foreach ($neighborhoods as $hood)
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" name="neighborhoods[]" value="{{ $hood }}">
                        <label class="form-check-label">{{ $hood }}</label>
                    </div>
                @endforeach
            </div>

            <!-- المساحة -->
            <div class="col-md-6 mb-3">
                <label>المساحة من (م²)</label>
                <input type="number" name="area_from" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label>إلى (م²)</label>
                <input type="number" name="area_to" class="form-control">
            </div>

            <!-- طريقة الشراء -->
            <div class="col-12 mb-3">
                <label>الشراء عن طريق *</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="purchase_method" value="كاش" class="form-check-input" required>
                    <label class="form-check-label">كاش</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="purchase_method" value="تمويل بنكي" class="form-check-input" required>
                    <label class="form-check-label">تمويل بنكي</label>
                </div>
            </div>

            <!-- الميزانية -->
            <div class="col-md-6 mb-3">
                <label>الميزانية من (ر.س)</label>
                <input type="number" name="budget_from" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>إلى (ر.س)</label>
                <input type="number" name="budget_to" class="form-control" required>
            </div>

            <!-- الجدية -->
            <div class="col-12 mb-3">
                <label>ما مدى جديتك؟</label><br>
                @foreach(['مستعد فورًا', 'خلال شهر', 'خلال 3 أشهر', 'لاحقًا / استكشاف فقط'] as $option)
                    <div class="form-check form-check-inline">
                        <input type="radio" name="seriousness" value="{{ $option }}" class="form-check-input">
                        <label class="form-check-label">{{ $option }}</label>
                    </div>
                @endforeach
            </div>

            <!-- الهدف -->
            <div class="col-12 mb-3">
                <label>هدف الشراء</label><br>
                @foreach(['سكن خاص', 'استثمار وتأجير', 'بناء وبيع', 'مشروع تجاري'] as $goal)
                    <div class="form-check form-check-inline">
                        <input type="radio" name="purchase_goal" value="{{ $goal }}" class="form-check-input">
                        <label class="form-check-label">{{ $goal }}</label>
                    </div>
                @endforeach
            </div>

            <!-- استقبال العروض -->
            <div class="col-12 mb-3">
                <label>هل ترغب باستقبال عروض مشابهة؟</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="wants_similar_offers" value="1" class="form-check-input">
                    <label class="form-check-label">نعم</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="wants_similar_offers" value="0" class="form-check-input">
                    <label class="form-check-label">لا</label>
                </div>
            </div>

            <!-- بيانات التواصل -->
            <div class="col-md-6 mb-3">
                <label>الاسم الكامل *</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>رقم الجوال *</label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <!-- واتساب -->
            <div class="col-12 mb-3">
                <label>هل ترغب بالتواصل عبر واتساب؟</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="contact_on_whatsapp" value="1" class="form-check-input" checked>
                    <label class="form-check-label">نعم</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="contact_on_whatsapp" value="0" class="form-check-input">
                    <label class="form-check-label">لا</label>
                </div>
            </div>

            <!-- ملاحظات -->
            <div class="col-12 mb-3">
                <label>تفاصيل إضافية</label>
                <textarea name="notes" rows="4" class="form-control"></textarea>
            </div>

            <div class="col-12 text-center">
                <button type="submit" class="btn btn-primary">إرسال الطلب</button>
            </div>
        </div>
    </form>
</div>
@endsection
