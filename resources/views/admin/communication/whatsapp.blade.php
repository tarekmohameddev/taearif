@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">واتس اب</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{route('admin.dashboard')}}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">التواصل</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">واتس اب</a>
      </li>
    </ul>
  </div>

  <!-- Service Selection Cards -->
  <div class="row mb-4">
    <div class="col-md-6">
      <div class="card service-card {{($abs->whatsapp_service ?? '') == 'meta_cloud' ? 'border-primary' : ''}}" 
           data-service="meta_cloud" style="cursor: pointer;">
        <div class="card-body text-center">
          <div class="service-icon mb-3">
            <i class="fab fa-facebook-messenger fa-3x text-primary"></i>
          </div>
          <h5 class="card-title">Meta Cloud API</h5>
          <p class="card-text">استخدم واجهة برمجة تطبيقات Meta الرسمية لإرسال رسائل واتس اب</p>
          <div class="service-status">
            @if(($abs->whatsapp_service ?? '') == 'meta_cloud')
              <span class="badge badge-success">مفعل</span>
            @else
              <span class="badge badge-secondary">غير مفعل</span>
            @endif
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card service-card {{($abs->whatsapp_service ?? '') == 'evolution_api' ? 'border-primary' : ''}}" 
           data-service="evolution_api" style="cursor: pointer;">
        <div class="card-body text-center">
          <div class="service-icon mb-3">
            <i class="fab fa-whatsapp fa-3x text-success"></i>
          </div>
          <h5 class="card-title">Evolution API</h5>
          <p class="card-text">استخدم Evolution API لإرسال رسائل واتس اب من خلال خادم محلي</p>
          <div class="service-status">
            @if(($abs->whatsapp_service ?? '') == 'evolution_api')
              <span class="badge badge-success">مفعل</span>
            @else
              <span class="badge badge-secondary">غير مفعل</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Service Configuration Forms -->
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">إعدادات الخدمة المختارة</div>
        </div>
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-8 offset-lg-2">
              <!-- Meta Cloud API Form -->
              <div id="meta-cloud-form" class="service-form" style="display: {{($abs->whatsapp_service ?? '') == 'meta_cloud' ? 'block' : 'none'}};">
                <form action="{{route('admin.communication.meta-cloud.update')}}" method="POST">
                  @csrf
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="meta_access_token"><strong>Access Token **</strong></label>
                        <input type="text" class="form-control" id="meta_access_token" name="meta_access_token" 
                               value="{{$abs->meta_access_token ?? ''}}" placeholder="أدخل Meta Access Token">
                        <p class="text-muted">يمكن الحصول عليه من Meta for Developers</p>
                        @error('meta_access_token')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="meta_phone_number_id"><strong>Phone Number ID **</strong></label>
                        <input type="text" class="form-control" id="meta_phone_number_id" name="meta_phone_number_id" 
                               value="{{$abs->meta_phone_number_id ?? ''}}" placeholder="Phone Number ID">
                        @error('meta_phone_number_id')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="meta_business_account_id"><strong>Business Account ID **</strong></label>
                        <input type="text" class="form-control" id="meta_business_account_id" name="meta_business_account_id" 
                               value="{{$abs->meta_business_account_id ?? ''}}" placeholder="Business Account ID">
                        @error('meta_business_account_id')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="meta_template_name"><strong>Template Name **</strong></label>
                        <input type="text" class="form-control" id="meta_template_name" name="meta_template_name" 
                               value="{{$abs->meta_template_name ?? ''}}" placeholder="password_reset_template">
                        @error('meta_template_name')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="meta_template_language"><strong>Template Language **</strong></label>
                        <input type="text" class="form-control" id="meta_template_language" name="meta_template_language" 
                               value="{{$abs->meta_template_language ?? 'ar'}}" placeholder="ar">
                        @error('meta_template_language')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> حفظ إعدادات Meta Cloud API
                        </button>
                        <button type="button" class="btn btn-info ml-2" onclick="checkConfiguration()">
                          <i class="fas fa-check-circle"></i> فحص الإعدادات
                        </button>
                        <button type="button" class="btn btn-warning ml-2" onclick="showTestModal()">
                          <i class="fas fa-paper-plane"></i> اختبار الإرسال
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Evolution API Form -->
              <div id="evolution-api-form" class="service-form" style="display: {{($abs->whatsapp_service ?? '') == 'evolution_api' ? 'block' : 'none'}};">
                <form action="{{route('admin.communication.evolution-api.update')}}" method="POST">
                  @csrf
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="evolution_api_url"><strong>API URL **</strong></label>
                        <input type="url" class="form-control" id="evolution_api_url" name="evolution_api_url" 
                               value="{{$abs->evolution_api_url ?? ''}}" placeholder="https://your-evolution-api.com">
                        <p class="text-muted">رابط Evolution API الخاص بك</p>
                        @error('evolution_api_url')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="evolution_api_key"><strong>API Key **</strong></label>
                        <input type="text" class="form-control" id="evolution_api_key" name="evolution_api_key" 
                               value="{{$abs->evolution_api_key ?? ''}}" placeholder="أدخل API Key">
                        @error('evolution_api_key')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="evolution_instance_name"><strong>Instance Name **</strong></label>
                        <input type="text" class="form-control" id="evolution_instance_name" name="evolution_instance_name" 
                               value="{{$abs->evolution_instance_name ?? ''}}" placeholder="instance_name">
                        @error('evolution_instance_name')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="evolution_phone_number"><strong>Phone Number **</strong></label>
                        <input type="text" class="form-control" id="evolution_phone_number" name="evolution_phone_number" 
                               value="{{$abs->evolution_phone_number ?? ''}}" placeholder="+966501234567">
                        @error('evolution_phone_number')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <button type="submit" class="btn btn-success">
                          <i class="fas fa-save"></i> حفظ إعدادات Evolution API
                        </button>
                        <button type="button" class="btn btn-info ml-2" onclick="checkConfiguration()">
                          <i class="fas fa-check-circle"></i> فحص الإعدادات
                        </button>
                        <button type="button" class="btn btn-warning ml-2" onclick="showTestModal()">
                          <i class="fas fa-paper-plane"></i> اختبار الإرسال
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Hidden form for service selection -->
  <form id="service-selection-form" action="{{route('admin.communication.service.update')}}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="whatsapp_service" id="selected-service">
  </form>

  <!-- Test Modal -->
  <div class="modal fade" id="testModal" tabindex="-1" role="dialog" aria-labelledby="testModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="testModalLabel">اختبار إرسال رسالة واتس اب</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <form id="test-form" action="{{route('admin.communication.test-whatsapp')}}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="form-group">
              <label for="test_phone"><strong>رقم الهاتف للاختبار **</strong></label>
              <input type="text" class="form-control" id="test_phone" name="test_phone" 
                     placeholder="+966501234567" required>
              <p class="text-muted">أدخل رقم الهاتف مع رمز الدولة لإرسال رسالة اختبار</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">إرسال رسالة اختبار</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.service-card').click(function() {
        var service = $(this).data('service');
        
        // Update visual selection
        $('.service-card').removeClass('border-primary');
        $(this).addClass('border-primary');
        
        // Update status badges
        $('.service-status .badge').removeClass('badge-success').addClass('badge-secondary').text('غير مفعل');
        $(this).find('.service-status .badge').removeClass('badge-secondary').addClass('badge-success').text('مفعل');
        
        // Show/hide forms
        $('.service-form').hide();
        $('#' + service.replace('_', '-') + '-form').show();
        
        // Submit service selection
        $('#selected-service').val(service);
        $('#service-selection-form').submit();
    });
});

function showTestModal() {
    $('#testModal').modal('show');
}

function checkConfiguration() {
    $.post('{{route("admin.communication.check-config")}}', {
        _token: '{{csrf_token()}}'
    }, function(response) {
        // The response will be handled by the redirect with flash message
        location.reload();
    }).fail(function() {
        alert('حدث خطأ أثناء فحص الإعدادات');
    });
}
</script>

<style>
.service-card {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.service-card.border-primary {
    border-color: #007bff !important;
    box-shadow: 0 4px 15px rgba(0,123,255,0.2);
}

.service-icon {
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
@endsection
