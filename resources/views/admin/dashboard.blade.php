@extends('admin.layout')

@php
	$admin = Auth::guard('admin')->user();
	if (!empty($admin->role)) {
		$permissions = $admin->role->permissions;
		$permissions = json_decode($permissions, true);
	}
@endphp

@section('content')
  <div class="mt-2 mb-4">
    <h2 class="text-white pb-2">{{__('Welcome back')}}, {{Auth::guard('admin')->user()->first_name}} {{Auth::guard('admin')->user()->last_name}}!</h2>
  </div>

  {{-- Flash Messages --}}
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>Error!</strong> {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>Success!</strong> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong>Warning!</strong> {{ session('warning') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif
  <div class="row">
		@if (empty($admin->role) || (!empty($permissions) && in_array('Registered Users', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-info card-round" href="{{route('admin.register.user')}}">
				<div class="card-body">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-users"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Registered Users')}}</p>
								<h4 class="card-title">{{App\Models\User::count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Subscribers', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-warning card-round" href="{{route('admin.subscriber.index')}}">
				<div class="card-body ">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-mail-bulk"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Subscribers')}}</p>
								<h4 class="card-title">{{App\Models\Subscriber::count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Packages', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-success card-round" href="{{route('admin.package.index')}}">
				<div class="card-body ">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-list-ul"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Packages')}}</p>
								<h4 class="card-title">{{App\Models\Package::count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Payment Log', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-danger card-round" href="{{route('admin.payment-log.index')}}">
				<div class="card-body ">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-money-check-alt"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Payment Logs')}}</p>
								<h4 class="card-title">{{App\Models\Membership::count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

		@if (empty($admin->role) || (!empty($permissions) && in_array('Admins Management', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-secondary card-round" href="{{route('admin.user.index')}}">
				<div class="card-body ">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-users-cog"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Admins')}}</p>
								<h4 class="card-title">{{App\Models\Admin::count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

		@if (empty($admin->role) || (!empty($permissions) && in_array('Blogs', $permissions)))
		<div class="col-sm-4 col-md-2">
			<a class="card card-stats card-primary card-round" href="{{route('admin.blog.index', ['language' => $defaultLang->code])}}">
				<div class="card-body ">
					<div class="row">
						<div class="col-5">
							<div class="icon-big text-center">
								<i class="fas fa-users-cog"></i>
							</div>
						</div>
						<div class="col-7 col-stats">
							<div class="numbers">
								<p class="card-category">{{__('Blog')}}</p>
								<h4 class="card-title">{{$defaultLang->blogs()->count()}}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

  <!--  Customers total-->
  <div class="col-sm-4 col-md-2">
  <a class="card card-stats card-primary card-round" href="#">
    <div class="card-body">
      <div class="row">
        <div class="col-5">
          <div class="icon-big text-center"><i class="fas fa-user-friends"></i></div>
        </div>
        <div class="col-7 col-stats">
          <div class="numbers">
            <p class="card-category">{{ __('Customers') }}</p>
            <h4 class="card-title">{{ number_format($customersTotal ?? 0) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </a>
</div>
<!--  -->
<!--  Projects total-->
<div class="col-sm-4 col-md-2">
  <a class="card card-stats card-warning card-round" href="#">
    <div class="card-body">
      <div class="row">
        <div class="col-5">
          <div class="icon-big text-center"><i class="fas fa-city"></i></div>
        </div>
        <div class="col-7 col-stats">
          <div class="numbers">
            <p class="card-category">{{ __('Projects') }}</p>
            <h4 class="card-title">{{ number_format($projectsTotal ?? 0) }}</h4>
          </div>
        </div>
      </div>
    </div>
  </a>
</div>
<!--  -->
<!--  -->
@foreach(($propertiesPurposeTotals ?? collect()) as $row)
  <div class="col-sm-4 col-md-2">
    <div class="card card-stats card-secondary card-round">
      <div class="card-body">
        <div class="row">
          <div class="col-5">
            <div class="icon-big text-center"><i class="fas fa-exchange-alt"></i></div>
          </div>
          <div class="col-7 col-stats">
            <div class="numbers">
              <p class="card-category">
                {{ __($row->purpose ?? 'Unknown') }}
              </p>
              <h4 class="card-title">{{ number_format($row->total) }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endforeach

<!--  -->

	</div>



	<div class="row">
		@if (empty($admin->role) || (!empty($permissions) && in_array('Payment Log', $permissions)))
		<div class="col-lg-6">
			<div class="card">
				<div class="card-header">
					<div class="card-title">{{__('Monthly Income')}} ({{date('Y')}})</div>
				</div>
				<div class="card-body">
					<div class="chart-container">
						<canvas id="lineChart"></canvas>
					</div>
				</div>
			</div>
		</div>
		@endif

		@if (empty($admin->role) || (!empty($permissions) && in_array('Registered Users', $permissions)))
		<div class="col-lg-6">
			<div class="card">
				<div class="card-header">
					<div class="card-title">{{__('Monthly Premium Users')}} ({{date('Y')}})</div>
				</div>
				<div class="card-body">
					<div class="chart-container">
						<canvas id="usersChart"></canvas>
					</div>
				</div>
			</div>
		</div>
		@endif
	  </div>




@endsection

@php
	$months = [];
	$inTotals = [];

	for ($i=1; $i <= 12; $i++) {
		$monthNum  = $i;
		$dateObj   = DateTime::createFromFormat('!m', $monthNum);
		$months[] = $dateObj->format('M');

		$inFound = 0;
		foreach ($incomes as $key => $income) {
			if ($income->month == $i) {
				$inTotals[] = $income->total;
				$inFound = 1;
				break;
			}
		}
		if ($inFound == 0) {
			$inTotals[] = 0;
		}

		$userFound = 0;
		foreach ($users as $key => $user) {
			if ($user->month == $i) {
				$userTotals[] = $user->total;
				$userFound = 1;
				break;
			}
		}
		if ($userFound == 0) {
			$userTotals[] = 0;
		}
	}

@endphp
@section('scripts')
	<!-- Chart JS -->
	<script src="{{asset('assets/admin/js/plugin/chart.min.js')}}"></script>
	<script>
		"use strict";
		var months = @php echo json_encode($months) @endphp;
		var inTotals = @php echo json_encode($inTotals) @endphp;
		var userTotals = @php echo json_encode($userTotals) @endphp;
	</script>
	<script src="{{asset('assets/admin/js/dashboard.js')}}"></script>
@endsection
