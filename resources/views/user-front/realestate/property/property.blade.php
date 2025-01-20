 @forelse ($property_contents as $property)
     <div class="col-lg-4  col-md-6">
         @include('user-front.realestate.partials.property')
     </div>
 @empty
     <div class="col-lg-12">
         <h3 class="text-center mt-5">{{ $keywords['No Property Found'] ?? __('No Property Found') }}</h3>
     </div>
 @endforelse
 <div class="row">
     <div class="col-lg-12 pagination justify-content-center customPaginagte">
         {{ $property_contents->links() }}
     </div>
 </div>
