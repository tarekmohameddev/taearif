<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Edit Country') }}
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxEditForm" class="modal-form"
                    action="{{ route('user.property_management.update_country') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="in_id" name="id">

                    <div class="form-group  ">
                        <label for="">{{ __('Name') . '*' }}
                        </label>
                        <input type="text" id="in_name" class="form-control" name="name"
                            placeholder="{{ __('Enter country name') }}">
                        <p id="Eerr_name" class="mt-2 mb-0 text-danger em"></p>
                    </div>
                    <div class="form-group">
                        <label for="">{{ __('country name') . '*' }}</label>
                        <input type="text" id="in_serial_number" class="form-control ltr" name="name_en"
                            placeholder="Enter country name en">
                        <p id="Eerr_serial_number" class="mt-2 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2 mb-0">
                            
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
