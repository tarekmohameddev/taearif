@extends('admin.layout')

@php
	$admin = $adminUser ?? Auth::guard('admin')->user();
	$permissions = $adminPermissions ?? [];
@endphp

@section('content')
  <div class="mt-2 mb-4">
    <h2 class="font-weight-bold" style="color: var(--primary-color);">{{__('Welcome back')}}, {{Auth::guard('admin')->user()->first_name}} {{Auth::guard('admin')->user()->last_name}}!</h2>
    <p class="text-muted">{{ __('Here is a summary of what is happening.') }}</p>
  </div>

  {{-- Flash Messages --}}
  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <strong>{{ __('Error') }}!</strong> {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <strong>{{ __('Success') }}!</strong> {{ session('success') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif

  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
      <strong>{{ __('Warning') }}!</strong> {{ session('warning') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif
  <div class="row">
		@if (empty($admin->role) || (!empty($permissions) && in_array('Registered Users', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.register.user')}}">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5; border-radius: 12px; padding: 10px;">
								<i data-lucide="users"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Registered Users')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['users'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Subscribers', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.subscriber.index')}}">
				<div class="card-body ">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-radius: 12px; padding: 10px;">
								<i data-lucide="mail"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Subscribers')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['subscribers'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Packages', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.package.index')}}">
				<div class="card-body ">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 12px; padding: 10px;">
								<i data-lucide="package"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Packages')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['packages'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif


		@if (empty($admin->role) || (!empty($permissions) && in_array('Payment Log', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.payment-log.index')}}">
				<div class="card-body ">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 12px; padding: 10px;">
								<i data-lucide="file-text"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Payment Logs')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['memberships'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

		@if (empty($admin->role) || (!empty($permissions) && in_array('Admins Management', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.user.index')}}">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(100, 116, 139, 0.15); color: #64748b; border-radius: 12px; padding: 10px;">
								<i data-lucide="shield-check"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Admins')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['admins'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

		@if (empty($admin->role) || (!empty($permissions) && in_array('Blogs', $permissions)))
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="{{route('admin.blog.index', ['language' => $defaultLang->code])}}">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(14, 165, 233, 0.15); color: #0ea5e9; border-radius: 12px; padding: 10px;">
								<i data-lucide="book-open"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{__('Blog')}}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($counts['blogs'] ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>
		@endif

		<!-- Customers total -->
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="#">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(139, 92, 246, 0.15); color: #8b5cf6; border-radius: 12px; padding: 10px;">
								<i data-lucide="users-round"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{ __('Customers') }}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($customersTotal ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>

		<!-- Projects total -->
		<div class="col-sm-6 col-md-3 col-lg-2">
			<a class="card card-stats card-round dashboard-stat-card" href="#">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border-radius: 12px; padding: 10px;">
								<i data-lucide="building-2"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{ __('Projects') }}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($projectsTotal ?? 0) }}</h4>
							</div>
						</div>
					</div>
				</div>
			</a>
		</div>

		@foreach(($propertiesPurposeTotals ?? collect()) as $row)
		<div class="col-sm-6 col-md-3 col-lg-2">
			<div class="card card-stats card-round dashboard-stat-card">
				<div class="card-body">
					<div class="row align-items-center">
						<div class="col-4">
							<div class="icon-big text-center" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border-radius: 12px; padding: 10px;">
								<i data-lucide="bar-chart-2"></i>
							</div>
						</div>
						<div class="col-8 col-stats">
							<div class="numbers">
								<p class="card-category text-muted mb-1">{{ __($row->purpose ?? 'Unknown') }}</p>
								<h4 class="card-title font-weight-bold mb-0">{{ number_format($row->total) }}</h4>
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
		var chartLabelIncome = @json(__('Monthly Income'));
		var chartLabelPremiumUsers = @json(__('Monthly Premium Users'));
	</script>
	<script src="{{asset('assets/admin/js/dashboard.js')}}"></script>
@endsection
