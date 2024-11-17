<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">
                    @if ($userBs->theme == 'home_eleven')
                        {{ __('Add Donor') }}
                    @else
                        {{ __('Add Brand') }}
                    @endif
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxForm" class="modal-form" action="{{ route('user.home_page.brand_section.store_brand') }}"
                    method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <div class="col-12 mb-2">
                            <label for="image"><strong>{{ __('Image') }}</strong></label>
                        </div>
                        <div class="col-md-12 showImage mb-3">
                            <img src="{{ asset('assets/admin/img/noimage.jpg') }}" alt="..." class="img-thumbnail">
                        </div>
                        <input type="file" name="brand_img" id="image" class="form-control image">
                        <p id="errbrand_img" class="mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        @if ($userBs->theme == 'home_eleven')
                            <label for="">{{ __('Donor\'s URL*') }}</label>
                        @else
                            <label for="">{{ __('Brand\'s URL*') }}</label>
                        @endif
                        <input type="url" class="form-control ltr" name="brand_url"
                            placeholder="{{ __('Enter Brand URL') }}">
                        <p id="errbrand_url" class="mt-2 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number*') }}</label>
                        <input type="number" class="form-control ltr" name="serial_number"
                            placeholder="{{ __('Enter Serial Number') }}">
                        <p id="errserial_number" class="mt-2 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2">
                            <small>{{ __('The higher the serial number is, the later the brand will be shown.') }}</small>
                        </p>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button id="submitBtn" type="button" class="btn btn-primary">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
