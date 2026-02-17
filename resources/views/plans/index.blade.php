@extends('layouts.dashboard')

@section('title', 'Pricing Plans')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Pricing Plans</h1>
    <p>Choose the perfect plan for your business</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Plans</a></li>
  </ul>
</div>

<div class="row">
  @foreach($plans as $plan)
    <div class="col-md-4">
      <div class="tile" style="text-align: center; min-height: 500px;">
        <div class="tile-title-w-btn">
          <h3 class="title">{{ $plan->name }}</h3>
        </div>
        <div class="tile-body">
          <h2 class="text-primary">
            @if($plan->price == 0)
              BURE
            @else
              TSh {{ number_format($plan->price, 0) }}
            @endif
            <small>/mwezi</small>
          </h2>
          <p class="text-muted">{{ $plan->description }}</p>
          <hr>
          <ul style="text-align: left; list-style: none; padding: 0;">
            <li style="padding: 8px 0;"><i class="fa fa-check text-success"></i> {{ $plan->trial_days }} days free trial</li>
            <li style="padding: 8px 0;"><i class="fa fa-check text-success"></i> Up to {{ $plan->max_locations }} location(s)</li>
            <li style="padding: 8px 0;"><i class="fa fa-check text-success"></i> Up to {{ $plan->max_users }} user(s)</li>
            @if($plan->features)
              @foreach($plan->features as $feature)
                <li style="padding: 8px 0;"><i class="fa fa-check text-success"></i> {{ $feature }}</li>
              @endforeach
            @endif
          </ul>
        </div>
        <div class="tile-footer">
          <a href="{{ route('register', ['plan' => $plan->slug]) }}" class="btn btn-primary btn-block">
            <i class="fa fa-rocket"></i> Get Started
          </a>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endsection

