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
      </ul>
  </div>
</div>

<div class="tile">
  <div class="tile-body pt-3">



        @if($tab === 'financial')
          <!-- Financial Summary Tab -->
          <div class="table-responsive">
            <table class="table table-hover table-bordered">
              <thead class="bg-light">
                <tr>
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
                      $rowDiff = $fr->total_submitted - $fr->total_expected;
                      $rowTotalPaid = 0;
                      if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $rowTotalPaid = (int)$m[1];
                      $hasActiveShortage = $rowDiff < 0 && $rowTotalPaid < abs($rowDiff);
                  @endphp
                  <tr class="{{ $hasActiveShortage ? 'table-danger' : ($rowDiff < 0 ? 'table-success-light' : '') }}" style="{{ $hasActiveShortage ? 'background-color: #fff5f5;' : '' }}">
                    <td>{{ \Carbon\Carbon::parse($fr->reconciliation_date)->format('M d, Y') }}</td>
                    <td>
                      @if($fr->reconciliation_type === 'bar')
                        <span class="badge badge-info"><i class="fa fa-glass"></i> COUNTER (BAR)</span>
                      @else
                        <span class="badge badge-warning"><i class="fa fa-cutlery"></i> CHEF (FOOD)</span>
                      @endif
                    </td>
                    <td><strong>TSh {{ number_format($fr->total_expected) }}</strong></td>
                    <td>
                        <strong>TSh {{ number_format($fr->total_submitted + $rowTotalPaid) }}</strong>
                        @if($rowTotalPaid > 0)
                            <br><small class="text-muted">(Incl. TSh {{ number_format($rowTotalPaid) }} paid)</small>
                        @endif
                    </td>
                    <td>TSh {{ number_format($fr->total_cash) }}</td>
                    <td>TSh {{ number_format($fr->total_mobile) }}</td>
                    <td>TSh {{ number_format($fr->total_bank ?? 0) }}</td>
                    <td>TSh {{ number_format($fr->total_card ?? 0) }}</td>
                    <td>
                      @php 
                        $netSubmitted = $fr->total_submitted + $rowTotalPaid;
                        $netDiff = $netSubmitted - $fr->total_expected; 
                      @endphp
                      @if($netDiff < 0)
                        <span class="text-danger">TSh {{ number_format($netDiff) }}</span>
                      @elseif($netDiff > 0)
                        <span class="text-success">+TSh {{ number_format($netDiff) }}</span>
                      @else
                        <span class="text-success small font-weight-bold"><i class="fa fa-check-circle"></i> Balanced</span>
                      @endif
                    </td>
                    <td>
                      @if($fr->status_indicator === 'verified')
                        <span class="badge badge-success">Verified</span>
                      @else
                        <span class="badge badge-warning">Pending</span>
                      @endif
                    </td>
                    @if($canReconcile)
                    <td>
                        @if($fr->status_indicator === 'pending')
                        <button class="btn btn-sm btn-primary perform-dept-reconcile-btn" 
                                data-date="{{ $fr->reconciliation_date->format('Y-m-d') }}" 
                                data-type="{{ $fr->reconciliation_type }}"
                                data-expected="{{ $fr->total_expected }}"
                                data-cash-recorded="{{ $fr->total_cash }}"
                                data-mobile-recorded="{{ $fr->total_mobile }}"
                                data-bank-recorded="{{ $fr->total_bank ?? 0 }}"
                                data-card-recorded="{{ $fr->total_card ?? 0 }}">
                            <i class="fa fa-check-circle"></i> Reconcile Now
                        </button>
                        @elseif($rowDiff < 0)
                        <div class="d-flex align-items-center">
                            @php
                                $totalPaid = 0;
                                if(preg_match('/\[ShortagePaidTotal:(\d+)\]/', $fr->notes ?? '', $m)) $totalPaid = (int)$m[1];
                                $isFullyPaid = $totalPaid >= abs($rowDiff);
                                $remaining = abs($rowDiff) - $totalPaid;
                                $percent = abs($rowDiff) > 0 ? round(($totalPaid / abs($rowDiff)) * 100) : 0;
                            @endphp

                            @if($isFullyPaid)
                                <span class="badge badge-success p-2 mr-2"><i class="fa fa-money"></i> Fully Paid</span>
                            @else
                                <div class="mr-2">
                                    @if($totalPaid > 0)
                                        <span class="badge badge-danger p-2 mb-1 d-block"><i class="fa fa-warning"></i> Remaining: TSh {{ number_format($remaining) }}</span>
                                        <span class="badge badge-warning p-2 d-block"><i class="fa fa-clock-o"></i> Total Paid: TSh {{ number_format($totalPaid) }} ({{ $percent }}%)</span>
                                    @else
                                        <span class="badge badge-danger p-2 d-block"><i class="fa fa-warning"></i> Shortage: TSh {{ number_format(abs($diff)) }}</span>
                                    @endif
                                </div>
                                <button class="btn btn-sm btn-success pay-shortage-btn mr-2" 
                                        data-date="{{ $fr->reconciliation_date->format('Y-m-d') }}" 
                                        data-type="{{ $fr->reconciliation_type }}"
                                        data-shortage="{{ $remaining }}"
                                        title="Pay Remaining Shortage">
                                    <i class="fa fa-money"></i> Pay {{ $totalPaid > 0 ? 'Remaining' : '' }}
                                </button>
                            @endif
                            
                            <button class="btn btn-sm btn-outline-danger btn-reopen-shift" 
                                    data-date="{{ $fr->reconciliation_date->format('Y-m-d') }}" 
                                    data-type="{{ $fr->reconciliation_type }}"
                                    title="Re-open Shift to Edit">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>
                        @else
                        <div class="d-flex align-items-center">
                            <span class="badge badge-success p-2 mr-2"><i class="fa fa-check"></i> Balanced & Verified</span>
                            <button class="btn btn-sm btn-outline-danger btn-reopen-shift" 
                                    data-date="{{ $fr->reconciliation_date->format('Y-m-d') }}" 
                                    data-type="{{ $fr->reconciliation_type }}"
                                    title="Re-open Shift to Edit">
                                <i class="fa fa-undo"></i>
                            </button>
                        </div>
                        @endif
                    </td>
                    @endif
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center py-4">No financial data found for this period</td>
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

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
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

  // Pay Shortage Logic
  $('.pay-shortage-btn').click(function() {
      const date = $(this).data('date');
      const type = $(this).data('type');
      const shortage = $(this).data('shortage');

      Swal.fire({
          title: 'Mark Shortage as Paid?',
          text: `Enter the amount received (Full amount is TSh ${shortage.toLocaleString()})`,
          input: 'number',
          inputAttributes: {
              min: 1,
              step: 1
          },
          inputValue: shortage,
          showCancelButton: true,
          confirmButtonText: 'Record Payment',
          showLoaderOnConfirm: true,
          preConfirm: (amount) => {
              return $.ajax({
                  url: "{{ route('accountant.reconciliations.pay-shortage') }}",
                  type: "POST",
                  data: {
                      _token: "{{ csrf_token() }}",
                      date: date,
                      type: type,
                      amount: amount
                  }
              }).catch(error => {
                  Swal.showValidationMessage(`Request failed: ${error.responseJSON.message}`);
              });
          },
          allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
          if (result.isConfirmed && result.value.success) {
              Swal.fire('Success!', result.value.message, 'success').then(() => location.reload());
          }
      });
  });

  // Transfer details logic (kept from original)
  $('.view-transfer-details-btn').click(function() {
    const id = $(this).data('transfer-id');
    $('#transferDetailsModal').modal('show');
    // ... AJAX call logic ...
  });
});
</script>
@endpush
