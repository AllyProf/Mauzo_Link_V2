@extends('layouts.dashboard')

@section('title', 'Daily Stock Sheet')

@push('styles')
<style>
    .print-only {
        display: none;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .print-only {
            display: block;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
        }
        .page-break {
            page-break-after: always;
        }
    }
    .widget-small { border: 1px solid #e5e5e5; }
</style>
@endpush

@section('content')
<div class="app-title no-print d-print-none">
    <div>
        <h1><i class="fa fa-file-text-o"></i> Daily Stock Sheet</h1>
        <p>Current Counter Inventory Formatted for Printing</p>
    </div>
    <ul class="app-breadcrumb breadcrumb">
        <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
        <li class="breadcrumb-item"><a href="{{ route('bar.counter.dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('bar.counter.counter-stock') }}">Counter Stock</a></li>
        <li class="breadcrumb-item active">Daily Stock Sheet</li>
    </ul>
</div>

<div class="row no-print d-print-none mb-4">
    <div class="col-md-12 text-right">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow"><i class="fa fa-print"></i> Print Stock Sheet</button>
        <a href="{{ route('bar.counter.counter-stock') }}" class="btn btn-secondary btn-lg shadow ml-2"><i class="fa fa-arrow-left"></i> Back to Inventory</a>
    </div>
</div>

<div class="row print-area">
    <div class="col-md-12">
        <div class="tile">
            <div class="text-center mb-4">
                <h2 class="mb-0">DAILY COUNTER STOCK SHEET</h2>
                <p class="mb-1 text-muted"><strong>Date:</strong> {{ date('l, d F Y - h:i A') }}</p>
                <p class="mb-0 text-muted"><strong>Staff / Counter:</strong> {{ $staffName }}</p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%">#</th>
                            <th style="width: 20%">Item Details</th>
                            <th style="width: 15%">Category</th>
                            <th style="width: 15%" class="text-center text-success">Sold Today</th>
                            <th style="width: 15%" class="text-center text-success">Sales Amount</th>
                            <th style="width: 15%" class="text-center text-primary">System Stock</th>
                            <th style="width: 15%" class="text-center font-italic text-muted">Physical Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stock as $index => $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <strong>{{ $item->name }}</strong>
                                <br><small class="text-muted">{{ $item->measurement }}</small>
                            </td>
                            <td>{{ $item->category }}</td>
                            <td class="text-center" style="vertical-align: middle;">
                                {{ $item->sold_formatted }}
                            </td>
                            <td class="text-center" style="vertical-align: middle;">
                                {{ number_format($item->sold_revenue) }}
                            </td>
                            <td class="text-center font-weight-bold" style="font-size: 1.1rem; vertical-align: middle;">
                                {{ $item->quantity_formatted }}
                            </td>
                            <td class="text-center" style="vertical-align: middle;">
                                &nbsp;
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center p-4 alert alert-warning">
                                <i class="fa fa-info-circle"></i> No inventory found at the counter counter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="thead-light">
                            <th colspan="4" class="text-right font-weight-bold text-uppercase">Total Sales Amount:</th>
                            <th class="text-center font-weight-bold text-success" style="font-size: 1.1rem;">TSh {{ number_format($totalSalesRevenue) }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <div class="row mt-5 pt-4 border-top">
                <div class="col-md-6 col-xs-6 text-center">
                    <p class="mb-5"><strong>Counted By (Staff):</strong></p>
                    <p>______________________________________</p>
                    <p class="small text-muted">Signature & Date</p>
                </div>
                <div class="col-md-6 col-xs-6 text-center">
                    <p class="mb-5"><strong>Verified By (Manager):</strong></p>
                    <p>______________________________________</p>
                    <p class="small text-muted">Signature & Date</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
