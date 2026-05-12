@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('Edit WhatsApp Template') }}</h4>
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
            <a href="{{route('admin.communication.whatsapp')}}">{{ __('Communication') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="{{route('admin.whatsapp-templates.index')}}">{{ __('WhatsApp Templates') }}</a>
        </li>
        <li class="separator">
            <i class="flaticon-right-arrow"></i>
        </li>
        <li class="nav-item">
            <a href="#">{{ __('Edit Template') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="card-title">{{ __('Edit WhatsApp Template') }}</div>
            </div>
            <div class="card-body">
                <form action="{{route('admin.whatsapp-templates.update', $whatsappTemplate)}}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="name"><strong>{{ __('Template Name') }} **</strong></label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{old('name', $whatsappTemplate->name)}}" placeholder="welcome_template_1" required>
                                <p class="text-muted">{{ __('Unique template name (no spaces)') }}</p>
                                @error('name')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="type"><strong>{{ __('Template Type') }} **</strong></label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">{{ __('Select template type') }}</option>
                                    @foreach($types as $key => $label)
                                        <option value="{{$key}}" {{old('type', $whatsappTemplate->type) == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Hidden field for WhatsApp channel -->
                    <input type="hidden" name="channel" value="whatsapp">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="language"><strong>{{ __('Language') }} **</strong></label>
                                <select class="form-control" id="language" name="language" required>
                                    @foreach($languages as $key => $label)
                                        <option value="{{$key}}" {{old('language', $whatsappTemplate->language) == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                                @error('language')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="status">
                                    <strong>{{ __('Template Status') }}</strong>
                                </label>
                                <div class="toggle-switch-container">
                                    <div class="toggle-switch">
                                        <input type="checkbox" id="status" name="status" 
                                               value="1" {{old('status', $whatsappTemplate->status) ? 'checked' : ''}}>
                                        <label for="status" class="toggle-label">
                                            <span class="toggle-slider"></span>
                                            <span class="toggle-text">
                                                <span class="toggle-on">ON</span>
                                                <span class="toggle-off">OFF</span>
                                            </span>
                                        </label>
                                    </div>
                                    <span class="toggle-description">{{ __('Enable template for use') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="description"><strong>{{ __('Template Description') }}</strong></label>
                                <textarea class="form-control" id="description" name="description" rows="2" 
                                          placeholder="{{ __('Brief template description...') }}">{{old('description', $whatsappTemplate->description)}}</textarea>
                                @error('description')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>


                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="content"><strong>{{ __('Template Content') }} **</strong></label>
                                <textarea class="form-control" id="content" name="content" rows="6" 
                                          placeholder="{{ __('Enter template content here...') }}" required>{{old('content', $whatsappTemplate->content)}}</textarea>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        {{ __('Character count:') }} <span id="char-count">{{$whatsappTemplate->character_count}}</span> / 1600
                                    </small>
                                </div>
                                @error('content')
                                    <p class="text-danger">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Available Variables -->
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6>{{ __('Available Variables') }}</h6>
                                </div>
                                <div class="card-body">
                                    <div id="available-variables">
                                        @if($supportedVariables)
                                            <p>{{ __('You can use the following variables:') }}</p>
                                            @foreach($supportedVariables as $variable)
                                                <button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 variable-btn" data-variable="{{$variable}}">{{$variable}}</button>
                                            @endforeach
                                        @else
                                            <p class="text-muted">{{ __('Select template type to view available variables') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> {{ __('Save changes') }}
                                </button>
                                <a href="{{route('admin.whatsapp-templates.show', $whatsappTemplate)}}" class="btn btn-info">
                                    <i class="fas fa-eye"></i> {{ __('View') }}
                                </a>
                                <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
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
    // Character count
    $('#content').on('input', function() {
        var count = $(this).val().length;
        $('#char-count').text(count);
        
        if (count > 1600) {
            $('#char-count').addClass('text-danger');
        } else {
            $('#char-count').removeClass('text-danger');
        }
    });

    // Update available variables when type changes
    $('#type').on('change', function() {
        var type = $(this).val();
        var variables = {
            'welcome': ['{name}', '{email}'],
            'subscription_expiration': ['{name}', '{package_name}', '{expiry_date}'],
            'password_reset': ['{name}', '{code}']
        };

        var html = '';
        if (variables[type]) {
            html = '<p>{{ __('You can use the following variables:') }}</p>';
            variables[type].forEach(function(variable) {
                html += '<button type="button" class="btn btn-sm btn-outline-primary mr-2 mb-2 variable-btn" data-variable="' + variable + '">' + variable + '</button>';
            });
        } else {
            html = '<p class="text-muted">{{ __('Select template type to view available variables') }}</p>';
        }
        
        $('#available-variables').html(html);
    });

    // Insert variable into content
    $(document).on('click', '.variable-btn', function() {
        var variable = $(this).data('variable');
        var content = $('#content');
        var cursorPos = content.prop('selectionStart');
        var textBefore = content.val().substring(0, cursorPos);
        var textAfter = content.val().substring(cursorPos);
        
        content.val(textBefore + variable + textAfter);
        content.focus();
        content.prop('selectionStart', cursorPos + variable.length);
        content.prop('selectionEnd', cursorPos + variable.length);
        
        // Trigger character count update
        content.trigger('input');
    });

    // Initialize character count
    $('#content').trigger('input');
});
</script>
@endsection
