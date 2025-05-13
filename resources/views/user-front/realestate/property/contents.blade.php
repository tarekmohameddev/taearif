@foreach ($property_contents as $property)
    <div class="col-lg-3 col-md-3">
        @include('user-front.realestate.partials.property', ['property' => $property])
    </div>
@endforeach
