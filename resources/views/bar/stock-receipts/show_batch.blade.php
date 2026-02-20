@extends('layouts.dashboard')

@section('title', 'Batch Delivery Details #' . $receiptNumber)

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-cubes"></i> Batch Delivery Details</h1>
    <p>Viewing all items in shipment #{{ $receiptNumber }}</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-receipts.index') }}">Stock Receipts</a></li>
    <li class="breadcrumb-item">Batch details</li>
  </ul>
</div>

<div class="row d-print-none mb-4">
    <div class="col-md-12 text-right">
        <a href="{{ route('bar.stock-receipts.print-batch', $receiptNumber) }}" target="_blank" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="fa fa-print mr-2"></i> Print Official Receipt
        </a>
        <a href="{{ route('bar.stock-receipts.index') }}" class="btn btn-light shadow-sm rounded-pill px-4 border">
            <i class="fa fa-arrow-left mr-2"></i> Back to History
        </a>
    </div>
</div>

<div class="row">
    <!-- Delivery Summary -->
    <div class="col-md-4">
        <div class="tile shadow-sm border-0">
            <h4 class="tile-title border-bottom pb-2">Shipment Summary</h4>
            <div class="tile-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th class="text-muted">Batch Number:</th>
                        <td><span class="badge badge-dark">{{ $receiptNumber }}</span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Supplier:</th>
                        <td><strong>{{ $receipts->first()->supplier->company_name }}</strong></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Received Date:</th>
                        <td>{{ $receipts->first()->received_date->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Received By:</th>
                        <td>{{ $receipts->first()->receivedBy->name ?? 'System Admin' }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="tile shadow-sm border-0 bg-light">
            <h4 class="tile-title border-bottom pb-2">Financial Totals</h4>
            <div class="tile-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Gross Purchase:</span>
                    <span>TSh {{ number_format($receipts->sum('total_buying_cost')) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted text-danger">Total Discounts:</span>
                    <span class="text-danger font-weight-bold">(-) TSh {{ number_format($receipts->sum('discount_value')) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="font-weight-bold">NET COST:</span>
                    <span class="h5 mb-0 font-weight-bold" style="color: #940000;">TSh {{ number_format($receipts->sum('final_buying_cost')) }}</span>
                </div>
                <div class="alert alert-success mt-3 mb-0 py-2">
                    <small>Expected Total Profit:</small>
                    <div class="h5 mb-0 font-weight-bold">TSh {{ number_format($receipts->sum('total_selling_value') - $receipts->sum('final_buying_cost')) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items in this batch -->
    <div class="col-md-8">
        <div class="tile shadow-sm border-0">
            <h4 class="tile-title">Items in this Delivery ({{ $receipts->count() }})</h4>
            <div class="tile-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-light">
                            <tr>
                                <th>Product Details</th>
                                <th class="text-center">Main Qty</th>
                                <th class="text-center">Tot Btls/Pcs</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipts as $item)
                            <tr>
                                <td>
                                    <div class="font-weight-bold text-dark">{{ $item->productVariant->product->name }} ({{ $item->productVariant->name }})</div>
                                    
                                    @php
                                        $totalLineBtlProfit = ($item->selling_price_per_unit - $item->buying_price_per_unit) * $item->total_units;
                                        $hasTots = ($item->productVariant->can_sell_in_tots && ($item->selling_price_per_tot > 0 || $item->productVariant->selling_price_per_tot > 0));
                                        $displayTotPrice = $item->selling_price_per_tot > 0 ? $item->selling_price_per_tot : $item->productVariant->selling_price_per_tot;
                                        $totsPerUnit = $item->productVariant->total_tots > 0 ? $item->productVariant->total_tots : 0;
                                        $totalLineTots = $item->total_units * $totsPerUnit;
                                        $totCost = $totsPerUnit > 0 ? ($item->buying_price_per_unit / $totsPerUnit) : 0;
                                        $totProfitPerGlass = $displayTotPrice - $totCost;
                                        $totalLineTotProfit = $totalLineTots * $totProfitPerGlass;
                                    @endphp

                                    <div class="mt-2 p-2 border rounded shadow-sm bg-light">
                                        <div class="row no-gutters">
                                            <div class="col-6 border-right pr-2">
                                                <div class="smallest font-weight-bold text-uppercase text-primary mb-1 border-bottom pb-1">Bottle Channel</div>
                                                <div class="d-flex justify-content-between x-small mb-1">
                                                    <span class="text-muted">Price:</span>
                                                    <span class="font-weight-bold">TSh {{ number_format($item->selling_price_per_unit) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between x-small text-success">
                                                    <span class="font-weight-bold">Tot Profit:</span>
                                                    <span class="font-weight-bold">+TSh {{ number_format($totalLineBtlProfit) }}</span>
                                                </div>
                                            </div>
                                            <div class="col-6 pl-2">
                                                <div class="smallest font-weight-bold text-uppercase text-info mb-1 border-bottom pb-1">Portion Channel</div>
                                                @if($hasTots)
                                                    <div class="d-flex justify-content-between x-small mb-1">
                                                        <span class="text-muted">Price:</span>
                                                        <span class="font-weight-bold">TSh {{ number_format($displayTotPrice) }}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between x-small text-success">
                                                        <span class="font-weight-bold">Tot Profit:</span>
                                                        <span class="font-weight-bold">+TSh {{ number_format($totalLineTotProfit) }}</span>
                                                    </div>
                                                    <div class="smallest text-muted mt-1 italic">
                                                        Total Yield: {{ number_format($totalLineTots) }} Glasses
                                                    </div>
                                                @else
                                                    <div class="text-center py-2 text-muted smallest italic">No portion selling</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <small class="text-muted d-block mt-2">
                                        @if($item->productVariant->items_per_package <= 1)
                                            {{ $item->productVariant->packaging }} (Single Unit)
                                        @else
                                            {{ $item->productVariant->packaging }} of {{ $item->productVariant->items_per_package }} units
                                        @endif
                                    </small>
                                </td>
                                <td class="text-center">{{ number_format($item->quantity_received, 1) }}</td>
                                <td class="text-center">{{ number_format($item->total_units) }}</td>
                                <td class="text-right">{{ number_format($item->buying_price_per_unit) }}</td>
                                <td class="text-right font-weight-bold text-dark">{{ number_format($item->final_buying_cost) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        @if($receipts->first()->notes)
        <div class="tile shadow-sm border-0">
            <h4 class="tile-title">Internal Notes</h4>
            <div class="tile-body">
                <p class="mb-0 italic">{{ $receipts->first()->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
