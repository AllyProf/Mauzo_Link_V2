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
        <div class="widget-small primary coloured-icon">
            <i class="icon fa fa-shopping-cart fa-3x"></i>
            <div class="info">
                <h4>Total Items Received</h4>
                <p><b>{{ number_format($groupSummary->total_items ?? 0) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small info coloured-icon">
            <i class="icon fa fa-truck fa-3x"></i>
            <div class="info">
                <h4>Total Batches</h4>
                <p><b>{{ number_format($groupSummary->unique_batches ?? 0) }}</b></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="widget-small danger coloured-icon">
            <i class="icon fa fa-money fa-3x"></i>
            <div class="info">
                <h4>Total Buying Cost</h4>
                <p><b>TSh {{ number_format($groupSummary->total_buying_cost ?? 0) }}</b></p>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" class="row">
        <div class="col-md-4">
          <label class="font-weight-bold">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
          <label class="font-weight-bold">End Date</label>
          <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-search"></i> Filter Report</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead class="thead-light">
              <tr>
                <th>Product Variant</th>
                <th class="text-center">Qty (Pkgs)</th>
                <th class="text-center">Units</th>
                <th class="text-right">Buy Price</th>
                <th class="text-right">Sell Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Total Cost</th>
                <th class="text-right">Total Profit</th>
              </tr>
            </thead>
            <tbody>
              @php $lastReceiptNumber = null; @endphp
              @forelse($receipts as $receipt)
                @if($lastReceiptNumber !== $receipt->receipt_number)
                  <tr class="bg-light">
                    <td colspan="8" class="py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-dark">
                                    <i class="fa fa-folder-open mr-2"></i> BATCH: {{ $receipt->receipt_number }}
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
                <tr>
                  <td class="pl-4">
                      <div class="d-flex align-items-center">
                          <i class="fa fa-level-up fa-rotate-90 text-muted mr-3 mb-2"></i>
                          <div>
                              <strong>{{ $receipt->productVariant->name ?? 'N/A' }}</strong><br>
                              <span class="badge badge-light text-muted border">{{ $receipt->productVariant->product->name ?? '' }}</span>
                          </div>
                      </div>
                  </td>
                  <td class="text-center font-weight-bold">{{ $receipt->quantity_received }}</td>
                  <td class="text-center font-weight-bold">{{ $receipt->total_units }}</td>
                  <td class="text-right">TSh {{ number_format($receipt->buying_price_per_unit) }}</td>
                  <td class="text-right">
                      <div class="font-weight-bold">TSh {{ number_format($receipt->selling_price_per_unit) }}</div>
                      @if($receipt->productVariant->can_sell_in_tots ?? false)
                        <div class="small text-info mt-1">
                          <i class="fa fa-glass"></i> TSh {{ number_format($receipt->productVariant->selling_price_per_tot) }} /{{ strtolower($receipt->productVariant->portion_unit_name) }}
                        </div>
                      @endif
                  </td>
                  <td class="text-right text-info">
                      @if($receipt->discount_amount > 0)
                        {{ $receipt->discount_type == 'percent' ? $receipt->discount_amount.'%' : 'TSh '.number_format($receipt->discount_amount) }}
                      @else
                        -
                      @endif
                  </td>
                  <td class="text-right text-danger font-weight-bold">TSh {{ number_format($receipt->final_buying_cost) }}</td>
                  <td class="text-right text-success">
                      <div class="font-weight-bold">TSh {{ number_format($receipt->total_profit) }}</div>
                      @if($receipt->productVariant->can_sell_in_tots ?? false)
                        @php 
                           $glassRev = $receipt->total_units * ($receipt->productVariant->total_tots ?? 0) * ($receipt->productVariant->selling_price_per_tot ?? 0);
                           $glassProfit = $glassRev - $receipt->final_buying_cost;
                        @endphp
                        <div class="small text-muted mt-1">
                          <span class="{{ $glassProfit < $receipt->total_profit ? 'text-warning' : 'text-primary' }}">
                             Gl. Strategy: <strong>TSh {{ number_format($glassProfit) }}</strong>
                          </span>
                        </div>
                      @endif
                  </td>
                </tr>
              @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="fa fa-info-circle fa-3x mb-3 d-block opacity-50"></i>
                    No stock receipts found for the selected period.
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {!! $receipts->appends(request()->query())->links() !!}
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
