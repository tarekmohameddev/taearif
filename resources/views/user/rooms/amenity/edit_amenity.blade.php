<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">
                    {{   __('Update Room Ammenity') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxEditForm" class="modal-form" action="{{ route('user.rooms_management.update_amenity') }}"
                    method="post">
                    @csrf
                    <input type="hidden" name="amenity_id" id="inid">

                    <div class="form-group">
                        <label for="">{{   __('Ammenity Name') . '*' }}</label>
                        <input type="text" id="inname" class="form-control" name="name"
                            placeholder="Enter Ammenity Name">
                        <p id="Eerr_name" class="mt-1 mb-0 text-danger em"></p>
                    </div>

                    <div class="form-group">
                        <label
                            for="">{{   __('Ammenity Serial Number') . '*' }}</label>
                        <input type="number" id="inserial_number" class="form-control " name="serial_number"
                            placeholder="Enter Ammenity Serial Number">
                        <p id="Eerr_serial_number" class="mt-1 mb-0 text-danger em"></p>
                        <p class="text-warning mt-2">
                            <small>{{ __('The higher the serial number is, the later the ammenity will be shown.') }}</small>
                        </p>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    {{   __('Close') }}
                </button>
                <button id="updateBtn" type="button" class="btn btn-primary">
                    {{   __('Update') }}
                </button>
            </div>
        </div>
    </div>
</div>
