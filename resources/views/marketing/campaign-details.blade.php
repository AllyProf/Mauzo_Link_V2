@extends('layouts.dashboard')

@section('title', 'Campaign Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-eye"></i> Campaign Details</h1>
    <p>{{ $campaign->name }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.dashboard') }}">Marketing</a></li>
    <li class="breadcrumb-item"><a href="{{ route('marketing.campaigns') }}">Campaigns</a></li>
    <li class="breadcrumb-item">Details</li>
  </ul>
</div>

<!-- Status Summary Card -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="card border-{{ $campaign->status === 'completed' ? 'success' : ($campaign->status === 'sending' ? 'warning' : ($campaign->status === 'failed' ? 'danger' : 'secondary')) }}">
      <div class="card-body">
        <div class="row align-items-center">
          <div class="col-md-3 text-center">
            @if($campaign->status === 'completed')
              <i class="fa fa-check-circle fa-3x text-success"></i>
              <h4 class="mt-2 text-success">Completed</h4>
            @elseif($campaign->status === 'sending')
              <i class="fa fa-spinner fa-spin fa-3x text-warning"></i>
              <h4 class="mt-2 text-warning">Sending...</h4>
            @elseif($campaign->status === 'failed')
              <i class="fa fa-times-circle fa-3x text-danger"></i>
              <h4 class="mt-2 text-danger">Failed</h4>
            @else
              <i class="fa fa-file fa-3x text-secondary"></i>
              <h4 class="mt-2 text-secondary">Draft</h4>
            @endif
          </div>
          <div class="col-md-9">
            <div class="row">
              <div class="col-md-3">
                <strong>Total Recipients:</strong>
                <h3>{{ number_format($campaign->total_recipients) }}</h3>
              </div>
              <div class="col-md-3">
                <strong class="text-success">Sent:</strong>
                <h3 class="text-success">{{ number_format($campaign->sent_count) }}</h3>
              </div>
              <div class="col-md-3">
                <strong class="text-danger">Failed:</strong>
                <h3 class="text-danger">{{ number_format($campaign->failed_count) }}</h3>
              </div>
              <div class="col-md-3">
                <strong>Success Rate:</strong>
                <h3>
                  @if($campaign->total_recipients > 0)
                    @php
                      $successRate = ($campaign->success_count / $campaign->total_recipients) * 100;
                    @endphp
                    <span class="badge badge-{{ $successRate >= 90 ? 'success' : ($successRate >= 70 ? 'warning' : 'danger') }} badge-lg">
                      {{ number_format($successRate, 1) }}%
                    </span>
                  @else
                    <span class="badge badge-secondary badge-lg">0%</span>
                  @endif
                </h3>
              </div>
            </div>
            @if($campaign->status === 'draft')
              <div class="alert alert-info mt-2 mb-0">
                <i class="fa fa-info-circle"></i> This campaign has not been sent yet. 
                <strong>Click the "Send Campaign Now" button in the Quick Actions panel to start sending.</strong>
                @if($campaign->sent_at)
                  <br><small class="text-warning"><i class="fa fa-exclamation-triangle"></i> A previous send attempt was made on {{ $campaign->sent_at->format('M d, Y H:i') }} but failed. Check the Notes/Error section below for details.</small>
                @endif
              </div>
            @elseif($campaign->status === 'sending')
              <div class="alert alert-warning mt-2 mb-0">
                <i class="fa fa-spinner fa-spin"></i> Campaign is currently being sent. This page will auto-refresh to show progress.
              </div>
            @elseif($campaign->status === 'completed')
              @if($campaign->failed_count > 0)
                <div class="alert alert-warning mt-2 mb-0">
                  <i class="fa fa-exclamation-triangle"></i> Campaign completed with {{ $campaign->failed_count }} failed message(s). Check the recipients table below for details.
                </div>
              @else
                <div class="alert alert-success mt-2 mb-0">
                  <i class="fa fa-check-circle"></i> All messages sent successfully!
                </div>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-8">
    <div class="tile">
      <h3 class="tile-title">Campaign Information</h3>
      <div class="tile-body">
        <table class="table table-bordered">
          <tr>
            <th width="200">Campaign Name:</th>
            <td><strong>{{ $campaign->name }}</strong></td>
          </tr>
          <tr>
            <th>Type:</th>
            <td><span class="badge badge-info">{{ ucfirst($campaign->type) }}</span></td>
          </tr>
          <tr>
            <th>Status:</th>
            <td data-campaign-status="{{ $campaign->status }}">
              @if($campaign->status === 'completed')
                <span class="badge badge-success badge-lg">
                  <i class="fa fa-check-circle"></i> Completed
                </span>
                <small class="text-muted ml-2">All messages processed</small>
              @elseif($campaign->status === 'sending')
                <span class="badge badge-warning badge-lg">
                  <i class="fa fa-spinner fa-spin"></i> Sending...
                </span>
                <small class="text-muted ml-2">Messages are being sent (auto-refreshing...)</small>
              @elseif($campaign->status === 'scheduled')
                <span class="badge badge-info badge-lg">
                  <i class="fa fa-clock"></i> Scheduled
                </span>
              @elseif($campaign->status === 'cancelled')
                <span class="badge badge-danger badge-lg">
                  <i class="fa fa-times-circle"></i> Cancelled
                </span>
              @else
                <span class="badge badge-secondary badge-lg">
                  <i class="fa fa-file"></i> Draft
                </span>
                <small class="text-muted ml-2">Not sent yet - Click "Send Campaign" to start</small>
              @endif
            </td>
          </tr>
          <tr>
            <th>Message:</th>
            <td>
              <div class="card">
                <div class="card-body">
                  <p style="white-space: pre-wrap;">{{ $campaign->message }}</p>
                </div>
              </div>
            </td>
          </tr>
          <tr>
            <th>Total Recipients:</th>
            <td><strong>{{ number_format($campaign->total_recipients) }}</strong></td>
          </tr>
          <tr>
            <th>Sent:</th>
            <td>
              <span class="badge badge-success badge-lg">
                <i class="fa fa-check"></i> {{ number_format($campaign->sent_count) }}
              </span>
              @if($campaign->status === 'completed' && $campaign->sent_count > 0)
                <small class="text-success ml-2"><i class="fa fa-check-circle"></i> Successfully delivered</small>
              @endif
            </td>
          </tr>
          <tr>
            <th>Failed:</th>
            <td>
              @if($campaign->failed_count > 0)
                <span class="badge badge-danger badge-lg">
                  <i class="fa fa-times"></i> {{ number_format($campaign->failed_count) }}
                </span>
                <small class="text-danger ml-2"><i class="fa fa-exclamation-triangle"></i> Check error messages below</small>
              @else
                <span class="badge badge-success badge-lg">
                  <i class="fa fa-check"></i> 0
                </span>
                <small class="text-success ml-2">No failures</small>
              @endif
            </td>
          </tr>
          <tr>
            <th>Success Rate:</th>
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
          </tr>
          <tr>
            <th>Estimated Cost:</th>
            <td>TSh {{ number_format($campaign->estimated_cost, 2) }}</td>
          </tr>
          <tr>
            <th>Actual Cost:</th>
            <td><strong>TSh {{ number_format($campaign->actual_cost, 2) }}</strong></td>
          </tr>
          <tr>
            <th>Created:</th>
            <td>{{ $campaign->created_at->format('M d, Y H:i') }}</td>
          </tr>
          @if($campaign->scheduled_at)
            <tr>
              <th>Scheduled For:</th>
              <td>{{ $campaign->scheduled_at->format('M d, Y H:i') }}</td>
            </tr>
          @endif
          @if($campaign->sent_at)
            <tr>
              <th>Sent At:</th>
              <td>{{ $campaign->sent_at->format('M d, Y H:i') }}</td>
            </tr>
          @endif
          @if($campaign->completed_at)
            <tr>
              <th>Completed At:</th>
              <td>{{ $campaign->completed_at->format('M d, Y H:i') }}</td>
            </tr>
          @endif
          @if($campaign->notes)
            <tr>
              <th>Notes / Error:</th>
              <td>
                <div class="alert alert-{{ strpos(strtolower($campaign->notes), 'error') !== false ? 'danger' : 'warning' }}">
                  <i class="fa fa-{{ strpos(strtolower($campaign->notes), 'error') !== false ? 'exclamation-triangle' : 'info-circle' }}"></i>
                  {{ $campaign->notes }}
                </div>
              </td>
            </tr>
          @endif
        </table>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="tile">
      <h3 class="tile-title">Quick Actions</h3>
      <div class="tile-body">
        <button class="btn btn-info btn-block mb-2" id="refresh-status-btn">
          <i class="fa fa-sync-alt"></i> Refresh Status
        </button>
        <a href="{{ route('marketing.campaigns') }}" class="btn btn-secondary btn-block mb-2">
          <i class="fa fa-arrow-left"></i> Back to Campaigns
        </a>
        @if($campaign->status === 'draft' || $campaign->status === 'sending')
          <button class="btn btn-success btn-block mb-2 send-campaign-btn" data-id="{{ $campaign->id }}" style="font-size: 1.1rem; padding: 12px; font-weight: bold;">
            <i class="fa fa-paper-plane"></i> {{ $campaign->status === 'sending' ? 'Retry Sending' : 'Send Campaign Now' }}
          </button>
          @if($campaign->status === 'draft')
            <div class="alert alert-info mb-2" style="font-size: 0.9rem;">
              <i class="fa fa-info-circle"></i> Click the button above to send this campaign to all recipients.
              @if($campaign->notes)
                <br><small class="text-warning"><i class="fa fa-exclamation-triangle"></i> Previous error will be cleared when you send.</small>
              @endif
            </div>
          @endif
        @endif
        @if($campaign->status === 'completed' && $campaign->recipients()->where('status', 'pending')->exists())
          <button class="btn btn-warning btn-block mb-2 send-campaign-btn" data-id="{{ $campaign->id }}">
            <i class="fa fa-redo"></i> Retry Pending Recipients
          </button>
        @endif
        <a href="{{ route('marketing.campaigns.create') }}" class="btn btn-primary btn-block">
          <i class="fa fa-plus"></i> Create New Campaign
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recipients List -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Recipients ({{ $campaign->recipients->count() }})</h3>
      <div class="tile-body">
        @if($campaign->recipients->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead>
                <tr>
                  <th>Phone Number</th>
                  <th>Customer Name</th>
                  <th>Status</th>
                  <th>Sent At</th>
                  <th>Error</th>
                </tr>
              </thead>
              <tbody>
                @foreach($campaign->recipients as $recipient)
                  <tr>
                    <td><strong>{{ $recipient->phone_number }}</strong></td>
                    <td>{{ $recipient->customer_name ?? 'N/A' }}</td>
                    <td>
                      @if($recipient->status === 'sent' || $recipient->status === 'delivered')
                        <span class="badge badge-success badge-lg">
                          <i class="fa fa-check-circle"></i> Sent
                        </span>
                      @elseif($recipient->status === 'failed')
                        <span class="badge badge-danger badge-lg">
                          <i class="fa fa-times-circle"></i> Failed
                        </span>
                      @else
                        <span class="badge badge-warning badge-lg">
                          <i class="fa fa-clock"></i> Pending
                        </span>
                      @endif
                    </td>
                    <td>{{ $recipient->sent_at ? $recipient->sent_at->format('M d, Y H:i') : 'N/A' }}</td>
                    <td>
                      @if($recipient->error_message)
                        <span class="text-danger">
                          <i class="fa fa-exclamation-triangle"></i> 
                          <small>{{ $recipient->error_message }}</small>
                        </span>
                      @elseif($recipient->status === 'sent' || $recipient->status === 'delivered')
                        <span class="text-success">
                          <i class="fa fa-check"></i> Delivered
                        </span>
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">No recipients found.</p>
        @endif
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .badge-lg {
    font-size: 0.9rem;
    padding: 0.5rem 0.75rem;
  }
  .status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 5px;
  }
  .status-indicator.sent { background-color: #28a745; }
  .status-indicator.failed { background-color: #dc3545; }
  .status-indicator.pending { background-color: #ffc107; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  let statusPollingInterval = null;
  const campaignId = {{ $campaign->id }};
  const currentStatus = '{{ $campaign->status }}';

  // Auto-refresh if campaign is sending
  if (currentStatus === 'sending') {
    // Show notification that auto-refresh is active
    const notification = $('<div>')
      .addClass('alert alert-info alert-dismissible fade show')
      .css({'position': 'fixed', 'top': '20px', 'right': '20px', 'z-index': '9999', 'min-width': '300px'})
      .html(`
        <strong><i class="fa fa-sync-alt fa-spin"></i> Auto-refreshing...</strong>
        <p class="mb-0">This page will refresh every 10 seconds to show latest status.</p>
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
      `);
    $('body').append(notification);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
      notification.fadeOut();
    }, 5000);
    
    startStatusPolling();
  }

  // Manual refresh button
  $('#refresh-status-btn').on('click', function() {
    const btn = $(this);
    const icon = btn.find('i');
    icon.addClass('fa-spin');
    location.reload();
  });

  // Start polling for status updates
  function startStatusPolling() {
    if (statusPollingInterval) {
      clearInterval(statusPollingInterval);
    }
    
    statusPollingInterval = setInterval(function() {
      checkCampaignStatus();
    }, 10000); // Reload every 10 seconds while sending
  }

  // Stop polling
  function stopStatusPolling() {
    if (statusPollingInterval) {
      clearInterval(statusPollingInterval);
      statusPollingInterval = null;
    }
  }

  // Check campaign status - reload page periodically while sending
  function checkCampaignStatus() {
    // Simple approach: reload page every 5 seconds while sending
    // This ensures we always see the latest status
    location.reload();
  }

  // Send campaign button
  $(document).on('click', '.send-campaign-btn', function() {
    const btn = $(this);
    const originalText = btn.html();
    
    Swal.fire({
      title: 'Send Campaign?',
      html: `
        <p>Are you sure you want to send this campaign now?</p>
        <div class="text-left">
          <strong>Campaign:</strong> {{ $campaign->name }}<br>
          <strong>Recipients:</strong> {{ $campaign->total_recipients }}<br>
          <strong>Estimated Cost:</strong> TSh {{ number_format($campaign->estimated_cost, 2) }}
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Send Now',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#28a745'
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');
        
        // Show loading alert
        Swal.fire({
          title: 'Sending Campaign...',
          html: 'Please wait while we send the campaign. This may take a few moments.',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });
        
        $.ajax({
          url: '/marketing/campaigns/' + campaignId + '/send',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}'
          },
          timeout: 60000, // 60 second timeout
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Campaign Started!',
              html: `
                <p>The campaign is now being sent to all recipients.</p>
                <p><strong>This page will auto-refresh to show progress.</strong></p>
              `,
              timer: 3000,
              showConfirmButton: false
            }).then(() => {
              // Start polling and reload
              startStatusPolling();
              setTimeout(() => {
                location.reload();
              }, 1000);
            });
          },
          error: function(xhr) {
            btn.prop('disabled', false).html(originalText);
            
            let errorMessage = 'Failed to send campaign';
            let errorDetails = '';
            
            if (xhr.responseJSON) {
              errorMessage = xhr.responseJSON.error || xhr.responseJSON.message || errorMessage;
              if (xhr.responseJSON.errors) {
                errorDetails = '<br><br><strong>Details:</strong><br>' + JSON.stringify(xhr.responseJSON.errors, null, 2);
              }
            } else if (xhr.responseText) {
              try {
                const parsed = JSON.parse(xhr.responseText);
                errorMessage = parsed.error || parsed.message || errorMessage;
              } catch (e) {
                errorDetails = '<br><br><small>' + xhr.responseText.substring(0, 200) + '</small>';
              }
            }
            
            if (xhr.status === 0) {
              errorMessage = 'Network error. Please check your internet connection and try again.';
            } else if (xhr.status === 500) {
              errorMessage = 'Server error occurred. Please try again or contact support.';
            }
            
            Swal.fire({
              icon: 'error',
              title: 'Failed to Send Campaign',
              html: `
                <p><strong>${errorMessage}</strong></p>
                ${errorDetails}
                <hr>
                <p class="text-muted small">If this problem persists, please check the error logs or contact support.</p>
              `,
              confirmButtonText: 'OK',
              confirmButtonColor: '#dc3545'
            });
            
            console.error('Campaign send error:', xhr);
          }
        });
      }
    });
  });

  // Clean up on page unload
  $(window).on('beforeunload', function() {
    stopStatusPolling();
  });
</script>
@endpush
@endsection

