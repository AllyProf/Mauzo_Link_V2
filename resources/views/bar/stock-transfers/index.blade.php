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
                @php $lastNum = null; @endphp
                @foreach($transfers as $transfer)
                  @php
                    $isNewBatch = ($transfer->transfer_number !== $lastNum);
                    $lastNum = $transfer->transfer_number;
                  @endphp
                  <tr>
                    <td>
                      @if($isNewBatch)
                        <strong class="text-primary">{{ $transfer->transfer_number }}</strong>
                      @else
                        <span class="text-muted small" style="opacity: 0.6; font-size: 10px;">↳ batch item</span>
                      @endif
                    </td>
                    <td>
                      @php
                        $prodName = $transfer->productVariant->product->name ?? 'N/A';
                        $varName = $transfer->productVariant->name ?? '';
                        $displayName = $prodName;
                        if ($varName && $varName !== $prodName) {
                            $displayName = $prodName . ' (' . $varName . ')';
                        }
                      @endphp
                      {{ $displayName }}<br>
                      <small class="text-muted">{{ $transfer->productVariant->measurement ?? '' }} - {{ $transfer->productVariant->packaging ?? '' }}</small>
                    </td>
                    <td>
                      @php
                        $pkg = strtolower($transfer->productVariant->packaging ?? 'packages');
                        $pkgSing = rtrim($pkg, 's');
                        if ($pkgSing == 'boxe') $pkgSing = 'box';
                        $pkgDisp = $transfer->quantity_requested == 1 ? $pkgSing : $pkg;
                      @endphp
                      {{ $transfer->quantity_requested }} {{ ucfirst($pkgDisp) }}
                    </td>
                    <td>
                      @php
                        $unit = strtolower($transfer->productVariant->unit ?? 'btl');
                        if (in_array($unit, ['ml', 'cl', 'l'])) $unit = 'bottle';
                        $unitDisp = $transfer->total_units == 1 ? $unit : Str::plural($unit);
                      @endphp
                      {{ number_format($transfer->total_units) }} {{ $unitDisp }}
                    </td>
                    <td>
                      @if(isset($transfer->expected_profit) && $transfer->expected_profit > 0)
                        <strong class="text-primary">TSh {{ number_format($transfer->expected_profit) }}</strong>
                        @if($transfer->is_tot_calculation)
                          <br><span class="badge badge-warning smallest" style="font-size: 8px;">PROFIT BY GLASS</span>
                        @endif
                      @else
                        <span class="text-muted">-</span>
                      @endif
                    </td>
                    <td>
                      @if($isNewBatch)
                        <span class="badge badge-{{ $transfer->status === 'pending' ? 'warning' : ($transfer->status === 'approved' ? 'success' : ($transfer->status === 'prepared' ? 'info' : ($transfer->status === 'completed' ? 'primary' : 'secondary'))) }} text-uppercase">
                          {{ $transfer->status }}
                        </span>
                      @else
                        <small class="text-muted italic" style="font-size: 10px;">{{ $transfer->status }}</small>
                      @endif
                    </td>
                    <td>{{ $transfer->requestedBy->name ?? 'N/A' }}</td>
                    <td>{{ $transfer->created_at->format('M d, Y H:i') }}</td>
                    <td>{{ $transfer->approved_at ? $transfer->approved_at->format('M d, Y H:i') : '-' }}</td>
                    <td>
                      @if($isNewBatch)
                        @php
                          $canEdit = false;
                          if (session('is_staff')) {
                            $staff = \App\Models\Staff::find(session('staff_id'));
                            if ($staff && $staff->role) {
                              $canEdit = $staff->role->hasPermission('stock_transfer', 'edit');
                              if (!$canEdit) {
                                $roleName = strtolower(trim($staff->role->name ?? ''));
                                if (in_array($roleName, ['stock keeper', 'stockkeeper'])) $canEdit = true;
                              }
                            }
                          } else {
                            $user = Auth::user();
                            $canEdit = $user && ($user->hasPermission('stock_transfer', 'edit') || $user->hasRole('owner'));
                          }
                        @endphp
                        <div class="btn-group" role="group">
                          <button type="button" class="btn btn-primary btn-sm view-transfer-btn" data-transfer-id="{{ $transfer->id }}" title="View Batch Details">
                             <i class="fa fa-eye"></i> View Batch
                          </button>
                          @if($canEdit)
                            @if($transfer->status === 'pending')
                              <button type="button" class="btn btn-success btn-sm approve-btn" data-transfer-id="{{ $transfer->id }}" data-transfer-number="{{ $transfer->transfer_number }}" title="Approve Batch">
                                <i class="fa fa-check"></i>
                              </button>
                              <button type="button" class="btn btn-danger btn-sm reject-btn" data-transfer-id="{{ $transfer->id }}" data-transfer-number="{{ $transfer->transfer_number }}" title="Reject Batch">
                                <i class="fa fa-times-circle"></i>
                              </button>
                            @elseif($transfer->status === 'approved')
                              <button type="button" class="btn btn-info btn-sm prepare-btn" data-transfer-id="{{ $transfer->id }}" data-transfer-number="{{ $transfer->transfer_number }}" title="Mark Batch as Prepared">
                                <i class="fa fa-cubes"></i>
                              </button>
                            @elseif($transfer->status === 'prepared')
                              <button type="button" class="btn btn-success btn-sm mark-moved-btn" data-transfer-id="{{ $transfer->id }}" title="Transfer Batch to Counter">
                                <i class="fa fa-truck"></i>
                              </button>
                            @endif
                          @endif
                        </div>
                      @endif
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

    // Mark as Prepared handler
    $(document).on('click', '.prepare-btn', function(e) {
      e.preventDefault();
      const transferId = $(this).data('transfer-id');
      const transferNumber = $(this).data('transfer-number');
      
      Swal.fire({
        title: 'Mark as Prepared?',
        html: `<p>Batch <strong>${transferNumber}</strong> items are ready for transfer.</p>`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#17a2b8',
        confirmButtonText: 'Yes, Prepared',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const form = $('<form>', {
            'method': 'POST',
            'action': `/bar/stock-transfers/${transferId}/mark-as-prepared`
          });
          form.append($('<input>', { 'type': 'hidden', 'name': '_token', 'value': '{{ csrf_token() }}' }));
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
          
          // Group batch items info
          let batchItemsHtml = '';
          let totalBatchRevenue = 0;
          let totalBatchProfit = 0;

          if (response.batch_items) {
            response.batch_items.forEach(item => {
              totalBatchRevenue += item.expected_revenue;
              totalBatchProfit += item.expected_profit;
              
              batchItemsHtml += `
                <div class="p-2 border rounded mb-2 bg-light">
                   <div class="d-flex justify-content-between font-weight-bold">
                      <span>${item.product_name} (${item.variant_measurement})</span>
                      <span class="text-primary">TSh ${item.expected_revenue.toLocaleString()}</span>
                   </div>
                   <div class="d-flex justify-content-between smallest text-muted">
                      <span>Qty: ${item.quantity_requested} ${item.packaging_display} (${item.total_units} ${item.unit_display})</span>
                      <span>Profit: TSh ${item.expected_profit.toLocaleString()} ${item.is_tot ? '<b class="text-warning">[GLASS]</b>' : ''}</span>
                   </div>
                </div>
              `;
            });
          }

          // Determine status badge
          let statusBadge = '';
          const status = transfer.status;
          if (status === 'pending') statusBadge = '<span class="badge badge-warning">Pending</span>';
          else if (status === 'approved') statusBadge = '<span class="badge badge-success">Approved</span>';
          else if (status === 'rejected') statusBadge = '<span class="badge badge-danger">Rejected</span>';
          else if (status === 'prepared') statusBadge = '<span class="badge badge-info">Prepared</span>';
          else if (status === 'completed') statusBadge = '<span class="badge badge-primary">Completed</span>';
          
          // Build HTML content
          let html = `
            <div class="row">
              <div class="col-md-7 border-right">
                <h6 class="font-weight-bold text-uppercase smallest text-muted mb-3"><i class="fa fa-list"></i> Itemized Batch Details</h6>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                   ${batchItemsHtml}
                </div>
              </div>
              <div class="col-md-5">
                <h6 class="font-weight-bold text-uppercase smallest text-muted mb-3"><i class="fa fa-info-circle"></i> Batch Summary</h6>
                <div class="card bg-light border-0 mb-3">
                  <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Batch #:</span>
                      <span class="font-weight-bold">${transfer.transfer_number}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status:</span>
                        <span>${statusBadge}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Requested By:</span>
                        <span class="font-weight-bold text-dark">${transfer.requested_by_name}</span>
                    </div>
                     <div class="d-flex justify-content-between">
                        <span class="text-muted">Date:</span>
                        <small class="text-dark">${transfer.created_at}</small>
                    </div>
                  </div>
                </div>

                <h6 class="font-weight-bold text-uppercase smallest text-muted mb-2"><i class="fa fa-calculator"></i> Batch Financials</h6>
                <div class="p-3 border rounded">
                   <div class="d-flex justify-content-between mb-2">
                      <span class="text-muted">Total Revenue:</span>
                      <span class="text-success font-weight-bold">TSh ${totalBatchRevenue.toLocaleString()}</span>
                   </div>
                   <div class="d-flex justify-content-between">
                      <span class="text-muted">Total Profit:</span>
                      <span class="text-primary font-weight-bold">TSh ${totalBatchProfit.toLocaleString()}</span>
                   </div>
                </div>
                
                ${transfer.notes ? `
                <div class="mt-3 p-2 bg-light rounded small">
                  <strong>Notes:</strong><br>${transfer.notes}
                </div>` : ''}
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

