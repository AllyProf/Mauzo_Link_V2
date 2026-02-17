@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-dashboard"></i> Dashboard</h1>
    <p>
      @if(session('is_staff'))
        Welcome back, {{ session('staff_name') }}!
      @elseif(auth()->check())
        @if(auth()->user()->business_name)
          Welcome to <strong>{{ auth()->user()->business_name }}</strong> | Welcome back, {{ auth()->user()->name }}!
        @else
          Welcome back, {{ auth()->user()->name }}!
        @endif
      @endif
    </p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
  </ul>
</div>

@php
  $hasPendingPayment = $pendingSubscription || ($pendingInvoices->count() > 0);
@endphp

@if($pendingSubscription)
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-danger" style="border-left: 5px solid #dc3545; background-color: #f8d7da; padding: 20px;">
      <h4 style="margin-top: 0; color: #721c24;"><i class="fa fa-lock"></i> Payment Required to Activate Subscription</h4>
      <p class="mb-3" style="font-size: 16px; color: #721c24;">You have selected <strong>{{ $pendingSubscription->plan->name }}</strong> but payment is required to activate your subscription. <strong>You cannot access any features until payment is completed.</strong></p>
      @php
        $pendingInvoice = \App\Models\Invoice::where('user_id', auth()->id())
            ->where('plan_id', $pendingSubscription->plan_id)
            ->whereIn('status', ['pending', 'paid'])
            ->latest()
            ->first();
      @endphp
      @if($pendingInvoice)
        <a href="{{ route('payments.instructions', $pendingInvoice) }}" class="btn btn-primary btn-lg">
          <i class="fa fa-credit-card"></i> Complete Payment Now
        </a>
      @else
        <a href="{{ route('upgrade.index') }}" class="btn btn-primary btn-lg">
          <i class="fa fa-arrow-up"></i> View Plans
        </a>
      @endif
    </div>
  </div>
</div>
@endif

@if($trialExpired)
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-danger" style="border-left: 5px solid #dc3545; background-color: #f8d7da; padding: 20px;">
      <h4 style="margin-top: 0; color: #721c24;"><i class="fa fa-exclamation-circle"></i> Your Free Trial Has Expired</h4>
      <p class="mb-3" style="font-size: 16px; color: #721c24;">Your 30-day free trial has ended. Please upgrade to continue using MauzoLink. <strong>You cannot access any features until you upgrade.</strong></p>
      <a href="{{ route('upgrade.index') }}" class="btn btn-primary btn-lg">
        <i class="fa fa-arrow-up"></i> Upgrade Now
      </a>
    </div>
  </div>
</div>
@elseif($trialExpiringSoon)
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-warning" style="border-left: 5px solid #ffc107; background-color: #fff3cd; padding: 20px;">
      <h4 style="margin-top: 0; color: #856404;"><i class="fa fa-clock-o"></i> Your Free Trial Expires Soon</h4>
      <p class="mb-3" style="font-size: 16px; color: #856404;">Your free trial will expire in <strong>{{ $trialDaysRemaining }} day(s)</strong>. Upgrade now to continue enjoying all features!</p>
      <a href="{{ route('upgrade.index') }}" class="btn btn-primary btn-lg">
        <i class="fa fa-arrow-up"></i> Upgrade Now
      </a>
    </div>
  </div>
</div>
@endif

