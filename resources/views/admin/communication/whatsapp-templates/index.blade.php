@extends('admin.layout')

@section('content')
<div class="page-header">
    <h4 class="page-title">{{ __('WhatsApp Templates Management') }}</h4>
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
            <a href="#">{{ __('WhatsApp Templates') }}</a>
        </li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="card-title">{{ __('WhatsApp Templates') }}</div>
                    <a href="{{route('admin.whatsapp-templates.create')}}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Create New Template') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <form method="GET" action="{{route('admin.whatsapp-templates.index')}}" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="type" class="mr-2">{{ __('Type') }}:</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="">{{ __('All Types') }}</option>
                                    @foreach(\App\Models\WhatsAppTemplate::getTypes() as $key => $label)
                                        <option value="{{$key}}" {{request('type') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="language" class="mr-2">{{ __('Language') }}:</label>
                                <select name="language" id="language" class="form-control">
                                    <option value="">{{ __('All Languages') }}</option>
                                    @foreach(\App\Models\WhatsAppTemplate::getLanguages() as $key => $label)
                                        <option value="{{$key}}" {{request('language') == $key ? 'selected' : ''}}>{{$label}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mr-3">
                                <label for="status" class="mr-2">{{ __('Status') }}:</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="">{{ __('All Statuses') }}</option>
                                    <option value="active" {{request('status') == 'active' ? 'selected' : ''}}>{{ __('Active') }}</option>
                                    <option value="inactive" {{request('status') == 'inactive' ? 'selected' : ''}}>{{ __('Inactive') }}</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-filter"></i> {{ __('Filter') }}
                            </button>
                            <a href="{{route('admin.whatsapp-templates.index')}}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times"></i> {{ __('Reset') }}
                            </a>
                        </form>
                    </div>
                </div>

                <!-- Templates Table -->
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('Template Name') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Language') }}</th>
                                <th>{{ __('Character Count') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($templates as $template)
                                <tr>
                                    <td>
                                        <strong>{{$template->name}}</strong>
                                        @if($template->description)
                                            <br><small class="text-muted">{{$template->description}}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-info">{{$template->type_label}}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">{{$template->language_label}}</span>
                                    </td>
                                    <td>{{$template->character_count}}</td>
                                    <td>
                                        @if($template->status)
                                            <span class="badge badge-success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>{{$template->created_at ? $template->created_at->format('Y-m-d H:i') : __('Not set') }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{route('admin.whatsapp-templates.show', $template)}}" class="btn btn-sm btn-info" title="{{ __('View') }}">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.edit', $template)}}" class="btn btn-sm btn-warning" title="{{ __('Edit') }}">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.preview', $template)}}" class="btn btn-sm btn-secondary" title="{{ __('Preview') }}">
                                                <i class="fas fa-search"></i>
                                            </a>
                                            <a href="{{route('admin.whatsapp-templates.duplicate', $template)}}" class="btn btn-sm btn-primary" title="{{ __('Duplicate') }}">
                                                <i class="fas fa-copy"></i>
                                            </a>
                                            <form action="{{route('admin.whatsapp-templates.toggle-status', $template)}}" method="POST" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{$template->status ? 'btn-warning' : 'btn-success'}}" title="{{$template->status ? __('Deactivate') : __('Activate') }}">
                                                    <i class="fas fa-{{$template->status ? 'pause' : 'play'}}"></i>
                                                </button>
                                            </form>
                                            <form action="{{route('admin.whatsapp-templates.destroy', $template)}}" method="POST" style="display: inline;" onsubmit="return confirm(@json(__('Are you sure you want to delete this template?')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        {{ __('No templates yet.') }} <a href="{{route('admin.whatsapp-templates.create')}}">{{ __('Create New Template') }}</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($templates->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{$templates->appends(request()->query())->links()}}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
