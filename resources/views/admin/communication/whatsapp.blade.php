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
            <span class="tab-header-api"></span>
          </button>
          <button class="tab-button {{request('tab') == 'subscription_expiration' ? 'active' : ''}}" 
                  data-tab="subscription_expiration">
            اشعار قبل انتهاء الباقة
            <span class="tab-header-api"></span>
          </button>
          <button class="tab-button {{request('tab') == 'subscription_expired' ? 'active' : ''}}" 
                  data-tab="subscription_expired">
            إشعار انتهاء الباقة
            <span class="tab-header-api"></span>
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
                          <select class="form-control" id="meta_template_name" name="meta_template_name" onchange="console.log('Template changed to:', this.value); saveTemplateSelection()">
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

                    <!-- Template Testing Section -->
                    <div class="row" id="template-testing-section" style="display: none;">
                      <div class="col-lg-12">
                        <div class="card bg-light">
                          <div class="card-body">
                            <h6 class="card-title">
                              <i class="fas fa-flask"></i> اختبار القالب المحدد
                            </h6>
                            <div class="row">
                              <div class="col-lg-8">
                                <div class="form-group">
                                  <label for="selected_template_display"><strong>القالب المحدد للاختبار:</strong></label>
                                  <div class="input-group">
                                    <input type="text" class="form-control" id="selected_template_display" 
                                           value="{{$abs->meta_test_template_name ?? ''}}" readonly>
                                    <div class="input-group-append">
                                      <span class="input-group-text">
                                        <i class="fas fa-check-circle text-success" id="template-saved-icon" style="display: none;"></i>
                                        <i class="fas fa-exclamation-triangle text-warning" id="template-unsaved-icon"></i>
                                      </span>
                                    </div>
                                  </div>
                                  <small class="text-muted">سيتم حفظ القالب المحدد تلقائياً عند تغيير الاختيار</small>
                                </div>
                              </div>
                              <div class="col-lg-4">
                                <div class="form-group">
                                  <label for="test_phone_input"><strong>رقم الهاتف للاختبار:</strong></label>
                                  <input type="text" class="form-control" id="test_phone_input" 
                                         placeholder="+966501234567" value="+201147170572">
                                </div>
                              </div>
                            </div>
                            <div class="row">
                              <div class="col-lg-12">
                                <button type="button" class="btn btn-success" onclick="testSelectedTemplate()" id="test-template-btn">
                                  <i class="fas fa-paper-plane"></i> إرسال رسالة اختبار
                                </button>
                                <button type="button" class="btn btn-outline-secondary ml-2" onclick="clearTemplateSelection()">
                                  <i class="fas fa-times"></i> إلغاء الاختيار
                                </button>
                                <button type="button" class="btn btn-outline-info ml-2" onclick="console.log('Current template:', $('#selected_template_display').val()); console.log('Test section visible:', $('#template-testing-section').is(':visible'));">
                                  <i class="fas fa-bug"></i> Debug
                                </button>
                              </div>
                            </div>
                          </div>
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
                          <button type="button" class="btn btn-warning ml-2" onclick="testWhatsApp()">
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
                      <div class="col-lg-12">
                        <div class="form-group">
                          <label for="evolution_instance_name"><strong>Instance Name **</strong></label>
                          <input type="text" class="form-control" id="evolution_instance_name" name="evolution_instance_name" 
                                 value="{{$abs->evolution_instance_name ?? ''}}" placeholder="instance_name">
                          @error('evolution_instance_name')
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
                          <button type="button" class="btn btn-warning ml-2" onclick="testWhatsApp()">
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
                <form action="{{route('admin.communication.welcome-message.update')}}" method="POST" id="welcome-message-form">
                  @csrf
                  <input type="hidden" name="selected_api" id="welcome_selected_api" value="">
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="welcome_message_enabled">
                          <strong>تفعيل رسالة الترحيب</strong>
                          <span class="api-indicator" style="font-size: 12px; margin-left: 10px;"></span>
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
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="forceUpdateTextAreas()">
                          <i class="fas fa-sync"></i> تحديث النص حسب API المحدد
                        </button>
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
                            <optgroup label="Meta API Templates" class="meta-api-optgroup">
                              <option value="" disabled>جاري تحميل القوالب من Facebook...</option>
                            </optgroup>
                            <optgroup label="Local Templates" class="local-templates-optgroup">
                              @php
                                  try {
                                      $welcomeTemplates = \App\Models\WhatsAppTemplate::active()->ofType('welcome')->get();
                                  } catch (Exception $e) {
                                      $welcomeTemplates = collect();
                                  }
                              @endphp
                              @foreach($welcomeTemplates as $template)
                                <option value="{{$template->name}}" {{($abs->welcome_message_template ?? '') == $template->name ? 'selected' : ''}}>
                                  {{$template->name}} ({{$template->language_label}}) - Local
                                </option>
                              @endforeach
                            </optgroup>
                          </select>
                          <p class="text-muted">اختر قالب من Meta API أو القوالب المحفوظة محلياً</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="loadMetaTemplatesForWelcome(true)">
                              <i class="fas fa-sync"></i> تحديث قوالب Meta API
                            </button>
                            <small class="text-muted ml-2">(يتم التحميل تلقائياً ويتم حفظه لمدة 24 ساعة)</small>
                            <a href="{{route('admin.whatsapp-templates.create')}}?type=welcome" target="_blank" class="btn btn-sm btn-outline-success ml-2 create-local-template-btn">
                              <i class="fas fa-plus"></i> إنشاء قالب محلي
                            </a>
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
                <form action="{{route('admin.communication.subscription-expiration.update')}}" method="POST" id="subscription-expiration-form">
                  @csrf
                  <input type="hidden" name="selected_api" id="subscription_expiration_selected_api" value="">
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expiration_enabled">
                          <strong>تفعيل رسالة انتهاء الباقة</strong>
                          <span class="api-indicator" style="font-size: 12px; margin-left: 10px;"></span>
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
                          <span class="toggle-description">إرسال رسالة تنبيه قبل انتهاء الباقة</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expiration_text"><strong>نص رسالة انتهاء الباقة **</strong></label>
                        <textarea class="form-control" id="subscription_expiration_text" name="subscription_expiration_text" rows="4" 
                                  placeholder="تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً...">{{$abs->subscription_expiration_text ?? 'تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.'}}</textarea>
                        <p class="text-muted">يمكن استخدام المتغيرات: {name}, {package_name}, {expiry_date}</p>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="forceUpdateTextAreas()">
                          <i class="fas fa-sync"></i> تحديث النص حسب API المحدد
                        </button>
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
                            <optgroup label="Meta API Templates" class="meta-api-optgroup">
                              <option value="" disabled>جاري تحميل القوالب من Facebook...</option>
                            </optgroup>
                            <optgroup label="Local Templates" class="local-templates-optgroup">
                              @php
                                  try {
                                      $subscriptionTemplates = \App\Models\WhatsAppTemplate::active()->ofType('subscription_expiration')->get();
                                  } catch (Exception $e) {
                                      $subscriptionTemplates = collect();
                                  }
                              @endphp
                              @foreach($subscriptionTemplates as $template)
                                <option value="{{$template->name}}" {{($abs->subscription_expiration_template ?? '') == $template->name ? 'selected' : ''}}>
                                  {{$template->name}} ({{$template->language_label}}) - Local
                                </option>
                              @endforeach
                            </optgroup>
                          </select>
                          <p class="text-muted">اختر قالب من Meta API أو القوالب المحفوظة محلياً</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="loadMetaTemplatesForSubscription(true)">
                              <i class="fas fa-sync"></i> تحديث قوالب Meta API
                            </button>
                            <small class="text-muted ml-2">(يتم التحميل تلقائياً ويتم حفظه لمدة 24 ساعة)</small>
                            <a href="{{route('admin.whatsapp-templates.create')}}?type=subscription_expiration" target="_blank" class="btn btn-sm btn-outline-success ml-2 create-local-template-btn">
                              <i class="fas fa-plus"></i> إنشاء قالب محلي
                            </a>
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

              <!-- On Expiration Notification Tab -->
              <div id="subscription_expired-tab" class="tab-content {{request('tab') == 'subscription_expired' ? 'active' : ''}}">
                <form action="{{route('admin.communication.subscription-expired.update')}}" method="POST" id="subscription-expired-form">
                  @csrf
                  <input type="hidden" name="selected_api" id="subscription_expired_selected_api" value="">
                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expired_enabled">
                          <strong>تفعيل إشعار انتهاء الباقة</strong>
                          <span class="api-indicator" style="font-size: 12px; margin-left: 10px;"></span>
                        </label>
                        <div class="toggle-switch-container">
                          <div class="toggle-switch">
                            <input type="checkbox" id="subscription_expired_enabled" name="subscription_expired_enabled" 
                                   value="1" {{($abs->subscription_expired_enabled ?? false) ? 'checked' : ''}}>
                            <label for="subscription_expired_enabled" class="toggle-label">
                              <span class="toggle-slider"></span>
                              <span class="toggle-text">
                                <span class="toggle-on">ON</span>
                                <span class="toggle-off">OFF</span>
                              </span>
                            </label>
                          </div>
                          <span class="toggle-description">إرسال إشعار عند انتهاء الباقة فعلياً</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <label for="subscription_expired_text"><strong>نص إشعار انتهاء الباقة **</strong></label>
                        <textarea class="form-control" id="subscription_expired_text" name="subscription_expired_text" rows="4" placeholder="مرحبا {name}
