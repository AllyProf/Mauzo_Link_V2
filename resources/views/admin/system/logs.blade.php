@extends('layouts.dashboard')

@section('title', 'System Activity Logs')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-history"></i> System Activity Logs</h1>
    <p>Human-readable tracking of recent system events</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item">System Monitor</li>
    <li class="breadcrumb-item">Logs</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">History Audit</h3>
        <div class="btn-group">
            <a class="btn btn-primary icon-btn" href="{{ url()->current() }}"><i class="fa fa-refresh"></i> Refresh</a>
            <button class="btn btn-secondary icon-btn" onclick="toggleRaw()"><i class="fa fa-code"></i> Toggle Tech View</button>
        </div>
      </div>
      
      <!-- Filters Section -->
      <div class="row mb-4 p-3 border rounded bg-light mx-1 shadow-sm">
          <div class="col-md-3">
              <label><strong>Importance:</strong></label>
              <select id="levelFilter" class="form-control">
                  <option value="">All Levels</option>
                  <option value="INFO">Info (Normal)</option>
                  <option value="WARNING">Warning (Caution)</option>
                  <option value="ERROR">Error (Critical)</option>
              </select>
          </div>
          <div class="col-md-3">
              <label><strong>From Date:</strong></label>
              <input type="date" id="startDate" class="form-control" placeholder="Select Start">
          </div>
          <div class="col-md-3">
              <label><strong>To Date:</strong></label>
              <input type="date" id="endDate" class="form-control" placeholder="Select End">
          </div>
          <div class="col-md-3 align-self-end">
              <button class="btn btn-danger btn-block" onclick="resetFilters()"><i class="fa fa-times"></i> Reset All</button>
          </div>
      </div>

      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="logTable">
            <thead>
              <tr>
                <th width="150">Timestamp</th>
                <th width="180">Action</th>
                <th>Details & Context</th>
                <th width="100">Level</th>
                <th class="d-none">DateHelper</th> {{-- Hidden column for filtering --}}
              </tr>
            </thead>
            <tbody>
              @foreach($parsedLogs as $log)
                @php
                    $rawTimestamp = strtotime($log['exact_time']);
                    $dateOnly = date('Y-m-d', $rawTimestamp);
                @endphp
                <tr class="{{ $log['class'] }}">
                  <td data-order="{{ $rawTimestamp }}">
                    <strong>{{ $log['time'] }}</strong><br>
                    <small class="text-muted">{{ $log['exact_time'] }}</small>
                  </td>
                  <td>
                    <i class="fa {{ $log['icon'] }} mr-2"></i>
                    <strong>{{ $log['action'] }}</strong>
                  </td>
                  <td>
                    {{ $log['details'] }}
                    <div class="raw-log text-muted small mt-2 d-none" style="font-family: 'Courier New', Courier, monospace; background: #fdfdfd; padding: 10px; border: 1px solid #eee; border-radius: 4px; overflow-x: auto;">
                        <code>{{ $log['raw'] }}</code>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge {{ trim($log['level']) == 'ERROR' ? 'badge-danger' : (trim($log['level']) == 'WARNING' ? 'badge-warning' : 'badge-info') }}">
                        {{ trim($log['level']) }}
                    </span>
                  </td>
                  <td class="d-none">{{ $dateOnly }}</td> {{-- Hidden column 4 --}}
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* Custom DataTables styling to match theme */
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: #940000 !important;
    color: white !important;
    border: 1px solid #940000 !important;
}
.dataTables_wrapper .dataTables_filter input {
    border-radius: 4px;
    border: 1px solid #ddd;
    padding: 5px 10px;
}
.table-success { background-color: #f0fff4 !important; }
.table-danger { background-color: #fff5f5 !important; }
.table-warning { background-color: #fffaf0 !important; }
label { font-weight: bold; margin-bottom: 5px; color: #333; }
</style>

@section('scripts')
<!-- DataTables -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Initialise DataTables
    var logTable = $('#logTable').DataTable({
        "order": [[0, "desc"]], // Time descending
        "pageLength": 50,
        "language": {
            "search": "Keyword Search:",
            "lengthMenu": "Get _MENU_ records"
        }
    });

    // 2. Custom Date Filter - MUST be pushed before draw() calls
    $.fn.dataTable.ext.search.push(
        function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'logTable') return true;

            var startDate = $('#startDate').val(); // YYYY-MM-DD
            var endDate = $('#endDate').val();
            var rowDate = data[4]; // HIDDEN Column 4

            if (!rowDate) return true;

            if (startDate && rowDate < startDate) return false;
            if (endDate && rowDate > endDate) return false;
            
            return true;
        }
    );

    // 3. LISTENERS
    
    // Level Filter
    $('#levelFilter').on('change', function() {
        var val = $(this).val();
        logTable.column(3).search(val ? '^' + val + '$' : '', true, false).draw();
    });

    // Date Filters
    $('#startDate, #endDate').on('change', function() {
        logTable.draw();
    });

    // Global Reset
    window.resetFilters = function() {
        $('#levelFilter').val('');
        $('#startDate').val('');
        $('#endDate').val('');
        logTable.column(3).search('').draw();
        logTable.draw();
    };
});

function toggleRaw() {
    $('.raw-log').toggleClass('d-none');
}
</script>
@endsection
@endsection
