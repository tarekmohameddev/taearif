@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-eye"></i> {{ __('Preview Email Template') }}: {{ $emailTemplate->name }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-envelope"></i> {{ __('Email Preview') }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="email-header bg-light p-3 border-bottom">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>{{ __('From') }}:</strong> {{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;
                                            </div>
                                            <div class="col-md-6 text-right">
                                                <strong>{{ __('To') }}:</strong> {{ __('User') }} &lt;user@example.com&gt;
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-12">
                                                <strong>{{ __('Subject') }}:</strong> {{ $emailTemplate->subject }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="email-body p-4" style="min-height: 300px; background-color: #f8f9fa;">
                                        <div class="email-content" style="background-color: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            {!! nl2br(e($emailTemplate->preview_content)) !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('Template Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>{{ __('Name') }}:</strong></td>
                                            <td>{{ $emailTemplate->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Type') }}:</strong></td>
                                            <td><span class="badge badge-info">{{ $emailTemplate->type_label }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Language') }}:</strong></td>
                                            <td><span class="badge badge-secondary">{{ $emailTemplate->language_label }}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Status') }}:</strong></td>
                                            <td>
                                                @if($emailTemplate->status)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Character Count') }}:</strong></td>
                                            <td>{{ $emailTemplate->character_count }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('Actions') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('admin.email-templates.edit', $emailTemplate) }}" 
                                           class="btn btn-warning">
                                            <i class="fas fa-edit"></i> {{ __('Edit Template') }}
                                        </a>
                                        <a href="{{ route('admin.email-templates.show', $emailTemplate) }}" 
                                           class="btn btn-info">
                                            <i class="fas fa-info-circle"></i> {{ __('Template Details') }}
                                        </a>
                                        <a href="{{ route('admin.email-templates.index') }}" 
                                           class="btn btn-secondary">
                                            <i class="fas fa-list"></i> {{ __('All Templates') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('Variables Used') }}</h5>
                                </div>
                                <div class="card-body">
                                    @if($emailTemplate->variables && count($emailTemplate->variables) > 0)
                                        @foreach($emailTemplate->variables as $variable)
                                            <span class="badge badge-primary mr-1 mb-1">{{ $variable }}</span>
                                        @endforeach
                                    @else
                                        <p class="text-muted">{{ __('No variables') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('Original Content') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h6><strong>{{ __('Subject') }}:</strong></h6>
                                            <div class="alert alert-light">
                                                {{ $emailTemplate->subject }}
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <h6><strong>{{ __('Content') }}:</strong></h6>
                                            <div class="alert alert-light">
                                                <pre style="white-space: pre-wrap; font-family: inherit; margin: 0;">{{ $emailTemplate->content }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
