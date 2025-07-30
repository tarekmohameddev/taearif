@if (session('impersonator_admin_id'))
<div class="alert alert-warning mb-0" style="border-radius:0;">
    You are currently impersonating this account.
    <form action="{{ route('impersonate.stop') }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-sm btn-dark ml-2">Stop impersonating</button>
    </form>
</div>
@endif