@if($pendingInvoices->count() > 0)
<div class="row">
  <div class="col-md-12">
    <div class="alert alert-danger" style="border-left: 5px solid #dc3545; background-color: #f8d7da; padding: 20px;">
      <h4 style="margin-top: 0; color: #721c24;"><i class="fa fa-lock"></i> Payment Required - Access Restricted</h4>
      <p class="mb-3" style="font-size: 16px; color: #721c24;">You have <strong>{{ $pendingInvoices->count() }} pending invoice(s)</strong> that require payment. <strong>You cannot access Sales, Products, Customers, or Reports until payment is completed.</strong></p>
      <div class="mb-3">
        @foreach($pendingInvoices as $invoice)
        <div class="card mb-2" style="background-color: #fff; border: 1px solid #dc3545;">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-6">
                <h5 class="mb-1"><strong>Invoice #{{ $invoice->invoice_number }}</strong></h5>
                <p class="mb-0">Amount: <strong>{{ $invoice->formatted_amount }}</strong></p>
                <p class="mb-0">Plan: <strong>{{ $invoice->plan->name ?? 'N/A' }}</strong></p>
              </div>
              <div class="col-md-6 text-right">
                @if($invoice->status === 'pending')
                  <a href="{{ route('payments.instructions', $invoice) }}" class="btn btn-primary btn-lg">
                    <i class="fa fa-credit-card"></i> Make Payment
                  </a>
                @elseif($invoice->status === 'pending_verification' || ($invoice->status === 'paid' && !$invoice->verified_at))
                  <span class="badge badge-info badge-lg" style="font-size: 14px; padding: 8px 12px;">Awaiting Verification</span>
                  <br><br>
                  <a href="{{ route('payments.instructions', $invoice) }}" class="btn btn-outline-primary">
                    <i class="fa fa-eye"></i> View Status
                  </a>
                @elseif($invoice->status === 'verified')
                  <span class="badge badge-success badge-lg" style="font-size: 14px; padding: 8px 12px;">Verified</span>
                @endif
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      <p class="mb-0" style="font-size: 14px; color: #721c24;"><i class="fa fa-info-circle"></i> Once your payment is verified by our team, you will have full access to all features.</p>
    </div>
  </div>
</div>
@endif

@if($hasPendingPayment)
<div class="row">
  <div class="col-md-12">
    <div class="card" style="border: 2px dashed #dc3545; background-color: #fff3f3;">
      <div class="card-body text-center" style="padding: 40px;">
        <i class="fa fa-lock fa-4x text-danger mb-3"></i>
        <h3 class="text-danger">Access Restricted</h3>
        <p class="lead">Please complete your payment to unlock all features of MauzoLink.</p>
        <p>You can still access:</p>
        <ul class="list-unstyled">
          <li><i class="fa fa-check text-success"></i> Dashboard</li>
          <li><i class="fa fa-check text-success"></i> Payment & Invoice Management</li>
          <li><i class="fa fa-check text-success"></i> Settings</li>
        </ul>
        <p class="mt-3"><strong>You cannot access:</strong></p>
        <ul class="list-unstyled">
          <li><i class="fa fa-times text-danger"></i> Sales (POS, Orders, Transactions)</li>
          <li><i class="fa fa-times text-danger"></i> Products Management</li>
          <li><i class="fa fa-times text-danger"></i> Customers Management</li>
          <li><i class="fa fa-times text-danger"></i> Reports</li>
        </ul>
      </div>
    </div>
  </div>
</div>
@endif

@if(!$hasPendingPayment)
@if(auth()->user()->business_name)
@php
  $businessTypes = auth()->user()->enabledBusinessTypes()->get();
@endphp
<div class="row mb-4">
  <div class="col-md-12">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <div class="card-body text-white" style="padding: 30px;">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2 class="mb-2" style="color: white; font-weight: bold;">
              <i class="fa fa-building"></i> {{ auth()->user()->business_name }}
            </h2>
            @if($businessTypes->count() > 0)
              <p class="mb-2" style="color: rgba(255,255,255,0.9);">
                <i class="fa fa-tags"></i> <strong>Business Types:</strong>
                @foreach($businessTypes as $index => $businessType)
                  <span class="badge" style="background-color: rgba(255,255,255,0.25); color: white; padding: 5px 10px; margin-left: 5px; font-size: 12px;">
                    {{ $businessType->name }}
                  </span>
                @endforeach
              </p>
            @endif
            @if(auth()->user()->phone)
              <p class="mb-1" style="color: rgba(255,255,255,0.9);">
                <i class="fa fa-phone"></i> {{ auth()->user()->phone }}
              </p>
            @endif
            @if(auth()->user()->address)
              <p class="mb-0" style="color: rgba(255,255,255,0.9);">
                <i class="fa fa-map-marker"></i> {{ auth()->user()->address }}
                @if(auth()->user()->city)
                  , {{ auth()->user()->city }}
                @endif
                @if(auth()->user()->country)
                  , {{ auth()->user()->country }}
                @endif
              </p>
            @endif
          </div>
          <div class="col-md-4 text-right">
            <i class="fa fa-building fa-5x" style="opacity: 0.3;"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endif
