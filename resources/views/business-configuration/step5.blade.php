<form action="{{ route('business-configuration.step4') }}" method="POST">
  @csrf
  <h3 class="mb-4">Step 4: Review & Complete</h3>
  
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="fa fa-building"></i> Business Information</h5>
    </div>
    <div class="card-body">
      <p><strong>Business Name:</strong> {{ auth()->user()->business_name }}</p>
      <p><strong>Phone:</strong> {{ auth()->user()->phone }}</p>
      <p><strong>Address:</strong> {{ auth()->user()->address }}, {{ auth()->user()->city }}, {{ auth()->user()->country }}</p>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header bg-info text-white">
      <h5 class="mb-0"><i class="fa fa-tags"></i> Business Types</h5>
    </div>
    <div class="card-body">
      @foreach($businessTypes as $businessType)
      <span class="badge badge-primary mr-2 mb-2">
        <i class="fa {{ $businessType->icon }}"></i> {{ $businessType->name }}
        @if($businessType->pivot->is_primary)
          <span class="badge badge-light">Primary</span>
        @endif
      </span>
      @endforeach
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0"><i class="fa fa-users"></i> Roles</h5>
    </div>
    <div class="card-body">
      @foreach($roles as $role)
      <div class="mb-2">
        <strong>{{ $role->name }}</strong>
        <small class="text-muted">({{ $role->permissions->count() }} permissions)</small>
      </div>
      @endforeach
    </div>
  </div>

  <div class="alert alert-success">
    <h5><i class="fa fa-check-circle"></i> Ready to Complete!</h5>
    <p>Review the information above and click "Complete Configuration" to finish setting up your business.</p>
  </div>

  <div class="mt-4">
    <a href="{{ route('business-configuration.step3') }}" class="btn btn-secondary">
      <i class="fa fa-arrow-left"></i> Previous
    </a>
    <button type="submit" class="btn btn-success btn-lg">
      <i class="fa fa-check-circle"></i> Complete Configuration
    </button>
  </div>
</form>

