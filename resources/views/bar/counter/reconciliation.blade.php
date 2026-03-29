@extends('layouts.dashboard')

@section('title', 'Daily Reconciliation')

@push('styles')
{{-- Use local styles instead of CDN --}}
<style>
  #waiters-table { border-collapse: collapse !important; border-radius: 8px; overflow: hidden; border: 1px solid #dee2e6 !important; }
  #waiters-table th, #waiters-table td { vertical-align: middle; padding: 12px 10px; border: 1px solid #dee2e6 !important; }
  #waiters-table thead th { background-color: #2d3436 !important; color: white !important; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; border-right: 1px solid rgba(255,255,255,0.1) !important; }
  .table-responsive { border-radius: 8px; border: 1px solid #dee2e6; }
  
  /* Audit & Variance Highlighting */
  .audit-col-bg { background-color: #f1f7fe !important; }
  .diff-col-bg { background-color: #fff9f1 !important; }
  
  /* Status Pill Badges */
  .status-pill { border-radius: 50px; padding: 4px 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
  
  /* Simple Compact Widgets */
  .widget-small { height: 90px; border-radius: 8px !important; margin-bottom: 15px; }
  .widget-small.coloured-icon .info { color: #000 !important; }
  .widget-small .icon { min-width: 70px !important; padding: 10px !important; font-size: 2rem !important; }
  .widget-small .info h4 { font-size: 0.8rem !important; margin-bottom: 2px !important; }
  .widget-small .info p { font-size: 15px !important; }

  #waiters-table_wrapper .row { margin-bottom: 15px; }
  .badge { font-weight: 600; padding: 5px 8px; }
  
  @media (max-width: 768px) {
    .widget-small { margin-bottom: 15px; }
    .tile-title { font-size: 1.2rem; }
  }
  .date-header-row td { background-color: #4b5e71 !important; color: white !important; font-size: 0.85rem; font-weight: 700; letter-spacing: 1px; border: none !important; position: sticky; top: 0; z-index: 5; }
</style>
@endpush

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-balance-scale"></i> Daily Reconciliation</h1>
    <p>View and verify waiter reconciliations</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Finance</li>
    <li class="breadcrumb-item">Reconciliation</li>
  </ul>
</div>

<!-- Date Selector and Search -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ Route::currentRouteName() === 'accountant.counter.reconciliation' ? route('accountant.counter.reconciliation') : route('bar.counter.reconciliation') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="week" class="mr-2">Select Week:</label>
          <input type="week" name="week" id="week" class="form-control" value="{{ $week }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="status-filter" class="mr-2">Status:</label>
          <select id="status-filter" class="form-control">
            <option value="">All Statuses</option>
            <option value="verified">Verified</option>
            <option value="submitted">Settled (Ready)</option>
            <option value="paid">Paid</option>
            <option value="partial">Partial</option>
            <option value="pending">Pending</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> View Weekly Reconciliation
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Active Waiters</h4>
        <p><b>{{ $waiters->count() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Bar Sales (Drinks)</h4>
        <p><b>TSh {{ number_format($waiters->sum('bar_sales'), 0) }}</b></p>
      </div>
    </div>
  </div>
  @php
    // Use actual handover breakdown if submitted; otherwise fall back to waiter sums
    if (isset($todayHandover) && $todayHandover && $todayHandover->payment_breakdown) {
        $hBreakdown = $todayHandover->payment_breakdown;
        $widgetCash = $hBreakdown['cash'] ?? 0;
        $widgetDigital = array_sum(array_filter($hBreakdown, fn($k) => $k !== 'cash', ARRAY_FILTER_USE_KEY));
    } else {
        $widgetCash = $waiters->sum('cash_collected');
        $widgetDigital = $waiters->sum('mobile_money_collected');
    }
  @endphp
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-bank fa-3x"></i>
      <div class="info">
        <h4>Total Cash</h4>
        <p><b>TSh {{ number_format($widgetCash, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-mobile fa-3x"></i>
      <div class="info">
        <h4>Total Digital Money</h4>
        <p><b>TSh {{ number_format($widgetDigital, 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Waiters List -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Waiters Reconciliation - {{ $displayDate }}</h3>
      <div class="tile-body">
        @if($waiters->count() > 0)
          <div class="table-responsive shadow-sm">
            <table class="table table-hover table-bordered table-striped" id="waiters-table">
              <thead>
                <tr>
                  <th class="all">#</th>
                  <th class="all">Staff</th>
                  <th class="all text-center d-none">Date</th>
                  <th>Bar Sales</th>
                  <th>Orders</th>
                  <th>Cash</th>
                  <th>Digital</th>
                  <th>Expenses</th>
                  <th class="all audit-col-bg text-center">Expected Amount</th>
                  <th class="all audit-col-bg">Recorded</th>
                  <th class="all audit-col-bg">Settled Amount</th>
                  <th class="all diff-col-bg text-center">Diff</th>
                  <th class="all text-center">Status</th>
                  <th class="all">Actions</th>
                </tr>
              </thead>
              <tbody>
                @php 
                  $lastDate = null; 
                  $totalExpenses = $shiftExpenses->sum('amount');
                @endphp
                @foreach($waiters as $index => $data)
                  @php 
                    $currentDate = date('Y-m-d', strtotime($data['date']));
                    $dayName = date('l, F d, Y', strtotime($data['date']));
                  @endphp

 @php $lastDate = $currentDate; @endphp
                <tr data-waiter-id="{{ $data['waiter']->id }}" class="waiter-row">
                  <td>{{ $index + 1 }}</td>
                   <td>
                    <strong>{{ $data['waiter']->full_name }}</strong>
                    <span class="badge badge-secondary ml-1">{{ $data['waiter']->role->name ?? 'Staff' }}</span><br>
                    <small class="text-muted">{{ $data['waiter']->email }}</small>
                  </td>
                  <td class="text-center d-none">
                    <span class="badge badge-light border">{{ date('M d, Y', strtotime($data['date'])) }}</span>
                  </td>
                  <td>
                    <strong>TSh {{ number_format($data['bar_sales'], 0) }}</strong>
                  </td>
                  <td><span class="badge badge-info">{{ $data['total_orders'] }}</span></td>
                  <td>TSh {{ number_format($data['cash_collected'], 0) }}</td>
                  <td>TSh {{ number_format($data['mobile_money_collected'], 0) }}</td>
                  <td>-</td>
                  <td class="audit-col-bg"><strong>TSh {{ number_format($data['expected_amount'], 0) }}</strong></td>
                   <td class="audit-col-bg">
                    @if(isset($data['recorded_amount']) && $data['recorded_amount'] > 0)
                      <strong class="text-info">TSh {{ number_format($data['recorded_amount'], 0) }}</strong>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="audit-col-bg">
                    @php
                      // For verified rows: show the actual recorded (order-based) amount cleanly
                      // For pending/partial: show submitted amount from reconciliation
                      $displaySettled = ($data['status'] === 'verified')
                          ? $data['recorded_amount']   // clean value from actual orders
                          : ($data['submitted_amount'] > 0 ? $data['submitted_amount'] : null);
                      $displayDiff = ($data['status'] === 'verified')
                          ? ($data['recorded_amount'] - $data['expected_amount'])
                          : $data['difference'];
                    @endphp
                    @if($displaySettled !== null && $displaySettled > 0)
                      <strong class="{{ $displaySettled >= $data['expected_amount'] ? 'text-success' : 'text-warning' }}">
                        TSh {{ number_format($displaySettled, 0) }}
                      </strong>
                    @else
                      <span class="text-muted">Waiting</span>
                    @endif
                  </td>
                  <td class="diff-col-bg text-center">
                    @if($data['submitted_amount'] > 0 || $data['reconciliation'])
                      <strong class="{{ $displayDiff >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($displayDiff > 0)
                          +{{ number_format($displayDiff, 0) }}
                        @elseif($displayDiff < 0)
                          {{ number_format($displayDiff, 0) }}
                        @else
                          0
                        @endif
                      </strong>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td class="text-center">
                    @if($data['status'] === 'verified')
                      <span class="status-pill badge-success">Verified</span>
                    @elseif($data['status'] === 'submitted')
                      <span class="status-pill badge-info">Settled</span>
                    @elseif($data['status'] === 'paid')
                      <span class="status-pill badge-success">Paid</span>
                    @elseif($data['status'] === 'partial')
                      <span class="status-pill badge-warning">Partial</span>
                    @elseif($data['status'] === 'disputed')
                      <span class="status-pill badge-danger">Disputed</span>
                    @else
                      <span class="status-pill badge-warning">Pending</span>
                    @endif
                  </td>
                  <td class="text-nowrap">
                    <!-- Always show View Orders -->
                    <button class="btn btn-sm btn-info view-orders-btn mr-1 mb-1" 
                            data-waiter-id="{{ $data['waiter']->id }}"
                            data-date="{{ $data['date'] }}"
                            data-waiter-name="{{ $data['waiter']->full_name }}" title="View Orders">
                      <i class="fa fa-eye"></i> View
                    </button>
                    
                    @if(Route::currentRouteName() === 'accountant.counter-reconciliation' && $data['reconciliation'] && $data['status'] === 'submitted')
                      <button class="btn btn-sm btn-success verify-btn mr-1 mb-1" 
                              data-reconciliation-id="{{ $data['reconciliation']->id }}" title="Verify">
                        <i class="fa fa-check"></i> Verify
                      </button>
                    @endif

                    {{-- Show Reconcile button if not verified and either (pending/partial) OR (Paid but not yet formally submitted) --}}
                    @if($data['status'] !== 'verified' && ($data['status'] === 'pending' || $data['status'] === 'partial' || ($data['status'] === 'paid' && $data['submitted_amount'] == 0)))
                      <button class="btn btn-sm btn-success mark-all-paid-btn mr-1 mb-1 font-weight-bold" 
                              data-waiter-id="{{ $data['waiter']->id }}"
                              data-date="{{ $data['date'] }}"
                              data-total-amount="{{ $data['expected_amount'] }}"
                              data-recorded-amount="{{ $data['recorded_amount'] ?? 0 }}"
                              data-submitted-amount="{{ $data['submitted_amount'] ?? 0 }}"
                              data-difference="{{ $data['difference'] ?? 0 }}"
                              data-breakdown="{{ json_encode($data['platform_totals'] ?? []) }}"
                              data-waiter-name="{{ $data['waiter']->full_name }}" title="{{ $data['status'] === 'paid' ? 'Submit Collection' : 'Reconcile Staff' }}">
                        <i class="fa fa-hand-holding-usd"></i> {{ $data['status'] === 'paid' ? 'Submit' : 'Reconcile' }}
                      </button>
                    @endif


                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No waiters with orders found for this date.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Handover to Accountant -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">
        <i class="fa fa-handshake-o"></i> My Handover to Manager
        
        @if(isset($latestClosedShift) && $latestClosedShift)
        <a href="{{ route('bar.counter.shift.print', $latestClosedShift->id) }}" target="_blank" class="btn {{ $latestClosedShift->status === 'open' ? 'btn-info' : 'btn-primary' }} btn-sm float-right shadow-sm">
          <i class="fa fa-print"></i> Export {{ $latestClosedShift->status === 'open' ? 'Current Active' : 'Recent' }} Shift #{{ $latestClosedShift->shift_number }} Report
        </a>
        @endif
      </h3>
      <div class="tile-body">
        @php
            $totalCashHandover = 0;
            $totalDigitalHandover = 0;
            $overallTotalHandover = 0;
            $platformTotals = [];
            
            $totalCashRecordedArr = 0;
            $totalDigitalRecordedArr = 0;
            
            foreach($waiters as $data) {
                $totalCashHandover += $data['cash_collected'];
                $totalDigitalHandover += $data['mobile_money_collected'];
                $totalCashRecordedArr += $data['recorded_cash'];
                $totalDigitalRecordedArr += $data['recorded_digital'];
                
                foreach($data['orders'] as $order) {
                    if ($order->orderPayments->count() > 0) {
                        foreach($order->orderPayments as $payment) {
                            if ($payment->payment_method === 'cash') continue;
                            
                            $provider = strtolower(trim($payment->mobile_money_number ?? ''));
                            $method = strtolower($payment->payment_method ?? '');
                            $label = 'MOBILE MONEY';
                            
                            if (str_contains($provider, 'nmb') || str_contains($method, 'nmb')) $label = 'NMB BANK';
                            elseif (str_contains($provider, 'crdb') || str_contains($method, 'crdb')) $label = 'CRDB BANK';
                            elseif (str_contains($provider, 'kcb') || str_contains($method, 'kcb')) $label = 'KCB BANK';
                            elseif (str_contains($provider, 'nbc') || str_contains($method, 'nbc')) $label = 'NBC BANK';
                            elseif (str_contains($provider, 'mpesa') || str_contains($provider, 'm-pesa')) $label = 'M-PESA';
                            elseif (str_contains($provider, 'mixx')) $label = 'MIXX BY YAS';
                            elseif (str_contains($provider, 't-pesa') || str_contains($provider, 'tpesa') || str_contains($provider, 'ttcl')) $label = 'T-PESA';
                            elseif (str_contains($provider, 'airtel')) $label = 'AIRTEL MONEY';
                            elseif (str_contains($provider, 'tigo')) $label = 'TIGO PESA';
                            elseif (str_contains($provider, 'halo')) $label = 'HALOPESA';
                            elseif (str_contains($provider, 'visa')) $label = 'VISA CARD';
                            elseif (str_contains($provider, 'mastercard') || str_contains($provider, 'master card')) $label = 'MASTERCARD';
                            elseif (str_contains($provider, 'equity')) $label = 'EQUITY BANK';
                            elseif (str_contains($provider, 'absa')) $label = 'ABSA BANK';
                            elseif (str_contains($provider, 'dtb') || str_contains($provider, 'diamond')) $label = 'DTB BANK';
                            elseif (str_contains($provider, 'exim')) $label = 'EXIM BANK';
                            elseif (str_contains($provider, 'azania')) $label = 'AZANIA BANK';
                            elseif (str_contains($provider, 'stanbic')) $label = 'STANBIC BANK';
                            elseif ($method === 'card' || str_contains($method, 'pos')) {
                                $label = 'BANK CARD';
                            } elseif (str_contains($method, 'bank') || str_contains($provider, 'bank') || str_contains($provider, 'transfer')) {
                                $label = 'BANK TRANSFER';
                            }
                            
                            $platformTotals[$label] = ($platformTotals[$label] ?? 0) + $payment->amount;
                        }
                    } else {
                        if ($order->payment_method === 'cash') continue;
                        $provider = strtolower(trim($order->mobile_money_number ?? ''));
                        $method = strtolower($order->payment_method ?? '');
                        $label = 'MOBILE MONEY';
                        
                        if (str_contains($provider, 'nmb') || str_contains($method, 'nmb')) $label = 'NMB BANK';
                        elseif (str_contains($provider, 'crdb') || str_contains($method, 'crdb')) $label = 'CRDB BANK';
                        elseif (str_contains($provider, 'kcb') || str_contains($method, 'kcb')) $label = 'KCB BANK';
                        elseif (str_contains($provider, 'nbc') || str_contains($method, 'nbc')) $label = 'NBC BANK';
                        elseif (str_contains($provider, 'equity')) $label = 'EQUITY BANK';
                        elseif (str_contains($provider, 'mpesa') || str_contains($provider, 'm-pesa')) $label = 'M-PESA';
                        elseif (str_contains($provider, 'visa')) $label = 'VISA CARD';
                        elseif (str_contains($provider, 'mastercard')) $label = 'MASTERCARD';
                        elseif (str_contains($provider, 'stanbic')) $label = 'STANBIC BANK';
                        elseif ($method === 'card' || str_contains($method, 'pos')) {
                            $label = 'BANK CARD';
                        } elseif (str_contains($method, 'bank') || str_contains($provider, 'bank') || str_contains($provider, 'transfer')) {
                            $label = 'BANK TRANSFER';
                        }
                        
                        $platformTotals[$label] = ($platformTotals[$label] ?? 0) + $order->total_amount;
                    }
                }
            }

            // --- Subtract Expenses from Handover Totals ---
            $totalExpenses = 0;
            foreach($shiftExpenses as $expense) {
                $overallTotalHandover -= $expense->amount;
                $totalExpenses += $expense->amount;
                $method = strtolower($expense->payment_method ?: 'cash');
                
                if ($method === 'cash') {
                    $totalCashHandover -= $expense->amount;
                } else {
                    $totalDigitalHandover -= $expense->amount;
                    
                    // Subtract from individual platform totals too
                    $labelFound = null;
                    $normMethod = str_replace(['_', '-'], '', $method);
                    foreach($platformTotals as $l => $amt) {
                        $normL = strtolower(str_replace([' ', '-', '_'], '', $l));
                        if (str_contains($normL, $normMethod) || str_contains($normMethod, $normL)) {
                            $labelFound = $l;
                            break;
                        }
                    }
                    
                    if ($labelFound) {
                        $platformTotals[$labelFound] -= $expense->amount;
                    } else {
                        // Hardcoded fallbacks
                        if (str_contains($method, 'nbc')) {
                            $platformTotals['NBC BANK'] = ($platformTotals['NBC BANK'] ?? 0) - $expense->amount;
                            $labelFound = 'NBC BANK';
                        } elseif (str_contains($method, 'mpesa')) {
                            $platformTotals['M-PESA'] = ($platformTotals['M-PESA'] ?? 0) - $expense->amount;
                            $labelFound = 'M-PESA';
                        } elseif (str_contains($method, 'azania')) {
                            $platformTotals['AZANIA BANK'] = ($platformTotals['AZANIA BANK'] ?? 0) - $expense->amount;
                            $labelFound = 'AZANIA BANK';
                        } else {
                             // If completely unknown, treat as digital subtraction only
                        }
                    }
                    
                    // CASCADE NEGATIVE: If the platform is now negative, take the difference from cash
                    if ($labelFound && $platformTotals[$labelFound] < 0) {
                        $deficit = abs($platformTotals[$labelFound]);
                        $totalCashHandover -= $deficit;
                        $platformTotals[$labelFound] = 0; // Don't show negative in the UI/DB
                    }
                }
            }
            
            $overallTotalHandover = $totalCashHandover + $totalDigitalHandover;
            $staffBreakdown = []; // For audit table display
        @endphp

        @if($todayHandover)
          <div class="alert {{ $todayHandover->status === 'verified' ? 'alert-success' : ($todayHandover->status === 'disputed' ? 'alert-danger' : 'alert-info') }}">
            <h4>Handover {{ ucfirst($todayHandover->status) }}</h4>
            <p>You submitted your daily physical and digital collections on {{ $todayHandover->created_at->format('h:i A') }}.</p>
            <hr>
            <div class="row">
              <div class="col-md-6">
                <strong>Total Amount:</strong> TSh {{ number_format($todayHandover->amount, 0) }}<br>
                @if($todayHandover->payment_breakdown)
                  <ul class="mb-0 mt-2">
                    @foreach($todayHandover->payment_breakdown as $method => $amount)
                      @if($amount > 0)
                        <li><strong>{{ strtoupper(str_replace('_', ' ', $method)) }}:</strong> TSh {{ number_format($amount, 0) }}</li>
                      @endif
                    @endforeach
                  </ul>
                @endif
              </div>
              <div class="col-md-6 border-left">
                @if($totalExpenses > 0)
                  <div class="mb-3">
                    <strong class="text-danger">Total Operational Expenses:</strong><br>
                    <span class="badge badge-danger p-2 mt-1">- TSh {{ number_format($totalExpenses, 0) }}</span>
                    <ul class="mt-2 small text-muted list-unstyled">
                      @foreach($shiftExpenses as $exp)
                        <li><i class="fa fa-minus-circle mr-1"></i> {{ $exp->description }} ({{ strtoupper($exp->payment_method) }}): TSh {{ number_format($exp->amount, 0) }}</li>
                      @endforeach
                    </ul>
                  </div>
                @endif
                <strong>Manager:</strong> {{ $todayHandover->recipientStaff->full_name ?? 'N/A' }}<br>
                @if($todayHandover->notes)
                  <strong>Notes:</strong> 
                  @php
                      $cleanNotes = preg_replace('/\[ShortagePaidTotal:\d+\]/i', '', $todayHandover->notes);
                      $cleanNotes = preg_replace('/\[ShortagePaidBreakdown:[^\]]+\]/i', '', $cleanNotes);
                      $cleanNotes = preg_replace('/\[ShortagePaid:[^\]]+\]/i', '', $cleanNotes);
                      $cleanNotes = preg_replace('/\[ShortageNote:\s*([^\]]+)\]/i', '<span class="text-success d-block mt-1 font-italic">$1</span>', $cleanNotes);
                      echo trim($cleanNotes);
                  @endphp
                  <br>
                @endif
                @if($todayHandover->dispute_reason)
                  <strong class="text-danger">Dispute Reason:</strong> {{ $todayHandover->dispute_reason }}
                @endif
              </div>
            </div>

            @php
              // Normalize a platform key for comparison
              $normKey = fn(string $k): string => strtolower(trim(str_replace([' ', '-', '_'], '', $k)));

              // Build System Expected per-platform from actual order payments (gross), then deduct expenses
              $sysBreakdown = [];
              foreach ($waiters as $wData) {
                  foreach ($wData['orders'] as $order) {
                      foreach ($order->orderPayments as $payment) {
                          $provider = strtolower(trim($payment->mobile_money_number ?? ''));
                          $method   = strtolower(trim($payment->payment_method ?? 'cash'));
                          if ($method === 'cash') { $pKey = 'cash'; }
                          elseif (str_contains($provider, 'nbc') || str_contains($method, 'nbc')) { $pKey = 'nbc'; }
                          elseif (str_contains($provider, 'nmb') || str_contains($method, 'nmb')) { $pKey = 'nmb'; }
                          elseif (str_contains($provider, 'crdb') || str_contains($method, 'crdb')) { $pKey = 'crdb'; }
                          elseif (str_contains($provider, 'visa') || str_contains($method, 'visa')) { $pKey = 'visa'; }
                          elseif (str_contains($provider, 'mastercard') || str_contains($method, 'mastercard')) { $pKey = 'mastercard'; }
                          elseif (str_contains($provider, 'mixx') || str_contains($method, 'mixx')) { $pKey = 'mixx'; }
                          elseif (str_contains($provider, 'halo') || str_contains($method, 'halo')) { $pKey = 'halopesa'; }
                          elseif (str_contains($provider, 'mpesa') || str_contains($method, 'mpesa')) { $pKey = 'mpesa'; }
                          elseif (str_contains($provider, 'tigo') || str_contains($method, 'tigo')) { $pKey = 'tigo_pesa'; }
                          elseif (str_contains($provider, 'airtel') || str_contains($method, 'airtel')) { $pKey = 'airtel_money'; }
                          else { $pKey = $method; }
                          $sysBreakdown[$pKey] = ($sysBreakdown[$pKey] ?? 0) + (float)$payment->amount;
                      }
                  }
              }
              // Deduct expenses from appropriate platform
              foreach ($shiftExpenses as $exp) {
                  $expKey = strtolower(trim($exp->payment_method ?: 'cash'));
                  if (isset($sysBreakdown[$expKey])) {
                      $sysBreakdown[$expKey] -= $exp->amount;
                  }
              }

              // Staff submitted breakdown (from handover)
              $staffBreakdown = [];
              if ($todayHandover->payment_breakdown) {
                  foreach ($todayHandover->payment_breakdown as $k => $v) {
                      $staffBreakdown[strtolower(trim($k))] = (float)$v;
                  }
              }

              // Merge all channels
              $auditChannels = array_unique(array_merge(array_keys($staffBreakdown), array_keys($sysBreakdown)));

              // Calculate shortage
              $handoverExpected = $waiters->sum('expected_amount') - $totalExpenses;
              $handoverShortage = $handoverExpected - (float)$todayHandover->amount;
            @endphp

            @if($todayHandover->status === 'verified' && $handoverShortage > 0.01)
              {{-- ========= SHORTAGE DETECTED ========= --}}
              <div class="mt-3 border rounded shadow-sm overflow-hidden">
                <div class="p-3" style="background: #fff5f5; border-left: 5px solid #dc3545;">
                  <div class="d-flex align-items-center mb-2">
                    <i class="fa fa-exclamation-triangle fa-2x mr-3 text-danger"></i>
                    <div>
                      <h5 class="mb-0 text-danger font-weight-bold"><i class="fa fa-warning"></i> Manager Audit: Revenue Shortage Recorded</h5>
                      <small class="text-muted">Reference: Handover #{{ $todayHandover->id }} — Please report to your manager.</small>
                    </div>
                    <div class="ml-auto text-right">
                      <div class="text-danger h4 font-weight-bold mb-0">-TSh {{ number_format($handoverShortage, 0) }}</div>
                      <small class="text-muted">vs Expected TSh {{ number_format($handoverExpected, 0) }}</small>
                    </div>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm table-bordered mb-0" style="font-size: 0.85rem;">
                    <thead style="background-color: #f9f9f9;">
                      <tr>
                        <th style="color: #940000; font-weight: 800; font-size: 11px;">REVENUE CHANNEL</th>
                        <th>YOU SUBMITTED</th>
                        <th>SYSTEM EXPECTED</th>
                        <th>VARIANCE</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($auditChannels as $ch)
                        @php
                          $staffAmt  = $staffBreakdown[$ch] ?? 0;
                          $sysAmt    = $sysBreakdown[$ch]   ?? 0;
                          $variance  = $staffAmt - $sysAmt;
                        @endphp
                        @if($staffAmt > 0 || $sysAmt > 0)
                        <tr>
                          <td class="font-weight-bold">{{ strtoupper(str_replace('_', ' ', $ch)) }}</td>
                          <td>TSh {{ number_format($staffAmt, 0) }}</td>
                          <td class="text-muted">TSh {{ number_format($sysAmt, 0) }}</td>
                          <td>
                            @if($variance < 0)
                              <span class="text-danger font-weight-bold"><i class="fa fa-arrow-down"></i> Short: {{ number_format($variance, 0) }}</span>
                            @elseif($variance > 0)
                              <span class="text-success font-weight-bold"><i class="fa fa-arrow-up"></i> +{{ number_format($variance, 0) }}</span>
                            @else
                              <span class="text-success small"><i class="fa fa-check-circle"></i> OK</span>
                            @endif
                          </td>
                        </tr>
                        @endif
                      @endforeach
                      <tr style="background:#fff0f0; border-top: 2px solid #dc3545;">
                        <td class="font-weight-bold">TOTAL</td>
                        <td class="font-weight-bold">TSh {{ number_format($todayHandover->amount, 0) }}</td>
                        <td class="font-weight-bold text-muted">TSh {{ number_format($handoverExpected, 0) }}</td>
                        <td class="font-weight-bold text-danger">-TSh {{ number_format($handoverShortage, 0) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

            @elseif($todayHandover->status === 'verified')
              <div class="alert alert-success mt-3 mb-0 border-0 shadow-sm" style="border-left: 5px solid #28a745 !important;">
                <i class="fa fa-check-circle text-success mr-2"></i>
                <strong>Manager Audit Passed.</strong> Your handover has been fully verified with no discrepancies.
              </div>
            @endif

          </div>
        @elseif($manager)
          @php
            $keyMap = [
                'M-PESA' => 'mpesa_amount',
                'MIXX BY YAS' => 'mixx_amount',
                'T-PESA' => 'tpesa_amount',
                'HALOPESA' => 'halopesa_amount',
                'TIGO PESA' => 'tigo_pesa_amount',
                'AIRTEL MONEY' => 'airtel_money_amount',
                'NMB BANK' => 'nmb_amount',
                'CRDB BANK' => 'crdb_amount',
                'KCB BANK' => 'kcb_amount',
                'NBC BANK' => 'nbc_amount',
                'EQUITY BANK' => 'equity_amount',
                'ABSA BANK' => 'absa_amount',
                'DTB BANK' => 'dtb_amount',
                'EXIM BANK' => 'exim_amount',
                'AZANIA BANK' => 'azania_amount',
                'STANBIC BANK' => 'stanbic_amount',
                'VISA CARD' => 'visa_amount',
                'MASTERCARD' => 'mastercard_amount',
                'BANK CARD' => 'bank_card_amount',
                'BANK TRANSFER' => 'bank_transfer_amount',
                'MOBILE MONEY' => 'mobile_money_amount'
            ];
          @endphp

          <div class="alert alert-info border-primary mb-4 p-3 shadow-sm rounded">
            <h5><i class="fa fa-calculator"></i> Handover Summary</h5>
            <div class="row text-center mt-3">
              <div class="col-md-3 mb-2 mb-md-0">
                <small class="text-uppercase font-weight-bold text-muted">Total Cash</small>
                <h4 class="text-success mb-0">TSh {{ number_format($totalCashHandover, 0) }}</h4>
                @php 
                    $netRecordedCash = $totalCashRecordedArr - array_sum(array_column($shiftExpenses->where('payment_method', 'cash')->toArray(), 'amount'));
                @endphp
                @if($totalCashHandover < $netRecordedCash)
                  <small class="text-danger font-weight-bold">Short: -{{ number_format($netRecordedCash - $totalCashHandover, 0) }}</small>
                @elseif($totalCashHandover > $netRecordedCash)
                  <small class="text-success font-weight-bold">Surplus: +{{ number_format($totalCashHandover - $netRecordedCash, 0) }}</small>
                @endif
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <small class="text-uppercase font-weight-bold text-muted">Total Digital</small>
                <h4 class="text-success mb-0">TSh {{ number_format($totalDigitalHandover, 0) }}</h4>
                @php 
                    $netRecordedDigital = $totalDigitalRecordedArr - array_sum(array_column($shiftExpenses->where('payment_method', '!=', 'cash')->toArray(), 'amount'));
                @endphp
                @if($totalDigitalHandover < $netRecordedDigital)
                  <small class="text-danger font-weight-bold">Short: -{{ number_format($netRecordedDigital - $totalDigitalHandover, 0) }}</small>
                @elseif($totalDigitalHandover > $netRecordedDigital)
                  <small class="text-success font-weight-bold">Surplus: +{{ number_format($totalDigitalHandover - $netRecordedDigital, 0) }}</small>
                @endif
              </div>
              <div class="col-md-3 mb-2 mb-md-0" style="border-left: 1px solid #dee2e6;">
                <small class="text-uppercase font-weight-bold text-muted text-danger">Total Expenses</small>
                <h4 class="text-danger mb-0">-TSh {{ number_format($totalExpenses, 0) }}</h4>
              </div>
              <div class="col-md-3" style="border-left: 1px solid #dee2e6;">
                <small class="text-uppercase font-weight-bold text-muted">Net Handover</small>
                <h4 class="text-primary mb-0">TSh {{ number_format($overallTotalHandover, 0) }}</h4>
                <small class="text-muted">(After Expenses)</small>
              </div>
            </div>
          </div>

          <form action="{{ route('bar.counter.handover') }}" method="POST" id="handoverForm">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            
            <div class="alert alert-warning">
              <h5><i class="fa fa-warning"></i> Ready to Close Your Day?</h5>
              <p>Please confirm the totals gathered from waiter reconciliations for <strong>{{ date('M d, Y', strtotime($date)) }}</strong>.</p>
            </div>

            <div class="row">
              @if($totalCashHandover != 0)
              <div class="col-md-3 form-group">
                <label>Physical Cash</label>
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
                  <input type="number" name="cash_amount" class="form-control handover-input" value="{{ round($totalCashHandover) }}">
                </div>
              </div>
              @endif

              @foreach($platformTotals as $label => $amount)
                @if($amount != 0)
                <div class="col-md-3 form-group" title="{{ $label }} breakdown">
                  <label>{{ $label }}</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
                    <input type="number" name="{{ $keyMap[$label] ?? 'mobile_money_amount' }}" class="form-control handover-input" value="{{ round($amount) }}">
                  </div>
                </div>
                @endif
              @endforeach
              
              @if($overallTotalHandover == 0)
              <div class="col-md-12">
                <div class="alert alert-info border-info">
                  <i class="fa fa-info-circle"></i> No collections recorded today. Waiters must reconcile their orders before you can handover.
                </div>
              </div>
              @endif
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <div class="p-3 bg-light rounded text-right mb-3">
                  <h4 class="mb-0">Total Declaration: <span id="handover-total" class="text-primary font-weight-bold">TSh {{ number_format($overallTotalHandover, 0) }}</span></h4>
                </div>
              </div>
            </div>

            {{-- Hidden: circulation_money defaults to 0, manager can view/adjust from their end --}}
            <input type="hidden" name="circulation_money" value="0">

            <div class="row">
              <div class="col-md-12 form-group">
                <label>Notes / Comments (Optional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Any explanations for shortages or extra cash..."></textarea>
              </div>
            </div>
            
            <button type="button" id="submitHandoverBtn" class="btn btn-primary btn-block">
              <i class="fa fa-paper-plane"></i> Submit Detailed Handover to Manager
            </button>
          </form>
        @else
          <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> No active manager found. You cannot handover money until a manager is registered by the owner.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Orders Modal -->
<div class="modal fade" id="ordersModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Orders for <span id="modal-waiter-name"></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="orders-content">
          <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p>Loading orders...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}?v=2.1"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}?v=2.1"></script>
<script>
jQuery(document).ready(function($) {
  // Define global maps for JS
  const dailyExpenses = {!! json_encode($expensesByDate->map(fn($group) => $group->sum('amount'))) !!};
  const expensesDetails = {!! json_encode($expensesByDate) !!};

  // Initialize DataTables
  if ($('#waiters-table').length > 0) {
    const table = $('#waiters-table').DataTable({
      "pageLength": 50,
      "responsive": false,
      "ordering": false,
      "language": {
        "search": "_INPUT_",
        "searchPlaceholder": "Search Waiter or Date..."
      },
      "drawCallback": function(settings) {
        var api = this.api();
        var rows = api.rows({ page: 'current' }).nodes();
        var last = null;
 
        api.column(2, { page: 'current' }).data().each(function(group, i) {
          let tempDiv = document.createElement('div');
          tempDiv.innerHTML = group;
          let dateText = tempDiv.innerText.trim();
          
          // Original date format from badge is 'Mar 28, 2026' - need to match with Y-m-d
          const dateObj = new Date(dateText);
          const dateKey = dateObj.getFullYear() + '-' + String(dateObj.getMonth() + 1).padStart(2, '0') + '-' + String(dateObj.getDate()).padStart(2, '0');
          const dailyTotal = dailyExpenses[dateKey] || 0;

          if (last !== dateText) {
              $(rows[i]).before(
                `<tr class="date-header-row"><td colspan="13" style="background-color: #5d6d7e !important; color: white !important; font-weight: bold; padding: 10px;">
                  <div class="d-flex justify-content-between align-items-center">
                    <span><i class="fa fa-calendar-check-o mr-2"></i> ${dateText.toUpperCase()}</span>
                    <div>
                      <span class="badge badge-warning p-2 mr-2">
                        Expenses: TSh ${dailyTotal.toLocaleString()}
                        ${dailyTotal > 0 ? `<a href="javascript:void(0)" class="text-white ml-2 view-expenses-btn" data-date="${dateKey}" title="View Details"><i class="fa fa-search-plus"></i> View</a>` : ''}
                      </span>
                      <button class="btn btn-xs btn-outline-light add-expense-btn" 
                              data-date="${dateKey}" 
                              data-display-date="${dateText}"
                              data-platforms='{!! json_encode(array_keys(array_filter($expectedBreakdowns, fn($v) => $v > 0))) !!}'>
                        <i class="fa fa-plus-circle"></i> Add Expense
                      </button>
                    </div>
                  </div>
                </td></tr>`
              );
            last = dateText;
          }
        });
      }
    });

    // Custom Status Filter
    $('#status-filter').on('change', function() {
      const statusValue = $(this).val();
      if (statusValue) {
        // Regex exactly matches the selected status (case-insensitive) in the 12th column (Status)
        table.column(11).search('^' + statusValue + '$', true, false).draw();
      } else {
        // Clear filter
        table.column(11).search('').draw();
      }
    });
  }
  
  // View orders button
  $(document).on('click', '.view-orders-btn', function() {
    const waiterId = $(this).data('waiter-id');
    const waiterName = $(this).data('waiter-name');
    const date = $(this).data('date');
    
    $('#modal-waiter-name').text(waiterName);
    $('#orders-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading orders...</p></div>');
    $('#ordersModal').modal('show');
    
    $.ajax({
      url: '{{ Route::currentRouteName() === "accountant.counter.reconciliation" ? route("accountant.counter.reconciliation.waiter-orders", ":id") : route("bar.counter.reconciliation.waiter-orders", ":id") }}'.replace(':id', waiterId),
      method: 'GET',
      data: { date: date, shift_id: '{{ $shiftId }}' },
      success: function(response) {
        if (response.success && response.orders.length > 0) {
          let html = '<div class="table-responsive"><table class="table table-sm">';
          html += '<thead><tr><th>Order #</th><th>Time</th><th>Platform</th><th>Bar Items (Drinks)</th><th>Bar Amount</th><th>Total Order</th><th>Payment</th><th>Status</th></tr></thead><tbody>';
          
          response.orders.forEach(function(order) {
            // Calculate bar amount (from items - drinks)
            let barAmount = 0;
            if (order.items && order.items.length > 0) {
              barAmount = order.items.reduce(function(sum, item) {
                return sum + (parseFloat(item.total_price) || 0);
              }, 0);
            }
            
            // Calculate food amount (from kitchen_order_items)
            let foodAmount = 0;
            if (order.kitchen_order_items && order.kitchen_order_items.length > 0) {
              foodAmount = order.kitchen_order_items.reduce(function(sum, item) {
                return sum + (parseFloat(item.total_price) || 0);
              }, 0);
            }
            
            html += '<tr>';
            html += '<td><strong>' + order.order_number + '</strong></td>';
            html += '<td>' + new Date(order.created_at).toLocaleTimeString() + '</td>';
            html += '<td>';
            if (order.order_source) {
              const source = order.order_source.toLowerCase();
              let badgeClass = 'secondary';
              let displayText = order.order_source;
              if (source === 'mobile') { badgeClass = 'info'; displayText = 'Mobile'; }
              else if (source === 'web') { badgeClass = 'primary'; displayText = 'Web'; }
              else if (source === 'kiosk') { badgeClass = 'warning'; displayText = 'Kiosk'; }
              html += '<span class="badge badge-' + badgeClass + '">' + displayText + '</span>';
            } else {
              html += '<span class="text-muted">-</span>';
            }
            html += '</td>';
            html += '<td>';
            if (order.items && order.items.length > 0) {
              order.items.forEach(function(item) {
                html += '<span class="badge badge-primary">' + item.quantity + 'x ' + (item.product_variant?.product?.name || 'N/A') + '</span> ';
              });
            } else { html += '<span class="text-muted">-</span>'; }
            html += '</td>';
            html += '<td><strong>TSh ' + barAmount.toLocaleString() + '</strong></td>';
            html += '<td><strong>TSh ' + parseFloat(order.total_amount).toLocaleString() + '</strong></td>';
            html += '<td>';
            if (order.order_payments && order.order_payments.length > 0) {
              // Iterate through all payments if the NEW system is used
              order.order_payments.forEach(function(payment, idx) {
                if (idx > 0) html += '<hr class="my-1">';
                
                const method = payment.payment_method || 'N/A';
                const provider = (payment.mobile_money_number || 'MOBILE').toLowerCase();
                let displayLabel = method.toUpperCase();
                let badgeClass = 'secondary';
                
                if (method === 'cash') {
                  displayLabel = 'CASH';
                  badgeClass = 'warning';
                } else {
                  badgeClass = 'success';
                  if (provider.includes('mpesa')) displayLabel = 'M-PESA';
                  else if (provider.includes('mixx')) displayLabel = 'MIXX BY YAS';
                  else if (provider.includes('halo')) displayLabel = 'HALOPESA';
                  else if (provider.includes('tigo')) displayLabel = 'TIGO PESA';
                  else if (provider.includes('t-pesa') || provider.includes('tpesa') || provider.includes('ttcl')) displayLabel = 'T-PESA';
                  else if (provider.includes('airtel')) displayLabel = 'AIRTEL MONEY';
                  else if (provider.includes('nmb')) displayLabel = 'NMB BANK';
                  else if (provider.includes('crdb')) displayLabel = 'CRDB BANK';
                  else if (provider.includes('kcb')) displayLabel = 'KCB BANK';
                }
                
                html += '<span class="badge badge-' + badgeClass + '">' + displayLabel + '</span>';
                html += '<div style="font-size: 0.8rem;" class="mt-1">';
                html += '<strong>TSh ' + parseFloat(payment.amount).toLocaleString() + '</strong>';
                if (payment.transaction_reference) {
                   html += '<br><small class="text-muted"><i class="fa fa-hashtag"></i> Ref: ' + payment.transaction_reference + '</small>';
                }
                html += '</div>';
              });
            } else if (order.payment_method) {
              // Fallback for OLD system using order fields
              const method = order.payment_method;
              const providerName = (order.mobile_money_number || 'MOBILE').toLowerCase();
              let displayProvider = method.toUpperCase();
              let badgeClass = method === 'cash' ? 'warning' : 'success';
              
              if (method === 'mobile_money' || method === 'bank') {
                if (providerName.includes('mpesa')) displayProvider = 'M-PESA';
                else if (providerName.includes('mixx')) displayProvider = 'MIXX BY YAS';
                else if (providerName.includes('halo')) displayProvider = 'HALOPESA';
                else if (providerName.includes('tigo')) displayProvider = 'TIGO PESA';
                else if (providerName.includes('t-pesa') || providerName.includes('tpesa') || providerName.includes('ttcl')) displayProvider = 'T-PESA';
                else if (providerName.includes('airtel')) displayProvider = 'AIRTEL MONEY';
                else if (providerName.includes('nmb')) displayProvider = 'NMB BANK';
                else if (providerName.includes('crdb')) displayProvider = 'CRDB BANK';
                else if (providerName.includes('kcb')) displayProvider = 'KCB BANK';
              }
              
              html += '<span class="badge badge-' + badgeClass + '">' + displayProvider + '</span>';
              if (order.transaction_reference) {
                html += '<br><small class="text-muted" style="font-size: 0.8rem; margin-top: 3px; display: block;"><i class="fa fa-hashtag"></i> Ref: ' + order.transaction_reference + '</small>';
              }
            } else {
              html += '<span class="badge badge-secondary">Not Set</span>';
            }
            html += '</td>';
            html += '<td>';
            if (order.payment_status === 'paid' || order.paid_by_waiter_id || (order.order_payments && order.order_payments.length > 0)) {
              html += '<span class="badge badge-success">Paid</span>';
              if (order.paid_by_waiter?.full_name) html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter.full_name + '</small>';
              else if (order.paid_by_waiter) {
                // Determine name if paidByWaiter is an object, or use fallback
                const recorder = typeof order.paid_by_waiter === 'object' ? order.paid_by_waiter.full_name : order.paid_by_waiter;
                html += '<br><small class="text-muted">Paid by ' + recorder + '</small>';
              }
            } else {
              html += '<span class="badge badge-warning">Pending</span>';
            }
            html += '</td>';
            html += '</tr>';
          });
          html += '</tbody></table></div>';
          $('#orders-content').html(html);
        } else {
          $('#orders-content').html('<div class="alert alert-info">No orders found.</div>');
        }
      },
      error: function(xhr) {
        console.error('Error loading orders:', xhr);
        const errorMsg = xhr.responseJSON?.error || xhr.statusText || 'Error loading orders';
        $('#orders-content').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMsg + '</div>');
      }
    });
  });

  // Add counter expense
  $(document).on('click', '.add-expense-btn', function() {
    const date = $(this).data('date');
    const displayDate = $(this).data('display-date');
    const activePlatforms = $(this).data('platforms') || ['cash_amount'];
    const shiftId = '{{ $shiftId }}';
    
    // Map internal database keys to friendly labels
    const platformNames = {
      'cash_amount': 'Petty Cash (Daily Drawer)',
      'mpesa_amount': 'M-PESA Phone',
      'tigo_pesa_amount': 'Tigo Pesa Phone',
      'tpesa_amount': 'T-Pesa Phone',
      'airtel_money_amount': 'Airtel Money Phone',
      'halopesa_amount': 'Halopesa Phone',
      'mixx_amount': 'MIXX BY YAS',
      'nmb_amount': 'NMB Bank',
      'crdb_amount': 'CRDB Bank',
      'nbc_amount': 'NBC Bank',
      'kcb_amount': 'KCB Bank',
      'azania_amount': 'Azania Bank',
      'equity_amount': 'Equity Bank',
      'absa_amount': 'Absa Bank',
      'dtb_amount': 'DTB Bank',
      'exim_amount': 'Exim Bank',
      'stanbic_amount': 'Stanbic Bank',
      'bank_card_amount': 'Bank Card POS',
      'bank_transfer_amount': 'Bank Transfer',
      'visa_card_amount': 'VISA CARD Machine',
      'mastercard_amount': 'MASTERCARD Machine'
    };

    let platformOptions = `<option value="cash">Petty Cash (Daily Drawer)</option>`;
    // Only show digital/bank platforms if they have recorded money in this shift
    activePlatforms.forEach(p => {
      if (p === 'cash_amount') return; // Cash is already added first
      
      const label = platformNames[p] || p.replace('_amount', '').replace(/_/g, ' ').toUpperCase();
      platformOptions += `<option value="${p.replace('_amount', '')}">${label}</option>`;
    });

    if (!platformOptions) {
        platformOptions = `<option value="cash">Petty Cash (Daily Drawer)</option>`;
    }

    Swal.fire({
      title: 'Add Daily Expense',
      html: `
        <div class="text-left">
          <p class="mb-2">Expense for <strong>${displayDate}</strong></p>
          <div class="form-group">
            <label>Amount (TSh)</label>
            <input type="number" id="expense-amount" class="form-control" placeholder="e.g. 10000" autofocus>
          </div>
          <div class="form-group">
            <label>Description</label>
            <input type="text" id="expense-desc" class="form-control" placeholder="e.g. Electricity, Water, Soap...">
          </div>
          <div class="form-group">
            <label>Payment Platform</label>
            <select id="expense-method" class="form-control">
              ${platformOptions}
            </select>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Save Expense',
      showLoaderOnConfirm: true,
      preConfirm: () => {
        const amount = $('#expense-amount').val();
        const desc = $('#expense-desc').val();
        const method = $('#expense-method').val();
        if (!amount || !desc) { Swal.showValidationMessage('Please fill all fields'); return false; }
        return { amount, desc, method };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ route("bar.counter.expense") }}',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            amount: result.value.amount,
            description: result.value.desc,
            payment_method: result.value.method,
            expense_date: date,
            shift_id: shiftId
          },
          success: function(response) {
            Swal.fire({
              toast: true,
              position: 'top-end',
              icon: 'success',
              title: 'Expense Saved Successfully!',
              showConfirmButton: false,
              timer: 1500,
              timerProgressBar: true
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Oops...', text: xhr.responseJSON?.message || 'Error occurred' });
          }
        });
      }
    });
  });

  // View and Delete expenses
  $(document).on('click', '.view-expenses-btn', function() {
    const date = $(this).data('date');
    const dayExpenses = expensesDetails[date] || [];
    
    let listHtml = `
      <div class="table-responsive">
        <table class="table table-sm table-striped text-left">
          <thead>
            <tr>
              <th>Description</th>
              <th>Platform</th>
              <th>Amount</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    dayExpenses.forEach(exp => {
      listHtml += `
        <tr>
          <td>${exp.description}</td>
          <td><span class="badge badge-light">${exp.payment_method.toUpperCase()}</span></td>
          <td><strong>TSh ${parseFloat(exp.amount).toLocaleString()}</strong></td>
          <td>
            <button class="btn btn-xs btn-danger delete-exp-btn" data-id="${exp.id}">
              <i class="fa fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });
    
    listHtml += `</tbody></table></div>`;
    
    Swal.fire({
      title: 'Daily Expenses Details',
      html: listHtml,
      showConfirmButton: false,
      showCloseButton: true,
      width: '500px'
    });
  });

  // Handle delete expense
  $(document).on('click', '.delete-exp-btn', function() {
    const id = $(this).data('id');
    const btn = $(this);
    
    Swal.fire({
      title: 'Delete Expense?',
      text: "This will restore the handover balance.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ url("bar/counter/expense") }}/' + id,
          method: 'DELETE',
          data: { _token: '{{ csrf_token() }}' },
          success: function(response) {
            Swal.fire({ icon: 'success', title: 'Deleted!', toast: true, position: 'top-end', timer: 1500, showConfirmButton: false });
            location.reload();
          },
          error: function() {
            Swal.fire('Error', 'Failed to delete expense', 'error');
          }
        });
      }
    });
  });

  // Verify reconciliation button
  $(document).on('click', '.verify-btn', function() {
    const reconciliationId = $(this).data('reconciliation-id');
    const btn = $(this);
    Swal.fire({
      title: 'Verify Reconciliation?',
      text: 'Are you sure you want to verify this reconciliation?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Verify',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
        $.ajax({
          url: '{{ Route::currentRouteName() === "accountant.counter.reconciliation" ? route("accountant.counter.verify-reconciliation", ":id") : route("bar.counter.verify-reconciliation", ":id") }}'.replace(':id', reconciliationId),
          method: 'POST',
          data: { _token: '{{ csrf_token() }}' },
          success: function(response) {
            if (response.success) {
              Swal.fire({ icon: 'success', title: 'Verified!', text: 'Reconciliation verified successfully.', timer: 2000, timerProgressBar: true }).then(() => { location.reload(); });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to verify reconciliation';
            Swal.fire({ icon: 'error', title: 'Error', text: error });
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Verify');
          }
        });
      }
    });
  });
  
  // Mark all orders as paid
  $(document).on('click', '.mark-all-paid-btn', function() {
    const waiterId = $(this).data('waiter-id');
    const date = $(this).data('date');
    const totalAmount = parseFloat($(this).data('total-amount'));
    const recordedAmount = parseFloat($(this).data('recorded-amount')) || 0;
    const submittedAmount = parseFloat($(this).data('submitted-amount')) || 0;
    const difference = parseFloat($(this).data('difference')) || 0;
    const waiterName = $(this).data('waiter-name') || 'this waiter';
    const breakdown = $(this).data('breakdown') || {};
    const btn = $(this);
    
    let platformHtml = '';
    // Add Cash field first
    platformHtml += `
      <div class="form-group mb-2">
        <label class="small font-weight-bold mb-1">CASH COLLECTION</label>
        <div class="input-group input-group-sm">
          <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
          <input type="number" class="form-control platform-input" data-platform="cash" value="${recordedAmount > 0 ? (recordedAmount - Object.values(breakdown).reduce((a, b) => a + b, 0)) : totalAmount}" placeholder="0">
        </div>
      </div>
    `;
    
    // Add Digital platforms
    Object.keys(breakdown).forEach(platform => {
      platformHtml += `
        <div class="form-group mb-2">
          <label class="small font-weight-bold mb-1">${platform.toUpperCase()}</label>
          <div class="input-group input-group-sm">
            <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
            <input type="number" class="form-control platform-input" data-platform="${platform}" value="${breakdown[platform]}" placeholder="0">
          </div>
        </div>
      `;
    });

    const remainingAmount = totalAmount - submittedAmount;
    const defaultSubmitAmount = submittedAmount > 0 ? Math.max(0, remainingAmount) : (recordedAmount > 0 ? recordedAmount : totalAmount);
    
    let differenceHtml = difference > 0 ? `<span class="text-success">+TSh ${Math.abs(difference).toLocaleString()}</span>` : (difference < 0 ? `<span class="text-danger">TSh ${difference.toLocaleString()}</span>` : `<span class="text-muted">TSh 0</span>`);
    
    Swal.fire({
      title: 'Submit Payment',
      width: '450px',
      html: `
        <div class="text-left">
          <p class="mb-2">Record actual collections for <strong>${waiterName}</strong>.</p>
          <div class="alert alert-light border p-2 mb-3">
            <div class="row small"><div class="col-6">Expected:</div><div class="col-6 text-right"><strong>TSh ${totalAmount.toLocaleString()}</strong></div></div>
            ${recordedAmount > 0 ? `<div class="row small mt-1"><div class="col-6">Recorded:</div><div class="col-6 text-right text-info"><strong>TSh ${recordedAmount.toLocaleString()}</strong></div></div>` : ''}
            <div class="row small mt-1"><div class="col-6">Difference:</div><div class="col-6 text-right"><strong>${differenceHtml}</strong></div></div>
          </div>
          
          <div id="platform-breakdown-container">
            ${platformHtml}
          </div>
          
          <hr class="my-3">
          
          <div class="form-group mb-0">
            <label class="font-weight-bold">Total Amount to Submit:</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
              <input type="number" id="payment-amount" class="form-control font-weight-bold text-primary" value="${defaultSubmitAmount > 0 ? defaultSubmitAmount : ''}" readonly>
            </div>
            <small class="text-muted">This is automatically summed from the individual platform fields above.</small>
          </div>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Submit Payment',
      didOpen: () => {
        const updateTotal = () => {
          let total = 0;
          $('.platform-input').each(function() {
            total += parseFloat($(this).val()) || 0;
          });
          $('#payment-amount').val(total);
        };
        $('.platform-input').on('input', updateTotal);
        updateTotal(); // Run initially
      },
      preConfirm: () => {
        const amount = parseFloat(document.getElementById('payment-amount').value);
        if (!amount || amount <= 0) { Swal.showValidationMessage('Enter a valid amount'); return false; }
        
        const finalBreakdown = {};
        $('.platform-input').each(function() {
          const platform = $(this).data('platform');
          finalBreakdown[platform] = parseFloat($(this).val()) || 0;
        });
        
        return { amount: amount, breakdown: finalBreakdown };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Submitting...');
        $.ajax({
          url: '{{ route("bar.counter.mark-all-paid") }}',
          method: 'POST',
          data: { 
            _token: '{{ csrf_token() }}', 
            waiter_id: waiterId, 
            date: date, 
            submitted_amount: result.value.amount,
            breakdown: result.value.breakdown
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({ icon: 'success', title: 'Success!', text: 'Reconciliation submitted.', timer: 2000 }).then(() => { location.reload(); });
            }
          },
          error: function(xhr) {
            Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.error || 'Failed' });
            btn.prop('disabled', false).html('<i class="fa fa-hand-holding-usd"></i> Reconcile');
          }
        });
      }
    });
  });

  
  // Auto-calculate handover total
  $('.handover-input').on('input', function() {
    let total = 0;
    $('.handover-input').each(function() {
      total += parseFloat($(this).val()) || 0;
    });
    $('#handover-total').text('TSh ' + total.toLocaleString());
  });
  
  // Trigger calculation on load
  $('.handover-input').first().trigger('input');

  
  // Handover Form Confirmation (Using AJAX for max reliability and debugging)
  $(document).on('click', '#submitHandoverBtn', function() {
    const btn = $(this);
    const form = $('#handoverForm');
    
    // Parse the total dynamically from the UI
    const totalValue = parseFloat($('#handover-total').text().replace(/[^0-9.-]+/g, "")) || 0;
    
    if (totalValue <= 0) {
      Swal.fire({ icon: 'error', title: 'Empty Handover', text: 'You cannot submit a handover with zero collections. Please fill the boxes above.' });
      return false;
    }

    Swal.fire({
      title: 'Final Confirmation',
      text: "Submit TSh " + totalValue.toLocaleString() + " collection to the manager?",
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#009688',
      confirmButtonText: 'Yes, I am ready!'
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing Handover...');
        
        // Gather data manually to be safe
        const formData = form.serialize();

        $.ajax({
          url: form.attr('action'),
          method: 'POST',
          data: formData,
          success: function(response) {
            Swal.fire({
              icon: 'success',
              title: 'Handover Successful',
              text: 'The financial handover has been sent to the manager.',
              timer: 3000
            }).then(() => {
              location.reload();
            });
          },
          error: function(xhr) {
            btn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Submit Detailed Handover');
            let errorMsg = 'An error occurred during submission.';
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                errorMsg = Object.values(errors).flat().join('\n');
            } else if (xhr.responseJSON && xhr.responseJSON.error) {
                errorMsg = xhr.responseJSON.error;
            }
            Swal.fire({ icon: 'error', title: 'Submission Failed', text: errorMsg });
            console.error('Handover Error:', xhr);
          }
        });
      }
    });
  });
});
</script>


@endpush
