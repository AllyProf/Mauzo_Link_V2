@extends('layouts.dashboard')

@section('title', 'Stock Transfers')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Stock Transfers</h1>
    <p>Transfer stock from warehouse to counter</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Stock Transfers</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Stock Transfers</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.history') }}" class="btn btn-info mr-2">
            <i class="fa fa-history"></i> View History
          </a>
          <a href="{{ route('bar.stock-transfers.available') }}" class="btn btn-success mr-2">
            <i class="fa fa-cubes"></i> Browse Available Products
          </a>
          <a href="{{ route('bar.stock-transfers.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> New Transfer Request
          </a>
        </div>
      </div>

      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        @if($transfers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="transfersTable">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Total Bottles</th>
                  <th>Expected Profit</th>
                  <th>Status</th>
                  <th>Requested By</th>
                  <th>Requested Date</th>
                  <th>Approved Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($transfers as $transfer)
                  <tr>
                    <td><strong>{{ $transfer->transfer_number }}</strong></td>
                    <td>
                      {{ $transfer->productVariant->product->name ?? 'N/A' }}<br>
                      <small class="text-muted">{{ $transfer->productVariant->measurement ?? '' }} - {{ $transfer->productVariant->packaging ?? '' }}</small>
                    </td>
                    <td>
                      @php
                        $packagingType = strtolower($transfer->productVariant->packaging ?? 'packages');
                        $packagingTypeSingular = rtrim($packagingType, 's');
                        if ($packagingTypeSingular == 'boxe') {
                          $packagingTypeSingular = 'box';
                        }
                        $packagingDisplay = $transfer->quantity_requested == 1 ? $packagingTypeSingular : $packagingType;
                      @endphp
                      {{ $transfer->quantity_requested }} {{ ucfirst($packagingDisplay) }}
                    </td>
                    <td>{{ number_format($transfer->total_units) }} bottle(s)</td>
                    <td>
                      @if(isset($transfer->expected_profit) && $transfer->expected_profit > 0)
                        <strong class="text-primary">TSh {{ number_format($transfer->expected_profit, 2) }}</strong>
                        @if($transfer->status === 'completed')
                          <br><small class="text-muted">When all sold</small>
                        @endif
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($transfer->status === 'pending')
                        <span class="badge badge-warning">Pending</span>
                      @elseif($transfer->status === 'approved')
                        <span class="badge badge-success">Approved</span>
                      @elseif($transfer->status === 'prepared')
                        <span class="badge badge-info">Prepared</span>
                      @elseif($transfer->status === 'rejected')
                        <span class="badge badge-danger">Rejected</span>
                      @elseif($transfer->status === 'completed')
                        <span class="badge badge-primary">Completed</span>
                      @else
                        <span class="badge badge-info">{{ ucfirst($transfer->status) }}</span>
                      @endif
                    </td>
                    <td>{{ $transfer->requestedBy->name ?? 'N/A' }}</td>
                    <td>{{ $transfer->created_at->format('M d, Y H:i') }}</td>
                    <td>
                      @if($transfer->approved_at)
                        {{ $transfer->approved_at->format('M d, Y H:i') }}
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $canApprove = false;
                        if (session('is_staff')) {
                          $staff = \App\Models\Staff::find(session('staff_id'));
                          if ($staff && $staff->role) {
                            $canApprove = $staff->role->hasPermission('stock_transfer', 'edit');
                            // Allow stock keeper role even without explicit permission
                            if (!$canApprove) {
                              $roleName = strtolower(trim($staff->role->name ?? ''));
                              if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                                $canApprove = true;
                              }
                            }
                          }
                        } else {
                          $user = Auth::user();
                          $canApprove = $user && ($user->hasPermission('stock_transfer', 'edit') || $user->hasRole('owner'));
                        }
                      @endphp
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-info btn-sm view-transfer-btn" data-transfer-id="{{ $transfer->id }}" title="View Details">
                          <i class="fa fa-eye"></i>
                        </button>
                        @if($transfer->status === 'pending' && $canApprove)
                          <button type="button" class="btn btn-success btn-sm approve-btn" data-transfer-id="{{ $transfer->id }}" data-transfer-number="{{ $transfer->transfer_number }}" title="Approve Transfer">
                            <i class="fa fa-check"></i>
                          </button>
                          <button type="button" class="btn btn-danger btn-sm reject-btn" data-transfer-id="{{ $transfer->id }}" data-transfer-number="{{ $transfer->transfer_number }}" title="Reject Transfer">
                            <i class="fa fa-times-circle"></i>
                          </button>
                        @elseif($transfer->status === 'approved')
                          <button type="button" class="btn btn-success btn-sm mark-moved-btn" data-transfer-id="{{ $transfer->id }}" title="Transfer to Counter">
                            <i class="fa fa-truck"></i>
                          </button>
                        @endif
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $transfers->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock transfers found. 
            <a href="{{ route('bar.stock-transfers.create') }}">Create your first transfer request</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- Data table plugin-->
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    // Wait for jQuery and SweetAlert to be available
    if (typeof $ === 'undefined') {
      console.error('jQuery not loaded');
      return;
    }
    
    if (typeof Swal === 'undefined') {
      console.error('SweetAlert2 not loaded');
      return;
    }

    console.log('Initializing stock transfers page...');

    // Initialize DataTable only if it's available
    // Note: DataTable is optional, table will work without it
    if (typeof $.fn.DataTable !== 'undefined') {
      try {
        var table = $('#transfersTable').DataTable({
          "paging": false,
          "info": false,
          "searching": true,
        });
        console.log('DataTable initialized');
      } catch(e) {
        console.warn('DataTable initialization failed:', e);
      }
    } else {
      console.warn('DataTable plugin not loaded, table will work without it');
    }

    console.log('Reject buttons found:', $('.reject-btn').length);
    console.log('Approve buttons found:', $('.approve-btn').length);
    console.log('Transfer buttons found:', $('.mark-moved-btn').length);


    // Approve button handler
    $(document).on('click', '.approve-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      console.log('Approve button clicked');
      
      const $btn = $(this);
      const transferId = $btn.data('transfer-id');
      const transferNumber = $btn.data('transfer-number');
      
      console.log('Approve button data:', { transferId, transferNumber });
      
      if (!transferId) {
        console.error('Transfer ID not found');
        alert('Error: Transfer ID not found. Please refresh the page.');
        return;
      }
      
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded');
        alert('Error: SweetAlert2 not loaded. Please refresh the page.');
        return;
      }
      
      Swal.fire({
        title: 'Approve Transfer?',
        html: `
          <p>Transfer Number: <strong>${transferNumber || 'N/A'}</strong></p>
          <p>This will approve the transfer request. Stock will remain in warehouse until marked as prepared and moved.</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const form = $('<form>', {
            'method': 'POST',
            'action': `/bar/stock-transfers/${transferId}/approve`
          });
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
          }));
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_method',
            'value': 'POST'
          }));
          $('body').append(form);
          form.submit();
        }
      });
    });

    // Transfer button (from approved to completed)
    $(document).on('click', '.mark-moved-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      console.log('Transfer button clicked');
      const transferId = $(this).data('transfer-id');
      
      if (!transferId) {
        console.error('Transfer ID not found');
        return;
      }
      
      Swal.fire({
        title: 'Transfer to Counter?',
        text: 'This will transfer the stock from warehouse to counter. Are you sure?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Transfer',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const form = $('<form>', {
            'method': 'POST',
            'action': `/bar/stock-transfers/${transferId}/mark-as-moved`
          });
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
          }));
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_method',
            'value': 'POST'
          }));
          $('body').append(form);
          form.submit();
        }
      });
    });

    // Reject with reason
    $(document).on('click', '.reject-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      console.log('Reject button clicked');
      
      const $btn = $(this);
      const transferId = $btn.data('transfer-id');
      const transferNumber = $btn.data('transfer-number');
      
      console.log('Reject button data:', { transferId, transferNumber, button: $btn });
      
      if (!transferId) {
        console.error('Transfer ID not found');
        alert('Error: Transfer ID not found. Please refresh the page.');
        return;
      }
      
      if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded');
        alert('Error: SweetAlert2 not loaded. Please refresh the page.');
        return;
      }
      
      Swal.fire({
        title: 'Reject Transfer',
        html: `
          <p>Transfer Number: <strong>${transferNumber || 'N/A'}</strong></p>
          <p class="mb-3">Please provide a reason for rejection:</p>
          <textarea id="rejection-reason" class="swal2-textarea" placeholder="Enter rejection reason..." rows="4" required style="width: 100%; min-height: 100px;"></textarea>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Reject Transfer',
        cancelButtonText: 'Cancel',
        allowOutsideClick: false,
        preConfirm: () => {
          const reason = document.getElementById('rejection-reason').value.trim();
          if (!reason) {
            Swal.showValidationMessage('Please provide a rejection reason');
            return false;
          }
          return reason;
        }
      }).then((result) => {
        if (result.isConfirmed && result.value) {
          const reason = result.value;
          
          const form = $('<form>', {
            'method': 'POST',
            'action': `/bar/stock-transfers/${transferId}/reject-with-reason`
          });
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_token',
            'value': '{{ csrf_token() }}'
          }));
          form.append($('<input>', {
            'type': 'hidden',
            'name': '_method',
            'value': 'POST'
          }));
          form.append($('<input>', {
            'type': 'hidden',
            'name': 'rejection_reason',
            'value': reason
          }));
          $('body').append(form);
          form.submit();
        }
      });
    });
    
    // View transfer details modal
    $(document).on('click', '.view-transfer-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const transferId = $(this).data('transfer-id');
      const modal = $('#transferDetailsModal');
      const content = $('#transferDetailsContent');
      
      // Show modal with loading state
      content.html(`
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-3x"></i>
          <p>Loading transfer details...</p>
        </div>
      `);
      modal.modal('show');
      
      // Load transfer details via AJAX
      $.ajax({
        url: '{{ url("/bar/stock-transfers") }}/' + transferId,
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        success: function(response) {
          const transfer = response.transfer;
          const packagingDisplay = response.packagingDisplay;
          
          // Determine status badge
          let statusBadge = '';
          if (transfer.status === 'pending') {
            statusBadge = '<span class="badge badge-warning">Pending</span>';
          } else if (transfer.status === 'approved') {
            statusBadge = '<span class="badge badge-success">Approved</span>';
          } else if (transfer.status === 'rejected') {
            statusBadge = '<span class="badge badge-danger">Rejected</span>';
          } else if (transfer.status === 'prepared') {
            statusBadge = '<span class="badge badge-info">Prepared</span>';
          } else if (transfer.status === 'completed') {
            statusBadge = '<span class="badge badge-primary">Completed</span>';
          } else {
            statusBadge = '<span class="badge badge-secondary">' + (transfer.status ? transfer.status.charAt(0).toUpperCase() + transfer.status.slice(1) : 'N/A') + '</span>';
          }
          
          // Build HTML content
          let html = `
            <div class="row">
              <div class="col-md-6">
                <h5><i class="fa fa-info-circle"></i> Transfer Information</h5>
                <table class="table table-borderless table-sm">
                  <tr>
                    <th width="40%">Transfer Number:</th>
                    <td><strong>${transfer.transfer_number || 'N/A'}</strong></td>
                  </tr>
                  <tr>
                    <th>Status:</th>
                    <td>${statusBadge}</td>
                  </tr>
                  <tr>
                    <th>Product:</th>
                    <td>
                      <strong>${transfer.product_variant?.product?.name || 'N/A'}</strong><br>
                      <small class="text-muted">
                        ${transfer.product_variant?.measurement || ''} - ${transfer.product_variant?.packaging || ''}
                      </small>
                    </td>
                  </tr>
                  <tr>
                    <th>Quantity Requested:</th>
                    <td>
                      ${transfer.quantity_requested || 0} ${packagingDisplay || 'packages'}<br>
                      <small class="text-muted">(${parseInt(transfer.total_units || 0).toLocaleString()} total bottle(s))</small>
                    </td>
                  </tr>
                  <tr>
                    <th>Requested By:</th>
                    <td>${transfer.requested_by_user?.name || 'N/A'}</td>
                  </tr>
                  <tr>
                    <th>Requested Date:</th>
                    <td>${transfer.created_at ? new Date(transfer.created_at).toLocaleString() : 'N/A'}</td>
                  </tr>
          `;
          
          if (transfer.approved_by) {
            html += `
                  <tr>
                    <th>Approved/Rejected By:</th>
                    <td>${transfer.approved_by_user?.name || 'N/A'}</td>
                  </tr>
                  <tr>
                    <th>Approved/Rejected Date:</th>
                    <td>${transfer.approved_at ? new Date(transfer.approved_at).toLocaleString() : 'N/A'}</td>
                  </tr>
            `;
          }
          
          if (transfer.rejection_reason) {
            html += `
                  <tr>
                    <th>Rejection Reason:</th>
                    <td><span class="text-danger">${transfer.rejection_reason}</span></td>
                  </tr>
            `;
          }
          
          html += `
                </table>
              </div>
              <div class="col-md-6">
                <h5><i class="fa fa-calculator"></i> Financial Information</h5>
                <table class="table table-borderless table-sm">
                  <tr>
                    <th width="40%">Expected Profit:</th>
                    <td><strong class="text-primary">TSh ${parseFloat(transfer.expected_profit || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                  </tr>
          `;
          
          if (response.expectedRevenue) {
            html += `
                  <tr>
                    <th>Expected Revenue:</th>
                    <td><strong class="text-success">TSh ${parseFloat(response.expectedRevenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                  </tr>
            `;
          }
          
          if (transfer.notes) {
            html += `
                  <tr>
                    <th>Notes:</th>
                    <td>${transfer.notes}</td>
                  </tr>
            `;
          }
          
          html += `
                </table>
              </div>
            </div>
          `;
          
          content.html(html);
        },
        error: function(xhr) {
          let errorMsg = 'Failed to load transfer details.';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = xhr.responseJSON.message;
          }
          content.html(`
            <div class="alert alert-danger">
              <i class="fa fa-exclamation-triangle"></i> ${errorMsg}
            </div>
          `);
        }
      });
    });
  });
</script>

<!-- Transfer Details Modal -->
<div class="modal fade" id="transferDetailsModal" tabindex="-1" role="dialog" aria-labelledby="transferDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="transferDetailsModalLabel">
          <i class="fa fa-exchange"></i> Transfer Details
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="transferDetailsContent">
        <div class="text-center">
          <i class="fa fa-spinner fa-spin fa-3x"></i>
          <p>Loading transfer details...</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

