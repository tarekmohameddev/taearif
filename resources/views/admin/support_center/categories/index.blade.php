@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Support Center Categories') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Center of Support') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Categories') }}</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">{{ __('Categories') }}</div>
            </div>
            <div class="col-lg-4 offset-lg-4">
              <a href="{{ route('admin.support_center.categories.create') }}" class="btn btn-primary float-right btn-sm"><i class="fas fa-plus"></i> {{ __('Add Category') }}</a>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($categories) == 0)
                <h3 class="text-center">{{ __('NO CATEGORY FOUND') }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">{{ __('Icon') }}</th>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Slug') }}</th>
                        <th scope="col">{{ __('Short Description') }}</th>
                        <th scope="col">{{ __('Articles Count') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($categories as $category)
                        <tr>
                          <td>
                            @if ($category->icon_image)
                              <img src="{{ asset($category->icon_image) }}" alt="{{ $category->name }}" style="max-width: 40px; max-height: 40px;">
                            @else
                              <span class="badge badge-secondary">-</span>
                            @endif
                          </td>
                          <td>{{ $category->name }}</td>
                          <td>{{ $category->slug }}</td>
                          <td>{{ Illuminate\Support\Str::limit($category->short_description ?? '', 50) }}</td>
                          <td>{{ $category->articles()->count() }}</td>
                          <td>
                            <a class="btn btn-secondary btn-sm" href="{{ route('admin.support_center.categories.edit', $category->id) }}">
                              <span class="btn-label"><i class="fas fa-edit"></i></span>
                              {{ __('Edit') }}
                            </a>
                            <form class="deleteform d-inline-block" action="{{ route('admin.support_center.categories.destroy', $category->id) }}" method="post">
                              @csrf
                              <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                <span class="btn-label"><i class="fas fa-trash"></i></span>
                                {{ __('Delete') }}
                              </button>
                            </form>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
