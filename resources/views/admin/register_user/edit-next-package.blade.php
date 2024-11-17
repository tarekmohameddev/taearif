<!-- Modal -->
<div class="modal fade" id="editNextPackage" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Change Next Package</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="changeNextPackageForm" action="{{route('user.nextPackage.change')}}" method="POST">
                    @csrf
                    <input type="hidden" name="user_id" value="{{$user->id}}">
                    <div class="form-group">
                        <label for="">Package **</label>
                        <select name="package_id" id="" class="form-control" required>
                            <option value="" selected disabled>Select a Package</option>
                            @foreach ($packages as $package)
                                <option value="{{$package->id}}" {{!empty($nextPackage) && $nextPackage->id == $package->id ? 'selected' : ''}}>{{$package->title}} ({{$package->term}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="" selected disabled>Select a Payment Method</option>
                            @foreach ($gateways as $gateway)
                                <option value="{{$gateway->name}}" >{{$gateway->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="submit" form="changeNextPackageForm" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>