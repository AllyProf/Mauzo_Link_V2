@extends('layouts.dashboard')

@section('title', 'Business Configuration')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cog"></i> Business Configuration</h1>
    <p>Set up your business in a few simple steps</p>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <!-- Progress Steps -->
    <div class="card mb-4">
      <div class="card-body">
        <ul class="nav nav-pills nav-justified" id="wizardSteps">
          <li class="nav-item">
            <span class="nav-link {{ $step >= 1 ? ($step == 1 ? 'active' : 'completed') : '' }}">
              <i class="fa fa-info-circle"></i><br>
              <small>Business Info</small>
            </span>
          </li>
          <li class="nav-item">
            <span class="nav-link {{ $step >= 2 ? ($step == 2 ? 'active' : 'completed') : '' }}">
              <i class="fa fa-building"></i><br>
              <small>Business Type</small>
            </span>
          </li>
          <li class="nav-item">
            <span class="nav-link {{ $step >= 3 ? ($step == 3 ? 'active' : 'completed') : '' }}">
              <i class="fa fa-users"></i><br>
              <small>Roles & Permissions</small>
            </span>
          </li>
          <li class="nav-item">
            <span class="nav-link {{ $step >= 4 ? ($step == 4 ? 'active' : 'completed') : '' }}">
              <i class="fa fa-check-circle"></i><br>
              <small>Review & Complete</small>
            </span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Step Content -->
    <div class="card">
      <div class="card-body">
        @if(isset($include_step))
          @include('business-configuration.' . $include_step)
        @elseif($step == 1)
          @include('business-configuration.step1')
        @elseif($step == 2)
          @include('business-configuration.step2')
        @elseif($step == 3)
          @include('business-configuration.step3')
        @elseif($step == 4)
          @include('business-configuration.step5')
        @endif
      </div>
    </div>
  </div>
</div>

<style>
.nav-pills .nav-link.active {
  background-color: #940000;
  color: white;
}
.nav-pills .nav-link {
  color: #940000;
  border: 1px solid #940000;
  pointer-events: none;
  opacity: 0.5;
  cursor: default;
}
.nav-pills .nav-link.active,
.nav-pills .nav-link.completed {
  pointer-events: auto;
  opacity: 1;
}
.nav-pills .nav-link.completed {
  background-color: #28a745;
  color: white;
  border-color: #28a745;
}
</style>
@endsection
