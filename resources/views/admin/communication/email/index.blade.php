@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">البريد الإلكتروني</h4>
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
        <a href="#">البريد الإلكتروني</a>
      </li>
    </ul>
  </div>

  <!-- Master Toggle for All Email Notifications -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-light border-success">
        <div class="card-body py-3">
          <form action="{{route('admin.communication.master-toggle.email')}}" method="POST" id="master-email-toggle-form">
            @csrf
            <div class="row align-items-center">
              <div class="col-md-8">
                <h5 class="mb-2">
                  <i class="fas fa-power-off"></i>
                  <strong>التحكم الرئيسي في إشعارات البريد الإلكتروني</strong>
                </h5>
                <p class="mb-0 text-muted">
                  <i class="fas fa-info-circle"></i>
                  استخدم هذا الزر للتحكم في جميع رسائل البريد الإلكتروني دفعة واحدة (رسائل الترحيب، انتهاء الباقة، إعادة تعيين كلمة المرور، إلخ)
                </p>
              </div>
              <div class="col-md-4 text-right">
                <div class="master-toggle-container">
                  <div class="toggle-switch toggle-switch-lg">
                    <input type="checkbox" id="email_notifications_enabled" name="email_notifications_enabled"
                           value="1" {{($abs->email_notifications_enabled ?? true) ? 'checked' : ''}}
                           onchange="document.getElementById('master-email-toggle-form').submit()">
                    <label for="email_notifications_enabled" class="toggle-label">
                      <span class="toggle-slider"></span>
                      <span class="toggle-text">
                        <span class="toggle-on">تفعيل الكل</span>
                        <span class="toggle-off">إيقاف الكل</span>
                      </span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Navigation -->
  <div class="row mb-4">
    <div class="col-12">
      <div class="tab-navigation">
        <div class="tab-bar">
          <button class="tab-button {{(request('tab') == 'smtp_settings' || !request('tab')) ? 'active' : ''}}"
                  data-tab="smtp_settings">
            إعدادات SMTP
          </button>
          <button class="tab-button {{request('tab') == 'email_templates' ? 'active' : ''}}"
                  data-tab="email_templates">
            إعدادات القوالب
          </button>
          <button class="tab-button {{request('tab') == 'test_email' ? 'active' : ''}}"
                  data-tab="test_email">
            اختبار البريد الإلكتروني
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tab Content -->
  <div class="row">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h4 id="tab-title" class="card-title">إعدادات SMTP</h4>
            <div class="card-tools">
              <a href="{{route('admin.email-templates.index')}}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-cog"></i> إدارة القوالب
              </a>
            </div>
          </div>
        </div>
        <div class="card-body">

          <!-- SMTP Settings Tab -->
          <div id="smtp_settings-tab" class="tab-content {{(request('tab') == 'smtp_settings' || !request('tab')) ? 'active' : ''}}">
            <form action="{{route('admin.email-communication.smtp.update')}}" method="POST">
              @csrf
              <div class="row">
                <div class="col-lg-12">
                  <div class="form-group">
                    <label class="form-label">تفعيل SMTP</label>
                    <div class="custom-control custom-switch">
                      <input type="checkbox" class="custom-control-input" id="is_smtp" name="is_smtp" value="1"
                             {{($abs->is_smtp ?? 0) ? 'checked' : ''}}>
                      <label class="custom-control-label" for="is_smtp">استخدام SMTP لإرسال البريد الإلكتروني</label>
                    </div>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="smtp_host"><strong>خادم SMTP</strong></label>
                    <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                           value="{{$abs->smtp_host ?? ''}}" placeholder="smtp.gmail.com">
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="smtp_port"><strong>منفذ SMTP</strong></label>
                    <input type="text" class="form-control" id="smtp_port" name="smtp_port"
                           value="{{$abs->smtp_port ?? '587'}}" placeholder="587">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="smtp_username"><strong>اسم المستخدم</strong></label>
                    <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                           value="{{$abs->smtp_username ?? ''}}" placeholder="your-email@gmail.com">
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="smtp_password"><strong>كلمة المرور</strong></label>
                    <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                           value="{{$abs->smtp_password ?? ''}}" placeholder="كلمة المرور">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="encryption"><strong>التشفير</strong></label>
                    <select class="form-control" id="encryption" name="encryption">
                      <option value="TLS" {{($abs->encryption ?? 'TLS') == 'TLS' ? 'selected' : ''}}>TLS</option>
                      <option value="SSL" {{($abs->encryption ?? '') == 'SSL' ? 'selected' : ''}}>SSL</option>
                    </select>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="from_mail"><strong>البريد الإلكتروني المرسل</strong></label>
                    <input type="email" class="form-control" id="from_mail" name="from_mail"
                           value="{{$abs->from_mail ?? ''}}" placeholder="noreply@example.com">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <div class="form-group">
                    <label for="from_name"><strong>اسم المرسل</strong></label>
                    <input type="text" class="form-control" id="from_name" name="from_name"
                           value="{{$abs->from_name ?? ''}}" placeholder="اسم الشركة">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ الإعدادات
                  </button>
                </div>
              </div>
            </form>
          </div>

          <!-- Email Templates Settings Tab -->
          <div id="email_templates-tab" class="tab-content {{request('tab') == 'email_templates' ? 'active' : ''}}">
            <form action="{{route('admin.email-communication.templates.update')}}" method="POST">
              @csrf

              <!-- Email Notification Controls -->
              <div class="row mb-4">
                <div class="col-lg-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>إعدادات الإشعارات:</strong> تحكم في تفعيل/إلغاء تفعيل الإشعارات الإلكترونية لكل نوع من الرسائل.
                  </div>
                </div>
              </div>

              <div class="row mb-4">
                <div class="col-lg-4">
                  <div class="form-group">
                    <label for="welcome_message_email_enabled">
                      <strong>تفعيل رسالة الترحيب بالبريد الإلكتروني</strong>
                    </label>
                    <div class="toggle-switch-container">
                      <div class="toggle-switch">
                        <input type="checkbox" id="welcome_message_email_enabled" name="welcome_message_email_enabled"
                               value="1" {{($abs->welcome_message_email_enabled ?? true) ? 'checked' : ''}}>
                        <label for="welcome_message_email_enabled" class="toggle-label">
                          <span class="toggle-slider"></span>
                          <span class="toggle-text">
                            <span class="toggle-on">ON</span>
                            <span class="toggle-off">OFF</span>
                          </span>
                        </label>
                      </div>
                      <span class="toggle-description">إرسال رسالة ترحيب بالبريد الإلكتروني عند التسجيل</span>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="form-group">
                    <label for="subscription_expiration_email_enabled">
                      <strong>تفعيل إشعار انتهاء الاشتراك بالبريد الإلكتروني</strong>
                    </label>
                    <div class="toggle-switch-container">
                      <div class="toggle-switch">
                        <input type="checkbox" id="subscription_expiration_email_enabled" name="subscription_expiration_email_enabled"
                               value="1" {{($abs->subscription_expiration_email_enabled ?? true) ? 'checked' : ''}}>
                        <label for="subscription_expiration_email_enabled" class="toggle-label">
                          <span class="toggle-slider"></span>
                          <span class="toggle-text">
                            <span class="toggle-on">ON</span>
                            <span class="toggle-off">OFF</span>
                          </span>
                        </label>
                      </div>
                      <span class="toggle-description">إرسال إشعار قبل انتهاء الاشتراك بالبريد الإلكتروني</span>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="form-group">
                    <label for="subscription_expired_email_enabled">
                      <strong>تفعيل إشعار انتهاء الاشتراك بالبريد الإلكتروني</strong>
                    </label>
                    <div class="toggle-switch-container">
                      <div class="toggle-switch">
                        <input type="checkbox" id="subscription_expired_email_enabled" name="subscription_expired_email_enabled"
                               value="1" {{($abs->subscription_expired_email_enabled ?? true) ? 'checked' : ''}}>
                        <label for="subscription_expired_email_enabled" class="toggle-label">
                          <span class="toggle-slider"></span>
                          <span class="toggle-text">
                            <span class="toggle-on">ON</span>
                            <span class="toggle-off">OFF</span>
                          </span>
                        </label>
                      </div>
                      <span class="toggle-description">إرسال إشعار عند انتهاء الاشتراك بالبريد الإلكتروني</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="email_password_reset_template"><strong>قالب إعادة تعيين كلمة المرور</strong></label>
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
                    <small class="text-info">
                      <i class="fas fa-info-circle"></i>
                      <a href="{{route('admin.email-templates.create')}}?type=password_reset" target="_blank">إنشاء قالب جديد</a>
                    </small>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="welcome_message_template"><strong>قالب رسالة الترحيب</strong></label>
                    <select class="form-control" id="welcome_message_template" name="welcome_message_template">
                      <option value="">اختر قالب أو اتركه فارغاً</option>
                      @php
                          try {
                              $welcomeTemplates = \App\Models\EmailTemplate::active()->ofType('welcome')->get();
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
                    <small class="text-info">
                      <i class="fas fa-info-circle"></i>
                      <a href="{{route('admin.email-templates.create')}}?type=welcome" target="_blank">إنشاء قالب جديد</a>
                    </small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="subscription_expiration_template"><strong>قالب انتهاء الاشتراك</strong></label>
                    <select class="form-control" id="subscription_expiration_template" name="subscription_expiration_template">
                      <option value="">اختر قالب أو اتركه فارغاً</option>
                      @php
                          try {
                              $subscriptionTemplates = \App\Models\EmailTemplate::active()->ofType('subscription_expiration')->get();
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
                    <small class="text-info">
                      <i class="fas fa-info-circle"></i>
                      <a href="{{route('admin.email-templates.create')}}?type=subscription_expiration" target="_blank">إنشاء قالب جديد</a>
                    </small>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="form-group">
                    <label for="subscription_expired_template"><strong>قالب انتهاء الاشتراك</strong></label>
                    <select class="form-control" id="subscription_expired_template" name="subscription_expired_template">
                      <option value="">اختر قالب أو اتركه فارغاً</option>
                      @php
                          try {
                              $expiredTemplates = \App\Models\EmailTemplate::active()->ofType('subscription_expired')->get();
                          } catch (Exception $e) {
                              $expiredTemplates = collect();
                          }
                      @endphp
                      @foreach($expiredTemplates as $template)
                        <option value="{{$template->name}}" {{($abs->subscription_expired_template ?? '') == $template->name ? 'selected' : ''}}>
                          {{$template->name}} ({{$template->language_label}})
                        </option>
                      @endforeach
                    </select>
                    <small class="text-info">
                      <i class="fas fa-info-circle"></i>
                      <a href="{{route('admin.email-templates.create')}}?type=subscription_expired" target="_blank">إنشاء قالب جديد</a>
                    </small>
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <strong>ملاحظة:</strong> اختر قالب من القوالب المحفوظة أو اتركه فارغاً للرسالة العادية. يمكنك إنشاء قوالب جديدة من خلال الروابط أعلاه.
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="col-lg-12">
                  <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> حفظ إعدادات القوالب
                  </button>
                </div>
              </div>
            </form>

            <!-- Available Email Templates -->
            <div class="row mt-4">
              <div class="col-12">
                <div class="card">
                  <div class="card-header">
                    <h6 class="mb-0">
                      <i class="fas fa-list"></i> القوالب المتاحة
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
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th>اسم القالب</th>
                              <th>النوع</th>
                              <th>اللغة</th>
                              <th>الحالة</th>
                              <th>الإجراءات</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach($allEmailTemplates as $template)
                              <tr>
                                <td>
                                  <strong>{{$template->name}}</strong>
                                  @if($template->description)
                                    <br><small class="text-muted">{{$template->description}}</small>
                                  @endif
                                </td>
                                <td><span class="badge badge-info">{{$template->type_label}}</span></td>
                                <td><span class="badge badge-secondary">{{$template->language_label}}</span></td>
                                <td>
                                  @if($template->status)
                                    <span class="badge badge-success">نشط</span>
                                  @else
                                    <span class="badge badge-danger">غير نشط</span>
                                  @endif
                                </td>
                                <td>
                                  <div class="btn-group" role="group">
                                    <a href="{{route('admin.email-templates.edit', $template)}}" class="btn btn-sm btn-warning" title="تعديل">
                                      <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{route('admin.email-templates.preview', $template)}}" class="btn btn-sm btn-info" title="معاينة">
                                      <i class="fas fa-eye"></i>
                                    </a>
                                  </div>
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    @else
                      <div class="text-center text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                        لا توجد قوالب بعد.
                      </div>
                    @endif

                    <div class="text-center mt-3">
                      <a href="{{route('admin.email-templates.create')}}" class="btn btn-sm btn-primary mt-2">
                        <i class="fas fa-plus"></i> إنشاء قالب جديد
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Test Email Tab -->
          <div id="test_email-tab" class="tab-content {{request('tab') == 'test_email' ? 'active' : ''}}">
            <div class="row">
              <div class="col-lg-12">
                <div class="alert alert-info">
                  <i class="fas fa-info-circle"></i>
                  <strong>اختبار البريد الإلكتروني:</strong> استخدم هذه الأداة لاختبار إعدادات SMTP وإرسال رسالة تجريبية.
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-lg-6">
                <div class="form-group">
                  <label for="test_email_address"><strong>البريد الإلكتروني للاختبار</strong></label>
                  <input type="email" class="form-control" id="test_email_address"
                         placeholder="test@example.com">
                </div>
              </div>
              <div class="col-lg-6">
                <div class="form-group">
                  <label>&nbsp;</label>
                  <div>
                    <button type="button" class="btn btn-info" onclick="testEmailConfiguration()">
                      <i class="fas fa-paper-plane"></i> إرسال رسالة اختبار
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-lg-12">
                <div id="test-result" class="mt-3" style="display: none;">
                  <!-- Test results will be displayed here -->
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize tab from URL parameter
    var urlParams = new URLSearchParams(window.location.search);
    var activeTab = urlParams.get('tab') || 'smtp_settings';
    console.log('Initializing with tab:', activeTab);
    switchTab(activeTab);

    // Tab switching functionality
    $('.tab-button').click(function() {
        var tabName = $(this).data('tab');
        switchTab(tabName);
    });
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
        'smtp_settings': 'إعدادات SMTP',
        'email_templates': 'إعدادات القوالب',
        'test_email': 'اختبار البريد الإلكتروني'
    };
    $('#tab-title').text(titles[tabName]);
}

