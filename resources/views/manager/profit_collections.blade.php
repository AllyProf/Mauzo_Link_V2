@extends('layouts.dashboard')

@section('title', 'Profit Collections')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Profit Collections</h1>
    <p>Verify and confirm receipt of business profits from accountants.</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('manager.master-sheet.analytics') }}">Master Sheet</a></li>
    <li class="breadcrumb-item">Collections</li>
  </ul>
</div>

{{-- STATISTICS ROW --}}
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon"><i class="icon fa fa-clock-o fa-3x"></i>
      <div class="info">
        <h4>Pending</h4>
        <p><b>TSh {{ number_format($collectionStats['total_pending']) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon"><i class="icon fa fa-calendar-check-o fa-3x"></i>
      <div class="info">
        <h4>Received (Month)</h4>
        <p><b>TSh {{ number_format($collectionStats['total_received_month']) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon"><i class="icon fa fa-line-chart fa-3x"></i>
      <div class="info">
        <h4>Avg/Day</h4>
        <p><b>TSh {{ number_format($collectionStats['avg_collection']) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon"><i class="icon fa fa-files-o fa-3x"></i>
      <div class="info">
        <h4>Tot. Days</h4>
        <p><b>{{ $collectionStats['count_all'] }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  {{-- PENDING COLLECTIONS --}}
  <div class="col-md-12 mb-4">
    <div class="tile">
      <h3 class="tile-title text-danger"><i class="fa fa-clock-o"></i> Pending for Verification</h3>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead class="bg-light text-center">
              <tr>
                <th>Handover Date</th>
                <th>Source</th>
                <th>Submitted By</th>
                <th>Amount (TSh)</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody class="text-center">
               @forelse($pendingCollections as $handover)
                  <tr>
                     <td class="font-weight-bold">{{ $handover->handover_date->format('d M, Y') }}</td>
                     <td><span class="badge badge-info">Master Sheet Profit</span></td>
                     <td>{{ $handover->staff->full_name ?? 'Accountant' }}</td>
                     <td class="text-primary font-weight-bold" style="font-size: 1.1em;">{{ number_format($handover->amount) }}</td>
                     <td>
                        <form action="{{ route('manager.master-sheet.confirm-handover', $handover->id) }}" method="POST" id="confirm-form-{{ $handover->id }}">
                           @csrf
                           <button type="button" class="btn btn-success btn-sm" onclick="confirmReceipt({{ $handover->id }}, '{{ number_format($handover->amount) }}')">
                              <i class="fa fa-check-circle"></i> Mark as Received
                           </button>
                        </form>
                     </td>
                  </tr>
               @empty
                  <tr>
                    <td colspan="5" class="py-5 text-muted">
                        <i class="fa fa-check-circle-o fa-3x d-block mb-3"></i>
                        No pending collections. All business profits have been verified and received.
                    </td>
                  </tr>
               @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- RECENTLY RECEIVED --}}
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title text-success"><i class="fa fa-history"></i> Recently Received History</h3>
      <div class="tile-body text-center">
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead>
              <tr>
                <th>Ledger Date</th>
                <th>Confirmed At</th>
                <th>From Accountant</th>
                <th>Confirmed By</th>
                <th class="text-right">Amount (TSh)</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($receivedCollections as $received)
                <tr>
                  <td>{{ $received->handover_date->format('d/m/Y') }}</td>
                  <td>{{ $received->confirmed_at->format('d M, H:i') }}</td>
                  <td>{{ $received->staff->full_name ?? 'N/A' }}</td>
                  <td>Manager</td>
                  <td class="text-right font-weight-bold text-success">{{ number_format($received->amount) }}</td>
                  <td><span class="badge badge-success">Verified</span></td>
                  <td>
                      <form action="{{ route('manager.master-sheet.reset-handover', $received->id) }}" method="POST" id="reset-form-{{ $received->id }}">
                         @csrf
                         <button type="button" class="btn btn-sm btn-outline-danger" onclick="resetReceipt({{ $received->id }}, '{{ $received->handover_date->format('d M') }}')">
                            <i class="fa fa-undo"></i> Reset
                         </button>
                      </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-muted italic py-3">No collection history found for the current period.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  function confirmReceipt(id, amount) {
      showConfirm(
          "Are you sure you have physically received TSh " + amount + " from the accountant?",
          "Confirm Profit Receipt?",
          function() {
              document.getElementById('confirm-form-' + id).submit();
          }
      );
  }

  function resetReceipt(id, date) {
      showConfirm(
          "Do you want to reset the verification for " + date + "? This will move the amount back to pending list.",
          "Undo Confirmation?",
          function() {
              document.getElementById('reset-form-' + id).submit();
          }
      );
  }
</script>
@endsection
