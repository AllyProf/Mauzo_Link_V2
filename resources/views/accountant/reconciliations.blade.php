@extends('layouts.dashboard')

@section('title', 'Reconciliations')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-exchange"></i> Reconciliations</h1>
    <p>Financial Collection & Stock Transfer Verification</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Accountant</a></li>
    <li class="breadcrumb-item">Reconciliations</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('accountant.reconciliations') }}" class="form-inline">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="form-group mr-3">
          <label for="start_date" class="mr-2">Start Date:</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
        </div>
        <div class="form-group mr-3">
          <label for="end_date" class="mr-2">End Date:</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Filter
        </button>
      </form>
    </div>
  </div>
</div>

<style>
  .nav-pills .nav-link {
    border-radius: 30px;
    padding: 10px 25px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid #ddd;
    margin-right: 10px;
    background: #fff;
    color: #555 !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  }
  .nav-pills .nav-link:hover { background: #f8f9fa; transform: translateY(-2px); }
  .nav-pills .nav-link.active {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: #fff !important;
    border-color: #0056b3;
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
  }
  .nav-pills .nav-link i { margin-right: 8px; }
  .tab-financial.active { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important; border-color: #1e7e34 !important; }
  .tab-staff.active { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%) !important; border-color: #117a8b !important; }
  .tab-log.active { background: linear-gradient(135deg, #6610f2 0%, #520dc2 100%) !important; border-color: #520dc2 !important; }
</style>

<div class="row mb-4">
  <div class="col-md-12 text-center">
    <ul class="nav nav-pills justify-content-center">
        <li class="nav-item">
          <a class="nav-link tab-financial {{ $tab === 'financial' ? 'active' : '' }}" 
             href="{{ route('accountant.reconciliations', ['tab' => 'financial', 'start_date' => $startDate, 'end_date' => $endDate]) }}">
            <i class="fa fa-money"></i> Financial Summary
          </a>
        </li>
        @if($isManagerView)
        <li class="nav-item">
          <a class="nav-link tab-staff {{ $tab === 'waiters' ? 'active' : '' }}" 
             href="{{ route('accountant.reconciliations', ['tab' => 'waiters', 'start_date' => $startDate, 'end_date' => $endDate]) }}">
            <i class="fa fa-users"></i> Staff Details
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link tab-log {{ $tab === 'payments' ? 'active' : '' }}" 
             href="{{ route('accountant.reconciliations', ['tab' => 'payments', 'start_date' => $startDate, 'end_date' => $endDate]) }}">
            <i class="fa fa-list-alt"></i> Detailed Audit Log
          </a>
        </li>
        @endif
      </ul>
  </div>
</div>

<div class="tile">
  <div class="tile-body pt-3">



        @if($tab === 'financial')
          <!-- High Level Summary for Managers/Owners -->
          @php
              // Prepare financial reconciliations for display and summary
              // This involves filtering based on handover and calculating submitted totals
              // Also, extracting shortage payment details for JS mapping
              $processedFinancialReconciliations = $financialReconciliations
                ->filter(function($r) use ($handoverMap) {
                    // Only show to accountant if handover exists for the specific date and type
                    $dateVal = $r->reconciliation_date;
                    $dateStr = ($dateVal instanceof \Carbon\Carbon) ? $dateVal->format('Y-m-d') : date('Y-m-d', strtotime($dateVal));
                    $key = $dateStr . '_' . $r->reconciliation_type;
                    return isset($handoverMap[$key]);
                })
                ->map(function($r) {
                    // For accurate display, ensure total_submitted is the sum of platforms
                    // This is important if total_submitted was not correctly stored as sum of platforms
                    $calculatedTotal = $r->total_cash + $r->total_mobile + ($r->total_bank ?? 0) + ($r->total_card ?? 0);
                    // Only update if calculated total is positive, otherwise use existing (might be 0 or negative for some reason)
                    if ($calculatedTotal > 0) {
                        $r->total_submitted = $calculatedTotal;
                    }
                    
                    // Track shortage payments for JS mapping
                    $paid = 0;
                    if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $r->notes ?? '', $m)) $paid = (int)$m[1];
                    $r->shortage_paid = $paid; // Add new attribute for total paid
                    
                    $breakdown = "";
                    if(preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $r->notes ?? '', $bm)) $breakdown = $bm[1];
                    $r->shortage_breakdown = $breakdown; // Add new attribute for breakdown string

                    return $r;
                });

              $summaryExpected = $processedFinancialReconciliations->sum('total_expected');
              $summaryCollected = $processedFinancialReconciliations->sum(function($fr) {
                  return $fr->total_submitted + $fr->shortage_paid;
              });
              $summaryShortage = $summaryExpected - $summaryCollected;
          @endphp

          @if($isManagerView)
          <div class="row mb-4">
              <div class="col-md-3">
                  <div class="widget-small primary coloured-icon"><i class="icon fa fa-shopping-cart fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold">Total Expected (Sales)</p>
                          <p><b>TSh {{ number_format($summaryExpected) }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small success coloured-icon"><i class="icon fa fa-money fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="color: #000 !important;">Total Collected (Actual)</p>
                          <p><b style="color: #000 !important;">TSh {{ number_format($summaryCollected) }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small {{ $summaryShortage > 0 ? 'danger' : 'info' }} coloured-icon"><i class="icon fa {{ $summaryShortage > 0 ? 'fa-minus-circle' : 'fa-check-circle' }} fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold">Outstanding Shortage</p>
                          <p><b>TSh {{ number_format($summaryShortage) }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small warning coloured-icon"><i class="icon fa fa-line-chart fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold">Estimated Gross Profit</p>
                          <p><b>TSh {{ number_format($summaryProfit) }}</b></p>
                      </div>
                  </div>
              </div>
          </div>

          <!-- Charts Section -->
          <div class="row mb-4">
              <div class="col-md-7">
                  <div class="tile pb-2">
                      <h4 class="tile-title small text-uppercase font-weight-bold"><i class="fa fa-line-chart"></i> Daily Performance Trend</h4>
                      <div class="embed-responsive embed-responsive-16by9">
                          <canvas class="embed-responsive-item" id="performanceChart"></canvas>
                      </div>
                  </div>
              </div>
              <div class="col-md-5">
                  <div class="tile pb-2">
                      <h4 class="tile-title small text-uppercase font-weight-bold"><i class="fa fa-pie-chart"></i> Revenue Breakdown</h4>
                      <div class="embed-responsive embed-responsive-16by9">
                          <canvas class="embed-responsive-item" id="revenuePieChart"></canvas>
                      </div>
                  </div>
              </div>
          </div>
          @endif

          <!-- Financial Summary Tab -->
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead class="bg-light">
                <tr>
                  <th style="width: 40px;"></th>
                  <th>Date</th>
                  <th>Department</th>
                  <th>Expected (Sales)</th>
                  <th>Submitted (Actual)</th>
                  <th>Cash</th>
                  <th>Mobile</th>
                  <th>Bank</th>
                  <th>Card</th>
                  <th>Diff</th>
                  <th>Status</th>
                  @if($canReconcile)
                  <th>Action</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse($financialReconciliations as $fr)
                  @php
                      $rowTotalPaid = 0;
                      if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $rowTotalPaid = (int)$m[1];
                      
                      $netDiff = $fr->total_expected - ($fr->total_submitted + $rowTotalPaid);
                      $hasActiveShortage = ($netDiff > 0);

                      $breakdown = [];
                      if(preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $fr->notes ?? '', $bm)) {
                          foreach(explode(',', $bm[1]) as $p) {
                              $kv = explode('=', $p);
                              if(count($kv) == 2) $breakdown[$kv[0]] = (int)$kv[1];
                          }
                      }
                  @endphp
                  <tr class="revenue-breakdown-row {{ $hasActiveShortage ? 'table-danger' : ($netDiff > 0 ? 'table-warning-light' : ($netDiff < 0 ? 'table-success-light' : '')) }}" 
                      style="cursor: pointer; {{ $hasActiveShortage ? 'background-color: #fff5f5;' : '' }}"
                      data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                      data-type="{{ $fr->reconciliation_type }}"
                      data-target-row="details-{{ $loop->index }}">
                    <td class="text-center"><i class="fa fa-chevron-right toggle-icon"></i></td>
                    <td>{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('M d, Y') }}</td>
                    <td>
                      @if($fr->reconciliation_type === 'bar')
                        <span class="badge badge-info"><i class="fa fa-glass"></i> COUNTER (BAR)</span>
                      @else
                        <span class="badge badge-warning"><i class="fa fa-cutlery"></i> CHEF (FOOD)</span>
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($fr->total_expected) }}</strong></td>
                    <td><strong>TSh {{ number_format($fr->total_submitted_bag + $rowTotalPaid) }}</strong></td>
                    <td>
                      TSh {{ number_format($fr->submitted_cash + ($breakdown['cash'] ?? 0)) }}
                      @if(($fr->submitted_cash + ($breakdown['cash'] ?? 0)) < $fr->total_cash)
                        <br><small class="text-danger">Short: -{{ number_format($fr->total_cash - ($fr->submitted_cash + ($breakdown['cash'] ?? 0))) }}</small>
                      @endif
                    </td>
                    <td>
                      TSh {{ number_format($fr->submitted_mobile + ($breakdown['mobile_money'] ?? 0)) }}
                      @if(($fr->submitted_mobile + ($breakdown['mobile_money'] ?? 0)) < $fr->total_mobile)
                        <br><small class="text-danger">Short: -{{ number_format($fr->total_mobile - ($fr->submitted_mobile + ($breakdown['mobile_money'] ?? 0))) }}</small>
                      @endif
                    </td>
                    <td>TSh {{ number_format(($fr->submitted_bank ?? 0) + ($breakdown['bank_transfer'] ?? 0)) }}</td>
                    <td>TSh {{ number_format(($fr->submitted_card ?? 0) + ($breakdown['pos_card'] ?? 0)) }}</td>
                    <td>
                      @php 
                        // Show Expected - Submitted so that Positive = Shortage
                        $netDiff = $fr->total_expected - ($fr->total_submitted + $rowTotalPaid); 
                      @endphp
                      @if($netDiff > 0)
                        <span class="text-danger">TSh {{ number_format($netDiff) }} (Short)</span>
                      @elseif($netDiff < 0)
                        <span class="text-success">+TSh {{ number_format(abs($netDiff)) }} (Surplus)</span>
                      @else
                        <span class="text-success small font-weight-bold"><i class="fa fa-check-circle"></i> Balanced</span>
                      @endif
                    </td>
                    <td>
                      @if($fr->status_indicator === 'verified')
                        <span class="badge badge-success">Verified</span>
                      @elseif($fr->status_indicator === 'submitted')
                        <span class="badge badge-info">Submitted</span>
                      @else
                        <span class="badge badge-warning">Pending</span>
                      @endif
                    <td>
                      <div class="d-flex align-items-center">
                        @if($fr->status_indicator !== 'verified')
                          @if(in_array($fr->status_indicator, ['pending', 'submitted']))
                            <button class="btn btn-sm btn-primary perform-dept-reconcile-btn mr-1" 
                                    data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                    data-type="{{ $fr->reconciliation_type }}"
                                    data-expected="{{ $fr->total_expected }}"
                                    data-cash-recorded="{{ $fr->total_cash }}"
                                    data-mobile-recorded="{{ $fr->total_mobile }}"
                                    data-bank-recorded="{{ $fr->total_bank ?? 0 }}"
                                    data-card-recorded="{{ $fr->total_card ?? 0 }}">
                                <i class="fa fa-check-circle"></i> Reconcile
                            </button>
                          @endif
                        @endif

                        @if($netDiff > 0)
                          <button class="btn btn-sm btn-outline-danger pay-shortage-btn mr-1" 
                                  data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                  data-type="{{ $fr->reconciliation_type }}"
                                  data-shortage="{{ $netDiff }}"
                                  title="Pay Shortage (Detected)">
                              <i class="fa fa-money"></i> Pay
                          </button>
                        @endif

                        <button class="btn btn-sm btn-info view-dept-details-btn ml-1" 
                                data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                data-type="{{ $fr->reconciliation_type }}">
                            <i class="fa fa-eye"></i> Details
                        </button>
                      </div>
                    </td>
                  </tr>
                  <!-- Folded Payment Breakdown -->
                  <tr id="details-{{ $loop->index }}" class="details-row d-none" style="background-color: #fbfbfc;">
                    <td colspan="12">
                      <div class="px-4 py-3 border-bottom shadow-sm">
                         <div class="row">
                            <!-- Column 1: Staff Submission (From Handover) -->
                            <div class="col-md-8">
                                <h6 class="text-success font-weight-bold"><i class="fa fa-hand-grab-o"></i> (1) Staff Handover (Submitted)</h6>
                                <table class="table table-sm table-bordered mt-2" style="font-size: 0.85rem;">
                                    <thead class="bg-light"><tr><th>Channel/Provider</th><th>Submitted</th><th>Recorded (Sales)</th><th>Audit</th></tr></thead>
                                    <tbody>
                                        @php 
                                          $totalSub = 0; 
                                          $totalRec = 0;
                                          // Map platforms to check Recorded values
                                          $channelMap = [
                                              'cash' => 'total_cash',
                                              'mpesa' => 'total_mobile',
                                              'airtel_money' => 'total_mobile',
                                              'tigo_pesa' => 'total_mobile',
                                              'halopesa' => 'total_mobile',
                                              'mixx' => 'total_mobile',
                                          ];
                                        @endphp
                                        @php
                                            // Combine all unique channels from both breakdowns
                                            $allChannels = array_unique(array_merge(
                                                array_keys($fr->submitted_platform_breakdown ?? []),
                                                array_keys($fr->recorded_platform_breakdown ?? [])
                                            ));
                                            $totalSubVisible = 0;
                                        @endphp
                                        @forelse($allChannels as $channelKey)
                                            @php 
                                              // Original submission + Accountant's adjustment
                                              $origAmt = $fr->submitted_platform_breakdown[$channelKey] ?? 0;
                                              $adjAmt = $breakdown[$channelKey] ?? 0;
                                              $amt = $origAmt + $adjAmt;

                                              $recVal = $fr->recorded_platform_breakdown[$channelKey] ?? 0;
                                              $totalSubVisible += (float)$origAmt; // Still track original visible subtotal for overall balance logic
                                            @endphp
                                            @if((float)$amt > 0 || $recVal > 0)
                                                <tr>
                                                  <td>{{ strtoupper(str_replace('_', ' ', $channelKey)) }}</td>
                                                  <td>
                                                     TSh {{ number_format($amt) }}
                                                     @if($adjAmt > 0)
                                                        <br><small class="text-info"><i class="fa fa-plus-circle"></i> Incl. Audit Pay: +{{ number_format($adjAmt) }}</small>
                                                     @endif
                                                  </td>
                                                  <td class="text-muted">TSh {{ number_format($recVal) }}</td>
                                                  <td>
                                                    @if($amt < $recVal)
                                                      <span class="text-danger font-weight-bold">Short: -{{ number_format($recVal - $amt) }}</span>
                                                    @elseif($amt > $recVal)
                                                      <span class="text-success font-weight-bold">Surplus: +{{ number_format($amt - $recVal) }}</span>
                                                    @else
                                                      <span class="text-success small">Balanced</span>
                                                    @endif
                                                  </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr><td colspan="4" class="text-muted text-center italic">No platform details found</td></tr>
                                        @endforelse

                                        @if($rowTotalPaid > 0)
                                          <tr class="table-info">
                                            <td><i class="fa fa-plus-circle"></i> SHORTAGE PAYMENTS (ACCOUNTANT)</td>
                                            <td colspan="2">TSh {{ number_format($rowTotalPaid) }}</td>
                                            <td><span class="text-info small">Audit Adjusted</span></td>
                                          </tr>
                                        @endif

                                        <tr class="table-success">
                                            <td><strong>TOTAL DECLARED (With Adj.)</strong></td>
                                            <td><strong>TSh {{ number_format(($totalSubVisible > 0 ? $totalSubVisible : $fr->total_submitted_bag) + $rowTotalPaid) }}</strong></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Column 3: Audit Summary & Action -->
                            <div class="col-md-3 text-right">
                                <h6 class="text-info font-weight-bold"><i class="fa fa-info-circle"></i> Audit Status</h6>
                                <div class="card {{ $hasActiveShortage ? 'border-danger' : 'border-success' }} shadow-none">
                                    <div class="card-body p-2 text-center">
                                        @if($netDiff != 0)
                                            <div class="{{ $netDiff > 0 ? 'text-danger' : 'text-success' }} h5 mb-1">
                                                {{ $netDiff > 0 ? 'SHORTAGE' : 'SURPLUS' }}
                                            </div>
                                            <div class="font-weight-bold {{ $netDiff > 0 ? 'text-danger' : 'text-success' }}">
                                                {{ $netDiff > 0 ? '' : '+' }}TSh {{ number_format(abs($netDiff)) }}
                                            </div>
                                            <button class="btn btn-sm {{ $netDiff > 0 ? 'btn-success' : 'btn-primary' }} pay-shortage-btn mt-2 w-100" 
                                                    data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                                    data-type="{{ $fr->reconciliation_type }}"
                                                    data-shortage="{{ $netDiff }}">
                                                <i class="fa fa-money"></i> {{ $netDiff > 0 ? 'Record Payment' : 'Record Adjustment' }}
                                            </button>
                                        @else
                                            <div class="text-success h5 mb-1"><i class="fa fa-check-circle"></i> BALANCED</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                         </div>
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

        @elseif($tab === 'waiters')
          <!-- Waiter Details Tab -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Waiter</th>
                  <th>Type</th>
                  <th>Expected</th>
                  <th>Submitted</th>
                  <th>Difference</th>
                  <th>Notes</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($waiterReconciliations as $wr)
                  <tr>
                    <td>{{ \Carbon\Carbon::parse($wr->reconciliation_date)->format('M d') }}</td>
                    <td><strong>{{ $wr->waiter->full_name }}</strong></td>
                    <td>{{ ucfirst($wr->reconciliation_type) }}</td>
                    <td>TSh {{ number_format($wr->expected_amount) }}</td>
                    <td>TSh {{ number_format($wr->submitted_amount) }}</td>
                    <td>
                      @if($wr->difference < 0)
                        <span class="text-danger">{{ number_format($wr->difference) }}</span>
                      @else
                        <span class="text-success">{{ number_format($wr->difference) }}</span>
                      @endif
                    </td>
                    <td>
                      <small>{{ $wr->notes ?? '---' }}</small>
                    </td>
                    <td>
                      <span class="badge badge-{{ $wr->status === 'verified' ? 'success' : 'warning' }}">
                        {{ ucfirst($wr->status) }}
                      </span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <a href="{{ route('accountant.reconciliation-details', $wr->id) }}" class="btn btn-sm btn-info">
                          <i class="fa fa-eye"></i>
                        </a>
                        @if($wr->status !== 'verified')
                          <button class="btn btn-sm btn-success verify-financial-btn" 
                                  data-id="{{ $wr->id }}" 
                                  data-waiter="{{ $wr->waiter->full_name }}"
                                  data-shortage="{{ $wr->difference < 0 ? abs($wr->difference) : 0 }}">
                            <i class="fa fa-check"></i> Verify
                          </button>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-4">No waiter reconciliations found</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        @elseif($tab === 'payments')
          <!-- Detailed Payment Log Tab -->
          <div class="tile-title-w-btn">
            <h4 class="title"><i class="fa fa-list text-primary"></i> All Recorded Payments</h4>
            <div class="text-muted small">Audit reference numbers and mobile money logs</div>
          </div>

          <!-- Payment Search Filter Bar -->
          <div class="row mb-4 bg-light p-3 mx-0 border rounded">
            <div class="col-md-4 form-group">
              <label class="small font-weight-bold">Search (Order #, Ref #, Phone)</label>
              <input type="text" id="payment_js_search" class="form-control form-control-sm" placeholder="Search as you type...">
            </div>
            <div class="col-md-3 form-group">
              <label class="small font-weight-bold">Payment Method</label>
              <select id="payment_js_method" class="form-control form-control-sm">
                <option value="">All Methods</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank">Bank</option>
              </select>
            </div>
            <div class="col-md-3 form-group">
              <label class="small font-weight-bold">By Staff</label>
              <select id="payment_js_staff" class="form-control form-control-sm">
                <option value="">All Staff</option>
                @foreach($staffMembers as $staff)
                  <option value="{{ $staff->full_name }}">
                    {{ $staff->full_name }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-2 form-group pt-4">
              <small class="text-muted d-block mt-2"><i class="fa fa-bolt"></i> Real-time Filter</small>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-sm" id="payment_log_table">
              <thead class="bg-light">
                <tr>
                  <th>Time</th>
                  <th>Order #</th>
                  <th class="col-staff">Waiter/Staff</th>
                  <th class="col-method">Method</th>
                  <th>Amount</th>
                  <th class="col-ref">Ref / Number</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @forelse($payments as $p)
                  <tr class="payment-row">
                    <td>{{ $p->created_at->format('H:i') }} <small class="text-muted">({{ $p->created_at->format('M d') }})</small></td>
                    <td class="search-cell">
                      <span class="badge badge-light border">#{{ $p->order->order_number }}</span>
                      <br><small class="text-muted">{{ $p->order->table->name ?? 'Direct' }}</small>
                    </td>
                    <td class="staff-cell">{{ $p->order->waiter->full_name ?? 'Counter/Staff' }}</td>
                    <td class="method-cell">
                      @if($p->payment_method === 'cash')
                        <span class="badge badge-success px-2">CASH</span>
                      @elseif($p->payment_method === 'mobile_money')
                        <span class="badge badge-info px-2">MOBILE</span>
                      @else
                        <span class="badge badge-secondary px-2">{{ strtoupper($p->payment_method) }}</span>
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($p->amount) }}</strong></td>
                    <td class="search-cell">
                      @if($p->payment_method === 'mobile_money')
                        <div class="font-weight-bold text-dark">{{ $p->transaction_reference ?? 'N/A' }}</div>
                        <small class="text-muted">{{ $p->mobile_money_number }}</small>
                      @elseif($p->transaction_reference)
                        <span class="font-weight-bold">{{ $p->transaction_reference }}</span>
                      @else
                        <span class="text-muted">---</span>
                      @endif
                    </td>
                    <td>
                      <span class="badge badge-{{ $p->payment_status === 'verified' ? 'success' : 'warning' }}">
                          {{ ucfirst($p->payment_status) }}
                      </span>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-5">No payment logs found for this period.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
            <div class="mt-3">
              {{ $payments->appends(['tab' => 'payments', 'start_date' => $startDate, 'end_date' => $endDate])->links() }}
            </div>
          </div>

        @elseif($tab === 'stocks')
          <!-- Stock Transfers Tab -->
          <div class="alert alert-info py-2 mb-3">
             <i class="fa fa-info-circle"></i> verifying stock transfers ensures the revenue from moved stock matches sales records.
          </div>
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Profit</th>
                  <th>Revenue</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($transfers as $transfer)
                  <tr>
                    <td>{{ $transfer->transfer_number }}</td>
                    <td>{{ $transfer->productVariant->product->name ?? 'N/A' }}</td>
                    <td>{{ number_format($transfer->total_units) }} btl</td>
                    <td><strong class="text-primary">TSh {{ number_format($transfer->expected_profit ?? 0) }}</strong></td>
                    <td><strong class="text-info">TSh {{ number_format($transfer->expected_revenue ?? 0) }}</strong></td>
                    <td>
                      <span class="badge badge-{{ $transfer->verified_at ? 'success' : 'warning' }}">
                        {{ $transfer->verified_at ? 'Verified' : 'Pending' }}
                      </span>
                    </td>
                    <td>
                      <button class="btn btn-sm btn-info view-transfer-details-btn" data-transfer-id="{{ $transfer->id }}">
                        <i class="fa fa-eye"></i>
                      </button>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="7" class="text-center py-4">No stock transfers found</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
            {{ $transfers->appends(['tab' => 'stocks', 'start_date' => $startDate, 'end_date' => $endDate])->links() }}
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Modal for Transfer Details (kept from original) -->
<div class="modal fade" id="transferDetailsModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Stock Transfer Details</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="transferDetailsContent">
        <div class="text-center py-5"><i class="fa fa-spinner fa-spin fa-3x text-primary"></i></div>
      </div>
    </div>
  </div>
</div>
<!-- Modal for Department Reconciliation (Accountant Finalize) -->
<div class="modal fade" id="deptReconcileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa fa-calculator"></i> Finalize <span id="modal_dept_name"></span> Collection</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="deptReconcileForm">
        <div class="modal-body">
          <input type="hidden" name="date" id="dr_date">
          <input type="hidden" name="type" id="dr_type">
          
          <div class="alert alert-info py-2">
            <strong>Expected:</strong> TSh <span id="dr_expected_label">0</span>
          </div>

          <div class="row">
            <div class="col-md-6 form-group mb-3">
                <label class="font-weight-bold"><i class="fa fa-money text-success"></i> Actual Cash</label>
                <input type="number" name="cash_received" id="dr_cash" class="form-control" placeholder="0" required>
                <small class="text-muted">Recorded: TSh <span id="dr_cash_recorded_label">0</span></small>
            </div>
            <div class="col-md-6 form-group mb-3">
                <label class="font-weight-bold"><i class="fa fa-mobile text-primary"></i> Actual Mobile Money</label>
                <input type="number" name="mobile_received" id="dr_mobile" class="form-control" placeholder="0" required>
                <small class="text-muted">Recorded: TSh <span id="dr_mobile_recorded_label">0</span></small>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 form-group mb-3">
                <label class="font-weight-bold"><i class="fa fa-bank text-info"></i> Actual Bank Transfer</label>
                <input type="number" name="bank_received" id="dr_bank" class="form-control" placeholder="0" required>
                <small class="text-muted">Recorded: TSh <span id="dr_bank_recorded_label">0</span></small>
            </div>
            <div class="col-md-6 form-group mb-3">
                <label class="font-weight-bold"><i class="fa fa-credit-card text-secondary"></i> Actual Card/POS</label>
                <input type="number" name="card_received" id="dr_card" class="form-control" placeholder="0" required>
                <small class="text-muted">Recorded: TSh <span id="dr_card_recorded_label">0</span></small>
            </div>
          </div>

          <div class="form-group">
            <label>Notes / Remarks</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Any shortages or discrepancies?"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="submitDeptReconcile">
            <i class="fa fa-save"></i> Save & Reconcile
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal for Financial Verification -->
<div class="modal fade" id="verifyFinancialModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Verify Financial Collection</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="verifyFinancialForm">
        @csrf
        <input type="hidden" name="id" id="verify_id">
        <div class="modal-body">
          <p id="verify_description"></p>
          <div class="alert alert-warning shortage-alert d-none">
            <i class="fa fa-exclamation-triangle"></i> This reconciliation has a shortage of <strong id="verify_shortage_amount"></strong>.
          </div>
          <div class="form-group">
            <label>Verification Status</label>
            <select name="status" class="form-control" required>
              <option value="verified">Correct / Shortage Handled</option>
              <option value="flagged">Flag for Investigation</option>
            </select>
          </div>
          <div class="form-group">
            <label>Notes (e.g., Shortage Reason, Resolution)</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Enter details about the collection..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Confirm Verification</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal for Viewing Department Details (Orders & Payments) -->
<div class="modal fade" id="viewDeptOrdersModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="fa fa-list"></i> Department Details - <span id="orders_modal_title"></span></h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-0">
        <ul class="nav nav-tabs nav-justified" id="deptDetailsTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments_breakdown_panel" role="tab">Audit Breakdown</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="shortage-tab" data-toggle="tab" href="#shortage_history_panel" role="tab">Shortage Tracking</a>
          </li>
        </ul>
        <div class="tab-content">
          <!-- Orders Panel -->
          <div class="tab-pane fade show active" id="orders_list_panel" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0">
                <thead class="bg-light">
                  <tr>
                    <th>Time</th>
                    <th>Order #</th>
                    <th>Waiter</th>
                    <th>Table</th>
                    <th>Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="dept_orders_body">
                  <!-- Loaded via AJAX -->
                </tbody>
              </table>
            </div>
          </div>
          <!-- Payments Panel -->
          <div class="tab-pane fade" id="payments_breakdown_panel" role="tabpanel">
            <div id="dept_payments_body" class="p-3">
              <!-- Loaded via AJAX -->
            </div>
          </div>
          <!-- Shortage Panel -->
          <div class="tab-pane fade" id="shortage_history_panel" role="tabpanel">
            <div id="dept_shortage_body" class="p-3">
              <!-- Information about shortage payments -->
            </div>
          </div>
        </div>
        <div id="dept_orders_loader" class="text-center py-5 d-none">
          <i class="fa fa-spinner fa-spin fa-2x text-info"></i>
          <p class="mt-2">Loading details...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Paying Shortage -->
<div class="modal fade" id="payShortageModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title shortage-modal-title"><i class="fa fa-money"></i> Record Shortage Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="shortage_payment_form">
        @csrf
        <input type="hidden" name="date" id="shortage_date">
        <input type="hidden" name="type" id="shortage_type">
        <div class="modal-body">
            <div class="alert alert-info shadow-sm">
                <strong id="shortage_amount_label">Pending Shortage:</strong> TSh <span id="shortage_amount_display" class="font-weight-bold">0</span>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                    <label class="shortage-input-label font-weight-bold">Amount to Pay (TSh)</label>
                    <input type="number" name="amount" id="shortage_pay_amount" class="form-control" required>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                    <label class="font-weight-bold">Payment Channel</label>
                    <select name="channel" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="pos_card">POS / Card</option>
                    </select>
                </div>
              </div>
            </div>
            <div class="form-group">
                <label class="font-weight-bold">Notes / Comments <small class="text-muted">(Optional)</small></label>
                <textarea name="reference" class="form-control" rows="2" placeholder="e.g. Received from John..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" id="shortage_submit_btn">Save Settlement</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
  @if($isManagerView && $tab === 'financial' && !empty($chartData['dates']))
  // 1. Performance Trend Chart
  try {
      const perfCtx = document.getElementById('performanceChart').getContext('2d');
      new Chart(perfCtx, {
          type: 'line',
          data: {
              labels: {!! json_encode($chartData['dates']) !!},
              datasets: [{
                  label: 'Expected',
                  data: {!! json_encode($chartData['expected']) !!},
                  borderColor: '#940000',
                  backgroundColor: 'rgba(148, 0, 0, 0.05)',
                  fill: true,
                  tension: 0.4
              }, {
                  label: 'Collected',
                  data: {!! json_encode($chartData['collected']) !!},
                  borderColor: '#28a745',
                  backgroundColor: 'rgba(40, 167, 69, 0.05)',
                  fill: true,
                  tension: 0.4
              }]
          },
          options: {
              responsive: true,
              plugins: { legend: { position: 'top' } },
              scales: { y: { beginAtZero: true } }
          }
      });

      // 2. Revenue Breakdown Pie
      const pieCtx = document.getElementById('revenuePieChart').getContext('2d');
      new Chart(pieCtx, {
          type: 'doughnut',
          data: {
              labels: ['Cash', 'Mobile', 'Bank', 'Card'],
              datasets: [{
                  data: [
                      {{ $chartData['methods']['Cash'] }},
                      {{ $chartData['methods']['Mobile'] }},
                      {{ $chartData['methods']['Bank'] }},
                      {{ $chartData['methods']['Card'] }}
                  ],
                  backgroundColor: ['#28a745', '#17a2b8', '#ffc107', '#6c757d'],
                  hoverOffset: 4
              }]
          },
          options: {
              responsive: true,
              plugins: { 
                  legend: { position: 'bottom' },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              let val = context.raw || 0;
                              return context.label + ': TSh ' + val.toLocaleString();
                          }
                      }
                  }
              }
          }
      });
  } catch(e) { console.error("Chart error:", e); }
  @endif
  // Financial Verification Logic
  $('.verify-financial-btn').click(function() {
      const id = $(this).data('id');
      const waiter = $(this).data('waiter');
      const shortage = $(this).data('shortage');
      
      $('#verify_id').val(id);
      $('#verify_description').html(`Verifying collection for <strong>${waiter}</strong>`);
      
      if (shortage > 0) {
          $('.shortage-alert').removeClass('d-none');
          $('#verify_shortage_amount').text(`TSh ${parseFloat(shortage).toLocaleString()}`);
      } else {
          $('.shortage-alert').addClass('d-none');
      }
      
      $('#verifyFinancialModal').modal('show');
  });

  // Department Reconciliation Modal Pop-up
  $('.perform-dept-reconcile-btn').click(function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const expected = $(this).data('expected');
      const cash_recorded = $(this).data('cash-recorded');
      const mobile_recorded = $(this).data('mobile-recorded');
      const bank_recorded = $(this).data('bank-recorded');
      const card_recorded = $(this).data('card-recorded');

      $('#dr_date').val(date);
      $('#dr_type').val(type);
      $('#dr_expected_label').text(Number(expected).toLocaleString());
      $('#dr_cash').val(cash_recorded);
      $('#dr_mobile').val(mobile_recorded);
      $('#dr_bank').val(bank_recorded);
      $('#dr_card').val(card_recorded);
      $('#dr_cash_recorded_label').text(Number(cash_recorded).toLocaleString());
      $('#dr_mobile_recorded_label').text(Number(mobile_recorded).toLocaleString());
      $('#dr_bank_recorded_label').text(Number(bank_recorded).toLocaleString());
      $('#dr_card_recorded_label').text(Number(card_recorded).toLocaleString());
      $('#modal_dept_name').text(type === 'bar' ? 'COUNTER (BAR)' : 'CHEF (FOOD)');

      $('#deptReconcileModal').modal('show');
  });

  $('#deptReconcileForm').on('submit', function(e) {
      e.preventDefault();
      const $btn = $('#submitDeptReconcile');
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

      $.ajax({
          url: "{{ route('accountant.reconciliations.finalize') }}",
          method: "POST",
          data: $(this).serialize() + "&_token={{ csrf_token() }}",
          success: function(response) {
              if (response.success) {
                  Swal.fire('Success!', response.message, 'success').then(() => location.reload());
              } else {
                  Swal.fire('Error!', response.error || 'Failed to save.', 'error');
                  $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save & Reconcile');
              }
          },
          error: function() {
              Swal.fire('Error!', 'Server connection error.', 'error');
              $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Save & Reconcile');
          }
      });
  });

  $('#verifyFinancialForm').submit(function(e) {
      e.preventDefault();
      const id = $('#verify_id').val();
      const formData = $(this).serialize();
      const btn = $(this).find('button[type="submit"]');
      
      btn.prop('disabled', true).text('Processing...');
      
      $.ajax({
          url: `{{ route('accountant.financial.verify', ':id') }}`.replace(':id', id),
          method: 'POST',
          data: formData,
          success: function(response) {
              if (response.success) {
                  Swal.fire('Success', response.message, 'success').then(() => location.reload());
              }
          },
          error: function(xhr) {
              Swal.fire('Error', 'Failed to verify reconciliation', 'error');
              btn.prop('disabled', false).text('Confirm Verification');
          }
      });
  });

  // Row click to toggle folded payment breakdown
  $('.revenue-breakdown-row').css('cursor', 'pointer').click(function(e) {
      // Don't trigger if a button or link was clicked inside the row
      if ($(e.target).closest('button, a, input').length) return;
      
      const targetId = $(this).data('target-row');
      const detailsRow = $(`#${targetId}`);
      const icon = $(this).find('.toggle-icon');
      const date = $(this).data('date');
      const type = $(this).data('type');

      if (detailsRow.hasClass('d-none')) {
          $('.details-row').addClass('d-none');
          $('.toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');

          detailsRow.removeClass('d-none');
          icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');

          const paymentsBody = detailsRow.find('.payments-list-body');
          if (paymentsBody.data('loaded') !== 'true') {
              $.get("{{ route('accountant.reconciliations.orders') }}", { date, type }, function(response) {
                  if (response.success) {
                      let pHtml = '';
                      let totalPay = 0;
                      Object.keys(response.payment_breakdown).forEach(method => {
                          const data = response.payment_breakdown[method];
                          totalPay += data.total;
                          
                          // Method Total Header
                          pHtml += `<tr class="table-secondary"><td><strong>${method.toUpperCase()}</strong></td><td><strong>METHOD TOTAL</strong></td><td><strong>TSh ${new Intl.NumberFormat().format(data.total)}</strong></td></tr>`;
                          
                          // Group transactions by Platform (reference)
                          const platforms = {};
                          data.transactions.forEach(t => {
                              const plat = t.reference || 'Default';
                              if (!platforms[plat]) platforms[plat] = { total: 0, txs: [] };
                              platforms[plat].total += t.amount;
                              platforms[plat].txs.push(t);
                          });

                          Object.keys(platforms).forEach(plat => {
                              // Platform Subtotal
                              pHtml += `<tr class="bg-light"><td></td><td><i class="fa fa-caret-right"></i> ${plat}</td><td>TSh ${new Intl.NumberFormat().format(platforms[plat].total)}</td></tr>`;
                              
                              // Individual Transaction Details
                              platforms[plat].txs.forEach(t => {
                                  pHtml += `<tr><td class="pl-4 text-muted small">${t.time} - #${t.order}</td><td class="small text-muted">${t.waiter}</td><td class="small text-muted">TSh ${new Intl.NumberFormat().format(t.amount)}</td></tr>`;
                              });
                          });
                      });
                      pHtml += `<tr class="table-info"><td><strong>OVERALL TOTAL</strong></td><td></td><td><strong>TSh ${new Intl.NumberFormat().format(totalPay)}</strong></td></tr>`;
                      paymentsBody.html(pHtml).data('loaded', 'true');
                  } else {
                      paymentsBody.html(`<tr><td colspan="3" class="text-danger">Error: ${response.error}</td></tr>`);
                  }
              });
          }
      } else {
          detailsRow.addClass('d-none');
          icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
      }
  });

  // View Details Modal (specifically for orders)
  $(document).on('click', '.view-dept-details-btn', function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const deptName = type === 'bar' ? 'COUNTER (BAR)' : 'CHEF (FOOD)';
      
      $('#orders_modal_title').text(`${deptName} - ${date}`);
      $('#dept_orders_body').html('<tr><td colspan="6" class="text-center p-3 text-muted"><i class="fa fa-spinner fa-spin"></i> Loading orders...</td></tr>');
      $('#dept_payments_body').empty();
      $('#dept_shortage_body').empty();
      $('#dept_orders_loader').removeClass('d-none');
      $('#orders_modal_footer').html('<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>');

      $('#viewDeptOrdersModal').modal('show');

      $.get("{{ route('accountant.reconciliations.orders') }}", { date, type }, function(response) {
          $('#dept_orders_loader').addClass('d-none');
          if (response.success) {
              // 1. Orders
              let ordersHtml = '';
              if (response.orders.length > 0) {
                  response.orders.forEach(order => {
                      ordersHtml += `<tr><td>${order.created_at}</td><td>#${order.order_number}</td><td>${order.waiter_name}</td><td>${order.table_name}</td><td>TSh ${new Intl.NumberFormat().format(order.total_amount)}</td><td><span class="badge badge-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status}</span></td></tr>`;
                  });
              } else {
                  ordersHtml = '<tr><td colspan="6" class="text-center py-3 text-muted">No orders found</td></tr>';
              }
              $('#dept_orders_body').html(ordersHtml);

              // 2. Payments (Full)
              let pHtml = '';
              let totalPay = 0;
              Object.keys(response.payment_breakdown).forEach(method => {
                  const data = response.payment_breakdown[method];
                  totalPay += data.total;
                  pHtml += `<tr class="table-secondary"><td><strong>${method.toUpperCase()}</strong></td><td><strong>METHOD TOTAL</strong></td><td><strong>TSh ${new Intl.NumberFormat().format(data.total)}</strong></td></tr>`;
                  
                  // Group transactions by Platform (reference)
                  const platforms = {};
                  data.transactions.forEach(t => {
                      const plat = t.reference || 'Default';
                      if (!platforms[plat]) platforms[plat] = { total: 0, txs: [] };
                      platforms[plat].total += t.amount;
                      platforms[plat].txs.push(t);
                  });

                  Object.keys(platforms).forEach(plat => {
                      pHtml += `<tr class="bg-light"><td></td><td><i class="fa fa-caret-right"></i> ${plat}</td><td>TSh ${new Intl.NumberFormat().format(platforms[plat].total)}</td></tr>`;
                      platforms[plat].txs.forEach(t => {
                          pHtml += `<tr><td class="pl-4 small text-muted">${t.time} - #${t.order}</td><td class="small text-muted">${t.waiter}</td><td class="small text-muted">TSh ${new Intl.NumberFormat().format(t.amount)}</td></tr>`;
                      });
                  });
              });
              pHtml += `<tr class="table-info"><td><strong>OVERALL TOTAL</strong></td><td></td><td><strong>TSh ${new Intl.NumberFormat().format(totalPay)}</strong></td></tr>`;
              $('#dept_payments_body').html(pHtml);

              // 3. Footer Button logic
              // We'll trust the caller to re-trigger if needed, or add it here
          }
      });
  });

  // View Department Details
  $('.view-dept-orders-btn').click(function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const deptName = type === 'bar' ? 'COUNTER (BAR)' : 'CHEF (FOOD)';
      
      $('#orders_modal_title').text(`${deptName} - ${date}`);
      $('#dept_orders_body').empty();
      $('#dept_payments_body').empty();
      $('#dept_shortage_body').empty(); // Clear shortage body as well
      $('#dept_orders_loader').removeClass('d-none');
      $('#viewDeptOrdersModal').modal('show');

      $.get("{{ route('accountant.reconciliations.orders') }}", { date, type }, function(response) {
          $('#dept_orders_loader').addClass('d-none');
          if (response.success) {
              // 1. Populate Orders List
              let ordersHtml = '';
              if (response.orders.length > 0) {
                  response.orders.forEach(order => {
                      ordersHtml += `
                          <tr>
                              <td>${order.created_at}</td>
                              <td><strong>#${order.order_number}</strong></td>
                              <td>${order.waiter_name}</td>
                              <td>${order.table_name}</td>
                              <td>TSh ${new Intl.NumberFormat().format(order.total_amount)}</td>
                              <td><span class="badge badge-${order.payment_status === 'paid' ? 'success' : 'warning'}">${order.payment_status}</span></td>
                          </tr>
                      `;
                  });
              } else {
                  ordersHtml = '<tr><td colspan="6" class="text-center py-3 text-muted">No orders found</td></tr>';
              }
              $('#dept_orders_body').html(ordersHtml);

              // 2. Populate Payments Breakdown
              let paymentsHtml = '';
              if (Object.keys(response.payment_breakdown).length > 0) {
                  for (let method in response.payment_breakdown) {
                      let data = response.payment_breakdown[method];
                      let methodName = method.replace('_', ' ').toUpperCase();
                      paymentsHtml += `
                          <div class="card mb-3 border-left-primary shadow-sm">
                              <div class="card-header py-2 d-flex justify-content-between align-items-center bg-gray-100">
                                  <h6 class="m-0 font-weight-bold text-primary text-uppercase small">${methodName}</h6>
                                  <span class="badge badge-primary">TSh ${new Intl.NumberFormat().format(data.total)}</span>
                              </div>
                              <div class="card-body p-0">
                                  <table class="table table-sm table-hover mb-0" style="font-size: 0.8rem;">
                                      <thead>
                                          <tr class="text-muted small">
                                              <th>Time</th>
                                              <th>Order</th>
                                              <th>Waiter</th>
                                              <th>Amount</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                      `;
                      data.transactions.forEach(tx => {
                          paymentsHtml += `
                              <tr>
                                  <td><small>${tx.time}</small></td>
                                  <td><strong>#${tx.order}</strong></td>
                                  <td>${tx.waiter}</td>
                                  <td>TSh ${new Intl.NumberFormat().format(tx.amount)}</td>
                              </tr>
                          `;
                      });
                      paymentsHtml += `</tbody></table></div></div>`;
                  }
              } else {
                  paymentsHtml = '<div class="text-center py-4 text-muted"><p>No payment recorded from digital/cash platforms yet.</p></div>';
              }
              $('#dept_payments_body').html(paymentsHtml);

              // 3. Populate Shortage Tracking
              // Get data from the row buttons where it was pre-calculated
              const btnSource = $(`button.view-dept-orders-btn[data-date="${date}"][data-type="${type}"]`).first();
              const shortageTotal = btnSource.closest('tr').find('.pay-shortage-btn').data('shortage') || 0;
              
              // We'll use the notes field for history (it's in the row)
              let notesText = btnSource.closest('tr').find('td').eq(6).text(); // column index 6 is the status column or before?
              // Actually let's just get it from the response if we can.
              
              let shortageHtml = `
                  <div class="alert alert-info border-0 shadow-sm">
                      <h6 class="font-weight-bold"><i class="fa fa-info-circle"></i> Shortage Status</h6>
                      <p class="mb-0 small">Audit detects if the <strong>Submitted Amount</strong> matches the <strong>System Sales</strong>.</p>
                  </div>
              `;
              
              // Use class selectors to find values in the same row
              const row = btnSource.closest('tr');
              const shortageAmountText = row.find('.badge-danger').text() || 'None';
              
              shortageHtml += `
                  <div class="card border-0 bg-light mb-3">
                      <div class="card-body p-3 text-center">
                          <p class="text-muted mb-1 small">Current Outstanding Shortage</p>
                          <h4 class="font-weight-bold ${shortageTotal > 0 ? 'text-danger' : 'text-success'}">${shortageAmountText.replace('Shortage:', '') || 'TSh 0'}</h4>
                      </div>
                  </div>
              `;

              $('#dept_shortage_body').html(shortageHtml);
          } else {
              Swal.fire('Error', response.error || 'Failed to load details', 'error');
              $('#viewDeptOrdersModal').modal('hide');
          }
      }).fail(function() {
          $('#dept_orders_loader').addClass('d-none');
          Swal.fire('Error', 'Server error while loading details', 'error');
          $('#viewDeptOrdersModal').modal('hide');
      });
  });

  // Remove duplicate handler if any (merged into one below)
  $(document).off('click', '.pay-shortage-btn').on('click', '.pay-shortage-btn', function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const shortage = parseFloat($(this).data('shortage'));
      const isSurplus = shortage < 0;
      
      const title = isSurplus ? 'Record Surplus Adjustment' : 'Record Shortage Payment';
      const amtLabel = isSurplus ? 'Current Surplus:' : 'Pending Shortage:';
      const inputLabel = isSurplus ? 'Adjustment/Return Amount (TSh):' : 'Amount to Pay (TSh):';

      $('.shortage-modal-title').html(`<i class="fa fa-money"></i> ${title}`);
      $('#shortage_amount_label').text(amtLabel);
      $('.shortage-input-label').text(inputLabel);
      
      $('#shortage_date').val(date);
      $('#shortage_type').val(type);
      $('#shortage_amount_display').text(new Intl.NumberFormat().format(Math.abs(shortage)));
      $('#shortage_pay_amount').val(Math.abs(shortage));
      
      $('#payShortageModal').modal('show');
  });

  $('#shortage_payment_form').submit(function(e) {
      e.preventDefault();
      const btn = $(this).find('button[type="submit"]');
      btn.prop('disabled', true).text('Saving...');

      $.post("{{ route('accountant.reconciliations.pay-shortage') }}", $(this).serialize(), function(response) {
          if (response.success) {
              Swal.fire('Success', 'Shortage payment recorded!', 'success').then(() => {
                  location.reload();
              });
          } else {
              Swal.fire('Error', response.error || 'Failed to save payment', 'error');
              btn.prop('disabled', false).text('Save Payment');
          }
      }).fail(function() {
          Swal.fire('Error', 'Server error while saving payment', 'error');
          btn.prop('disabled', false).text('Save Payment');
      });
  });

  // Detailed Payment Log Real-time filtering
  function filterPayments() {
      const search = $('#payment_js_search').val().toLowerCase();
      const method = $('#payment_js_method').val().toLowerCase();
      const staff = $('#payment_js_staff').val().toLowerCase();

      $('.payment-row').each(function() {
          const rowText = $(this).text().toLowerCase();
          const rowMethod = $(this).find('.method-cell').text().toLowerCase();
          const rowStaff = $(this).find('.staff-cell').text().toLowerCase();

          const matchesSearch = rowText.includes(search);
          const matchesMethod = method === '' || rowMethod.includes(method);
          const matchesStaff = staff === '' || rowStaff.includes(staff);

          if (matchesSearch && matchesMethod && matchesStaff) {
              $(this).show();
          } else {
              $(this).hide();
          }
      });
  }

  $('#payment_js_search').on('keyup', filterPayments);
  $('#payment_js_method, #payment_js_staff').on('change', filterPayments);

  // Re-open Shift (Undo Reconciliation)
  $('.btn-reopen-shift').click(function() {
      const date = $(this).data('date');
      const type = $(this).data('type');

      Swal.fire({
          title: 'Re-open this shift?',
          text: "This will UNDO the reconciliation and allow you to re-enter physical amounts.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, Re-open!'
      }).then((result) => {
          if (result.isConfirmed) {
              $.ajax({
                  url: "{{ route('accountant.reconciliations.reopen') }}",
                  type: "POST",
                  data: {
                      _token: "{{ csrf_token() }}",
                      date: date,
                      type: type
                  },
                  success: function(resp) {
                      if (resp.success) {
                          Swal.fire('Re-opened!', resp.message, 'success').then(() => location.reload());
                      } else {
                          Swal.fire('Error!', resp.message, 'error');
                      }
                  },
                  error: function() {
                      Swal.fire('Error!', 'Something went wrong!', 'error');
                  }
              });
          }
      });
  });

  // Redundant Pay Shortage Logic removed - merged into Bootstrap Modal handler above.

  // Transfer details logic (kept from original)
  $('.view-transfer-details-btn').click(function() {
    const id = $(this).data('transfer-id');
    $('#transferDetailsModal').modal('show');
    // ... AJAX call logic ...
  });
});
</script>
@endpush
