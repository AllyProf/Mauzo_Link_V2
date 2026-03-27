@extends('layouts.dashboard')
@section('title', 'Financial Reconciliation - View Summary')
@section('content')

<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Manager Reconciliation (Audit)</h1>
    <p>Verify sales against submitted physical & digital collections</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Manager Audit</a></li>
  </ul>
</div>

<style>
    .bg-mauzo { background-color: #940000 !important; }
    .text-mauzo { color: #940000 !important; }
    .btn-mauzo { background-color: #940000 !important; color: white !important; border: none; }
    .btn-mauzo:hover { background-color: #7a0000 !important; color: white !important; }
    .table-success-light { background-color: #f0fff4 !important; }
    .table-danger-light { background-color: #fff5f5 !important; }
    .widget-small .info h4 { margin: 0; font-size: 1.1rem; }
    .revenue-breakdown-row:hover { background-color: #f8f9fa !important; }
    .toggle-icon { transition: transform 0.2s; }
    .fa-chevron-down { transform: rotate(0deg); }
    .shadow-inner { box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06); }
    .badge-info { background-color: #17a2b8; }
    .badge-warning { background-color: #ffc107; color: #333; }
    .badge-success { background-color: #28a745; }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-body">
        
        <ul class="nav nav-pills mb-4" id="reconcileTabs" role="tablist">
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'financial' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('bar.manager.reconciliations', ['tab' => 'financial']) }}">
                <i class="fa fa-university mr-1"></i> Financial Summary
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'waiters' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('bar.manager.reconciliations', ['tab' => 'waiters']) }}">
                <i class="fa fa-users mr-1"></i> Waiter Collections
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'payments' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('bar.manager.reconciliations', ['tab' => 'payments']) }}">
                <i class="fa fa-list mr-1"></i> Digital Payment Log
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'shortages' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('bar.manager.reconciliations', ['tab' => 'shortages']) }}">
                <i class="fa fa-exclamation-triangle mr-1"></i> Shortage Tracking
            </a>
          </li>
        </ul>

        @if($tab === 'financial')
          <!-- High Level Summary for Managers/Owners -->
          @php
              $summaryExpected = $financialReconciliations->sum('total_expected');
              $summaryCollected = $financialReconciliations->sum(function($fr) {
                  return ($fr->total_submitted_bag ?? $fr->total_submitted) + ($fr->shortage_paid ?? 0);
              });
              $summaryShortage = $summaryExpected - $summaryCollected;
          @endphp

          @if($isManagerView)
          <div class="row mb-4">
              <div class="col-md-3">
                  <div class="widget-small coloured-icon" style="border-left: 5px solid #000; border-radius: 8px;"><i class="icon fa fa-shopping-cart fa-3x" style="background-color: #000;"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="font-size: 10px; color: #000 !important;">Expected (Sales)</p>
                          <p><b class="h5 font-weight-bold" style="color: #000 !important;">TSh {{ number_format($summaryExpected) }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small coloured-icon" style="border-left: 5px solid #940000; border-radius: 8px;"><i class="icon fa fa-money fa-3x" style="background-color: #940000;"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="color: #940000; font-size: 10px;">Collected (Actual)</p>
                          <p><b class="h5 font-weight-bold" style="color: #940000;">TSh {{ number_format($summaryCollected) }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small coloured-icon" style="border-left: 5px solid #000; border-radius: 8px;"><i class="icon fa {{ $summaryShortage > 0 ? 'fa-minus-circle' : 'fa-shield' }} fa-3x" style="background-color: #000;"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="font-size: 10px; color: #000 !important;">Audit Status</p>
                          <p><b class="h5 font-weight-bold" style="color: #000 !important;">{{ $summaryShortage > 0 ? 'TSh '.number_format($summaryShortage) : 'COMPLIANT' }}</b></p>
                      </div>
                  </div>
              </div>
              <div class="col-md-3">
                  <div class="widget-small warning coloured-icon" style="border-radius: 8px;"><i class="icon fa fa-line-chart fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="font-size: 10px;">Estimated Profit</p>
                          <p><b class="h5 font-weight-bold">TSh {{ number_format($summaryProfit) }}</b></p>
                      </div>
                  </div>
              </div>
          </div>
          @endif

          <!-- Financial Summary Tab -->
          <div class="table-responsive">
            <table class="table table-hover table-bordered shadow-sm rounded-lg overflow-hidden">
              <thead class="bg-mauzo text-white">
                <tr>
                  <th style="width: 40px; border: none;"></th>
                  <th style="border: none;">Shift / Date</th>
                  <th style="border: none;">Audit Timeline</th>
                  <th style="border: none;">Department</th>
                  <th style="border: none;">Expected (Sales)</th>
                  <th style="border: none;">Submitted (Actual)</th>
                  <th style="border: none;">Profit</th>
                  <th style="border: none;">Circulation</th>
                  <th style="border: none;">Cash</th>
                  <th style="border: none;">Digital/Other</th>
                  <th style="border: none;">Diff</th>
                  <th style="border: none;">Status</th>
                  @if($canReconcile)
                  <th style="border: none;">Action</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @php $lastDate = null; @endphp
                @forelse($financialReconciliations as $fr)
                  @php 
                    $currentDate = \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d');
                    $dayName = \Carbon\Carbon::parse($fr->reconciliation_date)->format('l, F d, Y');
                  @endphp

                  @php 
                    $currentDate = \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d');
                    $dayName = \Carbon\Carbon::parse($fr->reconciliation_date)->format('l, F d, Y');
                  @endphp
                  @php
                      $rowTotalPaid = 0;
                      if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $rowTotalPaid = (int)$m[1];
                      
                      $netDiff = $fr->total_expected - ($fr->total_submitted_bag + $rowTotalPaid);
                      $hasActiveShortage = ($netDiff > 0);

                      $breakdown = [];
                      if(preg_match('/\[ShortagePaidBreakdown:([^\]]+)\]/', $fr->notes ?? '', $bm)) {
                          foreach(explode(',', $bm[1]) as $p) {
                              $kv = explode('=', $p);
                              if(count($kv) == 2) $breakdown[$kv[0]] = (int)$kv[1];
                          }
                      }
                  @endphp
                  <tr class="revenue-breakdown-row {{ $hasActiveShortage ? 'table-danger' : ($netDiff < 0 ? 'table-success-light' : '') }}" 
                      style="cursor: pointer;"
                      data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                      data-type="{{ $fr->reconciliation_type }}"
                      data-target-row="details-{{ $loop->index }}">
                    <td class="text-center"><i class="fa fa-chevron-right toggle-icon"></i></td>
                    <td>
                        @php $shiftObj = $fr->staff_shift_id ? \App\Models\StaffShift::find($fr->staff_shift_id) : null; @endphp
                        <div class="font-weight-bold" style="color: #940000; font-size: 1.05rem;">
                           {{ $shiftObj ? $shiftObj->shift_number : 'MANUAL RECORD' }}
                        </div>
                        <small class="text-muted">{{ \Carbon\Carbon::parse($fr->last_activity_date)->format('M d, Y') }}</small>
                    </td>
                    <td>
                        @if($fr->status_indicator === 'verified' || ($fr->handover_id && $fr->handover_status === 'verified'))
                           @php $handover = $fr->handover_id ? \App\Models\FinancialHandover::find($fr->handover_id) : null; @endphp
                           <div class="small">
                              <span class="text-success font-weight-bold"><i class="fa fa-check-circle"></i> VERIFIED</span>
                              @if($handover)
                                <div class="text-muted" style="font-size: 10px;">Audit: {{ \Carbon\Carbon::parse($handover->verified_at ?: $handover->updated_at)->format('d/m H:i') }}</div>
                              @endif
                           </div>
                        @else
                           <span class="badge badge-warning-light text-dark small" style="border-radius: 4px; border: 1px solid #ffeeba;">AWAITING AUDIT</span>
                        @endif
                    </td>
                    <td>
                      @if($fr->reconciliation_type === 'bar')
                        <span class="badge badge-info shadow-sm" style="border-radius: 4px;"><i class="fa fa-glass"></i> COUNTER (BAR)</span>
                      @else
                        <span class="badge badge-warning shadow-sm" style="border-radius: 4px;"><i class="fa fa-cutlery"></i> CHEF (FOOD)</span>
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($fr->total_expected) }}</strong></td>
                    <td><strong class="text-mauzo">TSh {{ number_format($fr->total_submitted_bag + $rowTotalPaid) }}</strong></td>
                    <td>
                      @php $profitAmt = $fr->handover_id ? (\App\Models\FinancialHandover::find($fr->handover_id)?->profit_amount ?? 0) : 0; @endphp
                      <span class="text-success font-weight-bold">TSh {{ number_format($profitAmt) }}</span>
                    </td>
                    <td class="text-muted">
                        @php $circulationAmt = max(0, $fr->total_expected - $profitAmt); @endphp
                        TSh {{ number_format($circulationAmt) }}
                    </td>
                    <td>TSh {{ number_format(($fr->submitted_cash ?? 0) + ($breakdown['cash'] ?? 0)) }}</td>
                    <td>TSh {{ number_format(($fr->total_submitted_bag + $rowTotalPaid) - (($fr->submitted_cash ?? 0) + ($breakdown['cash'] ?? 0))) }}</td>
                    <td>
                      @if($netDiff > 0)
                        <span class="text-danger font-weight-bold">Short: -{{ number_format($netDiff) }}</span>
                      @elseif($netDiff < 0)
                        <span class="text-success font-weight-bold">+{{ number_format(abs($netDiff)) }} (Surplus)</span>
                      @else
                        @if($fr->status_indicator === 'verified' || ($fr->handover_id && $fr->handover_status === 'verified'))
                            <span class="text-success small font-weight-bold"><i class="fa fa-check-circle"></i> BALANCED</span>
                        @else
                            <span class="text-muted small font-weight-bold"><i class="fa fa-clock-o"></i> PENDING VERIFY</span>
                        @endif
                      @endif
                    </td>
                    <td>
                      @if($fr->status_indicator === 'verified' || ($fr->handover_id && $fr->handover_status === 'verified'))
                        <span class="badge badge-success shadow-none">Verified</span>
                      @elseif($fr->status_indicator === 'submitted' || ($fr->handover_id && $fr->handover_status === 'submitted'))
                        <span class="badge badge-info shadow-none">Audited</span>
                      @elseif($fr->status_indicator === 'pending' || $fr->status_indicator === 'partial')
                        <span class="badge badge-warning shadow-none">Review Needed</span>
                      @else
                        <span class="badge badge-secondary shadow-none">Open</span>
                      @endif
                    </td>
                    @if($canReconcile)
                    <td>
                        @if($fr->staff_shift_id)
                          <a href="{{ route('bar.counter.shift.print', $fr->staff_shift_id) }}" target="_blank" class="btn btn-sm btn-outline-info shadow-sm" title="View Shift Report">
                              <i class="fa fa-file-text-o"></i>
                          </a>
                        @endif

                        @if($fr->status_indicator !== 'verified' && in_array($fr->status_indicator, ['pending', 'submitted']))
                            <button class="btn btn-sm btn-mauzo perform-dept-reconcile-btn shadow-sm" 
                                    data-date="{{ \Carbon\Carbon::parse($fr->last_activity_date)->format('Y-m-d') }}" 
                                    data-type="{{ $fr->reconciliation_type }}"
                                    data-shift="{{ $fr->staff_shift_id }}"
                                    data-expected="{{ $fr->total_expected }}"
                                    data-breakdown='{{ json_encode($fr->recorded_platform_breakdown) }}'
                                    title="Verify Financial Accuracy">
                                <i class="fa fa-check"></i>
                            </button>
                        @endif

                        @if($netDiff > 0)
                          <button class="btn btn-sm btn-outline-danger pay-shortage-btn" 
                                  data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                  data-type="{{ $fr->reconciliation_type }}"
                                  data-shortage="{{ $netDiff }}"
                                  title="Pay Shortage Discovered">
                              <i class="fa fa-money"></i>
                          </button>
                        @endif

                        @if($fr->status_indicator !== 'verified' && $fr->handover_id)
                          <button class="btn btn-sm btn-outline-secondary reset-handover-mgr-btn" 
                                  data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                  data-type="{{ $fr->reconciliation_type }}"
                                  title="Reset Handover For Staff Correction">
                              <i class="fa fa-undo"></i>
                          </button>
                        @endif
                      </div>
                    </td>
                    @endif
                  </tr>
                  <!-- Folded Payment Breakdown -->
                  <tr id="details-{{ $loop->index }}" class="details-row d-none" style="background-color: #fafafa;">
                    <td colspan="{{ $canReconcile ? 12 : 11 }}" class="p-0">
                      <div class="px-4 py-4 border-bottom shadow-inner" style="background: white; border-top: 3px solid #eee;">
                                  <h6 class="text-mauzo font-weight-bold ml-2 mb-1 text-uppercase small" style="letter-spacing: 1px;"><i class="fa fa-hand-grab-o"></i> (1) Shift Handover Audit (Staff vs System)</h6>
                                  <p class="small text-muted ml-2 mb-3">Comparing Staff Declaration against System recorded sales</p>
                                  <table class="table table-sm table-bordered" style="font-size: 0.85rem; border: 1px solid #eee; border-radius: 8px;">
                                      <thead style="background-color: #f9f9f9;"><tr><th style="color: #940000; font-weight: 800; font-size: 11px;">REVENUE CATEGORY / PLATFORM</th><th>STAFF (ACTUAL)</th><th>SYSTEM (EXPECTED)</th><th>TEAM VARIANCE</th></tr></thead>
                                     <tbody>
                                         @php 
                                             $allChannels = array_unique(array_merge(
                                                 array_keys($fr->submitted_platform_breakdown ?? []),
                                                 array_keys($fr->recorded_platform_breakdown ?? [])
                                             ));
                                             $totalSubVisible = 0;
                                             $totalRecVisible = 0;
                                         @endphp
                                         @forelse($allChannels as $channelKey)
                                             @php 
                                               $origAmt = $fr->submitted_platform_breakdown[$channelKey] ?? 0;
                                               $adjAmt = $breakdown[$channelKey] ?? 0;
                                               $amt = $origAmt + $adjAmt;

                                               $recVal = $fr->recorded_platform_breakdown[$channelKey] ?? 0;
                                               $totalSubVisible += (float)$amt;
                                               $totalRecVisible += (float)$recVal;
                                             @endphp
                                             @if((float)$amt > 0 || $recVal > 0)
                                                 <tr>
                                                   <td style="font-weight: 600;">{{ strtoupper(str_replace('_', ' ', $channelKey)) }}</td>
                                                   <td class="font-weight-bold">
                                                      TSh {{ number_format($amt) }}
                                                      @if($adjAmt > 0)
                                                         <small class="text-info ml-2"><i class="fa fa-plus-circle"></i> Audit Pay</small>
                                                      @endif
                                                   </td>
                                                   <td class="text-muted">TSh {{ number_format($recVal) }}</td>
                                                   <td>
                                                     @php $vDiff = $amt - $recVal; @endphp
                                                     @if($vDiff < 0)
                                                       <span class="text-danger font-weight-bold"><i class="fa fa-arrow-down"></i> Short: -{{ number_format(abs($vDiff)) }}</span>
                                                     @elseif($vDiff > 0)
                                                       <span class="text-success font-weight-bold"><i class="fa fa-arrow-up"></i> Surplus: +{{ number_format($vDiff) }}</span>
                                                     @else
                                                       <span class="text-success small font-weight-bold">
                                                          <i class="fa fa-check-circle"></i> {{ $fr->status_indicator === 'verified' ? 'AUDIT COMPLIANT' : 'STAFF BALANCED' }}
                                                       </span>
                                                     @endif
                                                   </td>
                                                 </tr>
                                             @endif
                                         @empty
                                             <tr><td colspan="4" class="text-muted text-center italic">No platform details found</td></tr>
                                         @endforelse

                                         @if($rowTotalPaid > 0)
                                           <tr style="background-color: #ebfaff;">
                                             <td style="font-weight: 800;"><i class="fa fa-plus-circle text-info"></i> AUDIT ADJUSTMENTS</td>
                                             <td colspan="2"><span class="badge badge-info shadow-sm">TSh {{ number_format($rowTotalPaid) }}</span></td>
                                             <td><span class="text-info small font-weight-bold">Manually Balanced</span></td>
                                           </tr>
                                         @endif

                                         <tr class="bg-light" style="border-top: 2px solid #555;">
                                             <td style="font-weight: 900; color: #333;">GRAND TOTAL DECLARED REVENUE</td>
                                             <td style="font-weight: 900;">TSh {{ number_format($totalSubVisible + ($totalSubVisible == 0 ? ($fr->total_submitted_bag + $rowTotalPaid) : 0)) }}</td>
                                             <td style="font-weight: 900; color: #777;">TSh {{ number_format($totalRecVisible > 0 ? $totalRecVisible : $fr->total_expected) }}</td>
                                             <td style="font-weight: 900;">
                                                @php $finalVar = ($totalSubVisible + ($totalSubVisible == 0 ? ($fr->total_submitted_bag + $rowTotalPaid) : 0)) - ($totalRecVisible > 0 ? $totalRecVisible : $fr->total_expected); @endphp
                                                @if($finalVar == 0)
                                                    <span class="text-success"><i class="fa fa-shield"></i> {{ $fr->status_indicator === 'verified' ? 'AUDIT COMPLIANT' : 'STAFF BALANCED' }}</span>
                                                @else
                                                    <span class="{{ $finalVar > 0 ? 'text-success' : 'text-danger' }}">TSh {{ number_format($finalVar) }}</span>
                                                @endif
                                             </td>
                                         </tr>
                                     </tbody>
                                 </table>
                            </div>

                            <!-- Column 3: Audit Summary & Action -->
                            <div class="col-md-4 text-right">
                                <h6 class="text-info font-weight-bold mr-2 mb-3 text-uppercase small" style="letter-spacing: 1px;"><i class="fa fa-info-circle"></i> Net Audit Result</h6>
                                <div class="card border-0 shadow-sm p-3 {{ $netDiff != 0 ? ($netDiff > 0 ? 'bg-danger-light' : 'bg-success-light') : 'bg-light' }}" style="border-left: 5px solid {{ $netDiff != 0 ? ($netDiff > 0 ? '#dc3545' : '#28a745') : '#ddd' }} !important;">
                                    <div class="card-body p-1 text-center font-weight-bold">
                                        @if($netDiff != 0)
                                            <div class="{{ $netDiff > 0 ? 'text-danger' : 'text-success' }} h5 mb-1 text-uppercase">
                                                {{ $netDiff > 0 ? 'REVENUE SHORTAGE' : 'REVENUE SURPLUS' }}
                                            </div>
                                            <div class="h4 font-weight-bold {{ $netDiff > 0 ? 'text-danger' : 'text-success' }} mb-3">
                                                {{ $netDiff > 0 ? '-' : '+' }}TSh {{ number_format(abs($netDiff)) }}
                                            </div>
                                            <button class="btn btn-sm {{ $netDiff > 0 ? 'btn-success' : 'btn-primary' }} pay-shortage-btn w-100 shadow-sm py-2" 
                                                    data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                                    data-type="{{ $fr->reconciliation_type }}"
                                                    data-shortage="{{ $netDiff }}">
                                                <i class="fa fa-money"></i> {{ $netDiff > 0 ? 'Pay Outstanding Shortage' : 'Balance Account' }}
                                            </button>
                                        @else
                                            <div class="text-success h5 mb-1 font-weight-bold"><i class="fa fa-check-circle"></i> {{ $fr->status_indicator === 'verified' ? 'AUDIT COMPLIANT' : 'STAFF BALANCED' }}</div>
                                            <div class="small text-muted font-italic">{{ $fr->status_indicator === 'verified' ? 'Final audit verified and closed.' : 'Team declaration matches system. Awaiting Manager Audit.' }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                         </div>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="{{ $canReconcile ? 12 : 11 }}" class="text-center py-5 text-muted">
                        <i class="fa fa-info-circle fa-2x mb-3"></i><br>
                        No financial reconciliations found for the selected period.
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        @elseif($tab === 'waiters')
          <!-- Waiter Details Tab -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th></th>
                  <th>Shift Name</th>
                  <th>Date</th>
                  <th>Waiter</th>
                  <th>Orders</th>
                  <th>Cash Collected</th>
                  <th>Digital Collected</th>
                  <th>Total Collected</th>
                </tr>
              </thead>
              <tbody>
                @php $lastWaiterDate = null; @endphp
                @forelse($waiterReconciliations as $wr)
                  @php 
                    $currentWDate = \Carbon\Carbon::parse($wr->reconciliation_date)->format('Y-m-d');
                    $dayWName = \Carbon\Carbon::parse($wr->reconciliation_date)->format('l, F d, Y');
                    $shiftObj = $wr->staff_shift_id ? \App\Models\StaffShift::with('staff')->find($wr->staff_shift_id) : null;
                    $shiftName = $shiftObj ? $shiftObj->shift_number : 'MANUAL';
                    $supervisor = $shiftObj && $shiftObj->staff ? $shiftObj->staff->full_name : 'Manual Entry';
                    
                    $digitalCollected = ($wr->mobile_money_collected ?? 0) + ($wr->bank_collected ?? 0) + ($wr->card_collected ?? 0);
                    
                    // Fallback to fetch counter orders by shift_id if none are linked via reconciliation
                    $counterOrders = collect();
                    if ($wr->orders->isEmpty() && $shiftObj) {
                        $counterOrders = \App\Models\BarOrder::where('shift_id', $shiftObj->id)
                            ->with(['orderPayments', 'table'])
                            ->get();
                    }
                    $displayOrders = $wr->orders->isEmpty() ? $counterOrders : $wr->orders;
                  @endphp

                  @if($lastWaiterDate !== $currentWDate)
                    <tr class="bg-dark text-white font-weight-bold">
                        <td colspan="8" class="py-2 text-uppercase" style="letter-spacing: 1px; background: #555;">
                            <i class="fa fa-calendar mr-2"></i> {{ $dayWName }}
                        </td>
                    </tr>
                    @php $lastWaiterDate = $currentWDate; @endphp
                  @endif

                  <tr class="revenue-breakdown-row" style="cursor: pointer;" data-target-row="waiter-details-{{ $loop->index }}">
                    <td class="text-center"><i class="fa fa-chevron-right toggle-icon"></i></td>
                    <td>
                        <span class="font-weight-bold text-mauzo">{{ $shiftName }}</span><br>
                        <small class="text-muted"><i class="fa fa-user"></i> By: {{ $supervisor }}</small>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($wr->reconciliation_date)->format('M d, Y') }}</td>
                    <td>
                      <div class="d-flex align-items-center">
                        <img src="{{ $wr->staff_image ? asset('storage/'.$wr->staff_image) : asset('assets/images/avatar.png') }}" class="rounded-circle mr-2" style="width: 30px; height: 30px; object-fit: cover;">
                        <div>
                          <p class="mb-0 font-weight-bold">{{ $wr->staff_name }}</p>
                          <small class="text-muted text-uppercase">{{ $wr->reconciliation_type === 'bar' ? 'Counter' : 'Chef' }}</small>
                        </div>
                      </div>
                    </td>
                    <td>
                        <span class="badge badge-secondary shadow-none">
                             {{ $displayOrders->count() }} Orders
                        </span>
                    </td>
                    <td>TSh {{ number_format($wr->cash_collected ?? 0) }}</td>
                    <td>TSh {{ number_format($digitalCollected) }}</td>
                    <td><strong class="text-success">TSh {{ number_format(($wr->cash_collected ?? 0) + $digitalCollected) }}</strong></td>
                  </tr>
                  
                  {{-- Expandable Orders Sub-Table --}}
                  <tr id="waiter-details-{{ $loop->index }}" class="d-none bg-light">
                      <td colspan="8" class="p-4" style="border-top: 2px solid #ddd;">
                          @if($displayOrders->count() > 0)
                              <h6 class="font-weight-bold mb-3"><i class="fa fa-shopping-cart text-mauzo"></i> {{ $wr->staff_name }}'s Orders for {{ $shiftName }}</h6>
                              <div class="table-responsive bg-white border" style="border-radius: 6px;">
                                  <table class="table table-sm table-bordered mb-0">
                                      <thead class="bg-light">
                                          <tr>
                                              <th>Order #</th>
                                              <th>Time</th>
                                              <th>Table</th>
                                              <th>Status</th>
                                              <th>Expected Amt</th>
                                              <th>Collected (Paid)</th>
                                              <th>Payment Platform</th>
                                          </tr>
                                      </thead>
                                      <tbody>
                                          @foreach($displayOrders as $o)
                                          <tr>
                                              <td><strong>{{ $o->order_number }}</strong></td>
                                              <td>{{ Carbon\Carbon::parse($o->created_at)->format('H:i') }}</td>
                                              <td>{{ $o->table ? $o->table->name : 'N/A' }}</td>
                                              <td>
                                                  @if($o->payment_status === 'paid')
                                                    <span class="badge badge-success shadow-none">Paid</span>
                                                  @elseif($o->payment_status === 'partial')
                                                    <span class="badge badge-warning shadow-none">Partial</span>
                                                  @else
                                                    <span class="badge badge-danger shadow-none">Unpaid</span>
                                                  @endif
                                              </td>
                                              <td>TSh {{ number_format($o->total_amount) }}</td>
                                              <td><strong class="text-mauzo">TSh {{ number_format($o->paid_amount) }}</strong></td>
                                              <td>
                                                  @if($o->orderPayments && $o->orderPayments->count() > 0)
                                                      @foreach($o->orderPayments as $payment)
                                                          <span class="badge badge-secondary shadow-none">
                                                              {{ strtoupper($payment->payment_method) }}
                                                          </span> 
                                                      @endforeach
                                                  @else
                                                      <span class="badge badge-secondary shadow-none">{{ strtoupper($o->payment_method ?: 'UNKNOWN') }}</span>
                                                  @endif
                                              </td>
                                          </tr>
                                          @endforeach
                                      </tbody>
                                  </table>
                              </div>
                          @else
                              <div class="text-center text-muted py-3">
                                  <i class="fa fa-folder-open-o fa-2x mb-2"></i><br>
                                  No orders found for this reconciliation record.
                              </div>
                          @endif
                      </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">No waiter collections found</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        @elseif($tab === 'payments')
          <!-- Digital Payment Log -->
          <div class="mb-3">
            <div class="row w-100 no-gutters">
                <div class="col-md-3 pr-2 mb-2">
                    <input type="text" id="payment_js_search" class="form-control" placeholder="Search Reference, Order, Platform...">
                </div>
                <div class="col-md-2 pr-2 mb-2">
                    <input type="date" id="payment_js_date" class="form-control" title="Filter by Date">
                </div>
                <div class="col-md-2 pr-2 mb-2">
                    <select id="payment_js_method" class="form-control">
                        <option value="">All Methods</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card / POS</option>
                        <option value="cash">Cash</option>
                    </select>
                </div>
                <div class="col-md-2 pr-2 mb-2">
                    <select id="payment_js_stafftype" class="form-control">
                        <option value="">All Staff Roles</option>
                        <option value="counter">Counter Staff</option>
                        <option value="waiter">Waiters</option>
                    </select>
                </div>
                <div class="col-md-2 pr-2 mb-2">
                    <select id="payment_js_staff" class="form-control">
                        <option value="">Specific Staff</option>
                        @foreach($staffMembers as $member)
                            <option value="{{ strtolower($member->full_name) }}">{{ $member->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 mb-2">
                    <button type="button" id="payment_js_clear" class="btn btn-outline-secondary btn-block">
                        <i class="fa fa-times"></i> Clear
                    </button>
                </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm" id="digitalPaymentsTable">
              <thead class="bg-light">
                <tr>
                  <th>Date & Time</th>
                  <th>Order #</th>
                  <th>Waiter</th>
                  <th>Method</th>
                  <th>Provider</th>
                  <th>Ref Number</th>
                  <th>Amount</th>
                </tr>
              </thead>
              
              @php
                  $groupedPayments = $payments->groupBy(function($dp) {
                      return $dp->order && $dp->order->shift ? $dp->order->shift->shift_number : 'MANUAL / NO SHIFT';
                  });
              @endphp

              @forelse($groupedPayments as $shiftName => $shiftPayments)
                  <tbody class="shift-group">
                      <tr class="bg-dark text-white font-weight-bold shift-header">
                          <td colspan="7" class="py-2 text-uppercase" style="letter-spacing: 1px; background: #555;">
                              <i class="fa fa-folder-open-o mr-2"></i> {{ $shiftName }}
                          </td>
                      </tr>
                      @foreach($shiftPayments as $dp)
                        @php
                            $waiterName = $dp->order && $dp->order->waiter ? $dp->order->waiter->full_name : ($dp->order && $dp->order->createdBy ? $dp->order->createdBy->name : '-');
                            $cleanMethod = strtoupper(str_replace('_', ' ', $dp->payment_method));
                            $provider = strtoupper($dp->mobile_money_number ?: '-');
                            $ref = $dp->transaction_reference ?: '-';
                            $searchContext = strtolower($dp->order_number . ' ' . $provider . ' ' . $ref);
                            // Classify staff type heuristically: if there is a waiter_id, it is a waiter. Otherwise, counter staff.
                            $staffType = ($dp->order && $dp->order->waiter_id) ? 'waiter' : 'counter';
                        @endphp
                        <tr class="payment-row" 
                            data-search="{{ $searchContext }}" 
                            data-method="{{ strtolower($dp->payment_method) }}" 
                            data-staff="{{ strtolower($waiterName) }}"
                            data-stafftype="{{ $staffType }}"
                            data-date="{{ \Carbon\Carbon::parse($dp->created_at)->format('Y-m-d') }}">
                          <td>{{ \Carbon\Carbon::parse($dp->created_at)->format('d M Y - H:i') }}</td>
                          <td class="font-weight-bold">#{{ $dp->order ? $dp->order->order_number : '-' }}</td>
                          <td class="staff-cell">{{ $waiterName }}</td>
                          <td class="method-cell">
                              <span class="badge badge-outline-dark">{{ $cleanMethod }}</span>
                          </td>
                          <td><strong>{{ $provider }}</strong></td>
                          <td class="text-muted">{{ $ref }}</td>
                          <td class="font-weight-bold text-success">TSh {{ number_format($dp->amount) }}</td>
                        </tr>
                      @endforeach
                  </tbody>
              @empty
                  <tbody>
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted" id="no-payments-msg">No digital payments recorded in this period</td>
                    </tr>
                  </tbody>
              @endforelse
            </table>
          </div>
          
          <nav aria-label="Payments pagination" class="mt-3">
              <ul class="pagination justify-content-center" id="payments-pagination">
                  <!-- Pagination dynamically generated by JS -->
              </ul>
          </nav>

        @elseif($tab === 'shortages')
            <!-- History of Counters with Shortages -->
             @php
                $shiftsWithShortages = collect();
                foreach($financialReconciliations as $fr) {
                    $paid = 0;
                    if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $paid = (int)$m[1];
                    $expected = $fr->total_expected;
                    $submitted = $fr->total_submitted_bag ?? 0;
                    $initialShortage = $expected - $submitted;
                    
                    if ($initialShortage > 0) {
                        $fr->initial_shortage = $initialShortage;
                        $fr->paid_amount = $paid;
                        $fr->remaining_shortage = $initialShortage - $paid;
                        $shiftsWithShortages->push($fr);
                    }
                }
             @endphp
             <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Date</th>
                            <th>Shift Name</th>
                            <th>Counter Staff</th>
                            <th>Shortage Origin</th>
                            <th>Total Shortage</th>
                            <th>Amount Paid</th>
                            <th>Remaining Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftsWithShortages as $short)
                            @php 
                                $shiftObj = $short->staff_shift_id ? \App\Models\StaffShift::find($short->staff_shift_id) : null; 
                                $shiftName = $shiftObj ? $shiftObj->shift_number : 'MANUAL';
                                
                                // Determine Counter Name
                                $counterName = 'Shift Collective';
                                if ($shiftObj && $shiftObj->user_id) {
                                    $u = \App\Models\User::find($shiftObj->user_id);
                                    if ($u) $counterName = $u->name;
                                }

                                // Determine platform causing the short
                                $shortagePlatforms = [];
                                $allChannels = array_unique(array_merge(
                                    is_array($short->submitted_platform_breakdown) ? array_keys($short->submitted_platform_breakdown) : [],
                                    is_array($short->recorded_platform_breakdown) ? array_keys($short->recorded_platform_breakdown) : []
                                ));
                                
                                foreach ($allChannels as $channelKey) {
                                   $expected = $short->recorded_platform_breakdown[$channelKey] ?? 0;
                                   $submittedStr = $short->submitted_platform_breakdown[$channelKey] ?? 0;
                                   $diffAmt = $expected - $submittedStr;
                                   if ($diffAmt > 0) {
                                       $shortagePlatforms[] = strtoupper(str_replace('_', ' ', $channelKey));
                                   }
                                }
                                $platformCause = count($shortagePlatforms) > 0 ? implode(', ', $shortagePlatforms) : 'CASH / MIXED FUNDS';
                            @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($short->reconciliation_date)->format('M d, Y') }}</td>
                                <td class="font-weight-bold text-mauzo">{{ $shiftName }}</td>
                                <td><i class="fa fa-user-circle-o text-muted mr-1"></i> {{ $counterName }}</td>
                                <td><span class="badge badge-light border">{{ $platformCause }}</span></td>
                                <td class="text-danger font-weight-bold">TSh {{ number_format($short->initial_shortage) }}</td>
                                <td class="text-success font-weight-bold">TSh {{ number_format($short->paid_amount) }}</td>
                                <td>
                                    @if($short->remaining_shortage > 0)
                                        <span class="badge badge-danger shadow-none" style="font-size: 13px;">TSh {{ number_format($short->remaining_shortage) }}</span>
                                    @else
                                        <span class="badge badge-success shadow-none"><i class="fa fa-check"></i> CLEARED</span>
                                    @endif
                                </td>
                                <td>
                                    @if($short->remaining_shortage > 0)
                                        <button class="btn btn-sm btn-outline-danger pay-shortage-btn" 
                                                data-date="{{ \Carbon\Carbon::parse($short->reconciliation_date)->format('Y-m-d') }}" 
                                                data-type="{{ $short->reconciliation_type }}"
                                                data-shortage="{{ $short->remaining_shortage }}">
                                            <i class="fa fa-money"></i> Record Payment
                                        </button>
                                    @else
                                        <span class="text-muted small">Fully Paid</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-5 text-muted italic">No counter shortages found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
             </div>
        @endif

      </div>
    </div>
  </div>
