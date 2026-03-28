@extends('layouts.dashboard')

@section('title', 'Daily Master Sheet')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-book"></i> Daily Master Sheet</h1>
    <p>Financial management of cash, collections, expenses and daily profit.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Accountant</a></li>
    <li class="breadcrumb-item">Master Sheet</li>
  </ul>
</div>

<div class="d-none d-print-block text-center mb-4 border-bottom pb-3">
    <h1 class="mb-0" style="letter-spacing: 2px;">MIGLOP INVESTMENT SYSTEM</h1>
    <h2 class="mb-1 text-uppercase">Daily Master Sheet Report</h2>
    <h4 class="font-weight-bold mt-2">DATE: {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</h4>
</div>

<style>
@media print {
  .app-sidebar, .app-header, .app-title, .breadcrumb, .d-print-none, .btn, .alert, .modal { display: none !important; }
  .tile { border: 1px solid #333 !important; box-shadow: none !important; margin-bottom: 20px !important; }
  .app-content { margin: 0 !important; padding: 10px !important; width: 100% !important; }
  body { background: #fff !important; font-size: 11pt !important; }
  .money-column { font-weight: bold; }
  .col-md-6 { width: 48% !important; float: left !important; margin-right: 2% !important; }
  .row { display: block !important; }
  .row:after { content: ""; display: table; clear: both; }
}
</style>

@if(request('print'))
<script>
    window.addEventListener('load', function() {
        window.print();
        // Optional: redirect back to history after print
        // setTimeout(() => { window.history.back(); }, 1000);
    });
</script>
@endif

{{-- TOP BAR: DATE & STATUS --}}
<div class="row mb-4">
  <div class="col-md-12">
    <div class="tile p-3">
      <div class="row align-items-center">
        <div class="col-md-6 border-right">
          <form method="GET" action="{{ route('accountant.daily-master-sheet') }}" class="form-inline">
            <span class="mr-2 font-weight-bold">View Date:</span>
            <input type="date" name="date" class="form-control mr-2" value="{{ $date }}" onchange="this.form.submit()">
            <button type="submit" class="btn btn-primary btn-sm mr-2"><i class="fa fa-refresh"></i></button>
            <a href="{{ route('accountant.daily-master-sheet.history') }}" class="btn btn-secondary btn-sm"><i class="fa fa-history"></i> Full History</a>
          </form>
        </div>
        <div class="col-md-6 pl-4 text-center">
          <span class="text-muted mr-2">Status:</span>
          @if($ledger->status === 'open')
            <span class="badge badge-success px-4 py-2" style="font-size:0.9rem;">OPEN & ACTIVE</span>
          @else
            <span class="badge badge-danger px-4 py-2" style="font-size:0.9rem;">CLOSED & LOCKED</span>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

@php
  $totalExpenses = $ledger->total_expenses_combined ?? $ledger->total_expenses;
  // Note: total_cash_received now represents the FULL consolidated collection (Cash + Digital)
  $totalRevenueToday = $ledger->total_cash_received; 
  $totalHandoverDigital = $ledger->total_digital_received;
  
  // Extract physical cash portion for the table display below
  $totalHandoverCash = $totalRevenueToday - $totalHandoverDigital;
  
  $stockProfit = $ledger->profit_generated;
  $totalBusinessValue = $ledger->opening_cash + $totalRevenueToday - $totalExpenses;
  $amountToCycle = $totalBusinessValue - $stockProfit;
@endphp

<div class="row">

  {{-- COLUMN 1: COLLECTIONS --}}
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title text-success"><i class="fa fa-arrow-down"></i> 1. Collections (Handovers)</h3>
      <div class="table-responsive">
        <table class="table table-sm table-hover">
          <thead class="bg-light">
            <tr>
              <th>Source</th>
              <th>Time</th>
              <th class="text-right">Cash</th>
              <th class="text-right">Digital</th>
            </tr>
          </thead>
          <tbody>
            @forelse($handovers as $h)
              @php
                $brk = is_string($h->payment_breakdown) ? json_decode($h->payment_breakdown, true) : $h->payment_breakdown;
                $hCash = is_array($brk) ? floatval($brk['cash'] ?? 0) : $h->amount;
                $hDigital = 0;
                if(is_array($brk)) {
                   foreach(['mpesa','nmb','kcb','crdb','mixx','tigo_pesa','airtel_money','halopesa'] as $m) {
                      $hDigital += floatval($brk[$m] ?? 0);
                   }
                }
              @endphp
              <tr>
                <td>{{ $h->staff->name ?? 'Staff' }} <small class="text-muted">({{ $h->department }})</small></td>
                <td>{{ $h->created_at->format('H:i') }}</td>
                <td class="text-right font-weight-bold">TSh {{ number_format($hCash) }}</td>
                <td class="text-right text-info font-weight-bold">TSh {{ number_format($hDigital) }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center py-3">No collections found.</td></tr>
            @endforelse
          </tbody>
          <tfoot class="font-weight-bold">
             <tr class="table-light">
               <td colspan="2">Today's Revenue (Handovers)</td>
               <td class="text-right text-success">TSh {{ number_format($totalHandoverCash) }}</td>
               <td class="text-right text-info">TSh {{ number_format($totalHandoverDigital) }}</td>
             </tr>
          </tfoot>
        </table>
      </div>
      
      {{-- Digital Breakdown as small pills --}}
      @if(count($digitalBreakdowns) > 0)
        <div class="mt-2 p-2 bg-light rounded">
          <small class="text-muted d-block mb-1 font-weight-bold">DIGITAL CHANNELS:</small>
          @foreach($digitalBreakdowns as $method => $amt)
            @if($amt > 0)
               <span class="badge badge-light border mr-1">
                 {{ strtoupper(str_replace('_', ' ', $method)) }}: <strong>TSh {{ number_format($amt) }}</strong>
               </span>
            @endif
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- COLUMN 2: EXPENDITURES --}}
  <div class="col-md-6">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title text-danger"><i class="fa fa-arrow-up"></i> 2. Expenditures (Expenses)</h3>
        @if($ledger->status === 'open')
          <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#expenseModal"><i class="fa fa-plus"></i> Log Expense</button>
        @endif
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead class="bg-light">
            <tr>
              <th>Expense / Purpose</th>
              <th class="text-right">Amount</th>
              <th class="text-right">Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($pettyCashIssues as $pci)
              <tr>
                <td><span class="badge badge-warning">Petty Cash</span> {{ $pci->purpose }}</td>
                <td class="text-right text-danger font-weight-bold">TSh {{ number_format($pci->amount) }}</td>
                <td></td>
              </tr>
            @endforeach
            @forelse($expenses as $exp)
              <tr id="expense-row-{{ $exp->id }}">
                <td><span class="badge badge-secondary">{{ $exp->category }}</span> {{ $exp->description }}</td>
                <td class="text-right text-danger font-weight-bold">TSh {{ number_format($exp->amount) }}</td>
                <td class="text-right">
                  @if($ledger->status === 'open')
                    <a href="javascript:void(0)" class="text-danger delete-expense-btn" data-id="{{ $exp->id }}" title="Delete Expense">
                      <i class="fa fa-trash"></i>
                    </a>
                  @endif
                </td>
              </tr>
            @empty
              @if($pettyCashIssues->isEmpty())
                <tr><td colspan="3" class="text-center py-3">No expenses logged.</td></tr>
              @endif
            @endforelse
          </tbody>
          <tfoot class="font-weight-bold">
            <tr class="table-light">
              <td>Total Outflow</td>
              <td class="text-right text-danger">− TSh {{ number_format($totalExpenses) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

</div>

<div class="row">
  {{-- FINAL RECONCILIATION & PROFIT --}}
  <div class="col-md-12">
    <div class="tile shadow-sm">
      <h3 class="tile-title border-bottom pb-2 font-weight-bold"><i class="fa fa-balance-scale"></i> 3. Final Reconciliation (Profit vs. Cycle)</h3>
      
      <div class="row">
        {{-- Summarized Math --}}
        <div class="col-md-5 border-right">
           <table class="table table-sm table-borderless">
             <tr style="font-size: 1.1rem;">
               <td><i class="fa fa-money text-success"></i> Physical Cash in Hand <small class="text-muted">(Consolidated)</small></td>
               <td class="text-right font-weight-bold">TSh {{ number_format($ledger->expected_closing_cash) }}</td>
             </tr>
             <tr style="font-size: 1.1rem;" class="text-muted">
               <td><i class="fa fa-exchange"></i> Digital Withdrawn to Cash</td>
               <td class="text-right font-weight-bold">TSh {{ number_format($totalHandoverDigital) }}</td>
             </tr>
             <tr class="border-top" style="font-size: 1.2rem;">
               <td class="pt-2"><strong>Total Physical Value</strong></td>
               <td class="text-right pt-2 text-primary font-weight-bold">TSh {{ number_format($totalBusinessValue) }}</td>
             </tr>
             <tr class="text-muted border-top">
               <td class="pt-3">Less: Profit to Boss (Cash)</td>
               <td class="text-right pt-3">− TSh {{ number_format($stockProfit) }}</td>
             </tr>
             <tr style="font-size: 1.3rem;">
               <td class="font-weight-bold text-success">Tomorrow's Cash Float</td>
               <td class="text-right text-success font-weight-bold">TSh {{ number_format($amountToCycle) }}</td>
             </tr>
           </table>
           
           <div class="alert alert-info mt-3 py-2 small">
             <i class="fa fa-info-circle"></i> <strong>Cash Audit:</strong> Today you have <strong>TSh {{ number_format($totalBusinessValue) }}</strong> in physical cash. 
             After giving <strong>TSh {{ number_format($stockProfit) }}</strong> to the boss, the remaining <strong>TSh {{ number_format($amountToCycle) }}</strong> is your float for tomorrow.
           </div>
        </div>

        {{-- Closing Form --}}
        <div class="col-md-7 pl-md-5">
          @if($ledger->status === 'open')
            <h5 class="mb-3 font-weight-bold"><i class="fa fa-check-square-o"></i> Ledger Submission</h5>
            <form id="closingForm" action="{{ route('accountant.daily-master-sheet.close') }}" method="POST">
              @csrf
              <input type="hidden" name="ledger_id" value="{{ $ledger->id }}">
              
              <div class="row form-group">
                <div class="col-md-6">
                  <label class="font-weight-bold small uppercase text-muted">ACTUAL CASH IN DRAWER</label>
                  <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text badge-primary border-0"><i class="fa fa-money text-white"></i></span></div>
                    <input class="form-control" type="number" name="actual_closing_cash" id="actual_closing" value="{{ round($ledger->expected_closing_cash) }}" required readonly style="background:#f8f9fa;">
                  </div>
                </div>
                <div class="col-md-6">
                   <label class="font-weight-bold small uppercase text-muted">PROFIT TO GIVE BOSS</label>
                   <div class="input-group">
                    <div class="input-group-prepend"><span class="input-group-text badge-info border-0"><i class="fa fa-bank text-white"></i></span></div>
                    <input class="form-control" type="number" name="profit_submitted_to_boss" id="profit_boss" value="{{ round($stockProfit) }}" required readonly style="background:#f8f9fa;">
                  </div>
                </div>
              </div>

              <div class="row form-group mt-3">
                <div class="col-md-12">
                   <label class="font-weight-bold small uppercase text-muted text-success">TOMORROW'S CASH FLOAT (Operating Fund)</label>
                   <div class="input-group border border-success rounded">
                    <div class="input-group-prepend"><span class="input-group-text badge-success border-0 px-3"><strong>TSh</strong></span></div>
                    <input class="form-control font-weight-bold text-success border-0" type="number" name="carried_forward" id="cycle_forward" value="{{ round($ledger->expected_closing_cash - $stockProfit) }}" required readonly style="background:#fff; font-size:1.2rem;">
                  </div>
                  <small class="text-success italic"><i class="fa fa-lightbulb-o"></i> This is the actual physical cash remaining in the drawer after profit distribution.</small>
                </div>
              </div>

              <div class="text-right mt-4">
                <button class="btn btn-primary px-5 py-2" type="submit">
                  <i class="fa fa-save"></i> <strong>FINAL SUBMIT &amp; LOCK</strong>
                </button>
              </div>
            </form>
          @else
            {{-- Locked summary --}}
            <div class="tile p-3 bg-light border">
               <h5 class="text-center mb-4"><i class="fa fa-check-circle text-success font-weight-bold"></i> DAY FINALIZED</h5>
               <div class="row text-center">
                  <div class="col-4">
                    <small class="text-muted uppercase font-weight-bold">Closing Cash</small><br>
                    <span class="h5">TSh {{ number_format($ledger->actual_closing_cash) }}</span>
                  </div>
                  <div class="col-4">
                    <small class="text-muted uppercase font-weight-bold">Profit to Boss</small><br>
                    <span class="h5 text-info">TSh {{ number_format($ledger->profit_submitted_to_boss ?? 0) }}</span>
                  </div>
                  <div class="col-4">
                    <small class="text-muted uppercase font-weight-bold">Tomorrow's Float</small><br>
                    <span class="h5 text-success">TSh {{ number_format($ledger->carried_forward) }}</span>
                  </div>
               </div>
               <hr>
               <div class="text-center small text-muted">Closed by <strong>{{ $ledger->accountant->name ?? 'System' }}</strong> at {{ $ledger->closed_at?->format('h:i A') ?? '—' }}</div>
               <div class="mt-4 d-print-none">
                  <button class="btn btn-dark btn-block" onclick="window.print()">
                    <i class="fa fa-print"></i> <strong>PRINT FINANCIAL SUMMARY</strong>
                  </button>
               </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add Expense Modal --}}
<div class="modal fade" id="expenseModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:8px;">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa fa-minus-circle"></i> Log Cash Outflow</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form id="expenseForm">
        @csrf
        <input type="hidden" name="ledger_id" value="{{ $ledger->id }}">
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold">CATEGORY</label>
            <input type="text" name="category" class="form-control" list="expenseCategories" placeholder="Select or type category..." required autocomplete="off">
            <datalist id="expenseCategories">
              <option value="Restocking / Procurement">
              <option value="Transport / Fare">
              <option value="Utilities (Water, Electricity)">
              <option value="Staff Meals/Allowances">
              <option value="Cleaning & Maintenance">
              <option value="Miscellaneous">
            </datalist>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold">DESCRIPTION</label>
            <input type="text" name="description" class="form-control" placeholder="What was the money for?" required>
          </div>
          <div class="form-group">
            <label class="font-weight-bold">AMOUNT (TSh)</label>
            <input type="number" name="amount" class="form-control" placeholder="0" required min="1">
            <small class="text-muted italic">This will be subtracted from Expected Physical Cash.</small>
          </div>
        </div>
        <div class="modal-footer bg-light">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" id="submitExpenseBtn">Confirm Expense</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
  $('#expenseForm').on('submit', function(e) {
    e.preventDefault();
    const $btn = $('#submitExpenseBtn');
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
    $.ajax({
      url: "{{ route('accountant.daily-master-sheet.expense') }}",
      method: "POST",
      data: $(this).serialize(),
      success: function(response) {
        if(response.success) { location.reload(); }
        else { 
           Swal.fire('Control Error', response.error || 'Failed to log expense', 'error');
           $btn.prop('disabled', false).html('Confirm Expense'); 
        }
      },
      error: function(xhr) { 
        let errorMsg = 'Connection error.';
        if(xhr.responseJSON && xhr.responseJSON.error) errorMsg = xhr.responseJSON.error;
        Swal.fire('Validation Failed', errorMsg, 'error');
        $btn.prop('disabled', false).html('Confirm Expense'); 
      }
    });
  });

  $('.delete-expense-btn').click(function() {
      const id = $(this).data('id');
      Swal.fire({
          title: 'Delete this expense?',
          text: "Are you sure you want to remove this record?",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
          if(result.isConfirmed) {
              $.post("{{ url('accountant/daily-master-sheet/expense') }}/" + id + "/delete", { _token: "{{ csrf_token() }}" }, function(res) {
                  if(res.success) {
                      location.reload();
                  } else {
                      Swal.fire('Error', res.error, 'error');
                  }
              });
          }
      });
  });

  // Balanced Split Logic
  const $actualCash = $('#actual_closing');
  const $profitBoss = $('#profit_boss');
  const $cycleForward = $('#cycle_forward');

  function updateFields() {
    const cash = parseFloat($actualCash.val()) || 0;
    const profit = parseFloat($profitBoss.val()) || 0;
    
    // Formula: Tomorrow's Cycle = Cash in hand - What we give to Boss
    // (Note: This 'cash' already includes any withdrawn digital funds)
    const remaining = cash - profit;
    $cycleForward.val(remaining);
    
    // Safety cue: if remaining is negative, something is wrong
    if (remaining < 0) {
      $cycleForward.addClass('text-danger font-weight-bold');
    } else {
      $cycleForward.removeClass('text-danger font-weight-bold');
    }
  }

  $actualCash.on('input', updateFields);
  $profitBoss.on('input', updateFields);

  // Final Submit with SweetAlert
  $('#closingForm').on('submit', function(e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('button[type="submit"]');

    Swal.fire({
      title: 'Finalize Today?',
      text: "You are about to lock the financial records for this day. This cannot be undone!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Submit & Lock!'
    }).then((result) => {
      if (result.isConfirmed) {
        $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Locking...');
        
        $.ajax({
          url: $form.attr('action'),
          method: 'POST',
          data: $form.serialize(),
          success: function(response) {
            if(response.success) {
               Swal.fire({
                 toast: true,
                 position: 'top-end',
                 showConfirmButton: false,
                 timer: 3000,
                 timerProgressBar: true,
                 icon: 'success',
                 title: 'Success!',
                 text: response.message
               });
               
               setTimeout(() => {
                 window.location.reload();
               }, 2000);
            } else {
               Swal.fire('Error', response.error || 'Submission failed', 'error');
               $btn.prop('disabled', false).html('<i class="fa fa-save"></i> <strong>FINAL SUBMIT & LOCK</strong>');
            }
          },
          error: function() {
            Swal.fire('Error', 'Connection failed.', 'error');
            $btn.prop('disabled', false).html('<i class="fa fa-save"></i> <strong>FINAL SUBMIT & LOCK</strong>');
          }
        });
      }
    });
  });
});
</script>
@endpush
