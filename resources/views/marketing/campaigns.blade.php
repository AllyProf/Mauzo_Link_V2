@extends('layouts.dashboard')

@section('title', 'Campaign History')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list"></i> Campaign History</h1>
    <p>View and manage all your SMS campaigns</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
    <li class="breadcrumb-item">Campaigns</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">All Campaigns</h3>
        <div class="btn-group">
          <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Create Campaign
          </a>
        </div>
      </div>
      <div class="tile-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('marketing.campaigns') }}" class="mb-4">
          <div class="row">
            <div class="col-md-3">
              <select name="status" class="form-control">
                <option value="">All Statuses</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="sending" {{ request('status') === 'sending' ? 'selected' : '' }}>Sending</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              </select>
            </div>
            <div class="col-md-3">
              <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From Date">
            </div>
            <div class="col-md-3">
              <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To Date">
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary btn-block">
                <i class="fa fa-filter"></i> Filter
              </button>
            </div>
          </div>
        </form>

        @if($campaigns->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Campaign Name</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>Recipients</th>
                  <th>Sent</th>
                  <th>Failed</th>
                  <th>Success Rate</th>
                  <th>Cost</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($campaigns as $campaign)
                  <tr>
                    <td><strong>{{ $campaign->name }}</strong></td>
                    <td>
                      <span class="badge badge-info">{{ ucfirst($campaign->type) }}</span>
                    </td>
                    <td>
                      <span class="badge badge-{{ $campaign->status === 'completed' ? 'success' : ($campaign->status === 'sending' ? 'warning' : ($campaign->status === 'scheduled' ? 'info' : ($campaign->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                        {{ ucfirst($campaign->status) }}
                      </span>
                    </td>
                    <td>{{ number_format($campaign->total_recipients) }}</td>
                    <td>{{ number_format($campaign->sent_count) }}</td>
                    <td>{{ number_format($campaign->failed_count) }}</td>
                    <td>
                      @if($campaign->total_recipients > 0)
                        @php
                          $successRate = ($campaign->success_count / $campaign->total_recipients) * 100;
                        @endphp
                        <span class="badge badge-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }}">
                          {{ number_format($successRate, 1) }}%
                        </span>
                      @else
                        0%
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($campaign->actual_cost, 2) }}</strong></td>
                    <td>{{ $campaign->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      <a href="{{ route('marketing.campaigns.show', $campaign->id) }}" class="btn btn-sm btn-info">
                        <i class="fa fa-eye"></i> View
                      </a>
                      @if($campaign->status === 'draft')
                        <button class="btn btn-sm btn-success send-campaign-btn" data-id="{{ $campaign->id }}">
                          <i class="fa fa-paper-plane"></i> Send
                        </button>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-center">
            {{ $campaigns->links() }}
          </div>
        @else
          <p class="text-muted">No campaigns found. <a href="{{ route('marketing.campaigns.create') }}">Create your first campaign</a></p>
        @endif
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).on('click', '.send-campaign-btn', function() {
    const campaignId = $(this).data('id');
    Swal.fire({
      title: 'Send Campaign?',
      text: 'Are you sure you want to send this campaign now?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Send Now',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/marketing/campaigns/' + campaignId + '/send',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Campaign is being sent...'
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: xhr.responseJSON?.error || 'Failed to send campaign'
            });
          }
        });
      }
    });
  });
</script>
@endpush
@endsection







