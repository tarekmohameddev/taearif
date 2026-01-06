<!-- Create Marketplace App Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">إضافة تطبيق متجر</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form"
                    action="{{ route('admin.marketplace-apps.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">اسم التطبيق*</label>
                        <input id="name" type="text" class="form-control" name="name"
                            placeholder="أدخل اسم التطبيق" value="">
                        <p id="errname" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="description">الوصف</label>
                        <textarea id="description" class="form-control" name="description" rows="3"
                            placeholder="أدخل وصف التطبيق"></textarea>
                        <p id="errdescription" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="price">السعر*</label>
                        <input id="price" type="number" step="0.01" class="form-control" name="price"
                            placeholder="أدخل سعر التطبيق" value="0">
                        <p class="text-warning">
                            <small>أدخل 0 للتطبيقات المجانية</small>
                        </p>
                        <p id="errprice" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="type">نوع التطبيق*</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="marketplace" selected>متجر</option>
                            <option value="builtin">مدمج</option>
                        </select>
                        <p id="errtype" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="rating">التقييم (0-5)</label>
                        <input id="rating" type="number" step="0.1" min="0" max="5" class="form-control" name="rating"
                            placeholder="أدخل التقييم" value="0">
                        <p id="errrating" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="img">رابط الصورة</label>
                        <input id="img" type="text" class="form-control" name="img"
                            placeholder="أدخل رابط الصورة" value="">
                        <p class="text-info">
                            <small>أو قم برفع ملف صورة أدناه</small>
                        </p>
                        <p id="errimg" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="image">رفع صورة</label>
                        <input id="image" type="file" class="form-control" name="image" accept="image/*">
                        <p class="text-info">
                            <small>مسموح: JPG، JPEG، PNG. الحد الأقصى للحجم: 2 ميجابايت</small>
                        </p>
                        <div id="imagePreview" class="mt-2" style="display:none;">
                            <img id="previewImg" src="" alt="معاينة" style="max-width: 200px; max-height: 200px;">
                        </div>
                        <p id="errimage" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="billing_type">نوع الفوترة*</label>
                        <select id="billing_type" name="billing_type" class="form-control" required>
                            @php
                                $billingTypeLabels = [
                                    'free' => 'مجاني',
                                    'paid' => 'مدفوع',
                                    'paid_trial' => 'مدفوع مع تجربة',
                                ];
                            @endphp
                            @foreach($billingTypes as $billingType)
                                <option value="{{ $billingType->value }}">{{ $billingTypeLabels[$billingType->value] ?? ucfirst(str_replace('_', ' ', $billingType->value)) }}</option>
                            @endforeach
                        </select>
                        <p id="errbilling_type" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group" id="trial_days_group" style="display: none;">
                        <label for="trial_days">أيام التجربة*</label>
                        <input id="trial_days" type="number" min="1" class="form-control" name="trial_days"
                            placeholder="أدخل أيام التجربة" value="">
                        <p id="errtrial_days" class="mb-0 text-danger em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                <button id="submitBtn" type="button" class="btn btn-primary marketplace-submit">إرسال</button>
            </div>
        </div>
    </div>
</div>


