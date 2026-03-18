@extends('layouts.dashboard')

@section('title', 'Stock Receipts Report')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-th-list"></i> Stock Receipts Report</h1>
    <p>Inventory incoming stock analysis</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Stock Receipts</li>
  </ul>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="widget-small primary coloured-icon" style="background: linear-gradient(135deg, #940000 0%, #7a0000 100%); border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(148,0,0,0.2);">
            <i class="icon fa fa-shopping-cart fa-3x" style="background-color: rgba(255,255,255,0.1);"></i>
            <div class="info">
                <h4 class="text-white-50">Total Items Received</h4>
                <p class="text-white"><b style="font-size: 1.5rem;">{{ number_format($groupSummary->total_items ?? 0) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small info coloured-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(23,162,184,0.2);">
            <i class="icon fa fa-truck fa-3x" style="background-color: rgba(255,255,255,0.1);"></i>
            <div class="info">
                <h4 class="text-white-50">Total Batches</h4>
                <p class="text-white"><b style="font-size: 1.5rem;">{{ number_format($groupSummary->unique_batches ?? 0) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small danger coloured-icon" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(40,167,69,0.2);">
            <i class="icon fa fa-money fa-3x" style="background-color: rgba(255,255,255,0.1);"></i>
            <div class="info">
                <h4 class="text-white-50">Total Buying Cost</h4>
                <p class="text-white"><b style="font-size: 1.5rem;">TSh {{ number_format($groupSummary->total_buying_cost ?? 0) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile" style="border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
      <form method="GET" class="row">
        <div class="col-md-4">
          <label class="font-weight-bold">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" style="border-radius: 8px;">
        </div>
        <div class="col-md-4">
          <label class="font-weight-bold">End Date</label>
          <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" style="border-radius: 8px;">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-block" style="border-radius: 8px; height: 38px; background-color: #940000; border: none;"><i class="fa fa-search"></i> Filter Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile" style="border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead style="background-color: #f8f9fa;">
              <tr>
                <th style="border-top-left-radius: 8px;">Product Variant</th>
                <th class="text-center">Qty (Pkgs)</th>
                <th class="text-center">Total Units</th>
                <th class="text-right">Buying Cost</th>
                <th class="text-right" style="border-top-right-radius: 8px;">Profit Margin</th>
              </tr>
            </thead>
            <tbody>
              @php $lastReceiptNumber = null; @endphp
              @forelse($receipts as $receipt)
                @if($lastReceiptNumber !== $receipt->receipt_number)
                  <tr style="background-color: #f0f4f8; border-top: 3px solid #dee2e6;">
                    <td colspan="5" class="py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-dark p-2" style="font-size: 0.9rem; border-radius: 6px;">
                                    <i class="fa fa-folder-open text-warning mr-2"></i> BATCH: {{ $receipt->receipt_number }}
                                </span>
                            </div>
                            <div class="text-dark">
                                <span class="mr-4"><i class="fa fa-calendar text-muted mr-1"></i> <strong>{{ \Carbon\Carbon::parse($receipt->received_date)->format('M d, Y') }}</strong></span>
                                <span><i class="fa fa-truck text-muted mr-1"></i> <strong>{{ $receipt->supplier->company_name ?? 'N/A' }}</strong></span>
                            </div>
                        </div>
                    </td>
                  </tr>
                  @php $lastReceiptNumber = $receipt->receipt_number; @endphp
                @endif
                <tr style="transition: background 0.2s;">
                  <td class="pl-4">
                      <div class="d-flex align-items-center">
                          <i class="fa fa-level-up fa-rotate-90 text-muted mr-3 mb-2"></i>
                          <div>
                              <strong style="font-size: 1.05rem; color: #333;">{{ $receipt->productVariant->name ?? 'N/A' }}</strong><br>
                              <span class="badge badge-light text-muted border" style="font-weight: normal; font-size: 0.8rem;">{{ $receipt->productVariant->product->name ?? '' }}</span>
                          </div>
                      </div>
                  </td>
                  <td class="text-center font-weight-bold" style="color: #555;">{{ $receipt->quantity_received }}</td>
                  <td class="text-center font-weight-bold" style="color: #555;">{{ $receipt->total_units }}</td>
                  <td class="text-right text-danger font-weight-bold" style="font-size: 1.05rem;">TSh {{ number_format($receipt->final_buying_cost) }}</td>
                  <td class="text-right text-success font-weight-bold" style="font-size: 1.05rem;">TSh {{ number_format($receipt->total_profit) }}</td>
                </tr>
              @empty
              <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="fa fa-info-circle fa-3x mb-3 d-block opacity-50"></i>
                    No stock receipts found for the selected period.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
    .table tbody tr:hover {
        background-color: rgba(148, 0, 0, 0.02);
    }
    .badge-primary { background-color: #940000; }
    .btn-primary { background-color: #940000; border-color: #940000; }
</style>
@endsection
