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

  <!-- Tab Navigation -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="tab-navigation">
        <div class="tab-bar">
          <button class="tab-button {{(request('tab') == 'meta_evolution' || !request('tab')) ? 'active' : ''}}" 
                  data-tab="meta_evolution">
            Meta & Evolution API
          </button>
          <button class="tab-button {{request('tab') == 'welcome_message' ? 'active' : ''}}" 
                  data-tab="welcome_message">
            رسالة الترحيب
          </button>
          <button class="tab-button {{request('tab') == 'subscription_expiration' ? 'active' : ''}}" 
                  data-tab="subscription_expiration">
            رسالة انتهاء الباقة
          </button>
          <button class="tab-button {{request('tab') == 'email_templates' ? 'active' : ''}}" 
                  data-tab="email_templates">
            قوالب البريد الإلكتروني
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Content -->
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title" id="tab-title">إعدادات Meta & Evolution API</div>
        </div>
        <div class="card-body pt-5 pb-4">
          <div class="row">
            <div class="col-lg-8 offset-lg-2">
              
              <!-- Meta & Evolution API Tab -->
              <div id="meta_evolution-tab" class="tab-content {{(request('tab') == 'meta_evolution' || !request('tab')) ? 'active' : ''}}">
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
                          <label for="meta_template_name"><strong>Template Name</strong></label>
                          <select class="form-control" id="meta_template_name" name="meta_template_name">
                            <option value="">اختر قالب من Facebook أو اتركه فارغاً للرسالة العادية</option>
                          </select>
                          <p class="text-muted">سيتم جلب القوالب من Facebook Meta API تلقائياً</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="loadMetaTemplates()">
                              <i class="fas fa-sync"></i> تحديث القوالب من Facebook
                            </button>
                          </small>
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

              <!-- Welcome Message Tab -->
              <div id="welcome_message-tab" class="tab-content {{request('tab') == 'welcome_message' ? 'active' : ''}}">
                <form action="{{route('admin.communication.welcome-message.update')}}" method="POST">
                  @csrf
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="welcome_message_enabled">
                          <strong>تفعيل رسالة الترحيب</strong>
                        </label>
                        <div class="toggle-switch-container">
                          <div class="toggle-switch">
                            <input type="checkbox" id="welcome_message_enabled" name="welcome_message_enabled" 
                                   value="1" {{($abs->welcome_message_enabled ?? false) ? 'checked' : ''}}>
                            <label for="welcome_message_enabled" class="toggle-label">
                              <span class="toggle-slider"></span>
                              <span class="toggle-text">
                                <span class="toggle-on">ON</span>
                                <span class="toggle-off">OFF</span>
                              </span>
                            </label>
                          </div>
                          <span class="toggle-description">إرسال رسالة ترحيب عند التسجيل لأول مرة</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="welcome_message_text"><strong>نص رسالة الترحيب **</strong></label>
                        <textarea class="form-control" id="welcome_message_text" name="welcome_message_text" rows="4" 
                                  placeholder="مرحباً بك في منصتنا! شكراً لك على التسجيل...">{{$abs->welcome_message_text ?? ''}}</textarea>
                        <p class="text-muted">يمكن استخدام المتغيرات: {name}, {email}</p>
                        @error('welcome_message_text')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="welcome_message_delay"><strong>تأخير الإرسال (بالثواني)</strong></label>
                        <input type="number" class="form-control" id="welcome_message_delay" name="welcome_message_delay" 
                               value="{{$abs->welcome_message_delay ?? 5}}" min="0" max="300">
                        <p class="text-muted">تأخير إرسال الرسالة بعد التسجيل</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                          <label for="welcome_message_template"><strong>اسم القالب (Meta API)</strong></label>
                          <select class="form-control" id="welcome_message_template" name="welcome_message_template">
                            <option value="">اختر قالب أو اتركه فارغاً</option>
                            @php
                                try {
                                    $welcomeTemplates = \App\Models\WhatsAppTemplate::active()->ofType('welcome')->get();
                                } catch (Exception $e) {
                                    $welcomeTemplates = collect();
                                }
                            @endphp
                            @foreach($welcomeTemplates as $template)
                              <option value="{{$template->name}}" {{($abs->welcome_message_template ?? '') == $template->name ? 'selected' : ''}}>
                                {{$template->name}} ({{$template->language_label}})
                              </option>
                            @endforeach
                          </select>
                          <p class="text-muted">اختر قالب من القوالب المحفوظة أو اتركه فارغاً للرسالة العادية</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <a href="{{route('admin.whatsapp-templates.create')}}?type=welcome" target="_blank">إنشاء قالب جديد</a>
                          </small>
                        </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> حفظ إعدادات رسالة الترحيب
                        </button>
                        <button type="button" class="btn btn-warning ml-2" onclick="testWelcomeMessage()">
                          <i class="fas fa-paper-plane"></i> اختبار الرسالة
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Subscription Expiration Tab -->
              <div id="subscription_expiration-tab" class="tab-content {{request('tab') == 'subscription_expiration' ? 'active' : ''}}">
                <form action="{{route('admin.communication.subscription-expiration.update')}}" method="POST">
                  @csrf
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expiration_enabled">
                          <strong>تفعيل رسالة انتهاء الباقة</strong>
                        </label>
                        <div class="toggle-switch-container">
                          <div class="toggle-switch">
                            <input type="checkbox" id="subscription_expiration_enabled" name="subscription_expiration_enabled" 
                                   value="1" {{($abs->subscription_expiration_enabled ?? false) ? 'checked' : ''}}>
                            <label for="subscription_expiration_enabled" class="toggle-label">
                              <span class="toggle-slider"></span>
                              <span class="toggle-text">
                                <span class="toggle-on">ON</span>
                                <span class="toggle-off">OFF</span>
                              </span>
                            </label>
                          </div>
                          <span class="toggle-description">إرسال رسالة تنبيه عند انتهاء الباقة</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expiration_text"><strong>نص رسالة انتهاء الباقة **</strong></label>
                        <textarea class="form-control" id="subscription_expiration_text" name="subscription_expiration_text" rows="4" 
                                  placeholder="تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً...">{{$abs->subscription_expiration_text ?? ''}}</textarea>
                        <p class="text-muted">يمكن استخدام المتغيرات: {name}, {package_name}, {expiry_date}</p>
                        @error('subscription_expiration_text')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="subscription_expiration_days_before"><strong>عدد الأيام قبل الانتهاء</strong></label>
                        <input type="number" class="form-control" id="subscription_expiration_days_before" name="subscription_expiration_days_before" 
                               value="{{$abs->subscription_expiration_days_before ?? 3}}" min="1" max="30">
                        <p class="text-muted">إرسال التنبيه قبل انتهاء الباقة بـ</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                          <label for="subscription_expiration_template"><strong>اسم القالب (Meta API)</strong></label>
                          <select class="form-control" id="subscription_expiration_template" name="subscription_expiration_template">
                            <option value="">اختر قالب أو اتركه فارغاً</option>
                            @php
                                try {
                                    $subscriptionTemplates = \App\Models\WhatsAppTemplate::active()->ofType('subscription_expiration')->get();
                                } catch (Exception $e) {
                                    $subscriptionTemplates = collect();
                                }
                            @endphp
                            @foreach($subscriptionTemplates as $template)
                              <option value="{{$template->name}}" {{($abs->subscription_expiration_template ?? '') == $template->name ? 'selected' : ''}}>
                                {{$template->name}} ({{$template->language_label}})
                              </option>
                            @endforeach
                          </select>
                          <p class="text-muted">اختر قالب من القوالب المحفوظة أو اتركه فارغاً للرسالة العادية</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <a href="{{route('admin.whatsapp-templates.create')}}?type=subscription_expiration" target="_blank">إنشاء قالب جديد</a>
                          </small>
                        </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expiration_send_time"><strong>وقت إرسال التنبيه</strong></label>
                        <input type="time" class="form-control" id="subscription_expiration_send_time" name="subscription_expiration_send_time" 
                               value="{{$abs->subscription_expiration_send_time ?? '09:00'}}">
                        <p class="text-muted">الوقت اليومي لإرسال تنبيهات انتهاء الباقة</p>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> حفظ إعدادات رسالة انتهاء الباقة
                        </button>
                        <button type="button" class="btn btn-warning ml-2" onclick="testSubscriptionExpiration()">
                          <i class="fas fa-paper-plane"></i> اختبار الرسالة
                        </button>
                      </div>
                    </div>
                  </div>
                </form>
              </div>


            </div>
          </div>
        </div>

        <!-- Email Templates Tab -->
        <div id="email_templates-tab" class="tab-content {{request('tab') == 'email_templates' ? 'active' : ''}}">
          <form action="{{route('admin.communication.email-templates.update')}}" method="POST">
            @csrf
            <input type="hidden" name="type" value="email_templates">

            <div class="row">
              <div class="col-lg-12">
                <div class="form-group">
                  <label for="email_password_reset_template"><strong>قالب إعادة تعيين كلمة المرور (البريد الإلكتروني)</strong></label>
                  <select class="form-control" id="email_password_reset_template" name="email_password_reset_template">
                    <option value="">اختر قالب أو اتركه فارغاً</option>
                    @php
                        try {
                            $emailPasswordResetTemplates = \App\Models\EmailTemplate::active()->ofType('password_reset')->get();
                        } catch (Exception $e) {
                            $emailPasswordResetTemplates = collect();
                        }
                    @endphp
                    @foreach($emailPasswordResetTemplates as $template)
                      <option value="{{$template->name}}" {{($abs->email_password_reset_template ?? '') == $template->name ? 'selected' : ''}}>
                        {{$template->name}} ({{$template->language_label}})
                      </option>
                    @endforeach
                  </select>
                  <p class="text-muted">اختر قالب من القوالب المحفوظة أو اتركه فارغاً للرسالة العادية</p>
                  <small class="text-info">
                    <i class="fas fa-info-circle"></i> 
                    <a href="{{route('admin.email-templates.create')}}?type=password_reset" target="_blank">إنشاء قالب جديد</a>
                  </small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-lg-12">
                <div class="form-group">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ إعدادات البريد الإلكتروني
                  </button>
                  <button type="button" class="btn btn-info ml-2" onclick="testEmailTemplates()">
                    <i class="fas fa-paper-plane"></i> اختبار البريد الإلكتروني
                  </button>
                </div>
              </div>
            </div>
          </form>

          <!-- Email Configuration Status -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">
                    <i class="fas fa-cog"></i> حالة إعدادات البريد الإلكتروني
                  </h6>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><strong>خادم SMTP:</strong></label>
                        <p class="form-control-plaintext">
                          @if($abs->is_smtp == 1)
                            <span class="badge badge-success">مفعل</span>
                            <small class="text-muted">({{$abs->smtp_host ?? 'غير محدد'}})</small>
                          @else
                            <span class="badge badge-warning">غير مفعل</span>
                            <small class="text-muted">(استخدام PHP Mail)</small>
                          @endif
                        </p>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                        <label><strong>البريد الإلكتروني المرسل:</strong></label>
                        <p class="form-control-plaintext">
                          {{$abs->from_mail ?? 'غير محدد'}}
                        </p>
                      </div>
                    </div>
                  </div>
                  
                  @if($abs->is_smtp != 1)
                    <div class="alert alert-warning">
                      <i class="fas fa-exclamation-triangle"></i>
                      <strong>تحذير:</strong> إعدادات SMTP غير مفعلة. قد لا تعمل رسائل البريد الإلكتروني بشكل صحيح.
                      <a href="{{route('admin.basicinfo')}}" class="alert-link">إعداد SMTP</a>
                    </div>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <!-- Available Email Templates -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h6 class="mb-0">
                    <i class="fas fa-envelope"></i> القوالب الإلكترونية المتاحة
                  </h6>
                </div>
                <div class="card-body">
                  @php
                    try {
                        $allEmailTemplates = \App\Models\EmailTemplate::active()->get();
                    } catch (Exception $e) {
                        $allEmailTemplates = collect();
                    }
                  @endphp
                  
                  @if($allEmailTemplates->count() > 0)
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead>
                          <tr>
                            <th>اسم القالب</th>
                            <th>النوع</th>
                            <th>اللغة</th>
                            <th>الموضوع</th>
                            <th>الإجراءات</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($allEmailTemplates as $template)
                            <tr>
                              <td><strong>{{$template->name}}</strong></td>
                              <td><span class="badge badge-info">{{$template->type_label}}</span></td>
                              <td><span class="badge badge-secondary">{{$template->language_label}}</span></td>
                              <td>{{$template->subject ?? 'غير محدد'}}</td>
                              <td>
                                <a href="{{route('admin.email-templates.edit', $template)}}" class="btn btn-sm btn-warning" title="تعديل">
                                  <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{route('admin.email-templates.preview', $template)}}" class="btn btn-sm btn-info" title="معاينة">
                                  <i class="fas fa-eye"></i>
                                </a>
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @else
                    <div class="text-center text-muted">
                      <i class="fas fa-inbox fa-2x mb-2"></i><br>
                      لا توجد قوالب بريد إلكتروني بعد.
                      <a href="{{route('admin.email-templates.create')}}" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus"></i> إنشاء قالب جديد
                      </a>
                    </div>
                  @endif
                </div>
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
    // Initialize tab from URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'meta_evolution';
    console.log('Initializing with tab:', activeTab);
    switchTab(activeTab);
    
    // Auto-load Meta templates when Meta Cloud form is shown
    if (activeTab === 'meta_evolution') {
        setTimeout(function() {
            if ($('#meta-cloud-form').is(':visible')) {
                loadMetaTemplates();
            }
        }, 1000);
    }
    
    // Tab switching functionality
    $('.tab-button').click(function() {
        var tabName = $(this).data('tab');
        switchTab(tabName);
    });

    // Service card selection (within meta_evolution tab)
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
        
        // Auto-load Meta templates when Meta Cloud is selected
        if (service === 'meta_cloud') {
            setTimeout(function() {
                loadMetaTemplates();
            }, 500);
        }
        
        // Submit service selection
        $('#selected-service').val(service);
        $('#service-selection-form').submit();
    });

    // Toggle switch functionality (no additional JS needed as CSS handles the visual changes)
    // The toggle switches work automatically with the existing form submission
});