</div>

<!-- Modal for Department Reconciliation (Finalize Audit) -->
<div class="modal fade" id="deptReconcileModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-mauzo text-white">
        <h5 class="modal-title"><i class="fa fa-shield"></i> Finalize <span id="modal_dept_name"></span> Reconciliation</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="deptReconcileForm">
        <input type="hidden" name="date" id="dr_date">
        <input type="hidden" name="type" id="dr_type">
        <input type="hidden" name="shift_id" id="dr_shift">
        <div class="modal-body">
            <div class="alert alert-info shadow-sm p-2 mb-3">
                <div class="d-flex justify-content-between">
                    <span>Expected System Sales:</span>
                    <strong class="h6 mb-0">TSh <span id="dr_expected_label">0</span></strong>
                </div>
            </div>
            
            <div id="dynamic_breakdown_inputs" class="row">
                <!-- Dynamically populated via JS -->
            </div>



            <div class="form-group mb-1">
                <label class="small font-weight-bold">Audit Notes</label>
                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Mention any specific audit remarks..."></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-mauzo btn-sm" id="submitDeptReconcile"><i class="fa fa-save"></i> Save & Reconcile</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal for Orders List Details -->
<div class="modal fade" id="viewDeptOrdersModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="orders_modal_title">Shift Breakdown</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body p-0">
        <ul class="nav nav-tabs nav-justified" id="deptDetailsTab" role="tablist">
          <li class="nav-item"><a class="nav-link active font-weight-bold" id="orders-tab" data-toggle="tab" href="#dept_orders_panel">ORDERS (SHIFT)</a></li>
          <li class="nav-item"><a class="nav-link font-weight-bold" id="payments-tab" data-toggle="tab" href="#payments_breakdown_panel">PAYMENT AUDIT</a></li>
          <li class="nav-item"><a class="nav-link font-weight-bold" id="shortage-tab" data-toggle="tab" href="#shortage_history_panel">SHORTAGE TRACKING</a></li>
        </ul>
        <div class="tab-content border-left border-right border-bottom" id="deptDetailsTabContent">
          <!-- Orders Panel -->
          <div class="tab-pane fade show active" id="dept_orders_panel" role="tabpanel">
            <div class="table-responsive">
              <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                <thead class="bg-light">
                  <tr>
                    <th>Time</th>
                    <th>Order #</th>
                    <th>Waiter</th>
                    <th>Table</th>
                    <th>Total</th>
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
              <div class="col-md-12">
                <div class="form-group">
                    <label class="shortage-input-label font-weight-bold">Amount to Pay (TSh)</label>
                    <input type="number" name="amount" id="shortage_pay_amount" class="form-control" required>
                    <input type="hidden" name="channel" value="cash">
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
  // Performance Trend Chart
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
  } catch(e) { console.error("Chart error:", e); }
  @endif

  // Row click to toggle folded payment breakdown
  $('.revenue-breakdown-row').css('cursor', 'pointer').click(function(e) {
      if ($(e.target).closest('button, a, input').length) return;
      
      const targetId = $(this).data('target-row');
      const detailsRow = $(`#${targetId}`);
      const icon = $(this).find('.toggle-icon');

      if (detailsRow.hasClass('d-none')) {
          $('.details-row').addClass('d-none');
          $('.toggle-icon').removeClass('fa-chevron-down').addClass('fa-chevron-right');
          detailsRow.removeClass('d-none');
          icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
      } else {
          detailsRow.addClass('d-none');
          icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
      }
  });

  // Department Reconciliation Modal Pop-up
  $(document).on('click', '.perform-dept-reconcile-btn', function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const shift = $(this).data('shift');
      const expected = parseFloat($(this).data('expected')) || 0;
      
      $('#dr_date').val(date);
      $('#dr_type').val(type);
      $('#dr_shift').val(shift);
      $('#modal_dept_name').text(type.toUpperCase());
      $('#dr_expected_label').text(new Intl.NumberFormat().format(expected));
      
      // BUILD DYNAMIC PALTFORM INPUTS
      const breakdown = $(this).data('breakdown') || {};
      const $container = $('#dynamic_breakdown_inputs');
      $container.empty();

      // Ensure 'cash' is always first if it exists
      const sortedKeys = Object.keys(breakdown).sort((a,b) => {
          if (a === 'cash') return -1;
          if (b === 'cash') return 1;
          return a.localeCompare(b);
      });

      if (sortedKeys.length === 0) {
          $container.append('<div class="col-12 text-center py-3 text-muted">No platform data found for this period.</div>');
      }

      sortedKeys.forEach(key => {
          const recordedAmt = parseFloat(breakdown[key]) || 0;
          const displayKey = key.replace(/_/g, ' ').toUpperCase();
          
          let html = `
            <div class="col-md-6 mb-3">
                <div class="card border-light shadow-none bg-light p-2" style="border-radius: 8px;">
                    <label class="small font-weight-bold mb-1" style="color: #555;">${displayKey}</label>
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend"><span class="input-group-text bg-white">TSh</span></div>
                        <input type="number" name="platform_amounts[${key}]" 
                               class="form-control dr-platform-input" 
                               value="${recordedAmt}" required>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Recorded: <strong>TSh ${new Intl.NumberFormat().format(recordedAmt)}</strong></small>
                        <div class="dr-diff-feedback small font-weight-bold"></div>
                    </div>
                </div>
            </div>
          `;
          $container.append(html);
      });

      // Bind dynamic diff feedback
      $container.find('.dr-platform-input').on('input', function() {
          const val = parseFloat($(this).val()) || 0;
          const inputName = $(this).attr('name');
          const key = inputName.match(/\[(.*?)\]/)[1];
          const rec = parseFloat(breakdown[key]) || 0;
          const diff = val - rec;
          const $feedback = $(this).closest('.card').find('.dr-diff-feedback');
          if (diff < 0) $feedback.html(`<span class="text-danger">Short: -${new Intl.NumberFormat().format(Math.abs(diff))}</span>`);
          else if (diff > 0) $feedback.html(`<span class="text-success">Extra: +${new Intl.NumberFormat().format(diff)}</span>`);
          else $feedback.html('<span class="text-success"><i class="fa fa-check"></i> Balanced</span>');
      }).trigger('input');
      
      $('#deptReconcileModal').modal('show');
  });

  $('#deptReconcileForm').on('submit', function(e) {
      e.preventDefault();
      const $btn = $('#submitDeptReconcile');
      $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

      $.ajax({
          url: "{{ route('bar.manager.reconciliations.finalize') }}",
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

  // Manager: Reset Handover Button
  $(document).on('click', '.reset-handover-mgr-btn', function() {
    const date = $(this).data('date');
    const type = $(this).data('type');
    Swal.fire({
      title: 'Reset Counter Handover?',
      html: `This will <strong>delete</strong> the submitted handover for <strong>${type.toUpperCase()} - ${date}</strong> and allow the counter to resubmit.<br><br>The counter's shift will be re-opened. Are you sure?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#940000',
      confirmButtonText: '<i class="fa fa-undo"></i> Yes, Reset It',
    }).then((result) => {
      if (result.isConfirmed) {
        // Re-route AJAX calls to manager endpoints
        const ordersUrl = "{{ route('bar.manager.reconciliations.orders') }}";
        const finalizeUrl = "{{ route('bar.manager.reconciliations.finalize') }}";
        const payShortageUrl = "{{ route('bar.manager.reconciliations.pay-shortage') }}";
        const resetHandoverUrl = "{{ route('bar.manager.reconciliations.reset-handover') }}";
        $.post(resetHandoverUrl, { _token: '{{ csrf_token() }}', date: date, type: type }, function(resp) {
            if (resp.success) location.reload();
            else Swal.fire('Error', resp.error, 'error');
        });
      }
    });
  });

  // Pay Shortage Button
  $(document).on('click', '.pay-shortage-btn', function() {
      const shortage = parseFloat($(this).data('shortage'));
      $('#shortage_date').val($(this).data('date'));
      $('#shortage_type').val($(this).data('type'));
      $('#shortage_amount_display').text(new Intl.NumberFormat().format(Math.abs(shortage)));
      $('#shortage_pay_amount').val(Math.abs(shortage));
      $('#payShortageModal').modal('show');
  });

  $('#shortage_payment_form').submit(function(e) {
      e.preventDefault();
      $.post("{{ route('bar.manager.reconciliations.pay-shortage') }}", $(this).serialize(), function(response) {
          if (response.success) location.reload();
          else Swal.fire('Error', response.error, 'error');
      });
  });

  // Client-side Payments Search and Pagination
  const paymentsPerPage = 10;
  let paymentCurrentPage = 1;
  let visibleShiftGroups = [];

  function processPaymentFiltersAndPagination() {
      const query = ($('#payment_js_search').val() || '').toLowerCase();
      const date = $('#payment_js_date').val() || '';
      const method = ($('#payment_js_method').val() || '').toLowerCase();
      const stafftype = ($('#payment_js_stafftype').val() || '').toLowerCase();
      const staff = ($('#payment_js_staff').val() || '').toLowerCase();

      visibleShiftGroups = [];

      $('.shift-group').each(function() {
          let hasVisibleRow = false;

          $(this).find('.payment-row').each(function() {
              const rowSearch = $(this).data('search') || '';
              const rowDate = $(this).data('date') || '';
              const rowMethod = $(this).data('method') || '';
              const rowStaffType = $(this).data('stafftype') || '';
              const rowStaff = $(this).data('staff') || '';

              let match = true;
              if (query && rowSearch.indexOf(query) === -1) match = false;
              if (date && rowDate !== date) match = false;
              if (method && rowMethod !== method) match = false;
              if (stafftype && rowStaffType !== stafftype) match = false;
              if (staff && rowStaff !== staff) match = false;

              if (match) {
                  $(this).show();
                  hasVisibleRow = true;
              } else {
                  $(this).hide();
              }
          });

          if (hasVisibleRow) {
              visibleShiftGroups.push(this);
          } else {
              $(this).hide();
          }
      });

      if (visibleShiftGroups.length === 0 && $('.payment-row').length > 0) {
          if ($('#no-payments-msg').length === 0) {
              $('#digitalPaymentsTable').append('<tbody><tr><td colspan="7" class="text-center py-4 text-muted" id="no-payments-msg">No digital payments matched your search</td></tr></tbody>');
          } else {
              $('#no-payments-msg').closest('tbody').show();
              $('#no-payments-msg').text('No digital payments matched your search');
          }
      } else {
          if ($('#no-payments-msg').length > 0) {
              $('#no-payments-msg').closest('tbody').hide();
          }
      }

      renderPaymentPagination();
  }

  function renderPaymentPagination() {
      const totalPages = Math.ceil(visibleShiftGroups.length / paymentsPerPage) || 1;
      if (paymentCurrentPage > totalPages) paymentCurrentPage = totalPages;

      let paginationHtml = '';
      if (totalPages > 1) {
          paginationHtml += `<li class="page-item ${paymentCurrentPage === 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${paymentCurrentPage - 1}">Previous</a></li>`;
          for (let i = 1; i <= totalPages; i++) {
              paginationHtml += `<li class="page-item ${paymentCurrentPage === i ? 'active' : ''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
          }
          paginationHtml += `<li class="page-item ${paymentCurrentPage === totalPages ? 'disabled' : ''}"><a class="page-link" href="#" data-page="${paymentCurrentPage + 1}">Next</a></li>`;
      }
      $('#payments-pagination').html(paginationHtml);

      const startIndex = (paymentCurrentPage - 1) * paymentsPerPage;
      const endIndex = startIndex + paymentsPerPage;

      $.each(visibleShiftGroups, function(index, group) {
          if (index >= startIndex && index < endIndex) {
              $(group).show();
          } else {
              $(group).hide();
          }
      });
  }

  $('#payment_js_search, #payment_js_date, #payment_js_method, #payment_js_stafftype, #payment_js_staff').on('input change', function() {
      paymentCurrentPage = 1;
      processPaymentFiltersAndPagination();
  });

  $('#payment_js_clear').on('click', function() {
      $('#payment_js_search').val('');
      $('#payment_js_date').val('');
      $('#payment_js_method').val('');
      $('#payment_js_stafftype').val('');
      $('#payment_js_staff').val('');
      paymentCurrentPage = 1;
      processPaymentFiltersAndPagination();
  });

  $(document).on('click', '#payments-pagination .page-link', function(e) {
      e.preventDefault();
      const page = parseInt($(this).data('page'));
      if (!isNaN(page)) {
          paymentCurrentPage = page;
          renderPaymentPagination();
      }
  });

  // Run on init if tab is payments
  if($('.payment-row').length > 0) {
      processPaymentFiltersAndPagination();
  }
});
</script>
@endpush
