@extends('layouts.dashboard')

@section('title', 'Reconciliations')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Reconciliations</h1>
    <p>View and verify stock transfers based on expected profit and revenue</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Accountant</a></li>
    <li class="breadcrumb-item">Reconciliations</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('accountant.reconciliations') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="start_date" class="mr-2">Start Date:</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="end_date" class="mr-2">End Date:</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="status" class="mr-2">Status:</label>
          <select name="status" id="status" class="form-control">
            <option value="">All Transfers</option>
            <option value="unverified" {{ $status === 'unverified' ? 'selected' : '' }}>Pending Verification</option>
            <option value="verified" {{ $status === 'verified' ? 'selected' : '' }}>Verified</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Filter
        </button>
        <a href="{{ route('accountant.reconciliations') }}" class="btn btn-secondary ml-2">
          <i class="fa fa-refresh"></i> Reset
        </a>
      </form>
    </div>
  </div>
</div>

<!-- Stock Transfers Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Reconciliations</h3>
      <div class="tile-body">
        @if($transfers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Expected Profit</th>
                  <th>Expected Revenue</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($transfers as $transfer)
                <tr>
                  <td><strong>{{ $transfer->transfer_number }}</strong></td>
                  <td>
                    <strong>{{ $transfer->productVariant->product->name ?? 'N/A' }}</strong><br>
                    <small class="text-muted">{{ $transfer->productVariant->measurement ?? '' }} - {{ $transfer->productVariant->packaging ?? '' }}</small>
                  </td>
                  <td>{{ number_format($transfer->total_units) }} bottle(s)</td>
                  <td><strong class="text-primary">TSh {{ number_format($transfer->expected_profit ?? 0, 2) }}</strong></td>
                  <td><strong class="text-info">TSh {{ number_format($transfer->expected_revenue ?? 0, 2) }}</strong></td>
                  <td>
                    @if($transfer->verified_at)
                      <span class="badge badge-success">Verified</span>
                      @if($transfer->verifiedBy)
                        <br><small class="text-muted">By: {{ $transfer->verifiedBy->full_name ?? 'N/A' }}</small>
                      @endif
                    @else
                      <span class="badge badge-warning">Pending Verification</span>
                    @endif
                  </td>
                  <td>
                    <div class="btn-group">
                      <button class="btn btn-sm btn-info view-transfer-details-btn" 
                              data-transfer-id="{{ $transfer->id }}">
                        <i class="fa fa-eye"></i> View
                      </button>
                      @if(!$transfer->verified_at)
                        <button class="btn btn-sm btn-success verify-transfer-btn" 
                                data-transfer-id="{{ $transfer->id }}"
                                data-transfer-number="{{ $transfer->transfer_number }}">
                          <i class="fa fa-check"></i> Verify
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          
          <!-- Pagination -->
          <div class="mt-3">
            {{ $transfers->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock transfers found for the selected criteria.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Modal for Transfer Details -->
<div class="modal fade" id="transferDetailsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fa fa-exchange-alt"></i> Stock Transfer Details
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="transferDetailsContent" style="max-height: 70vh; overflow-y: auto;">
        <div class="text-center py-5">
          <i class="fa fa-spinner fa-spin fa-3x text-primary"></i>
          <p class="mt-3">Loading transfer details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
          <i class="fa fa-times"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  .bg-gradient-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  }
  .card {
    transition: transform 0.2s;
  }
  .card:hover {
    transform: translateY(-2px);
  }
  .progress {
    border-radius: 10px;
    overflow: hidden;
  }
  .progress-bar {
    font-weight: bold;
    font-size: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  #transferDetailsContent {
    padding: 20px;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // View Transfer Details
  $(document).on('click', '.view-transfer-details-btn', function() {
    const transferId = $(this).data('transfer-id');
    const modal = $('#transferDetailsModal');
    const content = $('#transferDetailsContent');
    
    modal.modal('show');
    content.html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading transfer details...</p></div>');
    
    $.ajax({
      url: '{{ route("bar.stock-transfers.show", ":id") }}'.replace(':id', transferId),
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      },
      success: function(response) {
        if (response.success && response.transfer) {
          const transfer = response.transfer;
          
          // Calculate percentages
          const profitPercentage = transfer.expected_profit > 0 
            ? ((transfer.real_time_profit / transfer.expected_profit) * 100).toFixed(1) 
            : 0;
          const revenuePercentage = transfer.expected_revenue > 0 
            ? ((transfer.real_time_revenue / transfer.expected_revenue) * 100).toFixed(1) 
            : 0;
          
          // Determine profit status
          const profitStatus = transfer.real_time_profit >= transfer.expected_profit ? 'success' : 
                              transfer.real_time_profit > 0 ? 'warning' : 'danger';
          const revenueStatus = transfer.real_time_revenue >= transfer.expected_revenue ? 'success' : 
                               transfer.real_time_revenue > 0 ? 'warning' : 'danger';
          
          let html = `
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                  <i class="fa fa-exchange-alt"></i> Transfer #${transfer.transfer_number}
                </h5>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <h6 class="text-muted mb-3"><i class="fa fa-box"></i> Product Information</h6>
                    <table class="table table-sm table-borderless">
                      <tr>
                        <td class="text-muted" style="width: 40%;">Product:</td>
                        <td><strong>${transfer.product_name || 'N/A'}</strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Variant:</td>
                        <td><strong>${transfer.variant_measurement || ''} - ${transfer.variant_packaging || ''}</strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Quantity:</td>
                        <td><strong>${transfer.quantity_requested || 0} package(s)</strong> <small class="text-muted">(${transfer.total_units || 0} bottle(s))</small></td>
                      </tr>
                    </table>
                  </div>
                  <div class="col-md-6">
                    <h6 class="text-muted mb-3"><i class="fa fa-info-circle"></i> Transfer Details</h6>
                    <table class="table table-sm table-borderless">
                      <tr>
                        <td class="text-muted" style="width: 40%;">Status:</td>
                        <td>${getTransferStatusBadge(transfer.status)}</td>
                      </tr>
                      <tr>
                        <td class="text-muted">Requested By:</td>
                        <td><strong>${transfer.requested_by_name || 'N/A'}</strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Approved By:</td>
                        <td><strong>${transfer.approved_by_name || 'N/A'}</strong></td>
                      </tr>
                      <tr>
                        <td class="text-muted">Completed Date:</td>
                        <td><strong>${transfer.completed_date || 'N/A'}</strong></td>
                      </tr>
                      ${transfer.verified_by ? `
                      <tr>
                        <td class="text-muted">Verified By:</td>
                        <td><strong>${transfer.verified_by}</strong><br><small class="text-muted">${transfer.verified_at || ''}</small></td>
                      </tr>
                      ` : ''}
                    </table>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fa fa-chart-line"></i> Profit Analysis</h6>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <small class="text-muted d-block">Expected Profit</small>
                        <h4 class="mb-0">TSh ${formatNumber(transfer.expected_profit || 0)}</h4>
                      </div>
                      <div class="text-right">
                        <small class="text-muted d-block">Real-Time Profit</small>
                        <h4 class="mb-0 text-${profitStatus}">TSh ${formatNumber(transfer.real_time_profit || 0)}</h4>
                      </div>
                    </div>
                    <div class="progress" style="height: 25px;">
                      <div class="progress-bar bg-${profitStatus}" role="progressbar" 
                           style="width: ${Math.min(profitPercentage, 100)}%" 
                           aria-valuenow="${profitPercentage}" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                        ${profitPercentage}%
                      </div>
                    </div>
                    <div class="mt-2 text-center">
                      <small class="text-muted">
                        ${transfer.real_time_profit >= transfer.expected_profit ? 
                          '<i class="fa fa-check-circle text-success"></i> Target Achieved' : 
                          transfer.real_time_profit > 0 ? 
                            '<i class="fa fa-clock text-warning"></i> In Progress' : 
                            '<i class="fa fa-times-circle text-danger"></i> Not Started'}
                      </small>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fa fa-dollar-sign"></i> Revenue Analysis</h6>
                  </div>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <small class="text-muted d-block">Expected Revenue</small>
                        <h4 class="mb-0">TSh ${formatNumber(transfer.expected_revenue || 0)}</h4>
                      </div>
                      <div class="text-right">
                        <small class="text-muted d-block">Real-Time Revenue</small>
                        <h4 class="mb-0 text-${revenueStatus}">TSh ${formatNumber(transfer.real_time_revenue || 0)}</h4>
                      </div>
                    </div>
                    <div class="progress" style="height: 25px;">
                      <div class="progress-bar bg-${revenueStatus}" role="progressbar" 
                           style="width: ${Math.min(revenuePercentage, 100)}%" 
                           aria-valuenow="${revenuePercentage}" 
                           aria-valuemin="0" 
                           aria-valuemax="100">
                        ${revenuePercentage}%
                      </div>
                    </div>
                    <div class="mt-2">
                      ${transfer.real_time_revenue_pending > 0 ? `
                        <div class="alert alert-warning py-2 mb-0">
                          <small><i class="fa fa-exclamation-triangle"></i> <strong>Pending:</strong> TSh ${formatNumber(transfer.real_time_revenue_pending)}</small>
                        </div>
                      ` : `
                        <div class="text-center">
                          <small class="text-muted">
                            ${transfer.real_time_revenue >= transfer.expected_revenue ? 
                              '<i class="fa fa-check-circle text-success"></i> Target Achieved' : 
                              transfer.real_time_revenue > 0 ? 
                                '<i class="fa fa-clock text-warning"></i> In Progress' : 
                                '<i class="fa fa-times-circle text-danger"></i> Not Started'}
                          </small>
                        </div>
                      `}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          `;
          
          if (transfer.notes) {
            html += `
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                  <h6 class="mb-0"><i class="fa fa-sticky-note"></i> Notes</h6>
                </div>
                <div class="card-body">
                  <p class="mb-0">${transfer.notes}</p>
                </div>
              </div>
            `;
          }
          
          content.html(html);
        } else {
          content.html(`
            <div class="alert alert-danger">
              <i class="fa fa-exclamation-circle"></i> Failed to load transfer data.
            </div>
          `);
        }
      },
      error: function(xhr) {
        console.error('Error loading transfer details:', xhr);
        let errorMessage = 'Failed to load transfer details. Please try again.';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        } else if (xhr.status === 403) {
          errorMessage = 'You do not have permission to view this transfer.';
        } else if (xhr.status === 404) {
          errorMessage = 'Transfer not found.';
        } else if (xhr.status === 500) {
          errorMessage = 'Server error. Please contact administrator.';
        }
        
        content.html(`
          <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> ${errorMessage}
            ${xhr.status ? `<br><small>Status: ${xhr.status}</small>` : ''}
          </div>
        `);
      }
    });
  });
  
  function getTransferStatusBadge(status) {
    const badges = {
      'completed': '<span class="badge badge-success">Completed</span>',
      'approved': '<span class="badge badge-info">Approved</span>',
      'prepared': '<span class="badge badge-warning">Prepared</span>',
      'pending': '<span class="badge badge-warning">Pending</span>',
      'rejected': '<span class="badge badge-danger">Rejected</span>'
    };
    return badges[status] || '<span class="badge badge-secondary">' + status + '</span>';
  }
  
  function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {maximumFractionDigits: 2});
  }

  // Verify Stock Transfer
  $(document).on('click', '.verify-transfer-btn', function() {
    const transferId = $(this).data('transfer-id');
    const transferNumber = $(this).data('transfer-number') || 'this transfer';
    const btn = $(this);
    
    Swal.fire({
      title: 'Verify Stock Transfer?',
      html: `Are you sure you want to verify stock transfer <strong>${transferNumber}</strong>?<br><small class="text-muted">This action will mark the transfer as verified based on expected profit and revenue.</small>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Verify',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
        
        $.ajax({
          url: '{{ route("accountant.verify-stock-transfer", ":id") }}'.replace(':id', transferId),
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Verified!',
                text: 'Stock transfer verified successfully.',
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to verify stock transfer';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Verify');
          }
        });
      }
    });
  });
});
</script>
@endpush
