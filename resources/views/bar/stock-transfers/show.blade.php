@extends('layouts.dashboard')

@section('title', 'Stock Transfer Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Stock Transfer Details</h1>
    <p>View and manage stock transfer request</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-transfers.index') }}">Stock Transfers</a></li>
    <li class="breadcrumb-item">Transfer Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Transfer #{{ $stockTransfer->transfer_number }}</h3>
        <div>
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
          @if($stockTransfer->status === 'pending' && $canApprove)
            <form action="{{ route('bar.stock-transfers.approve', $stockTransfer) }}" method="POST" style="display: inline;" id="approveForm">
              @csrf
              <button type="button" class="btn btn-success" onclick="confirmApprove()">
                <i class="fa fa-check"></i> Approve Transfer
              </button>
            </form>
            <form action="{{ route('bar.stock-transfers.reject', $stockTransfer) }}" method="POST" style="display: inline;" id="rejectForm">
              @csrf
              <button type="button" class="btn btn-danger" onclick="confirmReject()">
                <i class="fa fa-times"></i> Reject Transfer
              </button>
            </form>
          @endif
          <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back
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

      @if(session('error') || $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ session('error') }}
          @if($errors->any())
            <ul class="mb-0 mt-2">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          @endif
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      @endif

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Transfer Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Transfer Number:</th>
                <td><strong>{{ $stockTransfer->transfer_number }}</strong></td>
              </tr>
              <tr>
                <th>Status:</th>
                <td>
                  @if($stockTransfer->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($stockTransfer->status === 'approved')
                    <span class="badge badge-success">Approved</span>
                  @elseif($stockTransfer->status === 'rejected')
                    <span class="badge badge-danger">Rejected</span>
                  @else
                    <span class="badge badge-info">{{ ucfirst($stockTransfer->status) }}</span>
                  @endif
                </td>
              </tr>
              <tr>
                <th>Product:</th>
                <td>
                  @php
                    $prodName = $stockTransfer->productVariant->product->name ?? 'N/A';
                    $varName = $stockTransfer->productVariant->name ?? '';
                    $displayName = $prodName;
                    if ($varName && $varName !== $prodName) {
                        $displayName = $prodName . ' (' . $varName . ')';
                    }
                  @endphp
                  <strong>{{ $displayName }}</strong><br>
                  <small class="text-muted">
                    {{ $stockTransfer->productVariant->measurement ?? '' }} - 
                    {{ $stockTransfer->productVariant->packaging ?? '' }}
                  </small>
                </td>
              </tr>
              <tr>
                <th>Quantity Requested:</th>
                <td>
                  @php
                    $packagingType = strtolower($stockTransfer->productVariant->packaging ?? 'packages');
                    $packagingTypeSingular = rtrim($packagingType, 's');
                    if ($packagingTypeSingular == 'boxe') {
                      $packagingTypeSingular = 'box';
                    }
                    $packagingDisplay = $stockTransfer->quantity_requested == 1 ? $packagingTypeSingular : $packagingType;
                  @endphp
                  {{ $stockTransfer->quantity_requested }} {{ ucfirst($packagingDisplay) }}<br>
                  <small class="text-muted">({{ number_format($stockTransfer->total_units) }} total bottle(s))</small>
                </td>
              </tr>
              <tr>
                <th>Requested By:</th>
                <td>{{ $stockTransfer->requestedBy->name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Requested Date:</th>
                <td>{{ $stockTransfer->created_at->format('M d, Y H:i') }}</td>
              </tr>
              @if($stockTransfer->approved_by)
              <tr>
                <th>Approved/Rejected By:</th>
                <td>{{ $stockTransfer->approvedBy->name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Approved/Rejected Date:</th>
                <td>{{ $stockTransfer->approved_at ? $stockTransfer->approved_at->format('M d, Y H:i') : 'N/A' }}</td>
              </tr>
              @endif
            </table>
          </div>
          <div class="col-md-6">
            <h4>Stock Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">From Location:</th>
                <td><span class="badge badge-info">Warehouse</span></td>
              </tr>
              <tr>
                <th>To Location:</th>
                <td><span class="badge badge-success">Counter</span></td>
              </tr>
              <tr>
                <th>Items per Package:</th>
                <td>{{ $stockTransfer->productVariant->items_per_package ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Total Bottles:</th>
                <td><strong>{{ number_format($stockTransfer->total_units) }} bottle(s)</strong></td>
              </tr>
              @if($stockTransfer->status === 'completed' && isset($expectedRevenue) && $expectedRevenue > 0)
              <tr>
                <th>Expected Revenue:</th>
                <td>
                  <strong class="text-success">TSh {{ number_format($expectedRevenue, 2) }}</strong><br>
                  <small class="text-muted">When all {{ number_format($stockTransfer->total_units) }} bottle(s) are sold</small>
                </td>
              </tr>
              @endif
            </table>
          </div>
        </div>

        @if($stockTransfer->notes)
          <div class="row mt-3">
            <div class="col-md-12">
              <h4>Notes</h4>
              <p>{{ $stockTransfer->notes }}</p>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
  function confirmApprove() {
    Swal.fire({
      title: 'Approve Transfer?',
      text: 'This will move stock from warehouse to counter. This action cannot be undone.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Approve',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('approveForm').submit();
      }
    });
  }

  function confirmReject() {
    Swal.fire({
      title: 'Reject Transfer?',
      text: 'This transfer request will be rejected. This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, Reject',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        document.getElementById('rejectForm').submit();
      }
    });
  }
</script>
@endpush




