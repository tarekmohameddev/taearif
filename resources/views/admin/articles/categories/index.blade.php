@extends('admin.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{__('Admin Article Categories')}}</h4>
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
        <a href="#">{{__('Admin Articles')}}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{__('Categories')}}</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card-title d-inline-block">{{__('Categories')}}</div>
                </div>
                <div class="col-lg-4 offset-lg-4">
                    <a href="{{route('admin.articles.categories.create')}}" class="btn btn-primary float-right btn-sm"><i class="fas fa-plus"></i> {{__('Add Category')}}</a>
                </div>
            </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($categories) == 0)
                <h3 class="text-center">{{__('NO CATEGORY FOUND')}}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">{{__('Name')}}</th>
                        <th scope="col">{{__('Slug')}}</th>
                        <th scope="col">{{__('Description')}}</th>
                        <th scope="col">{{__('Articles Count')}}</th>
                        <th scope="col">{{__('Actions')}}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($categories as $category)
                        <tr>
                          <td>{{$category->name}}</td>
                          <td>{{$category->slug}}</td>
                          <td>{{Illuminate\Support\Str::limit($category->description ?? '', 50)}}</td>
                          <td>{{$category->articles_count ?? $category->articles()->count()}}</td>
                          <td>
                            <a class="btn btn-secondary btn-sm" href="{{route('admin.articles.categories.edit', $category->id)}}">
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                              {{__('Edit')}}
                            </a>
                            <form class="deleteform d-inline-block" action="{{route('admin.articles.categories.destroy', $category->id)}}" method="post">
                              @csrf
                              <button type="submit" class="btn btn-danger btn-sm deletebtn">
                                <span class="btn-label">
                                  <i class="fas fa-trash"></i>
                                </span>
                                {{__('Delete')}}
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
