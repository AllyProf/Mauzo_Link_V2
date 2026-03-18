@extends('layouts.dashboard')

@section('title', 'Fund Issuance (Petty Cash)')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Fund Issuance (Petty Cash)</h1>
    <p>Issue funds to Chef or Stock Keeper for procurement</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="#">Accountant</a></li>
    <li class="breadcrumb-item">Fund Issuance</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">History of Issued Funds</h3>
        <button class="btn btn-primary icon-btn" type="button" data-toggle="modal" data-target="#issueFundsModal">
          <i class="fa fa-plus"></i> Issue New Funds
        </button>
      </div>
      
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered">
            <thead>
              <tr>
                <th>Date</th>
                <th>Recipient</th>
                <th>Amount</th>
                <th>Purpose</th>
                <th>Issued By</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($issues as $issue)
                <tr>
                  <td>{{ $issue->issue_date->format('M d, Y') }}</td>
                  <td><strong>{{ $issue->recipient->full_name }}</strong></td>
                  <td>TSh {{ number_format($issue->amount) }}</td>
                  <td>{{ $issue->purpose }}</td>
                  <td>{{ $issue->issuer->name }}</td>
                  <td>
                    @if($issue->status === 'issued')
                      <span class="badge badge-warning">Issued</span>
                    @elseif($issue->status === 'completed')
                      <span class="badge badge-success">Completed</span>
                    @else
                      <span class="badge badge-danger">Cancelled</span>
                    @endif
                  </td>
                  <td>
                    @if($issue->status === 'issued')
                      <button class="btn btn-sm btn-success update-status-btn" data-id="{{ $issue->id }}" data-status="completed">
                        <i class="fa fa-check"></i> Mark Completed
                      </button>
                      <button class="btn btn-sm btn-outline-danger update-status-btn" data-id="{{ $issue->id }}" data-status="cancelled">
                        <i class="fa fa-times"></i> Cancel
                      </button>
                    @else
                      ---
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center">No fund issuances recorded yet.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="d-flex justify-content-center">
            {{ $issues->links() }}
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Issue Funds Modal -->
<div class="modal fade" id="issueFundsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Issue Funds to Staff</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <form action="{{ route('accountant.fund-issuance.store') }}" method="POST">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label>Recipient Staff</label>
            <select name="staff_id" class="form-control" required>
              <option value="">-- Select Recipient --</option>
              @foreach($staffMembers as $staff)
                <option value="{{ $staff->id }}">{{ $staff->full_name }} ({{ $staff->role->name ?? 'Staff' }})</option>
              @endforeach
            </select>
          </div>
          <div class="form-group">
            <label>Amount (TSh)</label>
            <input type="number" name="amount" class="form-control" min="0" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label>Purpose / Description</label>
            <input type="text" name="purpose" class="form-control" required placeholder="e.g. Market purchase for kitchen">
          </div>
          <div class="form-group">
            <label>Issue Date</label>
            <input type="date" name="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="form-group">
            <label>Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Issue Funds Now</button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.update-status-btn').click(function() {
        const id = $(this).data('id');
        const status = $(this).data('status');
        const confirmText = status === 'completed' ? 'Mark this fund issuance as completed?' : 'Cancel this fund issuance?';
        
        if (confirm(confirmText)) {
            $.post(`{{ route('accountant.fund-issuance.update-status', ':id') }}`.replace(':id', id), {
                _token: '{{ csrf_token() }}',
                status: status
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
    });
});
</script>
@endsection
