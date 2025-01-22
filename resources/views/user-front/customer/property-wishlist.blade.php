@extends('user-front.layout')

@section('tab-title')
    {{ $keywords['Property Wishlist'] ?? __('Property Wishlist') }}
@endsection


@section('page-name')
    {{ $keywords['Property Wishlist'] ?? __('Property Wishlist') }}
@endsection
@section('br-name')
    {{ $keywords['Property Wishlist'] ?? __('Property Wishlist') }}
@endsection
@section('content')
    <section class="user-dashbord pt-100 pb-60">
        <div class="container">
            <div class="row">
                @includeIf('user-front.customer.side-navbar')
                <div class="col-lg-9">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="user-profile-details mb-40">
                                <div class="account-info">
                                    <div class="title mb-3">
                                        <h4>{{ $keywords['Property Wishlist'] ?? __('Property Wishlist') }} </h4>
                                    </div>
                                    <div class="main-info" id="refreshDiv">
                                        <div class="main-table">
                                            <div class="table-responsiv">
                                                <table id="order_table"
                                                    class="dataTables_wrapper table-striped dt-bootstrap4"
                                                    style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ $keywords['item'] ?? __('item') }}</th>
                                                            <th>{{ $keywords['title'] ?? __('title') }}</th>
                                                            <th>{{ $keywords['action'] ?? __('action') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if ($wishlist)
                                                            @foreach ($wishlist as $item)
                                                                @php
                                                                    $content = !empty($item->property)
                                                                        ? $item->property
                                                                            ->contents($language->id)
                                                                            ->first()
                                                                        : [];
                                                                @endphp
                                                                @if (!empty($content))
                                                                    <tr>
                                                                        <td width="15%"> <a
                                                                                href="{{ route('front.user.property.details', ['slug' => $content->slug, getParam()]) }}">
                                                                                <img src="{{ asset('assets/img/property/featureds/' . $item->property->featured_image) }}"
                                                                                    class="img-fluid" alt="image">
                                                                            </a>
                                                                        </td>
                                                                        <td width="50%" class="px-4"> <a
                                                                                target="_blank"
                                                                                href="{{ route('front.user.property.details', ['slug' => $content->slug, getParam()]) }}">
                                                                                {{ strlen($content->title) > 40 ? mb_substr($content->title, 0, 40, 'UTF-8') . '...' : $content->title }}
                                                                            </a>
                                                                        </td>

                                                                        <td>
                                                                            <div class="remove">
                                                                                <div class="checkbox">
                                                                                    <span
                                                                                        class="fas fa-times cursor-pointer item-remove"
                                                                                        rel="{{ $item->id }}"
                                                                                        data-pg="wish"
                                                                                        data-href="{{ route('customer.property.remove-to-wishlist', ['id' => $item->id, getParam()]) }} "></span>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                        @else
                                                            <tr class="text-center">
                                                                <td colspan="4">
                                                                    {{ $keywords['no_items'] ?? __('No Items found!') }}
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
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
    </section>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#order_table').DataTable({
                ordering: false
            });
        });
    </script>
@endsection