<div class="row">
  <div class="col-md-6 col-lg-3">
    <div class="widget-small primary coloured-icon"><i class="icon fa fa-credit-card fa-3x"></i>
      <div class="info">
        <h4>Current Plan</h4>
        <p><b>{{ $currentPlan ? $currentPlan->name : 'No Plan' }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small info coloured-icon"><i class="icon fa fa-calendar fa-3x"></i>
      <div class="info">
        <h4>Subscription</h4>
        <p>
          @if($subscription && $subscription->is_trial)
            <b>Trial Active</b>
          @elseif($subscription && $subscription->status === 'active')
            <b>Active</b>
          @else
            <b>Inactive</b>
          @endif
        </p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small warning coloured-icon"><i class="icon fa fa-file-text-o fa-3x"></i>
      <div class="info">
        <h4>Invoices</h4>
        <p><b>{{ auth()->user()->invoices()->count() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="widget-small danger coloured-icon"><i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Payments</h4>
        <p><b>{{ auth()->user()->payments()->count() }}</b></p>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Monthly Sales</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas class="embed-responsive-item" id="lineChartDemo"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title">Support Requests</h3>
      <div class="embed-responsive embed-responsive-16by9">
        <canvas class="embed-responsive-item" id="pieChartDemo"></canvas>
      </div>
    </div>
  </div>
</div>
@endif
@endsection

@section('scripts')
@if(!$hasPendingPayment)
<!-- Page specific javascripts-->
<script type="text/javascript" src="{{ asset('js/admin/plugins/chart.js') }}"></script>
<script type="text/javascript">
  var data = {
  	labels: ["January", "February", "March", "April", "May"],
  	datasets: [
  		{
  			label: "My First dataset",
  			fillColor: "rgba(220,220,220,0.2)",
  			strokeColor: "rgba(220,220,220,1)",
  			pointColor: "rgba(220,220,220,1)",
  			pointStrokeColor: "#fff",
  			pointHighlightFill: "#fff",
  			pointHighlightStroke: "rgba(220,220,220,1)",
  			data: [65, 59, 80, 81, 56]
  		},
  		{
  			label: "My Second dataset",
  			fillColor: "rgba(151,187,205,0.2)",
  			strokeColor: "rgba(151,187,205,1)",
  			pointColor: "rgba(151,187,205,1)",
  			pointStrokeColor: "#fff",
  			pointHighlightFill: "#fff",
  			pointHighlightStroke: "rgba(151,187,205,1)",
  			data: [28, 48, 40, 19, 86]
  		}
  	]
  };
  var pdata = [
  	{
  		value: 300,
  		color: "#46BFBD",
  		highlight: "#5AD3D1",
  		label: "Complete"
  	},
  	{
  		value: 50,
  		color:"#F7464A",
  		highlight: "#FF5A5E",
  		label: "In-Progress"
  	}
  ]
  
  var ctxl = $("#lineChartDemo").get(0).getContext("2d");
  var lineChart = new Chart(ctxl).Line(data);
  
  var ctxp = $("#pieChartDemo").get(0).getContext("2d");
  var pieChart = new Chart(ctxp).Pie(pdata);
</script>
@endif
@endsection


