@extends('layouts.dashboard')

@section('title', 'Stock Receipt Details')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-download"></i> Stock Receipt Details</h1>
    <p>View stock receipt information</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-receipts.index') }}">Stock Receipts</a></li>
    <li class="breadcrumb-item">Receipt Details</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Receipt #{{ $stockReceipt->receipt_number }}</h3>
        <a href="{{ route('bar.stock-receipts.index') }}" class="btn btn-secondary">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>

      <div class="tile-body">
        <div class="row">
          <div class="col-md-6">
            <h4>Receipt Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Receipt Number:</th>
                <td><strong>{{ $stockReceipt->receipt_number }}</strong></td>
              </tr>
              <tr>
                <th>Product:</th>
                <td>
                  <strong>{{ $stockReceipt->productVariant->product->name ?? 'N/A' }}</strong><br>
                  <small class="text-muted">
                    {{ $stockReceipt->productVariant->measurement ?? '' }} - 
                    {{ $stockReceipt->productVariant->packaging ?? '' }}
                  </small>
                </td>
              </tr>
              <tr>
                <th>Supplier:</th>
                <td>{{ $stockReceipt->supplier->company_name ?? 'N/A' }}</td>
              </tr>
              <tr>
                <th>Quantity Received:</th>
                <td>
                  {{ $stockReceipt->quantity_received }} {{ $stockReceipt->productVariant->packaging ?? 'packages' }}<br>
                  <small class="text-muted">({{ $stockReceipt->total_units }} total bottle(s))</small>
                </td>
              </tr>
              <tr>
                <th>Received Date:</th>
                <td>{{ $stockReceipt->received_date->format('M d, Y') }}</td>
              </tr>
              @if($stockReceipt->expiry_date)
              <tr>
                <th>Expiry Date:</th>
                <td>{{ $stockReceipt->expiry_date->format('M d, Y') }}</td>
              </tr>
              @endif
              <tr>
                <th>Received By:</th>
                <td>{{ $stockReceipt->receivedBy->name ?? 'N/A' }}</td>
              </tr>
            </table>
          </div>
          <div class="col-md-6">
            <h4>Financial Information</h4>
            <table class="table table-borderless">
              <tr>
                <th width="40%">Buying Price/Bottle:</th>
                <td>TSh {{ number_format($stockReceipt->buying_price_per_unit, 2) }}</td>
              </tr>
              <tr>
                <th>Selling Price/Bottle:</th>
                <td>TSh {{ number_format($stockReceipt->selling_price_per_unit, 2) }}</td>
              </tr>
              <tr>
                <th>Profit/Bottle:</th>
                <td>
                  <span class="badge badge-success">
                    TSh {{ number_format($stockReceipt->profit_per_unit, 2) }}
                  </span>
                </td>
              </tr>
              <tr>
                <th>Total Buying Cost:</th>
                <td><strong>TSh {{ number_format($stockReceipt->total_buying_cost, 2) }}</strong></td>
              </tr>
              <tr>
                <th>Total Selling Value:</th>
                <td>TSh {{ number_format($stockReceipt->total_selling_value, 2) }}</td>
              </tr>
              <tr>
                <th>Total Profit:</th>
                <td>
                  <span class="badge badge-success" style="font-size: 14px;">
                    TSh {{ number_format($stockReceipt->total_profit, 2) }}
                  </span>
                </td>
              </tr>
            </table>
          </div>
        </div>

        @if($stockReceipt->notes)
          <div class="row mt-3">
            <div class="col-md-12">
              <h4>Notes</h4>
              <p>{{ $stockReceipt->notes }}</p>
            </div>
          </div>
        @endif

        {{-- Barcode Generation Section --}}
        <div class="row mt-4">
          <div class="col-md-12">
            <div class="tile">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="tile-title"><i class="fa fa-barcode"></i> Product Barcodes</h3>
                <button type="button" class="btn btn-primary" onclick="printBarcodes()">
                  <i class="fa fa-print"></i> Print Barcodes
                </button>
              </div>
              <div class="tile-body" id="barcodeSection">
                <div class="row" id="barcodeContainer">
                  @php
                    $productId = $stockReceipt->productVariant->product_id;
                    $variantId = $stockReceipt->product_variant_id;
                    $receiptNumber = $stockReceipt->receipt_number;
                    $quantity = $stockReceipt->quantity_received;
                  @endphp
                  @for($i = 1; $i <= $quantity; $i++)
                    @php
                      $barcodeValue = $receiptNumber . '-' . $productId . '-' . $variantId . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                    @endphp
                    <div class="col-md-3 mb-3 text-center barcode-item" style="border: 1px solid #dee2e6; padding: 15px; margin: 5px;">
                      <div class="mb-2">
                        <strong>{{ $stockReceipt->productVariant->product->name }}</strong><br>
                        <small>{{ $stockReceipt->productVariant->measurement }} - {{ $stockReceipt->productVariant->packaging }}</small><br>
                        <small class="text-muted">#{{ $i }}/{{ $quantity }}</small>
                      </div>
                      <svg id="barcode-{{ $i }}" class="barcode-svg" data-value="{{ $barcodeValue }}"></svg>
                      <div class="mt-2">
                        <small class="text-muted">{{ $barcodeValue }}</small>
                      </div>
                    </div>
                  @endfor
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<!-- JsBarcode Library -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Generate barcodes for each item
    const barcodeSvgs = document.querySelectorAll('.barcode-svg[data-value]');
    
    barcodeSvgs.forEach(function(svg) {
      const barcodeValue = svg.getAttribute('data-value');
      
      try {
        JsBarcode('#' + svg.id, barcodeValue, {
          format: "CODE128",
          width: 2,
          height: 60,
          displayValue: true,
          fontSize: 12,
          margin: 5
        });
      } catch (e) {
        console.error('Barcode generation error:', e);
      }
    });
  });

  function printBarcodes() {
    const printContent = document.getElementById('barcodeSection').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
  }
</script>
<style>
  @media print {
    body * {
      visibility: hidden;
    }
    #barcodeSection, #barcodeSection * {
      visibility: visible;
    }
    #barcodeSection {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
    }
    .barcode-item {
      page-break-inside: avoid;
      margin-bottom: 20px;
    }
  }
</style>
@endpush