function switchTab(tabName) {
    console.log('Switching to tab:', tabName);
    
    // Update URL with tab parameter
    var url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
    
    // Update tab buttons
    $('.tab-button').removeClass('active');
    $('.tab-button[data-tab="' + tabName + '"]').addClass('active');
    
    // Update tab content
    $('.tab-content').removeClass('active');
    var targetTab = '#' + tabName + '-tab';
    console.log('Looking for tab element:', targetTab);
    $(targetTab).addClass('active');
    
    // Update card title
    var titles = {
        'meta_evolution': 'إعدادات Meta & Evolution API',
        'welcome_message': 'إعدادات رسالة الترحيب',
        'subscription_expiration': 'إعدادات رسالة انتهاء الباقة',
        'email_templates': 'إعدادات قوالب البريد الإلكتروني'
    };
    $('#tab-title').text(titles[tabName]);
}

function showTestModal() {
    $('#testModal').modal('show');
}



function testEmailTemplates() {
    var testEmail = prompt('أدخل البريد الإلكتروني للاختبار:');
    if (testEmail && testEmail.includes('@')) {
        // Send test email request
        $.post('{{route("admin.communication.test-email")}}', {
            _token: '{{csrf_token()}}',
            test_email: testEmail
        }, function(response) {
            if (response.success) {
                alert('تم إرسال رسالة اختبار بنجاح إلى: ' + testEmail);
            } else {
                alert('فشل في إرسال رسالة الاختبار: ' + (response.message || 'خطأ غير معروف'));
            }
        }).fail(function() {
            alert('فشل في إرسال رسالة الاختبار. تأكد من إعدادات SMTP.');
        });
    } else if (testEmail) {
        alert('يرجى إدخال بريد إلكتروني صحيح');
    }
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

function testWelcomeMessage() {
    var phone = prompt('أدخل رقم الهاتف للاختبار:', '+966501234567');
    if (phone) {
        $.post('{{route("admin.communication.welcome-message.test")}}', {
            _token: '{{csrf_token()}}',
            test_phone: phone
        }, function(response) {
            location.reload();
        }).fail(function() {
            alert('حدث خطأ أثناء اختبار رسالة الترحيب');
        });
    }
}

function testSubscriptionExpiration() {
    var phone = prompt('أدخل رقم الهاتف للاختبار:', '+966501234567');
    if (phone) {
        $.post('{{route("admin.communication.subscription-expiration.test")}}', {
            _token: '{{csrf_token()}}',
            test_phone: phone
        }, function(response) {
            location.reload();
        }).fail(function() {
            alert('حدث خطأ أثناء اختبار رسالة انتهاء الباقة');
        });
    }
}

function loadMetaTemplates() {
    var select = $('#meta_template_name');
    var button = $('button[onclick="loadMetaTemplates()"]');
    
    // Show loading state
    button.html('<i class="fas fa-spinner fa-spin"></i> جاري التحميل...');
    button.prop('disabled', true);
    
    $.get('{{route("admin.communication.fetch-meta-templates")}}', function(response) {
        if (response.success) {
            // Clear existing options except the first one
            select.find('option:not(:first)').remove();
            
            // Add templates from Facebook
            if (response.templates && response.templates.length > 0) {
                response.templates.forEach(function(template) {
                    var option = $('<option></option>')
                        .attr('value', template.name)
                        .text(template.name + ' (' + template.category + ' - ' + template.language + ')');
                    
                    // Check if this template is currently selected
                    if ('{{$abs->meta_template_name ?? ""}}' === template.name) {
                        option.attr('selected', true);
                    }
                    
                    select.append(option);
                });
                
                // Show success message
                showNotification('تم تحميل ' + response.templates.length + ' قالب من Facebook بنجاح', 'success');
            } else {
                showNotification('لم يتم العثور على قوالب معتمدة في Facebook', 'warning');
            }
        } else {
            showNotification('فشل في تحميل القوالب: ' + response.message, 'error');
        }
    }).fail(function() {
        showNotification('حدث خطأ في الاتصال بالخادم', 'error');
    }).always(function() {
        // Reset button state
        button.html('<i class="fas fa-sync"></i> تحديث القوالب من Facebook');
        button.prop('disabled', false);
    });
}

function showNotification(message, type) {
    var alertClass = 'alert-info';
    if (type === 'success') alertClass = 'alert-success';
    if (type === 'error') alertClass = 'alert-danger';
    if (type === 'warning') alertClass = 'alert-warning';
    
    var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
        message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>');
    
    // Insert notification at the top of the form
    $('#meta-cloud-form').prepend(notification);
    
    // Auto remove after 5 seconds
    setTimeout(function() {
        notification.alert('close');
    }, 5000);
}
</script>

<style>
/* Tab Navigation Styles */
.tab-navigation {
    background: #51c3a3;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-bar {
    display: flex;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.tab-button {
    flex: 1;
    padding: 12px 20px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #6c757d;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.tab-button:hover {
    background: #e9ecef;
    color: #495057;
}

.tab-button.active {
    background: #fff;
    color: #3a8b6f;
    border-bottom-color: #3a8b6f;
    font-weight: 600;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Service Card Styles */
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

/* Toggle Switch Styles */
.toggle-switch-container {
    margin-top: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
}

.toggle-switch input[type="checkbox"] {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    position: relative;
}

.toggle-slider {
    position: relative;
    width: 70px;
    height: 34px;
    background: linear-gradient(145deg, #f8f9fa, #e9ecef);
    border-radius: 34px;
    transition: all 0.3s ease;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.15);
    border: 2px solid #dee2e6;
}

.toggle-slider:before {
    content: "";
    position: absolute;
    height: 28px;
    width: 28px;
    left: 2px;
    bottom: 2px;
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    border-radius: 50%;
    transition: all 0.3s ease;
    box-shadow: 0 3px 6px rgba(0,0,0,0.25), 0 1px 2px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: 800;
    color: #2d7a5f;
    text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.toggle-switch input[type="checkbox"]:not(:checked) + .toggle-label .toggle-slider:before {
    content: "OFF";
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-slider:before {
    content: "ON";
}

.toggle-text {
    display: none;
}

.toggle-on {
    display: none;
}

.toggle-off {
    display: none;
}

.toggle-description {
    font-size: 14px;
    color: #6c757d;
    font-style: italic;
}

/* Toggle Switch Active State */
.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-slider {
    background: linear-gradient(145deg, #51c3a3, #2d7a5f);
    border-color: #2d7a5f;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.3), 0 0 0 1px rgba(81, 195, 163, 0.4);
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-slider:before {
    transform: translateX(36px);
    background: linear-gradient(145deg, #ffffff, #f8f9fa);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3), 0 2px 4px rgba(0,0,0,0.15);
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-on {
    opacity: 1;
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-off {
    opacity: 0;
}

/* Toggle Switch Hover Effect */
.toggle-switch:hover .toggle-slider {
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.15), 0 0 12px rgba(81, 195, 163, 0.4);
    border-color: #51c3a3;
}

.toggle-switch input[type="checkbox"]:checked + .toggle-label .toggle-slider:hover {
    background: linear-gradient(145deg, #5dd4b3, #42a085);
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.2), 0 0 12px rgba(81, 195, 163, 0.6);
}

.toggle-switch:hover .toggle-slider:before {
    box-shadow: 0 4px 10px rgba(0,0,0,0.3), 0 2px 6px rgba(0,0,0,0.2);
}

/* Toggle Switch Focus State */
.toggle-switch input[type="checkbox"]:focus + .toggle-label .toggle-slider {
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.15), 0 0 0 3px rgba(81, 195, 163, 0.3);
    outline: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .tab-button {
        padding: 10px 15px;
        font-size: 14px;
    }
}
</style>
@endsection