انتهى اشتراكك وتم نقلك إلى الباقة المجانية.
يمكنك الترقية في أي وقت.">{{trim($abs->subscription_expired_text ?? 'مرحبا {name}
انتهى اشتراكك وتم نقلك إلى الباقة المجانية.
يمكنك الترقية في أي وقت.')}}</textarea>
                        <p class="text-muted">يمكن استخدام المتغيرات: {name}, {package_name}, {expiry_date}</p>
                        <button type="button" class="btn btn-sm btn-outline-info mt-2" onclick="forceUpdateTextAreas()">
                          <i class="fas fa-sync"></i> تحديث النص حسب API المحدد
                        </button>
                        @error('subscription_expired_text')
                          <p class="text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                          <label for="subscription_expired_template"><strong>اسم القالب (Meta API)</strong></label>
                          <select class="form-control" id="subscription_expired_template" name="subscription_expired_template">
                            <option value="">اختر قالب أو اتركه فارغاً</option>
                            <optgroup label="Meta API Templates" class="meta-api-optgroup">
                              <option value="" disabled>جاري تحميل القوالب من Facebook...</option>
                            </optgroup>
                            <optgroup label="Local Templates" class="local-templates-optgroup">
                              @php
                                  try {
                                      $expiredTemplates = \App\Models\WhatsAppTemplate::active()->ofType('subscription_expired')->get();
                                  } catch (Exception $e) {
                                      $expiredTemplates = collect();
                                  }
                              @endphp
                              @foreach($expiredTemplates as $template)
                                <option value="{{$template->name}}" {{($abs->subscription_expired_template ?? '') == $template->name ? 'selected' : ''}}>
                                  {{$template->name}} ({{$template->language_label}}) - Local
                                </option>
                              @endforeach
                            </optgroup>
                          </select>
                          <p class="text-muted">اختر قالب من Meta API أو القوالب المحفوظة محلياً</p>
                          <small class="text-info">
                            <i class="fas fa-info-circle"></i> 
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="loadMetaTemplatesForExpired(true)">
                              <i class="fas fa-sync"></i> تحديث قوالب Meta API
                            </button>
                            <small class="text-muted ml-2">(يتم التحميل تلقائياً ويتم حفظه لمدة 24 ساعة)</small>
                            <a href="{{route('admin.whatsapp-templates.create')}}?type=subscription_expired" target="_blank" class="btn btn-sm btn-outline-success ml-2 create-local-template-btn">
                              <i class="fas fa-plus"></i> إنشاء قالب محلي
                            </a>
                          </small>
                        </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="subscription_expired_send_time"><strong>وقت إرسال الإشعار</strong></label>
                        <input type="time" class="form-control" id="subscription_expired_send_time" name="subscription_expired_send_time" 
                               value="{{$abs->subscription_expired_send_time ?? '09:00'}}">
                        <p class="text-muted">الوقت اليومي لإرسال إشعارات انتهاء الباقة</p>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-lg-12">
                      <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> حفظ إعدادات إشعار انتهاء الباقة
                        </button>
                        <button type="button" class="btn btn-warning ml-2" onclick="testSubscriptionExpired()">
                          <i class="fas fa-paper-plane"></i> اختبار الإشعار
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
    // Initialize tab from URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'meta_evolution';
    console.log('Initializing with tab:', activeTab);
    
    // Initialize API selection system
    initializeApiSelection();
    
    // Auto-load templates based on selected API
    loadTemplatesBasedOnApi();
    
    switchTab(activeTab);
    
    // API selection is now handled by service card clicks
    
    // Add form submission handlers to include API selection
    $('#welcome-message-form').submit(function() {
        $('#welcome_selected_api').val(currentApi);
    });
    
    $('#subscription-expiration-form').submit(function() {
        $('#subscription_expiration_selected_api').val(currentApi);
    });
    
    $('#subscription-expired-form').submit(function() {
        $('#subscription_expired_selected_api').val(currentApi);
    });
    
    // Auto-load Meta templates when Meta Cloud form is shown
    if (activeTab === 'meta_evolution') {
        setTimeout(function() {
            if ($('#meta-cloud-form').is(':visible')) {
                loadMetaTemplates();
                initializeTemplateSelection();
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
        
        // Update API selection for our context-aware system
        var api = service === 'meta_cloud' ? 'meta' : 'evolution';
        setApiSelection(api);
        
        // Auto-load Meta templates when Meta Cloud is selected
        if (service === 'meta_cloud') {
            setTimeout(function() {
                loadMetaTemplates();
                initializeTemplateSelection();
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
        'subscription_expiration': 'إعدادات رسالة انتهاء الباقة'
    };
    $('#tab-title').text(titles[tabName]);
}

function showTestModal() {
    $('#testModal').modal('show');
}

function testWhatsApp() {
    var phone = prompt('أدخل رقم الهاتف للاختبار:', '+201147170572');
    if (phone) {
        console.log('Testing WhatsApp with phone:', phone);
        console.log('Route URL:', '{{route("admin.communication.test-whatsapp")}}');
        
        $.post('{{route("admin.communication.test-whatsapp")}}', {
            _token: '{{csrf_token()}}',
            test_phone: phone
        }, function(response) {
            console.log('Test response:', response);
            location.reload();
        }).fail(function(xhr) {
            console.error('WhatsApp test failed:', xhr);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);
            alert('حدث خطأ أثناء اختبار واتس اب: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
        });
    }
}




function checkConfiguration() {
    console.log('Checking configuration...');
    console.log('Route URL:', '{{route("admin.communication.check-config")}}');
    
    $.post('{{route("admin.communication.check-config")}}', {
        _token: '{{csrf_token()}}'
    }, function(response) {
        console.log('Configuration check response:', response);
        // The response will be handled by the redirect with flash message
        location.reload();
    }).fail(function(xhr) {
        console.error('Configuration check failed:', xhr);
        console.error('Status:', xhr.status);
        console.error('Response:', xhr.responseText);
        alert('حدث خطأ أثناء فحص الإعدادات: ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'));
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

function testSubscriptionExpired() {
    var phone = prompt('أدخل رقم الهاتف للاختبار:', '+966501234567');
    if (phone) {
        $.post('{{route("admin.communication.subscription-expired.test")}}', {
            _token: '{{csrf_token()}}',
            test_phone: phone
        }, function(response) {
            location.reload();
        }).fail(function() {
            alert('حدث خطأ أثناء اختبار إشعار انتهاء الباقة');
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
                    
                    // Check if this template is currently selected or if it's the saved test template
                    var savedTestTemplate = '{{$abs->meta_test_template_name ?? ""}}';
                    var currentTemplate = '{{$abs->meta_template_name ?? ""}}';
                    if (currentTemplate === template.name || savedTestTemplate === template.name) {
                        option.attr('selected', true);
                    }
                    
                    select.append(option);
                });
                
                // Show success message
                showNotification('تم تحميل ' + response.templates.length + ' قالب من Facebook بنجاح', 'success');
                
                // Initialize template selection after loading
                setTimeout(function() {
                    initializeTemplateSelection();
                }, 500);
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

function loadMetaTemplatesForWelcome(forceRefresh = false) {
    if (forceRefresh) {
        clearTemplateCache('welcome');
    }
    loadMetaTemplatesForSelect('#welcome_message_template', 'welcome');
}

function loadMetaTemplatesForSubscription(forceRefresh = false) {
    if (forceRefresh) {
        clearTemplateCache('subscription_expiration');
    }
    loadMetaTemplatesForSelect('#subscription_expiration_template', 'subscription_expiration');
}

function loadMetaTemplatesForExpired(forceRefresh = false) {
    if (forceRefresh) {
        clearTemplateCache('subscription_expired');
    }
    loadMetaTemplatesForSelect('#subscription_expired_template', 'subscription_expired');
}

function clearTemplateCache(messageType) {
    var cacheKey = 'meta_templates_' + messageType;
    localStorage.removeItem(cacheKey);
    localStorage.removeItem(cacheKey + '_timestamp');
    console.log('Cache cleared for:', messageType);
}

// API Selection Management
var currentApi = 'meta'; // Default to Meta

function initializeApiSelection() {
    // Get current API selection from localStorage or default to Meta
    var savedApi = localStorage.getItem('whatsapp_api_selection');
    if (savedApi) {
        currentApi = savedApi;
    }
    
    // Set the service card selection based on saved API
    if (currentApi === 'meta') {
        // Select Meta Cloud card
        $('.service-card[data-service="meta_cloud"]').addClass('border-primary');
        $('.service-card[data-service="evolution_api"]').removeClass('border-primary');
        $('.service-card[data-service="meta_cloud"] .service-status .badge').removeClass('badge-secondary').addClass('badge-success').text('مفعل');
        $('.service-card[data-service="evolution_api"] .service-status .badge').removeClass('badge-success').addClass('badge-secondary').text('غير مفعل');
        $('.service-form').hide();
        $('#meta-cloud-form').show();
    } else {
        // Select Evolution API card
        $('.service-card[data-service="evolution_api"]').addClass('border-primary');
        $('.service-card[data-service="meta_cloud"]').removeClass('border-primary');
        $('.service-card[data-service="evolution_api"] .service-status .badge').removeClass('badge-secondary').addClass('badge-success').text('مفعل');
        $('.service-card[data-service="meta_cloud"] .service-status .badge').removeClass('badge-success').addClass('badge-secondary').text('غير مفعل');
        $('.service-form').hide();
        $('#evolution-api-form').show();
    }
    
    console.log('API Selection initialized:', currentApi);
    updateApiIndicators();
    
    // Set initial button visibility based on API selection
    if (currentApi === 'meta') {
        $('.create-local-template-btn').hide();
    } else {
        $('.create-local-template-btn').show();
    }
    
    // Show initial notification
    var apiName = currentApi === 'meta' ? 'Meta Cloud API' : 'Evolution API';
    console.log('Current API:', apiName);
}

function setApiSelection(api) {
    currentApi = api;
    localStorage.setItem('whatsapp_api_selection', api);
    console.log('API Selection changed to:', api);
    
    // Update visual indicators
    updateApiIndicators();
    
    // Update templates based on new API
    loadTemplatesBasedOnApi();
    
    // Update default texts
    updateDefaultTexts();
    
    // Show notification
    var apiName = api === 'meta' ? 'Meta Cloud API' : 'Evolution API';
    showNotification('تم التبديل إلى ' + apiName + ' - تم تحديث النصوص والقالب', 'info');
}

function forceUpdateTextAreas() {
    // Force update all textareas with current API's default texts
    if (currentApi === 'meta') {
        updateTextsForMeta();
    } else {
        updateTextsForEvolution();
    }
    console.log('Textareas force updated for:', currentApi);
}

function updateApiIndicators() {
    var apiName = currentApi === 'meta' ? 'Meta Cloud API' : 'Evolution API';
    var apiColor = currentApi === 'meta' ? '#51c3a3' : '#007bff';
    
    // Update all API indicators
    $('.api-indicator').each(function() {
        $(this).text(apiName);
        $(this).css('color', apiColor);
    });
    
    // Update tab headers with API indicator
    $('.tab-header-api').each(function() {
        $(this).text('(' + apiName + ')');
        $(this).css('color', apiColor);
    });
}

function loadTemplatesBasedOnApi() {
    if (currentApi === 'meta') {
        // For Meta API, load Meta API templates and hide local templates
        console.log('Meta API selected - using Meta API templates only');
        updateTemplateDropdownsForMeta();
        setTimeout(function() { loadMetaTemplatesForWelcome(); }, 500);
        setTimeout(function() { loadMetaTemplatesForSubscription(); }, 1000);
        setTimeout(function() { loadMetaTemplatesForExpired(); }, 1500);
    } else {
        // For Evolution API, only load local templates
        console.log('Evolution API selected - using local templates only');
        updateTemplateDropdownsForEvolution();
    }
}

function updateDefaultTexts() {
    if (currentApi === 'meta') {
        updateTextsForMeta();
    } else {
        updateTextsForEvolution();
    }
}

function updateTextsForMeta() {
    // Meta API default texts
    var metaTexts = {
        welcome: 'مرحبا {name}\nأهلاً وسهلاً بك في منصتنا!\nنتمنى لك تجربة ممتعة.',
        subscription_expiration: 'مرحبا {name}\nتنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.\nيرجى تجديد اشتراكك للاستمرار في الاستفادة من خدماتنا.',
        subscription_expired: 'مرحبا {name}\nانتهى اشتراكك وتم نقلك إلى الباقة المجانية.\nيمكنك الترقية في أي وقت.'
    };
    
    updateTextAreas(metaTexts);
}

function updateTextsForEvolution() {
    // Evolution API default texts
    var evolutionTexts = {
        welcome: 'مرحبا {name}\nمرحباً بك في منصتنا!\nنحن سعداء لانضمامك إلينا.',
        subscription_expiration: 'مرحبًا {name} 👋،\nنود تذكيرك أن باقتك {package_name} ستنتهي بتاريخ {expiry_date}.\nتبقى 3 أيام فقط للاستفادة من خدماتك قبل انتهاء الباقة.\n\n🔄 جدّد الآن لتفادي انقطاع الخدمة.',
        subscription_expired: 'مرحبا {name}\nانتهى اشتراكك.\nيمكنك الترقية في أي وقت.'
    };
    
    updateTextAreas(evolutionTexts);
}

function updateTextAreas(texts) {
    // Update welcome message text
    if ($('#welcome_message_text').length) {
        var currentText = $('#welcome_message_text').val().trim();
        // Only update if textarea is empty or contains default text
        if (!currentText || isDefaultText(currentText, 'welcome')) {
            $('#welcome_message_text').val(texts.welcome);
        }
    }
    
    // Update subscription expiration text
    if ($('#subscription_expiration_text').length) {
        var currentText = $('#subscription_expiration_text').val().trim();
        if (!currentText || isDefaultText(currentText, 'subscription_expiration')) {
            $('#subscription_expiration_text').val(texts.subscription_expiration);
        }
    }
    
    // Update subscription expired text
    if ($('#subscription_expired_text').length) {
        var currentText = $('#subscription_expired_text').val().trim();
        if (!currentText || isDefaultText(currentText, 'subscription_expired')) {
            $('#subscription_expired_text').val(texts.subscription_expired);
        }
    }
}

function isDefaultText(text, type) {
    // Check if the text matches any of the default texts
    var defaultTexts = {
        welcome: [
            'مرحباً بك في منصتنا! شكراً لك على التسجيل...',
            'مرحبا {name}\nأهلاً وسهلاً بك في منصتنا!\nنتمنى لك تجربة ممتعة.',
            'مرحبا {name}\nمرحباً بك في منصتنا!\nنحن سعداء لانضمامك إلينا.'
        ],
        subscription_expiration: [
            'تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.',
            'مرحبا {name}\nتنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.\nيرجى تجديد اشتراكك للاستمرار في الاستفادة من خدماتنا.',
            'مرحبا {name}\nتنبيه: اشتراكك سينتهي قريباً.\nيرجى تجديد الاشتراك للاستمرار.',
            'مرحبًا {name} 👋،\nنود تذكيرك أن باقتك {package_name} ستنتهي بتاريخ {expiry_date}.\nتبقى 3 أيام فقط للاستفادة من خدماتك قبل انتهاء الباقة.\n\n🔄 جدّد الآن لتفادي انقطاع الخدمة.'
        ],
        subscription_expired: [
            'انتهى اشتراكك وتم نقلك إلى الباقة المجانية. يمكنك الترقية في أي وقت.',
            'مرحبا {name}\nانتهى اشتراكك وتم نقلك إلى الباقة المجانية.\nيمكنك الترقية في أي وقت.',
            'مرحبا {name}\nانتهى اشتراكك.\nيمكنك الترقية في أي وقت.'
        ]
    };
    
    return defaultTexts[type].some(defaultText => text === defaultText);
}

function updateTemplateDropdownsForEvolution() {
    // For Evolution API, clear Meta API templates and show only local templates
    $('.meta-api-optgroup').each(function() {
        $(this).empty();
        $(this).append('<option disabled>Evolution API - استخدم القوالب المحلية فقط</option>');
    });
    
    // Show "Create Local Template" buttons for Evolution API
    $('.create-local-template-btn').show();
}

function updateTemplateDropdownsForMeta() {
    // For Meta API, clear local templates and show only Meta API templates
    $('.local-templates-optgroup').each(function() {
        $(this).empty();
        $(this).append('<option disabled>Meta API - استخدم قوالب Meta API فقط</option>');
    });
    
    // Hide "Create Local Template" buttons for Meta API
    $('.create-local-template-btn').hide();
}

function loadMetaTemplatesForSelect(selectId, messageType) {
    var select = $(selectId);
    var button = $('button[onclick="loadMetaTemplatesFor' + messageType.charAt(0).toUpperCase() + messageType.slice(1).replace('_', '') + '()"]');
    
    // Only load Meta templates if Meta API is selected
    if (currentApi !== 'meta') {
        console.log('Meta API not selected, skipping template load for:', messageType);
        return;
    }
    
    // Check if templates are already loaded (cached)
    var cacheKey = 'meta_templates_' + messageType;
    var cachedTemplates = localStorage.getItem(cacheKey);
    var cacheTimestamp = localStorage.getItem(cacheKey + '_timestamp');
    var now = new Date().getTime();
    var cacheExpiry = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    
    // If we have cached templates and they're not expired, use them
    if (cachedTemplates && cacheTimestamp && (now - parseInt(cacheTimestamp)) < cacheExpiry) {
        console.log('Using cached templates for:', messageType);
        var templates = JSON.parse(cachedTemplates);
        populateTemplatesInSelect(select, templates, messageType);
        return;
    }
    
    // Show loading state
    button.html('<i class="fas fa-spinner fa-spin"></i> جاري التحميل...');
    button.prop('disabled', true);
    
    $.get('{{route("admin.communication.fetch-meta-templates")}}', function(response) {
        if (response.success) {
            // Cache the templates
            localStorage.setItem(cacheKey, JSON.stringify(response.templates));
            localStorage.setItem(cacheKey + '_timestamp', now.toString());
            console.log('Templates cached for:', messageType);
            
            // Populate the select with templates
            populateTemplatesInSelect(select, response.templates, messageType);
            
            if (response.templates && response.templates.length > 0) {
                showNotification('تم تحميل ' + response.templates.length + ' قالب من Meta API بنجاح', 'success');
            } else {
                showNotification('لم يتم العثور على قوالب معتمدة في Meta API', 'warning');
            }
        } else {
            var metaGroup = select.find('optgroup[label="Meta API Templates"]');
            metaGroup.empty();
            metaGroup.append('<option value="" disabled>فشل في تحميل القوالب: ' + response.message + '</option>');
            showNotification('فشل في تحميل القوالب: ' + response.message, 'error');
        }
    }).fail(function() {
        var metaGroup = select.find('optgroup[label="Meta API Templates"]');
        metaGroup.empty();
        metaGroup.append('<option value="" disabled>حدث خطأ في الاتصال بالخادم</option>');
        showNotification('حدث خطأ في الاتصال بالخادم', 'error');
    }).always(function() {
        // Reset button state
        button.html('<i class="fas fa-sync"></i> تحديث قوالب Meta API');
        button.prop('disabled', false);
    });
}

function populateTemplatesInSelect(select, templates, messageType) {
    // Only populate Meta API templates if Meta API is selected
    if (currentApi !== 'meta') {
        console.log('Not Meta API, skipping template population');
        return;
    }
    
    // Find and update the Meta API Templates optgroup
    var metaGroup = select.find('optgroup[label="Meta API Templates"]');
    metaGroup.empty();
    
    // Add templates from Meta API
    if (templates && templates.length > 0) {
        templates.forEach(function(template) {
            var option = $('<option></option>')
                .attr('value', template.name)
                .text(template.name + ' (' + template.category + ' - ' + template.language + ') - Meta API');
            
            // Check if this template is currently selected
            var currentValue = '';
            if (messageType === 'welcome') {
                currentValue = '{{$abs->welcome_message_template ?? ""}}';
            } else if (messageType === 'subscription_expiration') {
                currentValue = '{{$abs->subscription_expiration_template ?? ""}}';
            } else if (messageType === 'subscription_expired') {
                currentValue = '{{$abs->subscription_expired_template ?? ""}}';
            }
            
            if (currentValue === template.name) {
                option.attr('selected', true);
            }
            
            metaGroup.append(option);
        });
    } else {
        metaGroup.append('<option value="" disabled>لم يتم العثور على قوالب معتمدة في Meta API</option>');
    }
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

// Template Selection and Testing Functions
function saveTemplateSelection() {
    var selectedTemplate = $('#meta_template_name').val();
    
    console.log('Template selection changed:', selectedTemplate);
    
    if (selectedTemplate) {
        // Show the testing section
        $('#template-testing-section').show();
        $('#selected_template_display').val(selectedTemplate);
        
        // Save to server
        console.log('Saving template to server:', selectedTemplate);
        $.post('{{route("admin.communication.save-selected-template")}}', {
            _token: '{{csrf_token()}}',
            template_name: selectedTemplate
        }, function(response) {
            console.log('Save response:', response);
            if (response.success) {
                $('#template-saved-icon').show();
                $('#template-unsaved-icon').hide();
                showNotification('تم حفظ القالب للاختبار: ' + selectedTemplate, 'success');
            } else {
                $('#template-saved-icon').hide();
                $('#template-unsaved-icon').show();
                showNotification('فشل في حفظ القالب: ' + response.message, 'error');
            }
        }).fail(function() {
            $('#template-saved-icon').hide();
            $('#template-unsaved-icon').show();
            showNotification('حدث خطأ أثناء حفظ القالب', 'error');
        });
    } else {
        // Hide the testing section if no template selected
        $('#template-testing-section').hide();
        $('#template-saved-icon').hide();
        $('#template-unsaved-icon').hide();
    }
}

function testSelectedTemplate() {
    var phoneNumber = $('#test_phone_input').val();
    var selectedTemplate = $('#selected_template_display').val();
    
    if (!phoneNumber) {
        alert('يرجى إدخال رقم الهاتف للاختبار');
        return;
    }
    
    if (!selectedTemplate) {
        alert('يرجى اختيار قالب أولاً');
        return;
    }
    
    // Disable button and show loading
    var btn = $('#test-template-btn');
    var originalText = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...');
    
    $.post('{{route("admin.communication.test-selected-template")}}', {
        _token: '{{csrf_token()}}',
        test_phone: phoneNumber
    }, function(response) {
        if (response.success) {
            showNotification(response.message, 'success');
        } else {
            showNotification(response.message, 'error');
        }
    }).fail(function() {
        showNotification('حدث خطأ أثناء إرسال رسالة الاختبار', 'error');
    }).always(function() {
        // Re-enable button
        btn.prop('disabled', false).html(originalText);
    });
}

function clearTemplateSelection() {
    $('#meta_template_name').val('');
    $('#selected_template_display').val('');
    $('#template-testing-section').hide();
    $('#template-saved-icon').hide();
    $('#template-unsaved-icon').hide();
    
    // Clear from server
    $.post('{{route("admin.communication.save-selected-template")}}', {
        _token: '{{csrf_token()}}',
        template_name: ''
    }, function(response) {
        if (response.success) {
            showNotification('تم إلغاء اختيار القالب', 'info');
        }
    });
}

// Initialize template selection on page load
function initializeTemplateSelection() {
    var savedTemplate = '{{$abs->meta_test_template_name ?? ""}}';
    if (savedTemplate) {
        $('#meta_template_name').val(savedTemplate);
        $('#selected_template_display').val(savedTemplate);
        $('#template-testing-section').show();
        $('#template-saved-icon').show();
        $('#template-unsaved-icon').hide();
    }
    
    // Also check if there's a regular template selected
    var currentTemplate = $('#meta_template_name').val();
    if (currentTemplate && !savedTemplate) {
        $('#template-testing-section').show();
        $('#selected_template_display').val(currentTemplate);
        $('#template-saved-icon').hide();
        $('#template-unsaved-icon').show();
    }
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

/* API Indicators */
.api-indicator {
    font-weight: normal;
    font-style: italic;
    opacity: 0.8;
}

.tab-header-api {
    font-size: 10px;
    font-weight: normal;
    display: block;
    margin-top: 2px;
    opacity: 0.7;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .tab-button {
        padding: 10px 15px;
        font-size: 14px;
    }
    
    .tab-header-api {
        font-size: 9px;
    }
}
</style>
@endsection
