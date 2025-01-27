@extends('user.layout')

@php
$default = \App\Models\User\Language::where('is_default', 1)->first();
$user = Auth::guard('web')->user();
$package = \App\Http\Helpers\UserPermissionHelper::currentPackagePermission($user->id);
if (!empty($user)) {
$permissions = \App\Http\Helpers\UserPermissionHelper::packagePermission($user->id);
$permissions = json_decode($permissions, true);
}
Config::set('app.timezone', $userBs->timezoneinfo->timezone??'');
@endphp
@section('content')
<div class="mt-2 mb-4">

</div>
<style>
    :root {
        --primary: rgb(0, 169, 145);
        --primary-dark: rgb(0, 149, 125);
    }

    .bg-primary {
        background-color: var(--primary) !important;
    }

    .btn-primary {
        background-color: var(--primary);
        border-color: var(--primary);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
    }

    .text-primary {
        color: var(--primary) !important;
    }

    .stats-card {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .shipments-banner {
        background: linear-gradient(to left, #ffe4e6, #ccfbf1);
        border-radius: 0.5rem;
        position: relative;
        overflow: hidden;
    }

    .progress {
        height: 0.5rem;
    }

    .progress-bar {
        background-color: var(--primary);
    }

    .website-settings .card,
    .website-settings .card * {
        /* Force all elements inside card to inherit proper white-space */
        white-space: normal !important;
    }

    .website-settings .card-body {
        display: flex !important;
        align-items: flex-start !important;
        gap: 1rem !important;
        /* Adds space between icon and text */
    }

    .bg-primary-light {
        background-color: rgba(0, 169, 145, 0.1);
    }

    .text-primary {
        color: rgb(0, 169, 145) !important;
    }

    .transition-hover {
        transition: all 0.3s ease;
    }

    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .card {
        border: 1px solid #e9ecef;
    }
</style>
            <!-- Next Steps & Store Status -->
            @php
            $completedSteps = collect($steps)->where('completed', true)->count();
            $totalSteps = count($steps);
            $progress = ($completedSteps / $totalSteps) * 100;
            @endphp

<div calss="row">
    <div class="container-fluid website-settings">
        <h1 class="h3 mb-4 font-weight-bold text-dark">خطوات اعدادات بيانات الموقع  الصحيحة</h1>
        </div>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card" style="border-radius: 15px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
            <div class="card-body" style="padding: 2rem;">
                <h5 class="card-title mb-4" style="font-size: 1.5rem; color: #333; border-bottom: 2px solid var(--primary); padding-bottom: 0.5rem;">يرجى إكمال الخطوات التالية</h5>

                <div class="d-flex flex-column gap-3">
                    @foreach($steps as $step)
                        <div class="d-flex align-items-center gap-3"
                            style="margin-bottom:5px;  padding: 10px; border-radius: 10px; transition: all 0.3s ease; cursor: pointer;
                            {{ !$step['completed'] ? 'background-color: #eeeeee6b;' : '' }}"
                            onmouseover="this.style.backgroundColor='rgba(0, 169, 145, 0.1)'"
                            onmouseout="this.style.backgroundColor='{{ !$step['completed'] ? '#eeeeee6b' : 'transparent' }}'">

                            @if(!$step['completed'])
                                <a href="{{route($step['url']). '?language=' . $default->code }}" style="text-decoration: none; color: inherit; display: flex; align-items: center; width: 100%;">
                            @endif

                            <div style="width: 24px; height: 24px; border-radius: 50%;
                            {{ $step['completed'] ? 'background-color: var(--primary);' : 'border: 2px solid #ccc;' }} display: flex;
                            justify-content: center;
                            align-items: center;
                            margin-right: 12px;">
                                @if($step['completed'])
                                    <i class="bi bi-check-lg" style="color: white; font-size: 14px;"></i>
                                @endif
                            </div>
                            <span style="font-size: 1rem; color: {{ $step['completed'] ? '#333' : '#666' }}; {{ $step['completed'] ? 'text-decoration: underline;' : '' }}">
                                {{ $step['title'] }}
                            </span>

                            @if(!$step['completed'])
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>


            </div>
        </div>
    </div>
</div>
    </div>
</div>

@endsection
