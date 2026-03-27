@extends('layouts.dashboard')

@section('title', 'Master Sheet (Accordion History)')

@section('content')
<style>
  .excel-table { font-size: 0.9rem; width: 100% !important; margin-bottom: 0 !important; }
  .excel-table th { background: #212529 !important; color: white !important; font-size: 0.75rem; vertical-align: middle !important; padding: 12px 8px !important; }
  .excel-table td { vertical-align: middle !important; padding: 10px 12px !important; }
  .excel-table tr.main-row { cursor: pointer; transition: background 0.2s; }
  .excel-table tr.main-row:hover { background-color: #f1f3f5 !important; }
  .excel-table tr.main-row[aria-expanded="true"] { background-color: #e7f3ff !important; border-bottom: none !important; }
  
  .money-column { text-align: right; font-family: 'Courier New', Courier, monospace; }
  .status-badge { font-size: 0.65rem; padding: 2px 5px; border-radius: 3px; font-weight: bold; text-transform: uppercase; }
  .badge-open { border: 1px solid #28a745; color: #28a745; }
  .badge-closed { border: 1px solid #dc3545; color: #dc3545; }

  .detail-row { background-color: #fcfcfc !important; }
  .detail-container { padding: 20px 40px; border-left: 5px solid #0056b3; box-shadow: inset 0 3px 6px rgba(0,0,0,0.08); background: #fdfdfd; }
  .nested-table { font-size: 0.85rem; background: white; border: 1px solid #dee2e6; }
  .nested-table th { background: #6c757d !important; color: white !important; text-transform: uppercase; font-size: 0.7rem; border: none !important; }
  
  @media print {
    .d-print-none { display: none !important; }
    .excel-table { font-size: 10pt; width: 100% !important; }
    .excel-table th { background: #eee !important; color: #000 !important; border: 1px solid #000 !important; }
    .excel-table td { border: 1px solid #000 !important; }
    .app-content { margin: 0 !important; padding: 10px !important; }
    @page { size: landscape; margin: 0.5cm; }
  }
</style>

@section('styles')
<style>
  .manager-received { background-color: #e8f5e9 !important; }
</style>
@endsection

<div class="app-title d-print-none">
  <div>
    <h1><i class="fa fa-list-alt"></i> Master Sheet Archive</h1>
    <p>Click any row to instantly see the reconciliation breakdown.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item">Manager</li>
    <li class="breadcrumb-item active">Interactive History</li>
  </ul>
</div>

<div class="tile d-print-none mb-3 py-2">
  <form method="GET" action="{{ route('Manager.daily-master-sheet.history') }}" class="row align-items-center">
    <div class="col-md-3">
      <label class="small font-weight-bold mb-0">From Date:</label>
      <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
    </div>
    <div class="col-md-3">
      <label class="small font-weight-bold mb-0">To Date:</label>
      <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
    </div>
    <div class="col-md-2 mt-3">
      <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
    </div>
    <div class="col-md-2 mt-3 text-right">
       <a href="{{ route('Manager.daily-master-sheet.history') }}" class="btn btn-outline-secondary btn-sm btn-block"><i class="fa fa-refresh"></i> Reset</a>
    </div>
  </form>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile p-0" style="overflow:hidden;">
      <div class="table-responsive">
        <table class="table table-bordered excel-table">
          <thead>
            <tr>
              <th rowspan="2" class="text-center">#</th>
              <th rowspan="2">DATE</th>
              <th rowspan="2" class="text-center">STATUS</th>
              <th rowspan="2" class="text-right">OPENING CASH</th>
              <th colspan="3" class="text-center">DAILY COLLECTIONS</th>
              <th rowspan="2" class="text-right bg-secondary text-white">ASSETS</th>
              <th rowspan="2" class="text-right">EXPENSES</th>
              <th colspan="3" class="text-center bg-dark text-white">RECONCILIATION</th>
              <th rowspan="2" class="text-center d-print-none">PRINT</th>
            </tr>
            <tr>
              <th class="text-right">CASH</th>
              <th class="text-right">DIGITAL</th>
              <th class="text-right">TOTAL</th>
              <th class="text-right">EXPECTED</th>
              <th class="text-right text-success">PROFIT</th>
              <th class="text-right text-info">CYCLE</th>
            </tr>
          </thead>
          <tbody>
            @if($ledgers->count() > 0)
              @foreach($ledgers as $index => $ledger)
                @php
                    $subTotal = ($ledger->handoverCash + $ledger->handoverDigital);
                    $totalAssets = $ledger->opening_cash + $subTotal;
                    $isClosed = $ledger->status === 'closed';
                    $rowClass = $ledger->isManagerReceived ? 'manager-received' : '';
                @endphp
                {{-- MAIN ROW: Click to Collapse --}}
                <tr class="main-row {{ $rowClass }}" data-toggle="collapse" data-target="#details-{{ $ledger->id }}">
                  <td class="text-center text-muted"><i class="fa fa-chevron-down"></i></td>
                  <td class="font-weight-bold text-primary">{{ \Carbon\Carbon::parse($ledger->ledger_date)->format('d M, Y') }}</td>
                  <td class="text-center">
                    <span class="status-badge {{ $isClosed ? 'badge-closed' : 'badge-open' }}">
                      {{ $isClosed ? 'LOCKED' : 'ACTIVE' }}
                    </span>
                  </td>
                  <td class="money-column">{{ number_format($ledger->opening_cash) }}</td>
                  <td class="money-column">{{ number_format($ledger->handoverCash) }}</td>
                  <td class="money-column">{{ number_format($ledger->handoverDigital) }}</td>
                  <td class="money-column font-weight-bold">{{ number_format($subTotal) }}</td>
                  <td class="money-column font-weight-bold bg-light">{{ number_format($totalAssets) }}</td>
                  <td class="money-column text-danger">({{ number_format($ledger->total_expenses) }})</td>
                  <td class="money-column font-weight-bold">{{ number_format($isClosed ? $ledger->actual_closing_cash : $ledger->expected_closing_cash) }}</td>
                  <td class="money-column text-success font-weight-bold">
                     {{ number_format($isClosed ? $ledger->profit_submitted_to_boss : $ledger->profit_generated) }}
                     @if($ledger->isManagerReceived)
                        <span class='badge badge-success'><i class='fa fa-check-circle'></i> Received</span>
                     @elseif($ledger->managerReceiptStatus === 'pending')
                        <span class='badge badge-warning'>Pending</span>
                     @endif
                  </td>
                  <td class="money-column text-info font-weight-bold">{{ number_format($isClosed ? $ledger->carried_forward : ($ledger->expected_closing_cash - $ledger->profit_generated)) }}</td>
                  <td class="text-center d-print-none py-1">
                      <a href="{{ route('Manager.daily-master-sheet', ['date' => \Carbon\Carbon::parse($ledger->ledger_date)->format('Y-m-d'), 'print' => 1]) }}" target="_blank" class="btn btn-outline-dark btn-sm px-2 pt-0 pb-0 shadow-sm" title="Instant Print">
                        <i class="fa fa-print"></i>
                      </a>
                  </td>
                </tr>
                {{-- COLLAPSIBLE DETAIL ROW --}}
                <tr id="details-{{ $ledger->id }}" class="collapse detail-row">
                  <td colspan="13">
                    <div class="detail-container">
                      <div class="row">
                         {{-- EXPENSE BREAKDOWN --}}
                         <div class="col-md-6 border-right">
                           <h6 class="text-danger"><i class="fa fa-minus-circle"></i> DAILY EXPENDITURES (CASH OUT)</h6>
                           <table class="table table-sm nested-table mt-2">
                             <thead>
                               <tr>
                                 <th>Description</th>
                                 <th class="text-right">Amount</th>
                               </tr>
                             </thead>
                             <tbody>
                               @if($ledger->expenseList->count() > 0)
                                 @foreach($ledger->expenseList as $ex)
                                   <tr>
                                     <td>{{ $ex->description }} <small class="text-muted">({{ $ex->category }})</small></td>
                                     <td class="text-right font-weight-bold">{{ number_format($ex->amount) }}</td>
                                   </tr>
                                 @endforeach
                               @else
                                 <tr><td colspan="2" class="text-center italic text-muted">No expenses recorded.</td></tr>
                               @endif
                               
                               @foreach($ledger->pettyCashList as $pc)
                                 <tr>
                                   <td><i class="fa fa-shopping-cart text-muted"></i> Petty Cash Issue (Request #{{ $pc->request_id }})</td>
                                   <td class="text-right font-weight-bold text-info">{{ number_format($pc->amount) }}</td>
                                 </tr>
                               @endforeach
                               <tr class="bg-light">
                                 <th class="text-right">Total Outflow:</th>
                                 <th class="text-right text-danger">TSh {{ number_format($ledger->total_expenses + ($ledger->pettyCashList->sum('amount') ?? 0)) }}</th>
                               </tr>
                             </tbody>
                           </table>
                         </div>

                         {{-- FINAL SUMMARY --}}
                         <div class="col-md-6">
                            <h6 class="text-success"><i class="fa fa-info-circle"></i> RECONCILIATION SUMMARY</h6>
                            <div class="mt-3">
                               <p class="mb-1 d-flex justify-content-between">
                                  <span>Gross Revenue (Total Assets):</span>
                                  <span class="font-weight-bold">TSh {{ number_format($totalAssets) }}</span>
                               </p>
                               <p class="mb-1 d-flex justify-content-between border-bottom pb-1">
                                  <span>Total Expenses Paid:</span>
                                  <span class="text-danger">(-) TSh {{ number_format($ledger->total_expenses + ($ledger->pettyCashList->sum('amount') ?? 0)) }}</span>
                               </p>
                               <p class="mb-3 d-flex justify-content-between h6">
                                  <span>Available Cash in Box:</span>
                                  <span class="font-weight-bold">{{ number_format($isClosed ? $ledger->actual_closing_cash : $ledger->expected_closing_cash) }}</span>
                               </p>
                               
                               <div class="alert alert-info py-2" style="font-size:0.85rem;">
                                  <strong>Final Settlement:</strong><br>
                                  You gave the boss <span class="badge badge-primary">TSh {{ number_format($isClosed ? $ledger->profit_submitted_to_boss : $ledger->profit_generated) }}</span> in profit today. 
                                  The remaining <span class="badge badge-success">TSh {{ number_format($isClosed ? $ledger->carried_forward : ($ledger->expected_closing_cash - $ledger->profit_generated)) }}</span> is in your float for next day's cycle.
                               </div>
                               
                               <div class="text-right">
                                  <a href="{{ route('Manager.daily-master-sheet', ['date' => \Carbon\Carbon::parse($ledger->ledger_date)->format('Y-m-d')]) }}" class="btn btn-primary btn-sm">
                                    <i class="fa fa-external-link"></i> Full Day View
                                  </a>
                               </div>
                            </div>
                         </div>
                      </div>
                    </div>
                  </td>
                </tr>
              @endforeach
            @else
              <tr><td colspan="13" class="text-center py-5 text-muted">No historical data available.</td></tr>
            @endif
          </tbody>
        </table>
      </div>
      
      <div class="mt-3 d-print-none d-flex justify-content-center pb-3">
        {{ $ledgers->links('pagination::bootstrap-4') }}
      </div>
    </div>
  </div>
</div>
@endsection
