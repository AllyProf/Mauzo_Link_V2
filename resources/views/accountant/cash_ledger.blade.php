@extends('layouts.dashboard')

@section('title', 'Cash Drawer Ledger')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-book"></i> Cash Drawer Ledger</h1>
    <p>Financial Audit: Physical Cash Movements Only</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('accountant.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Cash Ledger</li>
  </ul>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong><i class="fa fa-check-circle"></i> Success!</strong> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

@if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <strong><i class="fa fa-exclamation-triangle"></i> Error!</strong> {{ session('error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

<!-- Header Actions -->
<div class="row mb-3">
  <div class="col-md-8">
    <div class="tile p-3 mb-0">
      <form method="GET" action="{{ route('accountant.cash-ledger') }}" class="form-inline">
        <label for="date" class="mr-2">Audit Date:</label>
        <input type="date" name="date" id="date" class="form-control mr-3" value="{{ $date }}" required>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Refresh Ledger
        </button>
      </form>
    </div>
  </div>
  <div class="col-md-4">
     <button class="btn btn-success btn-block h-100" data-toggle="modal" data-target="#topupModal">
        <i class="fa fa-plus-circle"></i> Record Cash Injection / Top-up
     </button>
  </div>
</div>

<!-- Summary Cards -->
<div class="row">
  <div class="col-md-4">
    <div class="widget-small success coloured-icon"><i class="icon fa fa-arrow-down fa-3x"></i>
      <div class="info">
        <h4>Total Cash Received</h4>
        <p><b>TSh {{ number_format($totalIn) }}</b></p>
        <small class="text-muted">Staff Handovers + Top-ups</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small danger coloured-icon"><i class="icon fa fa-arrow-up fa-3x"></i>
      <div class="info">
        <h4>Total Cash Issued</h4>
        <p><b>TSh {{ number_format($totalOut) }}</b></p>
        <small class="text-muted">Purchases & Petty Cash</small>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small info coloured-icon"><i class="icon fa fa-balance-scale fa-3x"></i>
      <div class="info">
        <h4>Net Drawer Balance</h4>
        <p><b>TSh {{ number_format($netCash) }}</b></p>
        @if($netCash < 0)
          <small class="text-danger font-weight-bold">Drawer Shortage!</small>
        @endif
      </div>
    </div>
  </div>
</div>

<div class="row">
  <!-- Detailed Inflows -->
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title text-success"><i class="fa fa-plus-circle"></i> Cash Inflows (Paper Money)</h3>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Time</th>
              <th>Source</th>
              <th>Cash Amount</th>
            </tr>
          </thead>
          <tbody>
            {{-- Staff Reconciliations --}}
            @foreach($cashIn as $in)
              <tr>
                <td>{{ \Carbon\Carbon::parse($in->verified_at)->format('H:i') }}</td>
                <td>
                  <strong>{{ $in->waiter->full_name ?? 'Staff' }}</strong><br>
                  <small>Collection Portion</small>
                </td>
                <td class="text-success">TSh {{ number_format($in->cash_collected) }}</td>
              </tr>
            @endforeach

            {{-- Manual Topups --}}
            @foreach($topups as $tp)
              <tr class="table-info">
                <td>{{ $tp->created_at->format('H:i') }}</td>
                <td>
                  <strong>{{ $tp->source }}</strong><br>
                  <small>{{ $tp->notes }}</small>
                </td>
                <td class="text-success font-weight-bold">TSh {{ number_format($tp->amount) }}</td>
              </tr>
            @endforeach

            @if($cashIn->isEmpty() && $topups->isEmpty())
              <tr>
                <td colspan="3" class="text-center text-muted">No inflows recorded.</td>
              </tr>
            @endif
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Detailed Outflows -->
  <div class="col-md-6">
    <div class="tile">
      <h3 class="tile-title text-danger"><i class="fa fa-minus-circle"></i> Cash Outflows (Spent)</h3>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Time</th>
              <th>Purpose / Recipient</th>
              <th>Amount</th>
            </tr>
          </thead>
          <tbody>
            @forelse($cashOut as $out)
              <tr>
                <td>{{ $out->created_at->format('H:i') }}</td>
                <td>
                  <strong>{{ $out->purpose }}</strong><br>
                  <small>Recipient: {{ $out->recipient->full_name ?? 'N/A' }}</small>
                </td>
                <td class="text-danger font-weight-bold">TSh {{ number_format($out->amount) }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted">No money issued.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Handover Section -->
<div class="row mt-4">
  <div class="col-md-12">
    @if(session('success'))
      <div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> {{ session('success') }}</div>
    @endif

    <div class="tile">
      <h3 class="tile-title text-primary"><i class="fa fa-handshake-o"></i> Final Daily Handover to Boss</h3>
      
      @if(!$handover)
        <div class="row">
          <div class="col-md-8">
            <p>Ready to settle? Hand over the net cash balance to the Owner.</p>
            <div class="alert alert-info py-2">
              Remaining Physical Cash: <span class="h4 font-weight-bold" id="handover_amount_formatted">TSh {{ number_format($netCash) }}</span>
            </div>
          </div>
          <div class="col-md-4">
            <form action="{{ route('accountant.cash-ledger.handover') }}" method="POST" onsubmit="return confirm('Are you sure?')">
              @csrf
              <input type="hidden" name="date" value="{{ $date }}">
              <input type="hidden" name="amount" value="{{ $netCash }}">
              <button type="submit" class="btn btn-primary btn-block btn-lg" {{ $netCash <= 0 ? 'disabled' : '' }}>
                Handover to Boss
              </button>
            </form>
          </div>
        </div>
      @else
        <div class="alert alert-success m-0">
          <i class="fa fa-check-circle"></i> Handover of **TSh {{ number_format($handover->amount) }}** is **{{ $handover->status }}**.
        </div>
      @endif
    </div>
  </div>
</div>
{{-- Staff Handovers to Accountant Section --}}
@if($staffHandovers->count() > 0)
<div class="row mt-4">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title text-warning"><i class="fa fa-exchange"></i> Staff Cash Handovers to You</h3>
      <p class="text-muted">Cash physically handed over by Chefs and Counter staff to you for this date.</p>
      <div class="table-responsive">
        <table class="table table-hover table-sm">
          <thead class="thead-light">
            <tr>
              <th>Staff</th>
              <th>Department</th>
              <th>Amount</th>
              <th>Notes</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($staffHandovers as $sh)
              <tr>
                <td>
                  <strong>{{ $sh->staff->name ?? 'Unknown' }}</strong><br>
                  <small class="text-muted">{{ $sh->staff->role->name ?? '' }}</small>
                </td>
                <td>
                  @if($sh->department === 'food')
                    <span class="badge badge-info"><i class="fa fa-cutlery"></i> Food (Kitchen)</span>
                  @elseif($sh->department === 'bar')
                    <span class="badge badge-primary"><i class="fa fa-glass"></i> Bar (Counter)</span>
                  @else
                    <span class="badge badge-secondary">{{ ucfirst($sh->department ?? 'N/A') }}</span>
                  @endif
                </td>
                <td class="font-weight-bold text-success">TSh {{ number_format($sh->amount, 0) }}</td>
                <td>{{ $sh->notes ?? '—' }}</td>
                <td>
                  @if($sh->status === 'pending')
                    <span class="badge badge-warning">Pending</span>
                  @elseif($sh->status === 'confirmed')
                    <span class="badge badge-success">Confirmed</span>
                  @endif
                </td>
                <td>
                  @if($sh->status === 'pending')
                    <form action="{{ route('accountant.confirm-staff-handover', $sh->id) }}" method="POST"
                          onsubmit="return confirm('Confirm receiving TSh {{ number_format($sh->amount, 0) }} from {{ $sh->staff->name ?? 'this staff' }}?')">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-success">
                        <i class="fa fa-check"></i> Confirm Receipt
                      </button>
                    </form>
                  @else
                    <small class="text-muted">{{ $sh->confirmed_at ? \Carbon\Carbon::parse($sh->confirmed_at)->format('H:i') : '—' }}</small>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="table-light font-weight-bold">
              <td colspan="2">Total Staff Handovers</td>
              <td class="text-success">TSh {{ number_format($totalStaffHandovers, 0) }}</td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>
</div>
@endif

<div class="modal fade" id="topupModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form action="{{ route('accountant.cash-ledger.topup') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Record Cash Injection / Top-up</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-content p-3">
           <input type="hidden" name="topup_date" value="{{ $date }}">
           
           <div class="form-group">
             <label>Amount (Cash):</label>
             <input type="number" name="amount" class="form-control" required min="1">
           </div>

           <div class="form-group">
             <label>Source of Funds:</label>
             <select name="source" class="form-control" required>
                <option value="Starting Float">Starting Float (From Safe)</option>
                <option value="Withdrawal from Bank">Withdrawal from Bank</option>
                <option value="Mobile Money Withdrawal">Mobile Money Withdrawal</option>
                <option value="Owner Deposit">Owner Deposit</option>
             </select>
           </div>

           <div class="form-group">
             <label>Notes (Optional):</label>
             <textarea name="notes" class="form-control" placeholder="Check number, M-Pesa code etc."></textarea>
           </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-success">Save Cash Addition</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection
