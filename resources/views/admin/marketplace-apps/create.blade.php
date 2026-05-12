<!-- Create Marketplace App Modal -->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Marketplace App') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="ajaxForm" enctype="multipart/form-data" class="modal-form"
                    action="{{ route('admin.marketplace-apps.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">{{ __('App Name') }}*</label>
                        <input id="name" type="text" class="form-control" name="name"
                            placeholder="{{ __('Enter app name') }}" value="">
                        <p id="errname" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="description">{{ __('Description') }}</label>
                        <textarea id="description" class="form-control" name="description" rows="3"
                            placeholder="{{ __('Enter app description') }}"></textarea>
                        <p id="errdescription" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="price">{{ __('Price') }}*</label>
                        <input id="price" type="number" step="0.01" class="form-control" name="price"
                            placeholder="{{ __('Enter app price') }}" value="0">
                        <p class="text-warning">
                            <small>{{ __('Enter 0 for free apps') }}</small>
                        </p>
                        <p id="errprice" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="type">{{ __('App Type') }}*</label>
                        <select id="type" name="type" class="form-control" required>
                            <option value="marketplace" selected>{{ __('Marketplace') }}</option>
                            <option value="builtin">{{ __('Built-in') }}</option>
                        </select>
                        <p id="errtype" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="rating">{{ __('Rating (0-5)') }}</label>
                        <input id="rating" type="number" step="0.1" min="0" max="5" class="form-control" name="rating"
                            placeholder="{{ __('Enter rating') }}" value="0">
                        <p id="errrating" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="path">{{ __('App page path') }}</label>
                        <input id="path" type="text" class="form-control" name="path"
                            placeholder="/dashboard/app-slug" value="">
                        <p class="text-info">
                            <small>{{ __('App page path (optional). Example: /dashboard/whatsapp-center') }}</small>
                        </p>
                        <p id="errpath" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="img">{{ __('Image URL') }}</label>
                        <input id="img" type="text" class="form-control" name="img"
                            placeholder="{{ __('Enter image URL') }}" value="">
                        <p class="text-info">
                            <small>{{ __('OR upload an image file below') }}</small>
                        </p>
                        <p id="errimg" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="image">{{ __('Upload Image') }}</label>
                        <input id="image" type="file" class="form-control" name="image" accept="image/*">
                        <p class="text-info">
                            <small>{{ __('Allowed: JPG, JPEG, PNG. Max size: 2MB') }}</small>
                        </p>
                        <div id="imagePreview" class="mt-2" style="display:none;">
                            <img id="previewImg" src="" alt="{{ __('Preview') }}" style="max-width: 200px; max-height: 200px;">
                        </div>
                        <p id="errimage" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="billing_type">{{ __('Billing Type') }}*</label>
                        <select id="billing_type" name="billing_type" class="form-control" required>
                            @php
                                $billingTypeLabels = [
                                    'free' => __('Free'),
                                    'paid' => __('Paid'),
                                    'paid_trial' => __('Paid with Trial'),
                                ];
                            @endphp
                            @foreach($billingTypes as $billingType)
                                <option value="{{ $billingType->value }}">{{ $billingTypeLabels[$billingType->value] ?? ucfirst(str_replace('_', ' ', $billingType->value)) }}</option>
                            @endforeach
                        </select>
                        <p id="errbilling_type" class="mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group" id="trial_days_group" style="display: none;">
                        <label for="trial_days">{{ __('Trial Days') }}*</label>
                        <input id="trial_days" type="number" min="1" class="form-control" name="trial_days"
                            placeholder="{{ __('Enter trial days') }}" value="">
                        <p id="errtrial_days" class="mb-0 text-danger em"></p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                <button id="submitBtn" type="button" class="btn btn-primary marketplace-submit">{{ __('Submit') }}</button>
            </div>
        </div>
    </div>
</div>

