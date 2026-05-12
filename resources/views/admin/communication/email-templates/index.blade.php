@extends('admin.layout')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-envelope"></i> {{ __('Email Templates Management') }}
                    </h4>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> {{ __('Create New Template') }}
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($templates->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <th>{{ __('Type') }}</th>
                                        <th>{{ __('Language') }}</th>
                                        <th>{{ __('Subject') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Created') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($templates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{ $template->name }}</strong>
                                                @if($template->description)
                                                    <br><small class="text-muted">{{ $template->description }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-info">{{ $template->type_label }}</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">{{ $template->language_label }}</span>
                                            </td>
                                            <td>{{ $template->subject }}</td>
                                            <td>
                                                @if($template->status)
                                                    <span class="badge badge-success">{{ __('Active') }}</span>
                                                @else
                                                    <span class="badge badge-danger">{{ __('Inactive') }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $template->created_at ? $template->created_at->format('Y-m-d H:i') : __('Not set') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.email-templates.show', $template) }}"
                                                       class="btn btn-sm btn-info" title="{{ __('View') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.email-templates.edit', $template) }}"
                                                       class="btn btn-sm btn-warning" title="{{ __('Edit') }}">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('admin.email-templates.preview', $template) }}"
                                                       class="btn btn-sm btn-primary" title="{{ __('Preview') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form action="{{ route('admin.email-templates.toggle-status', $template) }}"
                                                          method="POST" style="display: inline;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm {{ $template->status ? 'btn-secondary' : 'btn-success' }}"
                                                                title="{{ $template->status ? __('Deactivate') : __('Activate') }}">
                                                            <i class="fas fa-{{ $template->status ? 'pause' : 'play' }}"></i>
                                                        </button>
                                                    </form>
                                                    <a href="{{ route('admin.email-templates.duplicate', $template) }}"
                                                       class="btn btn-sm btn-info" title="{{ __('Duplicate') }}">
                                                        <i class="fas fa-copy"></i>
                                                    </a>
                                                    <form action="{{ route('admin.email-templates.destroy', $template) }}"
                                                          method="POST" style="display: inline;"
                                                          onsubmit="return confirm(@json(__('Are you sure you want to delete this template?')))">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $templates->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">{{ __('No email templates found') }}</h5>
                            <p class="text-muted">{{ __('Start by creating a new template to manage email messages') }}</p>
                            <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('Create New Template') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
