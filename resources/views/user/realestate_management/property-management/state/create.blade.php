<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add State') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxForm" class="modal-form create"
                    action="{{ route('user.property_management.store_state') }}" method="post"
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
                            <label for="">{{ $keywords['Country'] ?? __('Country') . '*' }}</label>
                            <select name="country" class="form-control" id="country">
                                <option selected disabled>{{ __('Select a Country') }}
                                </option>
                                {{-- @foreach ($countries as $country)
                                    <option value="{{ $country->id }}">{{ $country->name }}
                                    </option>
                                @endforeach --}}
                            </select>
                            <p id="errcountry" class="mt-2 mb-0 text-danger em"></p>
                        </div>
                    @endif

                    <div class="form-group ">
                        <label for="">{{ __('State Name') . ' *' }} </label>
                        <input type="text" class="form-control" name="name"
                            placeholder="{{ __('Enter state name') }}">
                        <p id="errname" class="mt-2 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label for="">{{ __('Serial Number') . '*' }}</label>
                        <input type="number" class="form-control " name="serial_number"
                            placeholder="{{ __('Enter serial number') }}">
                        <p id="errserial_number" class="mt-2 mb-0 text-danger em"></p>
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
                <button id="submitBtn" type="button" class="btn btn-primary btn-sm">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
