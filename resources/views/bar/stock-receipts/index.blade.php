@extends('layouts.dashboard')

@section('title', 'Stock Receipts')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-download"></i> Stock Receipts</h1>
    <p>Manage stock receipts from suppliers</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Stock Receipts</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">All Stock Receipts</h3>
        <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-primary">
          <i class="fa fa-plus"></i> New Stock Receipt
        </a>
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
        @if($receipts->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="receiptsTable">
              <thead>
                <tr>
                  <th>Batch #</th>
                  <th>Supplier</th>
                  <th class="text-center">Items</th>
                  <th class="text-center">Tot Received</th>
                  <th class="text-center">Tot Btls/Pcs</th>
                  <th class="text-right">Total Cost</th>
                  <th class="text-right">Exp. Profit</th>
                  <th class="text-center">Received Date</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($receipts as $receipt)
                  <tr>
                    <td>
                        <span class="badge badge-dark px-3 py-2" style="font-size: 0.9rem;">
                            {{ $receipt->receipt_number }}
                        </span>
                    </td>
                    <td>
                      <div class="font-weight-bold text-dark">{{ $receipt->supplier->company_name ?? 'N/A' }}</div>
                      <small class="text-muted">{{ $receipt->supplier->phone ?? '' }}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-pill badge-light border px-2">
                            {{ $receipt->item_count }} types
                        </span>
                    </td>
                    <td class="text-center">{{ number_format($receipt->total_packages_sum, 1) }}</td>
                    <td class="text-center font-weight-bold">{{ number_format($receipt->total_units_sum) }}</td>
                    <td class="text-right">TSh {{ number_format($receipt->total_cost_sum) }}</td>
                    <td class="text-right font-weight-bold text-success">
                        TSh {{ number_format($receipt->total_profit_sum) }}
                    </td>
                    <td class="text-center">{{ $receipt->received_date->format('d M, Y') }}</td>
                    <td class="text-center">
                      @php
                        $canView = false;
                        $canEdit = false;
                        $canDelete = false;
                        if (session('is_staff')) {
                          $staff = \App\Models\Staff::find(session('staff_id'));
                          if ($staff && $staff->role) {
                            $canView = $staff->role->hasPermission('stock_receipt', 'view');
                            $canEdit = $staff->role->hasPermission('stock_receipt', 'edit');
                            $canDelete = $staff->role->hasPermission('stock_receipt', 'delete');
                          }
                        } else {
                          $user = \Illuminate\Support\Facades\Auth::user();
                          if ($user) {
                            $canView = $user->hasPermission('stock_receipt', 'view') || $user->hasRole('owner');
                            $canEdit = $user->hasPermission('stock_receipt', 'edit') || $user->hasRole('owner');
                            $canDelete = $user->hasPermission('stock_receipt', 'delete') || $user->hasRole('owner');
                          }
                        }
                      @endphp
                      
                      <div class="btn-group btn-group-sm">
                        @if($canView)
                            <a href="{{ route('bar.stock-receipts.print-batch', $receipt->receipt_number) }}" class="btn btn-primary" title="Print Receipt" target="_blank">
                                <i class="fa fa-print"></i>
                            </a>
                            <a href="{{ route('bar.stock-receipts.show', $receipt->receipt_number) }}" class="btn btn-info" title="View Details">
                                <i class="fa fa-eye"></i>
                            </a>
                        @endif
                        
                        @if($canDelete)
                            <button type="button" class="btn btn-danger delete-receipt-btn" 
                                    data-receipt-number="{{ $receipt->receipt_number }}">
                                <i class="fa fa-trash"></i>
                            </button>
                        @endif
                      </div>

                      @if($canDelete)
                        <form id="delete-form-{{ $receipt->receipt_number }}" action="{{ url('bar/stock-receipts/delete-batch/'.$receipt->receipt_number) }}" method="POST" style="display: none;">
                          @csrf
                          @method('DELETE')
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $receipts->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No stock receipts found. 
            <a href="{{ route('bar.stock-receipts.create') }}">Create your first stock receipt</a> to get started.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
  $(document).ready(function() {
    // Delete receipt with SweetAlert confirmation
    $(document).on('click', '.delete-receipt-btn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const receiptNumber = $(this).data('receipt-number');
      const form = $('#delete-form-' + receiptNumber);
      
      Swal.fire({
        title: 'Delete Stock Receipt?',
        html: `You are about to delete batch <strong>${receiptNumber}</strong>.<br><br><span class="text-danger">This will automatically reverse the stock from your Warehouse.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '<i class="fa fa-trash"></i> Yes, delete it!',
        cancelButtonText: 'No, cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Processing...',
            text: 'Reversing stock and deleting records...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Submit the form
          form.submit();
        }
      });
    });
  });
</script>
@endpush
