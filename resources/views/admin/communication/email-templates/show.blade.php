@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-eye"></i> {{ __('Email Template Details') }}: {{ $emailTemplate->name }}
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
                                    <h5 class="card-title mb-0">{{ __('Template Information') }}</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>{{ __('Template Name') }}:</strong></td>
                                            <td>{{ $emailTemplate->name }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Template Type') }}:</strong></td>
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
                                            <td><strong>{{ __('Created') }}:</strong></td>
                                            <td>{{ $emailTemplate->created_at ? $emailTemplate->created_at->format('Y-m-d H:i:s') : __('Not set') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>{{ __('Last Updated') }}:</strong></td>
                                            <td>{{ $emailTemplate->updated_at ? $emailTemplate->updated_at->format('Y-m-d H:i:s') : __('Not set') }}</td>
                                        </tr>
                                        @if($emailTemplate->description)
                                        <tr>
                                            <td><strong>{{ __('Description') }}:</strong></td>
                                            <td>{{ $emailTemplate->description }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
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
                                        <a href="{{ route('admin.email-templates.preview', $emailTemplate) }}" 
                                           class="btn btn-primary">
                                            <i class="fas fa-eye"></i> {{ __('Preview Template') }}
                                        </a>
                                        <form action="{{ route('admin.email-templates.toggle-status', $emailTemplate) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn {{ $emailTemplate->status ? 'btn-secondary' : 'btn-success' }} w-100">
                                                <i class="fas fa-{{ $emailTemplate->status ? 'pause' : 'play' }}"></i> 
                                                {{ $emailTemplate->status ? __('Deactivate') : __('Activate') }}
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.email-templates.duplicate', $emailTemplate) }}" 
                                           class="btn btn-info">
                                            <i class="fas fa-copy"></i> {{ __('Duplicate Template') }}
                                        </a>
                                        <form action="{{ route('admin.email-templates.destroy', $emailTemplate) }}" 
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm(@json(__('Are you sure you want to delete this template?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger w-100">
                                                <i class="fas fa-trash"></i> {{ __('Delete Template') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">{{ __('Template Content') }}</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <h6><strong>{{ __('Email Subject') }}:</strong></h6>
                                            <div class="alert alert-light">
                                                {{ $emailTemplate->subject }}
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <h6><strong>{{ __('Available Variables') }}:</strong></h6>
                                            <div class="alert alert-info">
                                                @foreach($emailTemplate->variables ?? [] as $variable)
                                                    <code>{{ $variable }}</code>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6><strong>{{ __('Template Content') }}:</strong></h6>
                                    <div class="alert alert-light">
                                        <pre style="white-space: pre-wrap; font-family: inherit;">{{ $emailTemplate->content }}</pre>
                                    </div>
                                    
                                    <h6><strong>{{ __('Preview with Sample Data') }}:</strong></h6>
                                    <div class="alert alert-success">
                                        <pre style="white-space: pre-wrap; font-family: inherit;">{{ $emailTemplate->preview_content }}</pre>
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
