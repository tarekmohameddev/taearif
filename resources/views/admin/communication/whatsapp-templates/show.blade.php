@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('View WhatsApp Template') }}</h4>
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
            <a href="#">{{ __('View Template') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="card-title">{{ __('Template Details') }}</div>
                    <div>
                        <a href="{{route('admin.whatsapp-templates.edit', $whatsappTemplate)}}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> {{ __('Edit') }}
                        </a>
                        <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> {{ __('Back') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Template Name') }}:</strong></label>
                            <p class="form-control-plaintext">{{$whatsappTemplate->name}}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Template Type') }}:</strong></label>
                            <p class="form-control-plaintext">
                                <span class="badge badge-info">{{$whatsappTemplate->type_label}}</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Channel') }}:</strong></label>
                            <p class="form-control-plaintext">
                                <span class="badge {{$whatsappTemplate->channel == 'whatsapp' ? 'badge-success' : 'badge-primary'}}">{{$whatsappTemplate->channel_label}}</span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Language') }}:</strong></label>
                            <p class="form-control-plaintext">
                                <span class="badge badge-secondary">{{$whatsappTemplate->language_label}}</span>
                            </p>
                        </div>
                    </div>
                </div>

                @if($whatsappTemplate->channel == 'email' && $whatsappTemplate->subject)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><strong>{{ __('Email Subject') }}:</strong></label>
                                <p class="form-control-plaintext">{{$whatsappTemplate->subject}}</p>
                            </div>
                        </div>
                    </div>
                @endif
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Status') }}:</strong></label>
                            <p class="form-control-plaintext">
                                @if($whatsappTemplate->status)
                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                @if($whatsappTemplate->description)
                    <div class="form-group">
                        <label><strong>{{ __('Description') }}:</strong></label>
                        <p class="form-control-plaintext">{{$whatsappTemplate->description}}</p>
                    </div>
                @endif

                <div class="form-group">
                    <label><strong>{{ __('Template Content') }}:</strong></label>
                    <div class="border p-3 bg-light">
                        <pre style="white-space: pre-wrap; font-family: inherit;">{{$whatsappTemplate->content}}</pre>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Character count:') }}</strong></label>
                            <p class="form-control-plaintext">{{$whatsappTemplate->character_count}}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><strong>{{ __('Created') }}:</strong></label>
                            <p class="form-control-plaintext">{{$whatsappTemplate->created_at ? $whatsappTemplate->created_at->format('Y-m-d H:i:s') : 'N/A'}}</p>
                        </div>
                    </div>
                </div>

                @if($whatsappTemplate->updated_at && $whatsappTemplate->created_at && $whatsappTemplate->updated_at != $whatsappTemplate->created_at)
                    <div class="form-group">
                        <label><strong>{{ __('Last Updated') }}:</strong></label>
                        <p class="form-control-plaintext">{{$whatsappTemplate->updated_at ? $whatsappTemplate->updated_at->format('Y-m-d H:i:s') : 'N/A'}}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Preview Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">{{ __('Template Preview') }}</div>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><strong>{{ __('Preview with Sample Data') }}:</strong></label>
                    <div class="border p-3 bg-light">
                        <pre style="white-space: pre-wrap; font-family: inherit;">{{$whatsappTemplate->preview_content}}</pre>
                    </div>
                </div>
                <a href="{{route('admin.whatsapp-templates.preview', $whatsappTemplate)}}" class="btn btn-info btn-block">
                    <i class="fas fa-eye"></i> {{ __('Detailed Preview') }}
                </a>
            </div>
        </div>

        <!-- Variables Card -->
        <div class="card mt-3">
            <div class="card-header">
                <div class="card-title">{{ __('Variables Used') }}</div>
            </div>
            <div class="card-body">
                @php
                    $usedVariables = [];
                    preg_match_all('/\{[^}]+\}/', $whatsappTemplate->content, $matches);
                    $usedVariables = array_unique($matches[0]);
                @endphp
                
                @if(count($usedVariables) > 0)
                    @foreach($usedVariables as $variable)
                        <span class="badge badge-primary mr-1 mb-1">{{$variable}}</span>
                    @endforeach
                @else
                    <p class="text-muted">{{ __('No variables used in this template') }}</p>
                @endif
            </div>
        </div>

        <!-- Actions Card -->
        <div class="card mt-3">
            <div class="card-header">
                <div class="card-title">{{ __('Actions') }}</div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{route('admin.whatsapp-templates.edit', $whatsappTemplate)}}" class="btn btn-warning">
                        <i class="fas fa-edit"></i> {{ __('Edit Template') }}
                    </a>
                    <a href="{{route('admin.whatsapp-templates.duplicate', $whatsappTemplate)}}" class="btn btn-primary">
                        <i class="fas fa-copy"></i> {{ __('Duplicate Template') }}
                    </a>
                    <form action="{{route('admin.whatsapp-templates.toggle-status', $whatsappTemplate)}}" method="POST">
                        @csrf
                        <button type="submit" class="btn {{$whatsappTemplate->status ? 'btn-warning' : 'btn-success'}} w-100">
                            <i class="fas fa-{{$whatsappTemplate->status ? 'pause' : 'play'}}"></i> 
                            {{$whatsappTemplate->status ? '{{ __('Deactivate') }}' : '{{ __('Activate') }}'}}
                        </button>
                    </form>
                    <form action="{{route('admin.whatsapp-templates.destroy', $whatsappTemplate)}}" method="POST" onsubmit="return confirm('@json(__('Are you sure you want to delete this template?'))')">
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
@endsection
