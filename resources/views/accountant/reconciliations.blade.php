@extends('layouts.dashboard')
@section('title', 'Financial Reconciliation - View Summary')
@section('content')

<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Financial Reconciliation</h1>
    <p>Verify sales against submitted physical & digital collections</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Financial Reconciliation</a></li>
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
            <a class="nav-link {{ $tab === 'financial' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('accountant.reconciliations', ['tab' => 'financial']) }}">
                <i class="fa fa-university mr-1"></i> Financial Summary
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'waiters' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('accountant.reconciliations', ['tab' => 'waiters']) }}">
                <i class="fa fa-users mr-1"></i> Waiter Collections
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'payments' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('accountant.reconciliations', ['tab' => 'payments']) }}">
                <i class="fa fa-list mr-1"></i> Digital Payment Log
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ $tab === 'shortages' ? 'active bg-mauzo' : '' }} text-uppercase font-weight-bold" href="{{ route('accountant.reconciliations', ['tab' => 'shortages']) }}">
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
                  <div class="widget-small coloured-icon" style="border-left: 5px solid #555; border-radius: 8px;"><i class="icon fa fa-shopping-cart fa-3x" style="background-color: #555;"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="font-size: 10px;">Expected (Sales)</p>
                          <p><b class="h5 font-weight-bold">TSh {{ number_format($summaryExpected) }}</b></p>
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
                  <div class="widget-small {{ $summaryShortage > 0 ? 'danger' : 'success' }} coloured-icon" style="border-radius: 8px;"><i class="icon fa {{ $summaryShortage > 0 ? 'fa-minus-circle' : 'fa-shield' }} fa-3x"></i>
                      <div class="info">
                          <p class="text-uppercase small font-weight-bold" style="font-size: 10px;">Audit Status</p>
                          <p><b class="h5 font-weight-bold">{{ $summaryShortage > 0 ? 'TSh '.number_format($summaryShortage) : 'COMPLIANT' }}</b></p>
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
                  <th style="border: none;">Date</th>
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

                  @if($lastDate !== $currentDate)
                    <tr style="background: #f4f4f4;">
                        <td colspan="{{ $canReconcile ? 12 : 11 }}" class="py-2 text-uppercase font-weight-bold" style="letter-spacing: 0.5px; color: #555; font-size: 11px;">
                            <i class="fa fa-calendar-check-o mr-2"></i> {{ $dayName }}
                        </td>
                    </tr>
                    @php $lastDate = $currentDate; @endphp
                  @endif
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
                    <td>{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('M d, Y') }}</td>
                    <td>
                      @if($fr->reconciliation_type === 'bar')
                        <span class="badge badge-info shadow-sm" style="border-radius: 4px;"><i class="fa fa-glass"></i> COUNTER (BAR)</span>
                      @else
                        <span class="badge badge-warning shadow-sm" style="border-radius: 4px;"><i class="fa fa-cutlery"></i> CHEF (FOOD)</span>
                      @endif
                      @if($fr->handover_id)
                        <div class="small text-muted mt-1 font-weight-bold">Shift Handover #{{ $fr->handover_id }} - {{ $fr->handover_staff_name ?? 'Counter Staff' }}</div>
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
                        @if($fr->status_indicator === 'verified')
                            <span class="text-success small font-weight-bold"><i class="fa fa-check-circle"></i> BALANCED</span>
                        @else
                            <span class="text-muted small font-weight-bold"><i class="fa fa-clock-o"></i> PENDING VERIFY</span>
                        @endif
                      @endif
                    </td>
                    <td>
                      @if($fr->status_indicator === 'verified')
                        <span class="badge badge-success shadow-none">Verified</span>
                      @elseif($fr->status_indicator === 'submitted')
                        <span class="badge badge-info shadow-none">Review Needed</span>
                      @else
                        <span class="badge badge-warning shadow-none">Open</span>
                      @endif
                    </td>
                    @if($canReconcile)
                    <td>
                      <div class="d-flex align-items-center" style="gap:4px;">
                        @if($fr->status_indicator !== 'verified' && in_array($fr->status_indicator, ['pending', 'submitted']))
                            <button class="btn btn-sm btn-mauzo perform-dept-reconcile-btn shadow-sm" 
                                    data-date="{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('Y-m-d') }}" 
                                    data-type="{{ $fr->reconciliation_type }}"
                                    data-shift="{{ $fr->staff_shift_id }}"
                                    data-expected="{{ $fr->total_expected }}"
                                    data-cash="{{ ($fr->submitted_cash ?? 0) + ($breakdown['cash'] ?? 0) }}"
                                    data-mobile="{{ ($fr->submitted_mobile ?? 0) + ($breakdown['mobile_money'] ?? 0) }}"
                                    data-bank="{{ ($fr->submitted_bank ?? 0) + ($breakdown['bank_transfer'] ?? 0) }}"
                                    data-card="{{ ($fr->submitted_card ?? 0) + ($breakdown['pos_card'] ?? 0) }}"
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
                         <div class="row">
                            <!-- Column 1: Staff Submission (From Handover) -->
                            <div class="col-md-8">
                                 <h6 class="text-mauzo font-weight-bold ml-2 mb-3 text-uppercase small" style="letter-spacing: 1px;"><i class="fa fa-hand-grab-o"></i> (1) Shift Handover Platform Audit (Bag Collection)</h6>
                                 <table class="table table-sm table-bordered" style="font-size: 0.85rem; border: 1px solid #eee; border-radius: 8px;">
                                     <thead style="background-color: #f9f9f9;"><tr><th style="color: #940000; font-weight: 800; font-size: 11px;">REVENUE CATEGORY / PLATFORM</th><th>SUBMITTED (ACTUAL)</th><th>RECORDED (EXPECTED)</th><th>AUDIT VARIANCE</th></tr></thead>
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
                                                       <span class="text-success small font-weight-bold"><i class="fa fa-check-circle"></i> BALANCED</span>
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
                                                    <span class="text-success"><i class="fa fa-shield"></i> COMPLIANT</span>
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
                                            <div class="text-success h5 mb-1 font-weight-bold"><i class="fa fa-check-circle"></i> AUDIT COMPLIANT</div>
                                            <div class="small text-muted font-italic">No discrepancies found in this shift submission.</div>
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
                @php $lastWaiterDate = null; @endphp
                @forelse($waiterReconciliations as $wr)
                  @php 
                    $currentWDate = \Carbon\Carbon::parse($wr->reconciliation_date)->format('Y-m-d');
                    $dayWName = \Carbon\Carbon::parse($wr->reconciliation_date)->format('l, F d, Y');
                  @endphp

                  @if($lastWaiterDate !== $currentWDate)
                    <tr class="bg-dark text-white font-weight-bold">
                        <td colspan="9" class="py-2 text-uppercase" style="letter-spacing: 1px; background: #555;">
                            <i class="fa fa-calendar mr-2"></i> {{ $dayWName }}
                        </td>
                    </tr>
                    @php $lastWaiterDate = $currentWDate; @endphp
                  @endif

                  <tr>
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
                      @if($wr->reconciliation_type === 'bar')
                        <span class="badge badge-info shadow-none"><i class="fa fa-glass"></i> BAR</span>
                      @else
                        <span class="badge badge-warning shadow-none"><i class="fa fa-cutlery"></i> FOOD</span>
                      @endif
                    </td>
                    <td>TSh {{ number_format($wr->total_expected) }}</td>
                    <td>TSh {{ number_format($wr->total_submitted) }}</td>
                    <td>
                      @php $wDiff = $wr->total_expected - $wr->total_submitted; @endphp
                      @if($wDiff > 0)
                        <span class="text-danger">TSh {{ number_format($wDiff) }} (Short)</span>
                      @elseif($wDiff < 0)
                        <span class="text-success">+TSh {{ number_format(abs($wDiff)) }} (Extra)</span>
                      @else
                        <span class="text-success small font-weight-bold">Balanced</span>
                      @endif
                    </td>
                    <td style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $wr->notes ?: '-' }}</td>
                    <td>
                      @if($wr->status_indicator === 'verified')
                        <span class="badge badge-success shadow-none">Verified</span>
                      @elseif($wr->status_indicator === 'submitted')
                        <span class="badge badge-info shadow-none">Submitted</span>
                      @else
                        <span class="badge badge-warning shadow-none">Pending</span>
                      @endif
                    </td>
                    <td>
                      @if($wr->status_indicator !== 'verified' && $canReconcile)
                        <button class="btn btn-sm btn-info verify-financial-btn" 
                                data-id="{{ $wr->id }}" 
                                data-waiter="{{ $wr->staff_name }}"
                                data-shortage="{{ $wDiff > 0 ? $wDiff : 0 }}">
                          Verify
                        </button>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center py-4 text-muted">No waiter collections found</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        @elseif($tab === 'payments')
          <!-- Digital Payment Log -->
          <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="row w-100 no-gutters">
                <div class="col-md-4 pr-2">
                    <input type="text" id="payment_js_search" class="form-control" placeholder="Search Reference / Order...">
                </div>
                <div class="col-md-3 pr-2">
                    <select id="payment_js_method" class="form-control">
                        <option value="">All Methods</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>
                <div class="col-md-3 pr-2">
                    <select id="payment_js_staff" class="form-control">
                        <option value="">All Staff</option>
                        {{-- Logic to populate staff if needed --}}
                    </select>
                </div>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover table-sm">
              <thead class="bg-light">
                <tr>
                  <th>Date & Time</th>
                  <th>Order #</th>
                  <th>Waiter</th>
                  <th>Method</th>
                  <th>Provider / Ref</th>
                  <th>Amount</th>
                </tr>
              </thead>
              <tbody>
                @forelse($digitalPayments as $dp)
                  <tr class="payment-row">
                    <td>{{ \Carbon\Carbon::parse($dp->created_at)->format('d M y - H:i') }}</td>
                    <td class="font-weight-bold">#{{ $dp->order_number }}</td>
                    <td class="staff-cell">{{ $dp->waiter_name }}</td>
                    <td class="method-cell"><span class="badge badge-outline-dark">{{ strtoupper(str_replace('_', ' ', $dp->payment_method)) }}</span></td>
                    <td>{{ $dp->provider_ref ?: ($dp->reference ?: '-') }}</td>
                    <td class="font-weight-bold text-success">TSh {{ number_format($dp->amount) }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">No digital payments recorded in this period</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        @elseif($tab === 'shortages')
            <!-- Historical Shortage Settlements Log -->
             <div class="table-responsive">
                <table class="table table-bordered table-sm shadow-sm rounded overflow-hidden">
                    <thead class="bg-mauzo text-white">
                        <tr>
                            <th style="border: none;">Settlement Date</th>
                            <th style="border: none;">Shift Source</th>
                            <th style="border: none;">Staff Member</th>
                            <th style="border: none;">Amount Paid</th>
                            <th style="border: none;">Channel</th>
                            <th style="border: none;">Reference / Notes</th>
                            <th style="border: none;">Accountant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shortageHistory as $sh)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($sh->created_at)->format('d M Y, H:i') }}</td>
                                <td>
                                    <div class="small">
                                        <i class="fa fa-calendar-o"></i> {{ \Carbon\Carbon::parse($sh->reconciliation_date)->format('M d, Y') }}<br>
                                        <span class="badge badge-light border">{{ strtoupper($sh->reconciliation_type) }}</span>
                                    </div>
                                </td>
                                <td><i class="fa fa-user-circle"></i> {{ $sh->staff_name ?: 'Shift Collective' }}</td>
                                <td class="font-weight-bold text-success">TSh {{ number_format($sh->amount) }}</td>
                                <td><span class="badge badge-info">{{ strtoupper($sh->channel) }}</span></td>
                                <td class="small">{{ $sh->reference ?: '-' }}</td>
                                <td><span class="text-muted"><i class="fa fa-check-shield"></i> {{ $sh->accountant_name }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-5 text-muted italic">No historical shortage settlements discovered.</td></tr>
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
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">PHYSICAL CASH </label>
                        <input type="number" name="cash_received" id="dr_cash" class="form-control form-control-sm" required>
                        <small class="text-muted">Reported: TSh <span id="dr_cash_recorded_label">0</span></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">DIGITAL (AIRTEL/MPESA/TIGO)</label>
                        <input type="number" name="mobile_received" id="dr_mobile" class="form-control form-control-sm" required>
                        <small class="text-muted">Reported: TSh <span id="dr_mobile_recorded_label">0</span></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">BANK TRANSFER</label>
                        <input type="number" name="bank_received" id="dr_bank" class="form-control form-control-sm" required>
                        <small class="text-muted">Reported: TSh <span id="dr_bank_recorded_label">0</span></small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold">POS / CARD MACHINE</label>
                        <input type="number" name="card_received" id="dr_card" class="form-control form-control-sm" required>
                        <small class="text-muted">Reported: TSh <span id="dr_card_recorded_label">0</span></small>
                    </div>
                </div>
            </div>

            <hr class="my-2">
            
            <div class="form-group mb-2">
                <label class="small font-weight-bold text-mauzo">CIRCULATION MONEY (NEXT SHIFT FLOAT)</label>
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend"><span class="input-group-text">TSh</span></div>
                    <input type="number" name="circulation_money" id="dr_circulation" class="form-control" value="0">
                </div>
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
      const cash = parseFloat($(this).data('cash')) || 0;
      const mobile = parseFloat($(this).data('mobile')) || 0;
      const bank = parseFloat($(this).data('bank')) || 0;
      const card = parseFloat($(this).data('card')) || 0;
      
      $('#dr_date').val(date);
      $('#dr_type').val(type);
      $('#dr_shift').val(shift);
      $('#modal_dept_name').text(type.toUpperCase());
      $('#dr_expected_label').text(new Intl.NumberFormat().format(expected));
      
      $('#dr_cash').val(cash);
      $('#dr_mobile').val(mobile);
      $('#dr_bank').val(bank);
      $('#dr_card').val(card);
      
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
        $.post("{{ route('accountant.reconciliations.reset-handover') }}", { _token: '{{ csrf_token() }}', date: date, type: type }, function(resp) {
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
      $.post("{{ route('accountant.reconciliations.pay-shortage') }}", $(this).serialize(), function(response) {
          if (response.success) location.reload();
          else Swal.fire('Error', response.error, 'error');
      });
  });
});
</script>
@endpush
