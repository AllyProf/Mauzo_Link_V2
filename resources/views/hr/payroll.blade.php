@extends('layouts.dashboard')

@section('title', 'Payroll Management')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-money"></i> Payroll Management</h1>
    <p>Manage staff payroll and salaries</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hr.dashboard') }}">HR</a></li>
    <li class="breadcrumb-item">Payroll</li>
  </ul>
</div>

<!-- Filters -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('hr.payroll') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="month" class="mr-2">Month:</label>
          <select name="month" id="month" class="form-control">
            @for($i = 1; $i <= 12; $i++)
              <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}</option>
            @endfor
          </select>
        </div>
        <div class="form-group mr-3">
          <label for="year" class="mr-2">Year:</label>
          <input type="number" name="year" id="year" class="form-control" value="{{ $year }}" min="2020" max="2100" required>
        </div>
        <div class="form-group mr-3">
          <label for="staff_id" class="mr-2">Staff:</label>
          <select name="staff_id" id="staff_id" class="form-control">
            <option value="">All Staff</option>
            @foreach($staff as $s)
              <option value="{{ $s->id }}" {{ $staffId == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
            @endforeach
          </select>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> Filter
        </button>
        <button type="button" class="btn btn-success ml-2" id="generate-payroll-btn">
          <i class="fa fa-plus"></i> Generate Payroll
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
  <div class="col-md-4">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-money fa-3x"></i>
      <div class="info">
        <h4>Total Gross</h4>
        <p><b>TSh {{ number_format($totalGross, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-minus-circle fa-3x"></i>
      <div class="info">
        <h4>Total Deductions</h4>
        <p><b>TSh {{ number_format($totalDeductions, 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-check-circle fa-3x"></i>
      <div class="info">
        <h4>Total Net</h4>
        <p><b>TSh {{ number_format($totalNet, 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Payroll Table -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Payroll - {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</h3>
      <div class="tile-body">
        @if($payrolls->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Staff</th>
                  <th>Basic Salary</th>
                  <th>Allowances</th>
                  <th>Deductions</th>
                  <th>Overtime</th>
                  <th>Bonus</th>
                  <th>Gross</th>
                  <th>Net</th>
                  <th>Payment Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($payrolls as $payroll)
                <tr>
                  <td>{{ $payroll->staff->full_name }}</td>
                  <td>TSh {{ number_format($payroll->basic_salary, 0) }}</td>
                  <td>
                    @if($payroll->allowances && count($payroll->allowances) > 0)
                      @foreach($payroll->allowances as $allowance)
                        <small>{{ $allowance['name'] }}: TSh {{ number_format($allowance['amount'], 0) }}</small><br>
                      @endforeach
                    @else
                      -
                    @endif
                  </td>
                  <td>
                    @if($payroll->deductions && count($payroll->deductions) > 0)
                      @foreach($payroll->deductions as $deduction)
                        <small>{{ $deduction['name'] }}: TSh {{ number_format($deduction['amount'], 0) }}</small><br>
                      @endforeach
                    @else
                      -
                    @endif
                  </td>
                  <td>TSh {{ number_format($payroll->overtime_amount, 0) }}</td>
                  <td>TSh {{ number_format($payroll->bonus, 0) }}</td>
                  <td><strong>TSh {{ number_format($payroll->gross_salary, 0) }}</strong></td>
                  <td><strong class="text-success">TSh {{ number_format($payroll->net_salary, 0) }}</strong></td>
                  <td>
                    @if($payroll->payment_status === 'paid')
                      <span class="badge badge-success">Paid</span>
                    @elseif($payroll->payment_status === 'pending')
                      <span class="badge badge-warning">Pending</span>
                    @else
                      <span class="badge badge-danger">Failed</span>
                    @endif
                  </td>
                  <td>
                    <button class="btn btn-sm btn-info view-payroll-btn" data-payroll-id="{{ $payroll->id }}">
                      <i class="fa fa-eye"></i> View
                    </button>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No payroll records found for this period.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Generate Payroll Modal -->
<div class="modal fade" id="generatePayrollModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Generate Payroll</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="generatePayrollForm">
        <div class="modal-body">
          <div class="form-group">
            <label>Staff <span class="text-danger">*</span></label>
            <select name="staff_id" id="payroll_staff_id" class="form-control" required>
              <option value="">Select Staff</option>
              @foreach($staff as $s)
                <option value="{{ $s->id }}">{{ $s->full_name }} ({{ $s->staff_id }})</option>
              @endforeach
            </select>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Month <span class="text-danger">*</span></label>
                <select name="payroll_month" id="payroll_month" class="form-control" required>
                  @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}</option>
                  @endfor
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Year <span class="text-danger">*</span></label>
                <input type="number" name="payroll_year" id="payroll_year" class="form-control" value="{{ $year }}" min="2020" max="2100" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Basic Salary (TSh) <span class="text-danger">*</span></label>
            <input type="number" name="basic_salary" id="basic_salary" class="form-control" step="0.01" min="0" required>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Overtime Hours</label>
                <input type="number" name="overtime_hours" id="overtime_hours" class="form-control" step="0.01" min="0" value="0">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Overtime Rate (TSh/hour)</label>
                <input type="number" name="overtime_rate" id="overtime_rate" class="form-control" step="0.01" min="0" value="0">
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Bonus (TSh)</label>
            <input type="number" name="bonus" id="bonus" class="form-control" step="0.01" min="0" value="0">
          </div>
          <div class="form-group">
            <label>Advance Payment (TSh)</label>
            <input type="number" name="advance_payment" id="advance_payment" class="form-control" step="0.01" min="0" value="0">
          </div>
          <div class="form-group">
            <label>Allowances</label>
            <div id="allowances-container">
              <div class="allowance-item mb-2">
                <div class="row">
                  <div class="col-md-6">
                    <input type="text" class="form-control allowance-name" placeholder="Allowance Name (e.g., Transport)">
                  </div>
                  <div class="col-md-5">
                    <input type="number" class="form-control allowance-amount" step="0.01" min="0" placeholder="Amount (TSh)">
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-allowance" style="display:none;">
                      <i class="fa fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-info mt-2" id="add-allowance">
              <i class="fa fa-plus"></i> Add Allowance
            </button>
          </div>
          <div class="form-group">
            <label>Deductions</label>
            <div id="deductions-container">
              <div class="deduction-item mb-2">
                <div class="row">
                  <div class="col-md-6">
                    <input type="text" class="form-control deduction-name" placeholder="Deduction Name (e.g., Tax)">
                  </div>
                  <div class="col-md-5">
                    <input type="number" class="form-control deduction-amount" step="0.01" min="0" placeholder="Amount (TSh)">
                  </div>
                  <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-danger remove-deduction" style="display:none;">
                      <i class="fa fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-info mt-2" id="add-deduction">
              <i class="fa fa-plus"></i> Add Deduction
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Generate Payroll</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Open generate payroll modal
  $('#generate-payroll-btn').on('click', function() {
    $('#generatePayrollForm')[0].reset();
    $('#payroll_month').val('{{ $month }}');
    $('#payroll_year').val('{{ $year }}');
    $('#generatePayrollModal').modal('show');
  });

  // Submit generate payroll form
  $('#generatePayrollForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
      _token: '{{ csrf_token() }}',
      staff_id: $('#payroll_staff_id').val(),
      payroll_month: $('#payroll_month').val(),
      payroll_year: $('#payroll_year').val(),
      basic_salary: $('#basic_salary').val(),
      overtime_hours: $('#overtime_hours').val() || 0,
      overtime_rate: $('#overtime_rate').val() || 0,
      bonus: $('#bonus').val() || 0,
      advance_payment: $('#advance_payment').val() || 0,
    };

    // Collect allowances
    const allowances = [];
    $('.allowance-item').each(function() {
      const name = $(this).find('.allowance-name').val().trim();
      const amount = parseFloat($(this).find('.allowance-amount').val()) || 0;
      if (name && amount > 0) {
        allowances.push({ name: name, amount: amount });
      }
    });
    if (allowances.length > 0) {
      formData.allowances = allowances;
    }

    // Collect deductions
    const deductions = [];
    $('.deduction-item').each(function() {
      const name = $(this).find('.deduction-name').val().trim();
      const amount = parseFloat($(this).find('.deduction-amount').val()) || 0;
      if (name && amount > 0) {
        deductions.push({ name: name, amount: amount });
      }
    });
    if (deductions.length > 0) {
      formData.deductions = deductions;
    }

    // Disable submit button
    const submitBtn = $(this).find('button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');

    $.ajax({
      url: '{{ route("hr.payroll.generate") }}',
      method: 'POST',
      data: formData,
      success: function(response) {
        if (response.success) {
          Swal.fire('Success!', response.message || 'Payroll generated successfully.', 'success').then(() => {
            location.reload();
          });
        } else {
          Swal.fire('Error', response.error || 'Failed to generate payroll', 'error');
        }
      },
      error: function(xhr) {
        let errorMsg = 'Failed to generate payroll';
        if (xhr.responseJSON && xhr.responseJSON.error) {
          errorMsg = xhr.responseJSON.error;
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        }
        Swal.fire('Error', errorMsg, 'error');
      },
      complete: function() {
        submitBtn.prop('disabled', false).html('Generate Payroll');
      }
    });
  });

  // Add allowance
  $('#add-allowance').on('click', function() {
    const newItem = $('.allowance-item').first().clone();
    newItem.find('input').val('');
    newItem.find('.remove-allowance').show();
    $('#allowances-container').append(newItem);
  });

  // Remove allowance
  $(document).on('click', '.remove-allowance', function() {
    if ($('.allowance-item').length > 1) {
      $(this).closest('.allowance-item').remove();
    }
  });

  // Add deduction
  $('#add-deduction').on('click', function() {
    const newItem = $('.deduction-item').first().clone();
    newItem.find('input').val('');
    newItem.find('.remove-deduction').show();
    $('#deductions-container').append(newItem);
  });

  // Remove deduction
  $(document).on('click', '.remove-deduction', function() {
    if ($('.deduction-item').length > 1) {
      $(this).closest('.deduction-item').remove();
    }
  });
});
</script>
@endpush
@endsection

