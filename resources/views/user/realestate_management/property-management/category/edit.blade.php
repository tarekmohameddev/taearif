<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">
                    {{ $keywords['Edit Property Category'] ?? __('Edit Property Category') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxEditForm" class="modal-form"
                    action="{{ route('user.property_management.update_category') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="in_id" name="id">
                    {{-- <div class="form-group">
                        <label for="">{{ $keywords['Image'] ?? __('Image') . '*' }}</label>
                        <br>
                        <div class="thumb-preview">
                            <img src="{{ asset('assets/img/noimage.jpg') }}" id="in_image" alt="..."
                                class="uploaded-img in_image">
                        </div>

                        <div class="mt-3">
                            <div role="button" class="btn btn-primary btn-sm upload-btn">
                                {{ $keywords['Choose Image'] ?? __('Choose Image') }}
                                <input type="file" class="img-input" name="image">
                            </div>
                        </div>

                        <p id="editErr_image" class="mb-0 text-danger em"></p>
                    </div> --}}

                    <div class="form-group">
                        <label for="">{{ __('Image') }}</label>
                        <br>
                        <div class="showImage">
                            <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..."
                                class="img-thumbnail category-img">
                        </div>

                        <div class="mt-3">

                            <input type="file" class="form-control image" id="image" name="image">

                        </div>

                        <p id="editErr_image" class=" mb-0 text-danger em"></p>
                    </div>

                    {{-- <div class="form-group">
                        <label for="">{{ __('Language') . '*' }}</label>
                        <select name="language" id="in_language" class="form-control language">
                            <option selected disabled>{{ __('Select Language') }}</option>
                            @foreach ($languages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="editErr_language" class="mt-2 mb-0 text-danger em"></p>
                    </div> --}}


                    <div class="form-group  ">
                        <label for="">{{ __('Name') . '*' }}

                        </label>
                        <input type="text" id="in_name" class="form-control" name="name"
                            placeholder="{{ __('Enter category name') }}">
                        <p id="editErr_name" class="mt-2 mb-0 text-danger em"></p>
                    </div>


                    <div class="form-group">
                        <label for="">{{ __('Status') . '*' }}</label>
                        <select name="status" id="in_status" class="form-control">
                            <option disabled>{{ __('Select Status') }}</option>
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Deactive') }}</option>
                        </select>
                        <p id="editErr_status" class="mt-2 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') . '*' }}</label>
                        <input type="number" id="in_serial_number" class="form-control ltr" name="serial_number"
                            placeholder="{{ __('Enter Serial Number') }}">
                        <p id="editErr_serial_number" class="mt-2 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2 mb-0">
                            <small>{{ __('The higher the serial number is, the later will be shown.') }}</small>
                        </p>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button id="updateBtn" type="button" class="btn btn-primary btn-sm">
                    {{ __('Update') }}
                </button>
            </div>
        </div>
    </div>
</div>