function testEmailConfiguration() {
    var testEmail = $('#test_email_address').val();

    if (!testEmail || !testEmail.includes('@')) {
        showNotification('يرجى إدخال بريد إلكتروني صحيح', 'error');
        return;
    }

    var resultDiv = $('#test-result');
    resultDiv.html('<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> جاري إرسال رسالة الاختبار...</div>').show();

    $.post('{{route("admin.email-communication.test")}}', {
        _token: '{{csrf_token()}}',
        test_email: testEmail
    }, function(response) {
        if (response.success) {
            resultDiv.html('<div class="alert alert-success"><i class="fas fa-check"></i> ' + response.message + '</div>');
        } else {
            resultDiv.html('<div class="alert alert-danger"><i class="fas fa-times"></i> ' + response.message + '</div>');
        }
    }).fail(function() {
        resultDiv.html('<div class="alert alert-danger"><i class="fas fa-times"></i> فشل في إرسال رسالة الاختبار. تأكد من إعدادات SMTP.</div>');
    });
}

function showNotification(message, type) {
    var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    var icon = type === 'success' ? 'fa-check' : 'fa-times';

    var notification = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
        '<i class="fas ' + icon + '"></i> ' + message +
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
        '<span aria-hidden="true">&times;</span>' +
        '</button>' +
        '</div>';

    // Remove existing notifications
    $('.alert').remove();

    // Add new notification at the top
    $('.page-header').after(notification);

    // Auto-hide after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
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

    .toggle-switch-container {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>
@endsection
