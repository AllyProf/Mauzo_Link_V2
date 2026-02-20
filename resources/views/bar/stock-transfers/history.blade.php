@extends('layouts.dashboard')

@section('title', 'Transfer History')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-history"></i> Transfer History</h1>
    <p>View transfer history with expected and real-time generated amounts</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.stock-transfers.index') }}">Stock Transfers</a></li>
    <li class="breadcrumb-item">History</li>
  </ul>
</div>

<!-- Statistics -->
<div class="row">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-list fa-3x"></i>
      <div class="info">
        <h4>Total Transfers</h4>
        <p><b>{{ $transfers->total() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Balanced</h4>
        <p><b>{{ $transfers->where('balance_status', 'balanced')->count() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-exclamation-triangle fa-3x"></i>
      <div class="info">
        <h4>Unbalanced</h4>
        <p><b>{{ $transfers->where('balance_status', 'unbalanced')->count() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Expected</h4>
        <p><b>TSh {{ number_format($transfers->sum('expected_amount'), 2) }}</b></p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Transfer History</h3>
        <div>
          <a href="{{ route('bar.stock-transfers.index') }}" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Transfers
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if($transfers->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="historyTable">
              <thead>
                <tr>
                  <th>Transfer #</th>
                  <th>Product</th>
                  <th>Quantity</th>
                  <th>Total Bottles</th>
                  <th>Expected Amount</th>
                  <th>Real-Time Generated</th>
                  <th>Remaining</th>
                  <th>Progress</th>
                  <th>Status</th>
                  <th>Completed Date</th>
                </tr>
              </thead>
              <tbody>
                @php $lastNum = null; @endphp
                @foreach($transfers as $transfer)
                  @php
                    $isFirst = ($transfer->transfer_number !== $lastNum);
                    $lastNum = $transfer->transfer_number;
                  @endphp
                  <tr>
                    <td>
                      @if($isFirst)
                        <strong class="text-primary">{{ $transfer->transfer_number }}</strong>
                      @else
                        <span class="text-muted small" style="opacity: 0.6; font-size: 10px;">↳ batch item</span>
                      @endif
                    </td>
                    <td>
                      {{ $transfer->productVariant->product->name ?? 'N/A' }}<br>
                      <small class="text-muted">{{ $transfer->productVariant->measurement ?? '' }} - {{ $transfer->productVariant->packaging ?? '' }}</small>
                    </td>
                    <td>
                      @php
                        $pkgType = strtolower($transfer->productVariant->packaging ?? 'packages');
                        $pkgSing = rtrim($pkgType, 's');
                        if ($pkgSing == 'boxe') $pkgSing = 'box';
                        $pkgDisp = $transfer->quantity_requested == 1 ? $pkgSing : $pkgType;
                      @endphp
                      {{ $transfer->quantity_requested }} {{ ucfirst($pkgDisp) }}
                    </td>
                    <td>
                      @php
                        $uLabel = strtolower($transfer->productVariant->unit ?? 'btl');
                        if (in_array($uLabel, ['ml', 'cl', 'l'])) $uLabel = 'bottle';
                        $uDisp = $transfer->total_units == 1 ? $uLabel : str_plural($uLabel);
                      @endphp
                      {{ number_format($transfer->total_units) }} {{ $uDisp }}
                    </td>
                    <td>
                      @if($isFirst)
                        <strong class="text-primary" id="expected-amount-{{ $transfer->id }}" data-expected="{{ $transfer->expected_amount }}">
                          TSh {{ number_format($transfer->expected_amount) }}
                        </strong>
                      @else
                        <span class="text-muted italic small">-</span>
                      @endif
                    </td>
                    <td>
                      @if($isFirst)
                        <strong class="text-success" id="real-time-amount-{{ $transfer->id }}" data-real-time="{{ $transfer->real_time_amount }}">
                          TSh {{ number_format($transfer->real_time_amount, 2) }}
                        </strong>
                        <br><small class="text-muted"><i class="fa fa-refresh fa-spin"></i> Live</small>
                        <div id="real-time-breakdown-{{ $transfer->id }}">
                          @if(isset($transfer->real_time_submitted) && isset($transfer->real_time_pending))
                            <br><small class="text-info"><i class="fa fa-check-circle"></i> Sub: TSh {{ number_format($transfer->real_time_submitted, 2) }}</small>
                            @if($transfer->real_time_pending > 0)
                              <br><small class="text-warning"><i class="fa fa-clock-o"></i> Pen: TSh {{ number_format($transfer->real_time_pending, 2) }}</small>
                            @endif
                          @endif
                        </div>
                      @else
                        <span class="text-muted italic small">-</span>
                      @endif
                    </td>
                    <td>
                      @if($isFirst)
                        @php $remain = $transfer->expected_amount - $transfer->real_time_amount; @endphp
                        <strong class="text-{{ $remain > 0 ? 'warning' : 'success' }}" id="remaining-amount-{{ $transfer->id }}" data-remaining="{{ $remain }}">
                          TSh {{ number_format($remain, 2) }}
                        </strong>
                      @else
                        <span class="text-muted italic small">-</span>
                      @endif
                    </td>
                    <td>
                      @if($isFirst)
                        <div class="progress" style="height: 20px; font-size: 10px;">
                          @php $perc = 100 - $transfer->percentage_remaining; @endphp
                          <div class="progress-bar progress-bar-{{ $transfer->balance_status_class }} progress-bar-striped" 
                               style="width: {{ $perc }}%" id="progress-{{ $transfer->id }}">
                            {{ number_format($perc, 1) }}%
                          </div>
                        </div>
                      @endif
                    </td>
                    <td>
                      @if($isFirst)
                        <span class="badge badge-{{ $transfer->balance_status_class }} text-uppercase" style="font-size: 10px;">
                          {{ $transfer->balance_status_label }}
                        </span>
                      @endif
                    </td>
                    <td>{{ $transfer->updated_at->format('M d, Y H:i') }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $transfers->links() }}
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No completed transfers found. 
            <a href="{{ route('bar.stock-transfers.index') }}">View all transfers</a> to see pending and approved transfers.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<!-- Data table plugin-->
<script type="text/javascript" src="{{ asset('js/admin/plugins/jquery.dataTables.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/admin/plugins/dataTables.bootstrap.min.js') }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    // Initialize DataTable if available
    if (typeof $.fn.DataTable !== 'undefined') {
      try {
        $('#historyTable').DataTable({
          "paging": false,
          "info": false,
          "searching": true,
          "order": [[9, "desc"]], // Sort by completed date descending
        });
      } catch(e) {
        console.warn('DataTable initialization failed:', e);
      }
    }

    // Real-time amount tracking
    function updateRealTimeAmounts() {
      const transferIds = [];
      $('#historyTable tbody tr').each(function() {
        const transferId = $(this).find('[id^="real-time-amount-"]').attr('id');
        if (transferId) {
          const id = transferId.replace('real-time-amount-', '');
          transferIds.push(id);
        }
      });

      if (transferIds.length > 0) {
        $.ajax({
          url: '{{ route("bar.stock-transfers.real-time-profit") }}',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          data: {
            transfer_ids: transferIds
          },
          success: function(response) {
            if (response.success && response.data) {
              $.each(response.data, function(transferId, data) {
                const realTimeAmount = parseFloat(data.real_time_revenue || 0);
                const submittedAmount = parseFloat(data.real_time_revenue_submitted || 0);
                const pendingAmount = parseFloat(data.real_time_revenue_pending || 0);
                const expectedAmount = parseFloat(data.expected_amount);
                
                // Update real-time amount
                let html = 'TSh ' + realTimeAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                html += '<br><small class="text-muted"><i class="fa fa-refresh fa-spin"></i> Live</small>';
                $('#real-time-amount-' + transferId).html(html);
                $('#real-time-amount-' + transferId).attr('data-real-time', realTimeAmount);
                
                // Update breakdown separately
                let breakdownHtml = '';
                if (submittedAmount > 0 || pendingAmount > 0) {
                  breakdownHtml += '<br><small class="text-info"><i class="fa fa-check-circle"></i> Submitted: TSh ' + submittedAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</small>';
                  if (pendingAmount > 0) {
                    breakdownHtml += '<br><small class="text-warning"><i class="fa fa-clock-o"></i> Pending: TSh ' + pendingAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</small>';
                  }
                }
                $('#real-time-breakdown-' + transferId).html(breakdownHtml);
                
                // Calculate remaining
                const remaining = expectedAmount - realTimeAmount;
                const remainingElement = $('#remaining-amount-' + transferId);
                remainingElement.html('TSh ' + remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
                remainingElement.attr('data-remaining', remaining);
                remainingElement.removeClass('text-warning text-success').addClass(remaining > 0 ? 'text-warning' : 'text-success');
                
                // Calculate percentage
                const percentage = expectedAmount > 0 ? ((realTimeAmount / expectedAmount) * 100) : 0;
                const percentageRemaining = 100 - percentage;
                
                // Update progress bar
                const progressBar = $('#progress-' + transferId);
                const progressText = $('#progress-text-' + transferId);
                const progressDetail = $('#progress-detail-' + transferId);
                const statusBadge = $('#status-badge-' + transferId);
                
                progressBar.css('width', percentage + '%');
                progressBar.attr('aria-valuenow', percentage);
                progressBar.attr('data-percentage', percentage);
                progressText.text(percentage.toFixed(1) + '%');
                progressDetail.text(percentage.toFixed(1) + '% of expected amount');
                
                // Update status badge based on payment status
                if (submittedAmount >= expectedAmount) {
                  // Fully submitted and reconciled
                  progressBar.removeClass('progress-bar-warning progress-bar-info').addClass('progress-bar-success');
                  statusBadge.removeClass('badge-warning badge-info').addClass('badge-success');
                  statusBadge.text('Balanced');
                  statusBadge.attr('data-status', 'balanced');
                } else if (realTimeAmount >= expectedAmount) {
                  // Recorded amount meets expected but not yet submitted
                  progressBar.removeClass('progress-bar-success progress-bar-warning').addClass('progress-bar-info');
                  statusBadge.removeClass('badge-success badge-warning').addClass('badge-info');
                  statusBadge.text('Pending Reconciliation');
                  statusBadge.attr('data-status', 'pending_reconciliation');
                } else if (realTimeAmount > 0) {
                  // Some payments recorded but not enough
                  progressBar.removeClass('progress-bar-success progress-bar-info').addClass('progress-bar-warning');
                  statusBadge.removeClass('badge-success badge-info').addClass('badge-warning');
                  statusBadge.text('Partially Recorded');
                  statusBadge.attr('data-status', 'partially_recorded');
                } else {
                  // No payments recorded
                  progressBar.removeClass('progress-bar-success progress-bar-info').addClass('progress-bar-warning');
                  statusBadge.removeClass('badge-success badge-info').addClass('badge-warning');
                  statusBadge.text('Unbalanced');
                  statusBadge.attr('data-status', 'unbalanced');
                }
              });
            }
          },
          error: function(xhr) {
            console.error('Failed to update real-time amounts:', xhr);
          }
        });
      }
    }

    // Update real-time amounts every 10 seconds
    if ($('#historyTable tbody tr').length > 0) {
      updateRealTimeAmounts(); // Initial update
      setInterval(updateRealTimeAmounts, 10000); // Update every 10 seconds
    }
  });
</script>
@endsection

