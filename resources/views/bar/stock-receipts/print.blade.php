@extends('layouts.dashboard')

@section('title', 'Print Stock Receipt #' . $receiptNumber)

@section('content')
<div class="row d-print-none mb-4">
    <div class="col-md-12 text-right">
        <button onclick="window.print()" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="fa fa-print mr-2"></i> Print Receipt
        </button>
        <a href="{{ route('bar.stock-receipts.create') }}" class="btn btn-light shadow-sm rounded-pill px-4 border">
            <i class="fa fa-plus mr-2"></i> New Entry
        </a>
        <a href="{{ route('bar.stock-receipts.index') }}" class="btn btn-light shadow-sm rounded-pill px-4 border">
            <i class="fa fa-list mr-2"></i> View All
        </a>
    </div>
</div>

<div class="tile shadow-lg border-0 p-5 position-relative overflow-hidden" id="printableArea">
    <!-- RECEIVED Watermark -->
    <div class="watermark d-flex">RECEIVED</div>

    <!-- Header -->
    <div class="row mb-5 align-items-center">
        <div class="col-8">
            <h1 class="font-weight-bold mb-1" style="color: #940000; letter-spacing: -1px;">STOCK RECEIPT</h1>
            <p class="text-muted mb-0 font-weight-bold">OFFICIAL GOODS RECEIVED NOTE (GRN)</p>
            <div class="mt-3 badge badge-pill badge-light border px-3 py-2 text-dark font-weight-bold">
                BATCH #{{ $receiptNumber }}
            </div>
        </div>
        <div class="col-4 text-right">
            <!-- QR Code -->
            <div class="d-inline-block p-2 border rounded bg-white shadow-sm">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode(url()->current()) }}" alt="QR Code" style="width: 80px; height: 80px;">
            </div>
            <p class="smallest text-muted mt-2 mb-0">Scan to Verify Receipt</p>
        </div>
    </div>

    <hr class="mb-5 border-light-2">

    <!-- Supplier & Info -->
    <div class="row mb-5">
        <div class="col-4">
            <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3" style="letter-spacing: 1px;">From Supplier</h6>
            <h5 class="font-weight-bold mb-1">{{ $supplier->company_name }}</h5>
            <p class="text-muted small mb-0"><i class="fa fa-phone mr-1"></i> {{ $supplier->phone }}</p>
            <p class="text-muted small"><i class="fa fa-envelope mr-1"></i> {{ $supplier->email }}</p>
        </div>
        <div class="col-4 border-left">
            <div class="pl-4">
                <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3" style="letter-spacing: 1px;">Reception Details</h6>
                <p class="mb-1 small">Received: <span class="font-weight-bold text-dark">{{ \Carbon\Carbon::parse($receivedDate)->format('d M, Y') }}</span></p>
                <p class="mb-1 small">By: <span class="font-weight-bold text-dark">{{ $receivedBy->name ?? 'System Admin' }}</span></p>
                <p class="mb-0 small">Dept: <span class="text-muted">Warehouse/Inventory</span></p>
            </div>
        </div>
        <div class="col-4 text-right">
            <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3" style="letter-spacing: 1px;">Financial Summary</h6>
            <p class="mb-1 small text-muted">Gross Purchase: <span>TSh {{ number_format($receipts->sum('total_buying_cost')) }}</span></p>
            <p class="mb-1 small text-danger">Total Discounts: <span class="font-weight-bold">(-) TSh {{ number_format($receipts->sum('discount_value')) }}</span></p>
            <p class="mb-1 small">Net Buying Cost: <span class="font-weight-bold text-dark">TSh {{ number_format($receipts->sum('final_buying_cost')) }}</span></p>
            <p class="mb-0 h6 mt-2 font-weight-bold text-success border-top pt-2">EST. TOTAL PROFIT: TSh {{ number_format($receipts->sum('total_selling_value') - $receipts->sum('final_buying_cost')) }}</p>
            <div class="mt-2 smallest text-muted">
                {{ $receipts->count() }} items &bull; {{ number_format($receipts->sum('quantity_received'), 1) }} total units received
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm custom-print-table">
            <thead class="bg-light">
                <tr>
                    <th class="py-2 px-3 text-center" width="40">#</th>
                    <th class="py-2 px-3">Product Description</th>
                    <th class="py-2 px-3 text-center" width="100">Main Qty</th>
                    <th class="py-2 px-3 text-center" width="120">Total Btls/Pcs</th>
                    <th class="py-2 px-3 text-right" width="130">Price/Btl/Pc</th>
                    <th class="py-2 px-3 text-right" width="140">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipts as $index => $item)
                <tr>
                    <td class="py-2 px-3 text-center text-muted">{{ $index + 1 }}</td>
                    <td class="py-2 px-3">
                        <div class="font-weight-bold text-dark">{{ $item->productVariant->product->name }} ({{ $item->productVariant->name }})</div>
                        <div class="mt-1 smallest">
                            <span class="text-muted mr-2">
                                @if($item->productVariant->items_per_package <= 1)
                                    {{ $item->productVariant->packaging }} (Single)
                                @else
                                    {{ $item->productVariant->packaging }} ({{ $item->productVariant->items_per_package }} units)
                                @endif
                            </span>
                            <span class="text-info mr-2">Sell: <strong>TSh {{ number_format($item->selling_price_per_unit) }}</strong></span>
                            <span class="text-success font-weight-bold">Margin: +{{ number_format($item->profit_per_unit) }}</span>
                        </div>
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

                        <div class="mt-2 p-2 border rounded shadow-sm bg-white">
                            <div class="row no-gutters">
                                <div class="col-6 border-right pr-2">
                                    <div class="smallest font-weight-bold text-uppercase text-primary mb-1 border-bottom pb-1">Bottle Channel</div>
                                    <div class="d-flex justify-content-between smallest mb-1">
                                        <span class="text-muted">Price:</span>
                                        <span class="font-weight-bold">TSh {{ number_format($item->selling_price_per_unit) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between smallest text-success">
                                        <span class="font-weight-bold">Tot Profit:</span>
                                        <span class="font-weight-bold">+TSh {{ number_format($totalLineBtlProfit) }}</span>
                                    </div>
                                </div>
                                <div class="col-6 pl-2">
                                    <div class="smallest font-weight-bold text-uppercase text-info mb-1 border-bottom pb-1">Glass/Tot Channel</div>
                                    @if($hasTots)
                                        <div class="d-flex justify-content-between smallest mb-1">
                                            <span class="text-muted">Price:</span>
                                            <span class="font-weight-bold">TSh {{ number_format($displayTotPrice) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between smallest text-success">
                                            <span class="font-weight-bold">Tot Profit:</span>
                                            <span class="font-weight-bold">+TSh {{ number_format($totalLineTotProfit) }}</span>
                                        </div>
                                        <div class="smallest text-muted mt-1 italic" style="font-size: 8px;">
                                            Yields: {{ number_format($totalLineTots) }} Glasses total ({{ $totsPerUnit }}/btl)
                                        </div>
                                    @else
                                        <div class="text-center py-2 text-muted smallest italic">Not configured for portion sales</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="py-2 px-3 text-center">{{ number_format($item->quantity_received, 1) }}</td>
                    <td class="py-2 px-3 text-center font-weight-bold">{{ number_format($item->total_units) }}</td>
                    <td class="py-2 px-3 text-right">TSh {{ number_format($item->buying_price_per_unit) }}</td>
                    <td class="py-2 px-3 text-right font-weight-bold text-dark">TSh {{ number_format($item->final_buying_cost) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-white">
                <tr>
                    <th colspan="5" class="text-right py-3 px-3 border-0">NET COST TOTAL</th>
                    <th class="text-right py-3 px-3 h4 mb-0 font-weight-bold border shadow-sm" style="color: #940000; background: #fffcfc;">
                        TSh {{ number_format($receipts->sum('final_buying_cost')) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($notes)
    <div class="mt-4 p-3 bg-light rounded" style="border-left: 4px solid #940000;">
        <h6 class="font-weight-bold mb-1 small text-uppercase" style="letter-spacing: 1px;">Observations & Notes:</h6>
        <p class="mb-0 text-dark small italic" style="line-height: 1.5;">{{ $notes }}</p>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="row mt-5 pt-5">
        <div class="col-6">
            <div style="border-top: 1px dashed #333; width: 220px; margin-bottom: 8px;"></div>
            <p class="smallest font-weight-bold text-uppercase text-muted">Authorized Supplier/Delivery Agent</p>
            <p class="smallest text-muted">Name & Signature / Stamp</p>
        </div>
        <div class="col-6 text-right d-flex flex-column align-items-end text-right">
            <div style="border-top: 1px dashed #333; width: 220px; margin-bottom: 8px;"></div>
            <p class="smallest font-weight-bold text-uppercase text-muted">Receiving Officer (MauzoLink)</p>
            <p class="smallest text-muted">Verification Stamp Required</p>
        </div>
    </div>

    <div class="text-center mt-5 pt-5 opacity-25">
        <p class="smallest mb-0 text-uppercase" style="letter-spacing: 2px;">*** Electronic Stock Verification Document ***</p>
        <p class="smallest">Generated on {{ date('d M Y, H:i:s') }}</p>
    </div>
</div>

<style>
    .smallest { font-size: 0.65rem; letter-spacing: 0.5px; }
    .border-light-2 { border-color: #f0f0f0; }
    .custom-print-table th { 
        text-transform: uppercase; 
        font-size: 10px; 
        letter-spacing: 1px;
        background-color: #f8f9fa !important;
        color: #444 !important;
        vertical-align: middle;
    }
    .custom-print-table td { font-size: 13px; vertical-align: middle; }
    
    /* Watermark Style */
    .watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 120px;
        font-weight: 900;
        color: rgba(148, 0, 0, 0.1);
        z-index: 0;
        pointer-events: none;
        user-select: none;
        text-transform: uppercase;
        letter-spacing: 20px;
        border: 15px solid rgba(148, 0, 0, 0.1);
        padding: 20px 60px;
        border-radius: 20px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @media print {
        .app-header, .app-sidebar, .d-print-none, .breadcrumb, .main-footer { display: none !important; }
        .app-content { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .tile { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
        body { background: white !important; }
        .container-fluid, .row { width: 100% !important; margin: 0 !important; }
        .watermark { color: rgba(148, 0, 0, 0.15) !important; border-color: rgba(148, 0, 0, 0.15) !important; z-index: -10 !important; display: flex !important; }
        .bg-white { background-color: #fff !important; }
    }
</style>

@if(request()->has('auto_print'))
<script>
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 800);
    }
</script>
@endif

@endsection
