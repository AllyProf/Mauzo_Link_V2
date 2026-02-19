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

<div class="tile shadow-lg border-0 p-5" id="printableArea">
    <!-- Header -->
    <div class="row mb-5 align-items-center">
        <div class="col-6">
            <h2 class="font-weight-bold mb-1" style="color: #940000;">STOCK RECEIPT</h2>
            <p class="text-muted mb-0">Official Goods Received Note</p>
        </div>
        <div class="col-6 text-right">
            <h4 class="font-weight-bold mb-0">#{{ $receiptNumber }}</h4>
            <p class="mb-0 text-muted">Date: {{ \Carbon\Carbon::parse($receivedDate)->format('d M, Y') }}</p>
        </div>
    </div>

    <hr class="mb-5 border-light-2">

    <!-- Supplier & Info -->
    <div class="row mb-5">
        <div class="col-4">
            <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3">Supplier Information</h6>
            <h5 class="font-weight-bold mb-1">{{ $supplier->company_name }}</h5>
            <p class="text-muted small mb-0">{{ $supplier->phone }}</p>
            <p class="text-muted small">{{ $supplier->email }}</p>
        </div>
        <div class="col-4">
            <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3">Received By</h6>
            <h5 class="font-weight-bold mb-1">{{ $receivedBy->name }}</h5>
            <p class="text-muted small mb-0">Warehouse Department</p>
        </div>
        <div class="col-4 text-right">
            <h6 class="text-muted text-uppercase smallest font-weight-bold mb-3">Summary</h6>
            <p class="mb-1 small">Total Items: <span class="font-weight-bold text-dark">{{ $receipts->count() }}</span></p>
            <p class="mb-1 small">Total Packages: <span class="font-weight-bold text-dark">{{ number_format($receipts->sum('quantity_received'), 1) }}</span></p>
            <p class="mb-0 small text-primary font-weight-bold">Total Cost: TSh {{ number_format($receipts->sum('final_buying_cost')) }}</p>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table table-bordered table-sm custom-print-table">
            <thead class="bg-light">
                <tr>
                    <th class="py-2 px-3" width="50">#</th>
                    <th class="py-2 px-3">Product Description</th>
                    <th class="py-2 px-3 text-center" width="100">Qty (Pkgs)</th>
                    <th class="py-2 px-3 text-center" width="100">Total Units</th>
                    <th class="py-2 px-3 text-right" width="130">Unit Price</th>
                    <th class="py-2 px-3 text-right" width="130">Total Cost</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipts as $index => $item)
                <tr>
                    <td class="py-2 px-3 text-center text-muted">{{ $index + 1 }}</td>
                    <td class="py-2 px-3">
                        <div class="font-weight-bold text-dark">{{ $item->productVariant->product->name }}</div>
                        <small class="text-muted">{{ $item->productVariant->packaging }} ({{ $item->productVariant->conversion_qty }} units)</small>
                    </td>
                    <td class="py-2 px-3 text-center">{{ number_format($item->quantity_received, 1) }}</td>
                    <td class="py-2 px-3 text-center">{{ number_format($item->total_units) }}</td>
                    <td class="py-2 px-3 text-right">{{ number_format($item->buying_price_per_unit) }}</td>
                    <td class="py-2 px-3 text-right font-weight-bold text-dark">{{ number_format($item->final_buying_cost) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="5" class="text-right py-2 px-3">GRAND TOTAL</th>
                    <th class="text-right py-2 px-3 h5 mb-0 font-weight-bold" style="color: #940000;">
                        TSh {{ number_format($receipts->sum('final_buying_cost')) }}
                    </th>
                </tr>
            </tfoot>
        </table>
    </div>

    @if($notes)
    <div class="mt-5 p-3 bg-light rounded" style="border-left: 4px solid #940000;">
        <h6 class="font-weight-bold mb-1 small text-uppercase">Internal Observation/Notes:</h6>
        <p class="mb-0 text-dark small italic">{{ $notes }}</p>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="row mt-5 pt-5">
        <div class="col-6">
            <div style="border-top: 2px solid #eee; width: 200px; margin-bottom: 5px;"></div>
            <p class="smallest font-weight-bold text-uppercase text-muted">Authorized Supplier Signature</p>
        </div>
        <div class="col-6 text-right d-flex flex-column align-items-end">
            <div style="border-top: 2px solid #eee; width: 200px; margin-bottom: 5px;"></div>
            <p class="smallest font-weight-bold text-uppercase text-muted">Receiving Officer Stamp</p>
        </div>
    </div>

    <div class="text-center mt-5 pt-5 opacity-25">
        <p class="smallest">System Generated Document &bull; {{ date('Y-m-d H:i:s') }}</p>
    </div>
</div>

<style>
    .smallest { font-size: 0.65rem; letter-spacing: 0.5px; }
    .border-light-2 { border-color: #f0f0f0; }
    .custom-print-table th { 
        text-transform: uppercase; 
        font-size: 11px; 
        letter-spacing: 1px;
        background-color: #fbfbfb !important;
        color: #666 !important;
    }
    .custom-print-table td { font-size: 13px; }
    
    @media print {
        .app-header, .app-sidebar, .d-print-none, .breadcrumb { display: none !important; }
        .app-content { margin: 0 !important; padding: 0 !important; }
        .tile { box-shadow: none !important; border: 0 !important; padding: 0 !important; }
        body { background: white !important; }
    }
</style>

@if(request()->has('auto_print'))
<script>
    window.onload = function() {
        window.print();
    }
</script>
@endif

@endsection
