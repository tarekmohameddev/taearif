<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Country') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxForm" class="modal-form create"
                    action="{{ route('user.property_management.store_country') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf

                    {{-- <div class="form-group">
                        <label for="">{{ __('Language') . ' *' }}</label>
                        <select name="language" id="" class="form-control">
                            <option selected disabled>{{ __('Select Language') }}</option>
                            @foreach ($languages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="errlanguage" class="mt-2 mb-0 text-danger em"></p>
                    </div> --}}
                    <div class="form-group">
                        <label for="">{{ __('Language') }} *</label>
                        <select id="language" name="user_language_id" class="form-control">
                            <option selected disabled>{{ __('Select a language') }}</option>
                            @foreach ($userLanguages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->name }}</option>
                            @endforeach
                        </select>
                        <p id="erruser_language_id" class="mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group  ">
                        <label for=""> {{ __('Name') . ' *' }} </label>
                        <input type="text" class="form-control" name="name"
                            placeholder=" {{ __('Enter country name') }}">
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
