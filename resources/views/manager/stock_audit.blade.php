@extends('layouts.dashboard')

@section('title', 'Stock-to-Cash Audit')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-line-chart"></i> Stock-to-Cash Audit</h1>
    <p>Financial Reconciliation per Stock Batch (Managers Only)</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Stock Audit</li>
  </ul>
</div>

<!-- Manager Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="widget-small primary coloured-icon"><i class="icon fa fa-shopping-cart fa-3x"></i>
            <div class="info">
                <p class="text-uppercase small font-weight-bold">Batch Expected Value</p>
                <p><b>TSh {{ number_format($totalExpected) }}</b></p>
                <small class="text-muted">Total value of transfers</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small success coloured-icon"><i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <p class="text-uppercase small font-weight-bold" style="color: #000 !important;">Batch Collected Cash</p>
                <p><b style="color: #000 !important;">TSh {{ number_format($totalCollected) }}</b></p>
                <small class="text-muted">Sales revenue received</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="widget-small info coloured-icon"><i class="icon fa fa-check-circle fa-3x"></i>
            <div class="info">
                <p class="text-uppercase small font-weight-bold">Sold Out Batches</p>
                <p><b>{{ $fullySoldBatchCount }} Batches</b></p>
                <small class="text-muted">Ready for final audit</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        @php $payoutDiff = $totalExpected - $totalCollected; @endphp
        <div class="widget-small {{ $payoutDiff > 0 ? 'danger' : 'info' }} coloured-icon"><i class="icon fa fa-arrow-down fa-3x"></i>
            <div class="info">
                <p class="text-uppercase small font-weight-bold">Pending Revenue</p>
                <p><b>TSh {{ number_format(max(0, $payoutDiff)) }}</b></p>
                <small class="text-muted">Remaining stock value</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('manager.stock-audit') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="start_date" class="mr-2 small font-weight-bold">Start Date:</label>
          <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" value="{{ $startDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="end_date" class="mr-2 small font-weight-bold">End Date:</label>
          <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" value="{{ $endDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="status" class="mr-2 small font-weight-bold">Status:</label>
          <select name="status" class="form-control form-control-sm">
            <option value="all" {{ $statusFilter == 'all' ? 'selected' : '' }}>All Batches</option>
            <option value="selling" {{ $statusFilter == 'selling' ? 'selected' : '' }}>Still Selling</option>
            <option value="sold_out" {{ $statusFilter == 'sold_out' ? 'selected' : '' }}>Ready to Audit (100% Sold)</option>
            <option value="audited" {{ $statusFilter == 'audited' ? 'selected' : '' }}>Finalized (Audited)</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="fa fa-refresh"></i> Update Report
        </button>
      </form>
    </div>
  </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <h3 class="tile-title"><i class="fa fa-list"></i> Batch Progress Audit</h3>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th>Transfer Info</th>
                            <th>Product Details</th>
                            <th>Batch Qty</th>
                            <th>Sold Qty</th>
                            <th>Sales Progress</th>
                            <th>Expected Revenue</th>
                            <th>Collected (Actual)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditData as $row)
                        <tr class="{{ $row['is_fully_sold'] ? 'table-success-light' : '' }}">
                            <td>
                                <strong class="text-primary details-link" style="cursor: pointer;" data-id="{{ $row['id'] }}">
                                    <i class="fa fa-search-plus"></i> {{ $row['number'] }}
                                </strong><br>
                                <small class="text-muted">{{ $row['date'] }}</small>
                            </td>
                            <td>{{ $row['product'] }}</td>
                            <td>{{ $row['qty'] }} units</td>
                            <td>{{ $row['sold_qty'] }} units</td>
                            <td style="width: 200px;">
                                <div class="progress" style="height: 15px;">
                                    <div class="progress-bar progress-bar-striped {{ $row['is_fully_sold'] ? 'bg-success' : 'progress-bar-animated bg-info' }}" 
                                         role="progressbar" 
                                         style="width: {{ $row['progress'] }}%;" 
                                         aria-valuenow="{{ $row['progress'] }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ $row['progress'] }}%
                                    </div>
                                </div>
                                @if($row['is_fully_sold'])
                                    <small class="text-success font-weight-bold"><i class="fa fa-check"></i> 100% Sold - Received Amount</small>
                                @endif
                            </td>
                            <td><strong>TSh {{ number_format($row['expected_revenue']) }}</strong></td>
                            <td>
                                <strong class="{{ $row['actual_revenue'] >= $row['expected_revenue'] ? 'text-success' : 'text-primary' }}">
                                    TSh {{ number_format($row['actual_revenue']) }}
                                </strong>
                            </td>
                            <td>
                                @if($row['is_audited'])
                                    <span class="badge badge-success p-2 px-3"><i class="fa fa-check-circle"></i> AUDITED & RECEIVED</span>
                                @elseif($row['is_fully_sold'])
                                    <button class="btn btn-primary btn-sm btn-block audit-batch-btn" 
                                            data-id="{{ $row['id'] }}" 
                                            data-number="{{ $row['number'] }}">
                                        <i class="fa fa-money"></i> Verify & Receive Cash
                                    </button>
                                @else
                                    <span class="badge badge-info p-2 px-3">ACTIVE (SELLING)</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">No batch transfers found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Sales Details Modal -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fa fa-list"></i> Sales Attribution Details: <span id="modalTransferNumber"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-bordered">
                        <thead class="bg-light">
                            <tr>
                                <th>Order #</th>
                                <th>Waiter</th>
                                <th>Qty</th>
                                <th>UnitPrice</th>
                                <th>Total</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="saleDetailsTableBody">
                            <!-- JS populated -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.table-success-light { background-color: rgba(40, 167, 69, 0.05); }
.details-link:hover { text-decoration: underline; color: #0056b3 !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Show Details Modal
    $('.details-link').on('click', function() {
        const batchId = $(this).data('id');
        $('#modalTransferNumber').text('Loading...');
        $('#saleDetailsTableBody').html('<tr><td colspan="6" class="text-center">Fetching details...</td></tr>');
        $('#saleDetailsModal').modal('show');
        
        $.get(`/manager/stock-audit/details/${batchId}`, function(response) {
            if (response.success) {
                $('#modalTransferNumber').text(response.transfer_number);
                let html = '';
                if (response.sales.length > 0) {
                    response.sales.forEach(sale => {
                        html += `
                            <tr>
                                <td><b>${sale.order_number}</b></td>
                                <td>${sale.waiter}</td>
                                <td>${sale.qty}</td>
                                <td>TSh ${parseInt(sale.unit_price).toLocaleString()}</td>
                                <td><b>TSh ${parseInt(sale.total_price).toLocaleString()}</b></td>
                                <td><small>${sale.date}</small></td>
                            </tr>
                        `;
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center text-muted">No sales attributed to this batch yet.</td></tr>';
                }
                $('#saleDetailsTableBody').html(html);
            }
        });
    });

    $('.audit-batch-btn').on('click', function() {
        const batchId = $(this).data('id');
        const batchNumber = $(this).data('number');
        
        Swal.fire({
            title: 'Verify Batch Revenue?',
            html: `Confirm that you have received all the generated cash for batch <b>${batchNumber}</b> and want to finalize this transfer as audited.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Verify & Receive',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: `/manager/stock-audit/audit/${batchId}`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    }
                }).then(response => {
                    if (!response.success) throw new Error(response.message);
                    return response;
                }).catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error.message}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Success!', result.value.message, 'success').then(() => {
                    location.reload();
                });
            }
        });
    });
});
</script>
@endsection
