@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-edit"></i> {{ __('Edit Email Template') }}: {{ $emailTemplate->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.email-templates.update', $emailTemplate) }}" method="POST">
                        @csrf
                        @method('PUT')
                
                        
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name"><strong>{{ __('Template Name') }} *</strong></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $emailTemplate->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="type"><strong>{{ __('Template Type') }} *</strong></label>
                                    <select class="form-control @error('type') is-invalid @enderror" 
                                            id="type" name="type" required>
                                        <option value="">{{ __('Select template type') }}</option>
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}" {{ old('type', $emailTemplate->type) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="language"><strong>{{ __('Language') }} *</strong></label>
                                    <select class="form-control @error('language') is-invalid @enderror" 
                                            id="language" name="language" required>
                                        @foreach($languages as $key => $label)
                                            <option value="{{ $key }}" {{ old('language', $emailTemplate->language) == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('language')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                                            <input type="hidden" name="status" value="0">
                                            <input type="checkbox" id="status" name="status" 
                                                   value="1" {{ old('status', $emailTemplate->status) ? 'checked' : '' }}>
                                            <label for="status" class="toggle-label">
                                                <span class="toggle-slider"></span>
                                                <span class="toggle-text">
                                                    <span class="toggle-on">ON</span>
                                                    <span class="toggle-off">OFF</span>
                                                </span>
                                            </label>
                                        </div>
                                        <span class="toggle-description">{{ __('Enable template') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description"><strong>{{ __('Template Description') }}</strong></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="2">{{ old('description', $emailTemplate->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="subject"><strong>{{ __('Email Subject') }} *</strong></label>
                            <input type="text" class="form-control @error('subject') is-invalid @enderror" 
                                   id="subject" name="subject" value="{{ old('subject', $emailTemplate->subject) }}" required>
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="content"><strong>{{ __('Template Content') }} *</strong></label>
                            <textarea class="form-control @error('content') is-invalid @enderror" 
                                      id="content" name="content" rows="10" required>{{ old('content', $emailTemplate->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                {{ __('You can use the following variables:') }}
                                <span id="available-variables">
                                    @foreach($supportedVariables as $variable)
                                        <code>{{ $variable }}</code>
                                    @endforeach
                                </span>
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> {{ __('Save changes') }}
                            </button>
                            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type');
    const variablesSpan = document.getElementById('available-variables');
    
    const variables = {
        'password_reset': ['{name}', '{code}'],
        'welcome': ['{name}', '{email}'],
        'notification': ['{name}', '{message}']
    };
    
    function updateVariables() {
        const selectedType = typeSelect.value;
        if (variables[selectedType]) {
            variablesSpan.innerHTML = variables[selectedType].map(v => `<code>${v}</code>`).join(' ');
        } else {
            variablesSpan.innerHTML = '<em>' + @json(__('Select template type first')) + '</em>';
        }
    }
    
    typeSelect.addEventListener('change', updateVariables);
    updateVariables();
});
</script>
@endsection
