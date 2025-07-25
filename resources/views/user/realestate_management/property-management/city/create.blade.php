<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add City') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxForm" class="modal-form create"
                    action="{{ route('user.property_management.store_city') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="">{{ __('Language') }} *</label>
                        <select id="language" name="language" class="form-control countryLang">
                            <option selected disabled>{{ __('Select a language') }}</option>
                            @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="errlanguage" class="mb-0 text-danger em"></p>
                    </div>

                    @if ($userBs->property_country_status == 1)
                        <div class="form-group">
                            <label for="">{{ __('Country') . '*' }}</label>
                            <select name="country" class="form-control" id="country">
                                <option selected disabled>{{ __('Select a Country') }}
                                </option>

                            </select>
                            <p id="errcountry" class="mt-2 mb-0 text-danger em"></p>
                        </div>
                    @endif
                    @if ($userBs->property_state_status == 1)
                        <div class="form-group {{ $userBs->property_country_status != 1 && $userBs->property_state_status == 1 ? 'd-block' : ''}}" id="state"  >
                            <label for="">{{ __('State') . '*' }}</label>
                            <select name="state" class="form-control" id="stateOption">
                                <option selected disabled>{{ __('Select a State') }}
                                </option>

                            </select>
                            <p id="errstate" class="mt-2 mb-0 text-danger em"></p>
                        </div>
                    @endif
                    <div class="form-group">
                        <label for="">{{ __('Image') . '*' }}</label>
                        <br>
                        <div class="showImage">
                            <img src="{{ asset('assets/front/img/noimage.jpg') }}" alt="..."
                                class="img-thumbnail">
                        </div>

                        <div class="mt-3">
                            <input type="file" class="form-control image" id="image" name="image">
                        </div>
                        <p id="errimage" class=" mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Name') . '*' }}

                        </label>
                        <input type="text" class="form-control" name="name"
                            placeholder="{{ __('Enter city name') }}">
                        <p id="errname" class="mt-2 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Status') . '*' }}</label>
                        <select name="status" class="form-control">
                            <option value="" selected disabled>
                                {{ __('Select Status') }}</option>
                            <option value="1">{{ __('Active') }}</option>
                            <option value="0">{{ __('Deactive') }}</option>
                        </select>
                        <p id="errstatus" class="mt-2 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') . '*' }}</label>
                        <input type="number" class="form-control " name="serial_number"
                            placeholder="{{ __('Enter Serial Number') }}">
                        <p id="errserial_number" class="mt-2 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2 mb-0">
                            <small>{{ __('The higher the serial number will be shown') }}</small>
                        </p>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button id="submitBtn" type="button" class="btn btn-primary btn-sm">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
