@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('View Article') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home"><a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a></li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="{{ route('admin.support_center.articles.index') }}">{{ __('Articles') }}</a></li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="#">{{ __('View') }}</a></li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title d-inline-block">{{ $article->title }}</div>
          <div class="float-right">
            <a href="{{ route('admin.support_center.articles.edit', $article->id) }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> {{ __('Edit') }}</a>
            <a href="{{ route('admin.support_center.articles.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
          </div>
        </div>
        <div class="card-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>{{ __('Category') }}:</strong> {{ $article->category->name ?? __('Uncategorized') }}<br>
              <strong>{{ __('Status') }}:</strong>
              @if ($article->status->value == 'published')
                <span class="badge badge-success">{{ __('Published') }}</span>
              @elseif ($article->status->value == 'draft')
                <span class="badge badge-warning">{{ __('Draft') }}</span>
              @elseif ($article->status->value == 'scheduled')
                <span class="badge badge-info">{{ __('Scheduled') }}</span>
              @else
                <span class="badge badge-secondary">{{ __('Archived') }}</span>
              @endif
              <br>
              <strong>{{ __('Author') }}:</strong> {{ $article->admin->first_name ?? __('Unknown') }} {{ $article->admin->last_name ?? '' }}<br>
              <strong>{{ __('Created At') }}:</strong> {{ $article->created_at->format('Y-m-d H:i:s') }}<br>
              <strong>{{ __('Published At') }}:</strong> {{ $article->published_at ? $article->published_at->format('Y-m-d H:i:s') : '-' }}<br>
              <strong>{{ __('Slug') }}:</strong> {{ $article->slug }}
            </div>
            <div class="col-md-6">
              @if ($article->main_image)
                <img src="{{ asset($article->main_image) }}" alt="{{ $article->title }}" class="img-fluid" style="max-height: 300px;">
              @endif
            </div>
          </div>
          @if ($article->excerpt)
            <div class="alert alert-info">
              <strong>{{ __('Excerpt') }}:</strong> {{ $article->excerpt }}
            </div>
          @endif
          <div class="article-body">{!! $article->body !!}</div>
          @if ($article->cta_enabled)
            <div class="mt-4 p-3 bg-light">
              <h5>{{ __('Call to Action') }}</h5>
              <a href="{{ $article->cta_url }}" class="btn btn-primary" {{ $article->cta_target_blank ? 'target="_blank"' : '' }}>{{ $article->cta_text }}</a>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
