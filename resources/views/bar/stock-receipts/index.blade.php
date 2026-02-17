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
                  <th>Receipt #</th>
                  <th>Product</th>
                  <th>Supplier</th>
                  <th>Quantity</th>
                  <th>Total Bottles</th>
                  <th>Buying Cost</th>
                  <th>Total Profit</th>
                  <th>Received Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($receipts as $receipt)
                  <tr>
                    <td><strong>{{ $receipt->receipt_number }}</strong></td>
                    <td>
                      {{ $receipt->productVariant->product->name ?? 'N/A' }}<br>
                      <small class="text-muted">{{ $receipt->productVariant->measurement ?? '' }} - {{ $receipt->productVariant->packaging ?? '' }}</small>
                    </td>
                    <td>{{ $receipt->supplier->company_name ?? 'N/A' }}</td>
                    <td>{{ $receipt->quantity_received }} {{ $receipt->productVariant->packaging ?? 'packages' }}</td>
                    <td>{{ $receipt->total_units }} bottle(s)</td>
                    <td>TSh {{ number_format($receipt->total_buying_cost, 2) }}</td>
                    <td>
                      <span class="badge badge-success">
                        TSh {{ number_format($receipt->total_profit, 2) }}
                      </span>
                    </td>
                    <td>{{ $receipt->received_date->format('M d, Y') }}</td>
                    <td>
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
                            // Allow edit/delete for stock keeper role
                            if (!$canEdit) {
                              $roleName = strtolower(trim($staff->role->name ?? ''));
                              if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                                $canEdit = true;
                              }
                            }
                            if (!$canDelete) {
                              $roleName = strtolower(trim($staff->role->name ?? ''));
                              if (in_array($roleName, ['stock keeper', 'stockkeeper'])) {
                                $canDelete = true;
                              }
                            }
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
                      @if($canView)
                        <a href="{{ route('bar.stock-receipts.show', $receipt) }}" class="btn btn-info btn-sm">
                          <i class="fa fa-eye"></i> View
                        </a>
                      @endif
                      @if($canEdit)
                        <a href="{{ route('bar.stock-receipts.edit', $receipt) }}" class="btn btn-warning btn-sm">
                          <i class="fa fa-pencil"></i> Edit
                        </a>
                      @endif
                      @if($canDelete)
                        <button type="button" class="btn btn-danger btn-sm delete-receipt-btn" data-receipt-id="{{ $receipt->id }}" data-receipt-number="{{ $receipt->receipt_number }}">
                          <i class="fa fa-trash"></i> Delete
                        </button>
                      @endif
                      @if(!$canView && !$canEdit && !$canDelete)
                        <span class="text-muted">No actions available</span>
                      @endif
                      @if($canDelete)
                        <form id="delete-form-{{ $receipt->id }}" action="{{ route('bar.stock-receipts.destroy', $receipt) }}" method="POST" style="display: none;">
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
      
      const receiptId = $(this).data('receipt-id');
      const receiptNumber = $(this).data('receipt-number') || 'this receipt';
      const form = $('#delete-form-' + receiptId);
      
      Swal.fire({
        title: 'Are you sure?',
        html: `You are about to delete stock receipt <strong>${receiptNumber}</strong>.<br><br>This will also adjust warehouse stock. This action cannot be undone!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Show loading state
          Swal.fire({
            title: 'Deleting...',
            text: 'Please wait while we delete the receipt.',
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
