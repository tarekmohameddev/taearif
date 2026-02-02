@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Support Center Articles') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}"><i class="flaticon-home"></i></a>
      </li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="#">{{ __('Center of Support') }}</a></li>
      <li class="separator"><i class="flaticon-right-arrow"></i></li>
      <li class="nav-item"><a href="#">{{ __('Articles') }}</a></li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">{{ __('Articles') }}</div>
            </div>
            <div class="col-lg-8">
              <form method="GET" action="{{ route('admin.support_center.articles.index') }}" class="d-inline-flex flex-wrap align-items-center gap-2">
                <select name="status" class="form-control form-control-sm" style="width: auto;">
                  <option value="">{{ __('All Statuses') }}</option>
                  <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>{{ __('Draft') }}</option>
                  <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>{{ __('Published') }}</option>
                  <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>{{ __('Scheduled') }}</option>
                  <option value="archived" {{ request('status') == 'archived' ? 'selected' : '' }}>{{ __('Archived') }}</option>
                </select>
                <select name="category_id" class="form-control form-control-sm" style="width: auto;">
                  <option value="">{{ __('All Categories') }}</option>
                  @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                  @endforeach
                </select>
                <input type="text" name="search" class="form-control form-control-sm" style="width: 180px;" placeholder="{{ __('Search') }}" value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary btn-sm">{{ __('Filter') }}</button>
                <a href="{{ route('admin.support_center.articles.index') }}" class="btn btn-secondary btn-sm">{{ __('Reset') }}</a>
              </form>
            </div>
            <div class="col-lg-12 mt-3">
              <a href="{{ route('admin.support_center.articles.create') }}" class="btn btn-primary float-right btn-sm"><i class="fas fa-plus"></i> {{ __('Add Article') }}</a>
              <a href="{{ route('admin.support_center.categories.index') }}" class="btn btn-info float-right btn-sm mr-2"><i class="fas fa-list"></i> {{ __('Manage Categories') }}</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if ($articles->count() == 0)
                <h3 class="text-center">{{ __('NO ARTICLE FOUND') }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">{{ __('Image') }}</th>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('Category') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Author') }}</th>
                        <th scope="col">{{ __('Published At') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($articles as $article)
                        <tr>
                          <td>
                            @if ($article->main_image)
                              <img src="{{ asset($article->main_image) }}" alt="{{ $article->title }}" style="max-width: 50px; max-height: 50px;">
                            @else
                              <span class="badge badge-secondary">{{ __('No Image') }}</span>
                            @endif
                          </td>
                          <td>{{ Illuminate\Support\Str::limit($article->title, 50) }}</td>
                          <td>{{ $article->category->name ?? __('Uncategorized') }}</td>
                          <td>
                            @if ($article->status->value == 'published')
                              <span class="badge badge-success">{{ __('Published') }}</span>
                            @elseif ($article->status->value == 'draft')
                              <span class="badge badge-warning">{{ __('Draft') }}</span>
                            @elseif ($article->status->value == 'scheduled')
                              <span class="badge badge-info">{{ __('Scheduled') }}</span>
                            @else
                              <span class="badge badge-secondary">{{ __('Archived') }}</span>
                            @endif
                          </td>
                          <td>{{ $article->admin->first_name ?? __('Unknown') }} {{ $article->admin->last_name ?? '' }}</td>
                          <td>{{ $article->published_at ? $article->published_at->format('Y-m-d H:i') : '-' }}</td>
                          <td>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.support_center.articles.show', $article->id) }}"><i class="fas fa-eye"></i></a>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.support_center.articles.edit', $article->id) }}"><i class="fas fa-edit"></i></a>
                            <form class="deleteform d-inline-block" action="{{ route('admin.support_center.articles.destroy', $article->id) }}" method="post">
                              @csrf
                              <button type="submit" class="btn btn-danger btn-sm deletebtn"><i class="fas fa-trash"></i></button>
                            </form>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
                <div class="mt-3">{{ $articles->links() }}</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
