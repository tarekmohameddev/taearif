@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Credit System - Communication Providers') }}</h4>
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
        <a href="{{route('admin.credit-management.index')}}">{{ __('Credit Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Communication Providers') }}</a>
      </li>
    </ul>
  </div>

  <div class="row mb-4">
    <div class="col-12">
      <div class="card bg-light border-info">
        <div class="card-body py-3">
          <div class="row align-items-center">
            <div class="col-md-8">
              <h5 class="mb-2">
                <i class="fas fa-info-circle"></i>
                <strong>{{ __('Communication Provider Settings for Credit System') }}</strong>
              </h5>
              <p class="mb-0 text-muted">
                {{ __('Configure shared communication providers (WhatsApp & SMS) that all tenants will use when spending credits. Admin credentials are used for all tenant messaging.') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- WhatsApp Meta Cloud Provider --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">
              <i class="fab fa-whatsapp text-success"></i> {{ __('WhatsApp Meta Cloud') }}
            </h4>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input provider-toggle" 
                     id="enable-meta" 
                     data-provider="whatsapp_meta"
                     {{$providers['whatsapp_meta']->is_enabled ? 'checked' : ''}}>
              <label class="custom-control-label" for="enable-meta">
                <strong>{{$providers['whatsapp_meta']->is_enabled ? __('Enabled') : __('Disabled')}}</strong>
              </label>
            </div>
          </div>
        </div>
        <div class="card-body">
          @if($providers['whatsapp_meta']->status === 'active')
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i> {{ __('Provider is connected and active.') }} 
              {{ __('Last tested') }}: {{$providers['whatsapp_meta']->last_tested_at ? $providers['whatsapp_meta']->last_tested_at->diffForHumans() : __('Never')}}
            </div>
          @elseif($providers['whatsapp_meta']->status === 'error')
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle"></i> {{ __('Connection error') }}: {{$providers['whatsapp_meta']->error_message}}
            </div>
          @endif

          <form id="form-whatsapp-meta" class="provider-form" data-provider="whatsapp_meta">
            @csrf
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Phone Number ID') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="phone_number_id" 
                         value="{{$providers['whatsapp_meta']->phone_number_id}}"
                         placeholder="123456789012345">
                  <small class="text-muted">{{ __('Your WhatsApp Business Account Phone Number ID from Meta') }}</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Business Account ID') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="business_account_id" 
                         value="{{$providers['whatsapp_meta']->business_account_id}}"
                         placeholder="987654321098765">
                  <small class="text-muted">{{ __('WhatsApp Business Account ID from Meta') }}</small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('API URL') }} <span class="text-danger">*</span></label>
                  <input type="url" class="form-control" name="api_url" 
                         value="{{$providers['whatsapp_meta']->api_url}}"
                         placeholder="https://graph.facebook.com/v18.0">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('Access Token') }} <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="access_token" 
                         placeholder="{{ __('Enter Meta Cloud access token') }}">
                  <small class="text-muted">{{ __('Current') }}: {{$providers['whatsapp_meta']->getMaskedApiKey()}}</small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('Webhook Verify Token') }}</label>
                  <input type="text" class="form-control" name="webhook_verify_token" 
                         value="{{$providers['whatsapp_meta']->webhook_verify_token}}"
                         placeholder="{{ __('Optional webhook verify token') }}">
                </div>
              </div>
            </div>

            <div class="btn-group">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ __('Save Configuration') }}
              </button>
              <button type="button" class="btn btn-info test-connection" data-provider="whatsapp_meta">
                <i class="fas fa-vial"></i> {{ __('Test Connection') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- WhatsApp Evolution API Provider --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">
              <i class="fab fa-whatsapp text-success"></i> {{ __('WhatsApp Evolution API') }}
            </h4>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input provider-toggle" 
                     id="enable-evolution" 
                     data-provider="whatsapp_evolution"
                     {{$providers['whatsapp_evolution']->is_enabled ? 'checked' : ''}}>
              <label class="custom-control-label" for="enable-evolution">
                <strong>{{$providers['whatsapp_evolution']->is_enabled ? __('Enabled') : __('Disabled')}}</strong>
              </label>
            </div>
          </div>
        </div>
        <div class="card-body">
          @if($providers['whatsapp_evolution']->status === 'active')
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i> {{ __('Provider is connected and active.') }} 
              {{ __('Last tested') }}: {{$providers['whatsapp_evolution']->last_tested_at ? $providers['whatsapp_evolution']->last_tested_at->diffForHumans() : __('Never')}}
            </div>
          @elseif($providers['whatsapp_evolution']->status === 'error')
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle"></i> {{ __('Connection error') }}: {{$providers['whatsapp_evolution']->error_message}}
            </div>
          @endif

          <form id="form-whatsapp-evolution" class="provider-form" data-provider="whatsapp_evolution">
            @csrf
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Instance Name') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="instance_name" 
                         value="{{$providers['whatsapp_evolution']->instance_name}}"
                         placeholder="business_instance">
                  <small class="text-muted">{{ __('Your Evolution API instance name') }}</small>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('API URL') }} <span class="text-danger">*</span></label>
                  <input type="url" class="form-control" name="api_url" 
                         value="{{$providers['whatsapp_evolution']->api_url}}"
                         placeholder="https://evolution-api.yourdomain.com">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('API Key') }} <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="evolution_api_key" 
                         placeholder="{{ __('Enter Evolution API key') }}">
                  <small class="text-muted">{{ __('Current') }}: {{$providers['whatsapp_evolution']->getMaskedApiKey()}}</small>
                </div>
              </div>
            </div>

            <div class="btn-group">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ __('Save Configuration') }}
              </button>
              <button type="button" class="btn btn-info test-connection" data-provider="whatsapp_evolution">
                <i class="fas fa-vial"></i> {{ __('Test Connection') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- SMS Gateway Provider --}}
  <div class="row mb-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title">
              <i class="fas fa-sms text-primary"></i> {{ __('SMS Gateway') }}
            </h4>
            <div class="custom-control custom-switch">
              <input type="checkbox" class="custom-control-input provider-toggle" 
                     id="enable-sms" 
                     data-provider="sms"
                     {{$providers['sms']->is_enabled ? 'checked' : ''}}>
              <label class="custom-control-label" for="enable-sms">
                <strong>{{$providers['sms']->is_enabled ? __('Enabled') : __('Disabled')}}</strong>
              </label>
            </div>
          </div>
        </div>
        <div class="card-body">
          @if($providers['sms']->status === 'active')
            <div class="alert alert-success">
              <i class="fas fa-check-circle"></i> {{ __('Provider is configured and active.') }} 
              {{ __('Last tested') }}: {{$providers['sms']->last_tested_at ? $providers['sms']->last_tested_at->diffForHumans() : __('Never')}}
            </div>
          @elseif($providers['sms']->status === 'error')
            <div class="alert alert-danger">
              <i class="fas fa-exclamation-circle"></i> {{ __('Configuration error') }}: {{$providers['sms']->error_message}}
            </div>
          @endif

          <form id="form-sms" class="provider-form" data-provider="sms">
            @csrf
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('SMS Provider') }} <span class="text-danger">*</span></label>
                  <select class="form-control" name="sms_provider">
                    <option value="">{{ __('Select Provider') }}</option>
                    <option value="twilio" {{$providers['sms']->sms_provider === 'twilio' ? 'selected' : ''}}>Twilio</option>
                    <option value="unifonic" {{$providers['sms']->sms_provider === 'unifonic' ? 'selected' : ''}}>Unifonic</option>
                    <option value="nexmo" {{$providers['sms']->sms_provider === 'nexmo' ? 'selected' : ''}}>Nexmo / Vonage</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>{{ __('Account SID') }} <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="account_sid" 
                         value="{{$providers['sms']->account_sid}}"
                         placeholder="ACxxxxxxxxxxxx">
                  <small class="text-muted">{{ __('Account ID or SID from your SMS provider') }}</small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('API URL') }} <span class="text-danger">*</span></label>
                  <input type="url" class="form-control" name="api_url" 
                         value="{{$providers['sms']->api_url}}"
                         placeholder="https://api.twilio.com">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('API Key / Auth Token') }} <span class="text-danger">*</span></label>
                  <input type="password" class="form-control" name="api_key" 
                         placeholder="{{ __('Enter API key or auth token') }}">
                  <small class="text-muted">{{ __('Current') }}: {{$providers['sms']->getMaskedApiKey()}}</small>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="form-group">
                  <label>{{ __('From Number') }}</label>
                  <input type="text" class="form-control" name="from_number" 
                         value="{{$providers['sms']->from_number}}"
                         placeholder="+1234567890">
                  <small class="text-muted">{{ __('Default sender phone number (optional)') }}</small>
                </div>
              </div>
            </div>

            <div class="btn-group">
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> {{ __('Save Configuration') }}
              </button>
              <button type="button" class="btn btn-info test-connection" data-provider="sms">
                <i class="fas fa-vial"></i> {{ __('Test Configuration') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle provider enable/disable
    $('.provider-toggle').on('change', function() {
        const provider = $(this).data('provider');
        const isEnabled = $(this).is(':checked');
        const $switch = $(this);
        const $label = $switch.next('label').find('strong');
        
        $.post(`/admin/credit-management/providers/${provider}/toggle`, {
            _token: '{{csrf_token()}}'
        })
        .done(function(response) {
            if (response.success) {
                $label.text(response.is_enabled ? '{{ __('Enabled') }}' : '{{ __('Disabled') }}');
                swal('{{ __('Success') }}', response.message, 'success');
            }
        })
        .fail(function(xhr) {
            // Revert toggle on error
            $switch.prop('checked', !isEnabled);
            swal('{{ __('Error') }}', xhr.responseJSON?.message || '{{ __('Failed to toggle provider') }}', 'error');
        });
    });

    // Save provider configuration
    $('.provider-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const provider = form.data('provider');
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __('Saving...') }}');
        
        $.post(`/admin/credit-management/providers/${provider}`, form.serialize())
        .done(function(response) {
            if (response.success) {
                swal('{{ __('Success') }}', response.message, 'success');
                // Remove error alerts
                form.closest('.card-body').find('.alert-danger').remove();
            }
        })
        .fail(function(xhr) {
            swal('{{ __('Error') }}', xhr.responseJSON?.message || '{{ __('Failed to save configuration') }}', 'error');
        })
        .always(function() {
            submitBtn.prop('disabled', false).html(originalText);
        });
    });

    // Test provider connection
    $('.test-connection').on('click', function() {
        const provider = $(this).data('provider');
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> {{ __('Testing...') }}');
        
        $.post(`/admin/credit-management/providers/${provider}/test`, {
            _token: '{{csrf_token()}}'
        })
        .done(function(response) {
            if (response.success) {
                let resultText = response.message;
                if (response.result) {
                    resultText += '<br><small>' + JSON.stringify(response.result, null, 2) + '</small>';
                }
                swal({
                    title: '{{ __('Connection Successful') }}',
                    html: resultText,
                    type: 'success'
                });
            }
        })
        .fail(function(xhr) {
            swal('{{ __('Connection Failed') }}', xhr.responseJSON?.message || '{{ __('Unable to connect to provider') }}', 'error');
        })
        .always(function() {
            btn.prop('disabled', false).html(originalText);
        });
    });
});
</script>

<style>
.provider-toggle {
    width: 50px;
    height: 24px;
}

.card-header .custom-control-label {
    padding-left: 10px;
}

.btn-group .btn {
    margin-right: 10px;
}

.alert {
    margin-bottom: 20px;
}

.form-group label .text-danger {
    margin-left: 2px;
}

.form-group small {
    display: block;
    margin-top: 5px;
}
</style>
@endsection
