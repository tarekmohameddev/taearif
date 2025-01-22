<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title card-title" id="exampleModalLongTitle">
                    {{ __('Add Property Type') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="propertyForm" class="modal-form create"
                    action="{{ route('user.project_management.project_type.store') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="project_id" value="{{ $project_id }}">

                    <div class="alert alert-danger pb-1  " id="propertyErrors" style="display: none">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <ul></ul>
                    </div>

                    <div id="accordion" class="mt-3 custom-accordion px-2">
                        @foreach ($languages as $language)
                            <div class="version">
                                <div class="version-header" id="heading{{ $language->id }}">
                                    <h5 class="mb-0">
                                        <button type="button" class="btn btn-link" data-toggle="collapse"
                                            data-target="#collapse{{ $language->id }}"
                                            aria-expanded="{{ $language->is_default == 1 ? 'true' : 'false' }}"
                                            aria-controls="collapse{{ $language->id }}">
                                            {{ $language->name . __(' Language') }}
                                            {{ $language->is_default == 1 ? '(Default)' : '' }}
                                        </button>
                                    </h5>
                                </div>

                                <div id="collapse{{ $language->id }}"
                                    class="collapse {{ $language->is_default == 1 ? 'show' : '' }}"
                                    aria-labelledby="heading{{ $language->id }}" data-parent="#accordion">
                                    <div class="version-body">
                                        <div class="row">
                                            <div class="col-lg-8">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Name') }} *</label>
                                                    <input type="text" class="form-control"
                                                        name="{{ $language->code }}_name"
                                                        placeholder="{{ __('Enter name') }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-4">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Total Unit') . '*' }}</label>
                                                    <input type="text" name="{{ $language->code }}_total_unit"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter total unit') }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Min Area (sqft)') . '*' }}</label>
                                                    <input type="text" name="{{ $language->code }}_min_area"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter minimum area') }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Max Area (sqft)') }}</label>
                                                    <input type="text" name="{{ $language->code }}_max_area"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter maximum area') }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Minimum Price') . ' (' . $userBs->base_currency_text . ') ' . '*' }}</label>
                                                    <input type="text" name="{{ $language->code }}_min_price"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter minimum price') }}">
                                                </div>
                                            </div>

                                            <div class="col-lg-6">
                                                <div
                                                    class="form-group {{ $language->rtl == 1 ? 'rtl text-right' : '' }}">
                                                    <label>{{ __('Maximum Price') . ' (' . $userBs->base_currency_text . ')' }}</label>
                                                    <input type="text" name="{{ $language->code }}_max_price"
                                                        class="form-control"
                                                        placeholder="{{ __('Enter maximum price') }}">
                                                </div>
                                            </div>

                                        </div>


                                        <div class="row">
                                            <div class="col">
                                                @php $currLang = $language; @endphp

                                                @foreach ($languages as $language)
                                                    @continue($language->id == $currLang->id)

                                                    <div class="form-check py-0">
                                                        <label class="form-check-label">
                                                            <input class="form-check-input" type="checkbox"
                                                                onchange="cloneInput('collapse{{ $currLang->id }}', 'collapse{{ $language->id }}', event)">
                                                            <span
                                                                class="form-check-sign text-focus">{{ __('Clone for') }}
                                                                <strong
                                                                    class="text-capitalize text-secondary">{{ $language->name }}</strong>
                                                                {{ __('language') }}</span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button id="propertySubmit" type="button" class="btn btn-primary btn-sm">
                    {{ __('Save') }}
                </button>
            </div>
        </div>
    </div>
</div>
