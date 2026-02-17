@extends('layouts.dashboard')

@section('title', 'Daily Reconciliation - Chef')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-balance-scale"></i> Daily Reconciliation</h1>
    <p>View waiter reconciliations (Food Orders Focus)</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Chef</li>
    <li class="breadcrumb-item">Reconciliation</li>
  </ul>
</div>

<!-- Date Selector and Search -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <form method="GET" action="{{ route('bar.chef.reconciliation') }}" class="form-inline">
        <div class="form-group mr-3">
          <label for="date" class="mr-2">Select Date:</label>
          <input type="date" name="date" id="date" class="form-control" value="{{ $date }}" required>
        </div>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-search"></i> View Reconciliation
        </button>
      </form>
    </div>
  </div>
</div>

<!-- Search Waiter -->
<div class="row mb-3">
  <div class="col-md-12">
    <div class="tile">
      <div class="form-group">
        <label for="search-waiter">Search Waiter:</label>
        <input type="text" id="search-waiter" class="form-control" placeholder="Type waiter name or email to search...">
      </div>
    </div>
  </div>
</div>

<!-- Summary Cards -->
<div class="row mb-3">
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon">
      <i class="icon fa fa-users fa-3x"></i>
      <div class="info">
        <h4>Active Waiters</h4>
        <p><b>{{ $waiters->count() }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon">
      <i class="icon fa fa-cutlery fa-3x"></i>
      <div class="info">
        <h4>Food Sales</h4>
        <p><b>TSh {{ number_format($waiters->sum('food_sales'), 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon">
      <i class="icon fa fa-bank fa-3x"></i>
      <div class="info">
        <h4>Total Cash</h4>
        <p><b>TSh {{ number_format($waiters->sum('cash_collected'), 0) }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small success coloured-icon">
      <i class="icon fa fa-mobile fa-3x"></i>
      <div class="info">
        <h4>Total Digital Money</h4>
        <p><b>TSh {{ number_format($waiters->sum('mobile_money_collected'), 0) }}</b></p>
      </div>
    </div>
  </div>
</div>

<!-- Waiters List -->
<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <h3 class="tile-title">Waiters Reconciliation - {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h3>
      <div class="tile-body">
        @if($waiters->count() > 0)
          <div class="table-responsive">
            <table class="table table-hover" id="waiters-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Waiter Name</th>
                  <th>Food Sales</th>
                  <th>Food Orders</th>
                  <th>Cash</th>
                  <th>Digital Money</th>
                  <th>Expected</th>
                  <th>Recorded</th>
                  <th>Submitted</th>
                  <th>Difference</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($waiters as $index => $data)
                <tr data-waiter-id="{{ $data['waiter']->id }}" class="waiter-row">
                  <td>{{ $index + 1 }}</td>
                  <td>
                    <strong>{{ $data['waiter']->full_name }}</strong><br>
                    <small class="text-muted">{{ $data['waiter']->email }}</small>
                  </td>
                  <td>
                    <strong class="text-success">TSh {{ number_format($data['food_sales'], 0) }}</strong>
                  </td>
                  <td><span class="badge badge-success">{{ $data['food_orders_count'] }}</span></td>
                  <td>TSh {{ number_format($data['cash_collected'], 0) }}</td>
                  <td>TSh {{ number_format($data['mobile_money_collected'], 0) }}</td>
                  <td><strong>TSh {{ number_format($data['expected_amount'], 0) }}</strong></td>
                  <td>
                    @if(isset($data['recorded_amount']) && $data['recorded_amount'] > 0)
                      <strong class="text-info">TSh {{ number_format($data['recorded_amount'], 0) }}</strong>
                      <br><small class="text-muted">By Waiter</small>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($data['submitted_amount'] > 0)
                      <strong class="text-success">TSh {{ number_format($data['submitted_amount'], 0) }}</strong>
                      <br><small class="text-muted">Reconciled</small>
                    @else
                      <span class="text-muted">Not Submitted</span>
                    @endif
                  </td>
                  <td>
                    @if($data['submitted_amount'] > 0 || $data['reconciliation'])
                      <span class="{{ $data['difference'] >= 0 ? 'text-success' : 'text-danger' }}">
                        @if($data['difference'] > 0)
                          +TSh {{ number_format($data['difference'], 0) }}
                        @elseif($data['difference'] < 0)
                          TSh {{ number_format($data['difference'], 0) }}
                        @else
                          TSh 0
                        @endif
                      </span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($data['status'] === 'verified')
                      <span class="badge badge-success">Verified</span>
                    @elseif($data['status'] === 'submitted')
                      <span class="badge badge-info">Submitted</span>
                    @elseif($data['status'] === 'paid')
                      <span class="badge badge-success">Paid</span>
                    @elseif($data['status'] === 'partial')
                      <span class="badge badge-warning">Partial</span>
                    @elseif($data['status'] === 'disputed')
                      <span class="badge badge-danger">Disputed</span>
                    @else
                      <span class="badge badge-warning">Pending</span>
                    @endif
                  </td>
                  <td>
                    <div class="btn-group-vertical" style="width: 100%;">
                      <button class="btn btn-sm btn-info view-orders-btn mb-1" 
                              data-waiter-id="{{ $data['waiter']->id }}"
                              data-waiter-name="{{ $data['waiter']->full_name }}">
                        <i class="fa fa-eye"></i> View Orders
                      </button>
                      @if($data['reconciliation'] && $data['status'] === 'submitted')
                        <button class="btn btn-sm btn-success verify-btn mb-1" 
                                data-reconciliation-id="{{ $data['reconciliation']->id }}">
                          <i class="fa fa-check"></i> Verify
                        </button>
                      @endif
                      @if(isset($data['can_submit_payment']) && $data['can_submit_payment'])
                        <button class="btn btn-sm btn-primary submit-payment-btn mb-1" 
                                data-waiter-id="{{ $data['waiter']->id }}"
                                data-date="{{ $date }}"
                                data-total-amount="{{ $data['expected_amount'] }}"
                                data-recorded-amount="{{ $data['recorded_amount'] ?? 0 }}"
                                data-submitted-amount="{{ $data['submitted_amount'] ?? 0 }}"
                                data-difference="{{ $data['difference'] ?? 0 }}"
                                data-waiter-name="{{ $data['waiter']->full_name }}">
                          <i class="fa fa-money"></i> Submit Payment
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No waiters with orders found for this date.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Orders Modal -->
<div class="modal fade" id="ordersModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Food Orders for <span id="modal-waiter-name"></span></h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="orders-content">
          <div class="text-center">
            <i class="fa fa-spinner fa-spin fa-3x"></i>
            <p>Loading orders...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
  // Search waiter functionality
  $('#search-waiter').on('keyup', function() {
    const searchTerm = $(this).val().toLowerCase().trim();
    let visibleCount = 0;
    
    // Remove any existing no-results message
    $('#no-results-message').remove();
    
    $('#waiters-table tbody tr').each(function() {
      // Skip the no-results message row if it exists
      if ($(this).attr('id') === 'no-results-message') {
        return;
      }
      
      const waiterName = $(this).find('td:nth-child(2)').text().toLowerCase();
      if (searchTerm === '' || waiterName.includes(searchTerm)) {
        $(this).show();
        visibleCount++;
      } else {
        $(this).hide();
      }
    });
    
    // Show message if no results
    if (searchTerm !== '' && visibleCount === 0) {
      $('#waiters-table tbody').append('<tr id="no-results-message"><td colspan="12" class="text-center text-muted py-3"><i class="fa fa-info-circle"></i> No waiters found matching "' + searchTerm + '"</td></tr>');
    }
  });
  
  // Clear search when date changes
  $('#date').on('change', function() {
    setTimeout(function() {
      $('#search-waiter').val('').trigger('keyup');
    }, 100);
  });
  
  // View orders button
  $(document).on('click', '.view-orders-btn', function() {
    const waiterId = $(this).data('waiter-id');
    const waiterName = $(this).data('waiter-name');
    const date = '{{ $date }}';
    
    $('#modal-waiter-name').text(waiterName);
    $('#orders-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading orders...</p></div>');
    $('#ordersModal').modal('show');
    
    $.ajax({
      url: '{{ route("bar.chef.reconciliation.waiter-orders", ":id") }}'.replace(':id', waiterId),
      method: 'GET',
      data: { date: date },
      success: function(response) {
        if (response.success && response.orders.length > 0) {
          let html = '<div class="table-responsive"><table class="table table-sm">';
          html += '<thead><tr><th>Order #</th><th>Time</th><th>Platform</th><th>Food Items</th><th>Food Amount</th><th>Payment</th><th>Status</th></tr></thead><tbody>';
          
          response.orders.forEach(function(order) {
            // Calculate food amount (from kitchen_order_items)
            let foodAmount = 0;
            if (order.kitchen_order_items && order.kitchen_order_items.length > 0) {
              foodAmount = order.kitchen_order_items.reduce(function(sum, item) {
                return sum + (parseFloat(item.total_price) || 0);
              }, 0);
            }
            
            html += '<tr>';
            html += '<td><strong>' + order.order_number + '</strong></td>';
            html += '<td>' + new Date(order.created_at).toLocaleTimeString() + '</td>';
            html += '<td>';
            // Display order source/platform
            if (order.order_source) {
              const source = order.order_source.toLowerCase();
              let badgeClass = 'secondary';
              let displayText = order.order_source;
              
              if (source === 'mobile') {
                badgeClass = 'info';
                displayText = 'Mobile';
              } else if (source === 'web') {
                badgeClass = 'primary';
                displayText = 'Web';
              } else if (source === 'kiosk') {
                badgeClass = 'warning';
                displayText = 'Kiosk';
              }
              
              html += '<span class="badge badge-' + badgeClass + '">' + displayText + '</span>';
            } else {
              html += '<span class="text-muted">-</span>';
            }
            html += '</td>';
            html += '<td>';
            
            if (order.kitchen_order_items && order.kitchen_order_items.length > 0) {
              order.kitchen_order_items.forEach(function(item) {
                html += '<span class="badge badge-info">' + item.quantity + 'x ' + item.food_item_name + '</span> ';
              });
            } else {
              html += '<span class="text-muted">-</span>';
            }
            
            html += '</td>';
            html += '<td><strong>TSh ' + foodAmount.toLocaleString() + '</strong></td>';
            html += '<td>';
            if (order.payment_method) {
              if (order.payment_method === 'mobile_money') {
                // mobile_money_number contains the platform name (M-PESA, NMB, CRDB, Mixx by Yas, etc.)
                const providerName = order.mobile_money_number || 'MOBILE MONEY';
                // Format provider name nicely
                let displayProvider = providerName.toUpperCase();
                // Handle special cases like "Mixx by Yas" -> "MIXX BY YAS"
                if (providerName.toLowerCase().includes('mixx')) {
                  displayProvider = 'MIXX BY YAS';
                } else if (providerName.toLowerCase().includes('halopesa')) {
                  displayProvider = 'HALOPESA';
                } else if (providerName.toLowerCase().includes('tigo')) {
                  displayProvider = 'TIGO PESA';
                } else if (providerName.toLowerCase().includes('airtel')) {
                  displayProvider = 'AIRTEL MONEY';
                }
                
                html += '<span class="badge badge-success" style="font-size: 0.9rem;">' + displayProvider + '</span>';
                if (order.transaction_reference) {
                  html += '<br><small class="text-muted" style="font-size: 0.8rem; margin-top: 3px; display: block;"><i class="fa fa-hashtag"></i> Ref: ' + order.transaction_reference + '</small>';
                }
              } else if (order.payment_method === 'cash') {
                html += '<span class="badge badge-warning">CASH</span>';
              } else {
                const badgeClass = order.payment_method === 'cash' ? 'warning' : 'success';
                html += '<span class="badge badge-' + badgeClass + '">' + order.payment_method.replace('_', ' ').toUpperCase() + '</span>';
              }
            } else {
              html += '<span class="badge badge-secondary">Not Set</span>';
            }
            html += '</td>';
            html += '<td>';
            if (order.payment_status === 'paid') {
              html += '<span class="badge badge-success">Paid</span>';
            } else {
              html += '<span class="badge badge-warning">Pending</span>';
            }
            html += '</td>';
            html += '</tr>';
          });
          
          html += '</tbody></table></div>';
          $('#orders-content').html(html);
        } else {
          $('#orders-content').html('<div class="alert alert-info">No orders found.</div>');
        }
      },
      error: function() {
        $('#orders-content').html('<div class="alert alert-danger">Error loading orders.</div>');
      }
    });
  });
  
  // Verify reconciliation button
  $(document).on('click', '.verify-btn', function() {
    const reconciliationId = $(this).data('reconciliation-id');
    const btn = $(this);
    
    Swal.fire({
      title: 'Verify Reconciliation?',
      text: 'Are you sure you want to verify this reconciliation?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, Verify',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Verifying...');
        
        $.ajax({
          url: '{{ route("bar.counter.verify-reconciliation", ":id") }}'.replace(':id', reconciliationId),
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Verified!',
                text: 'Reconciliation verified successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to verify reconciliation';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Verify');
          }
        });
      }
    });
  });
  
  // Submit payment for food orders
  $(document).on('click', '.submit-payment-btn', function() {
    const waiterId = $(this).data('waiter-id');
    const date = $(this).data('date');
    const totalAmount = $(this).data('total-amount');
    const recordedAmount = parseFloat($(this).data('recorded-amount')) || 0;
    const submittedAmount = parseFloat($(this).data('submitted-amount')) || 0;
    const difference = parseFloat($(this).data('difference')) || 0;
    const waiterName = $(this).data('waiter-name') || 'this waiter';
    const btn = $(this);
    
    // Calculate remaining amount to submit
    const remainingAmount = totalAmount - submittedAmount;
    
    // Calculate the amount to submit:
    // - If already submitted, default to remaining amount
    // - Otherwise, default to recorded amount if available, else expected amount
    const defaultSubmitAmount = submittedAmount > 0 ? Math.max(0, remainingAmount) : (recordedAmount > 0 ? recordedAmount : totalAmount);
    
    // Format difference with color
    let differenceHtml = '';
    if (difference > 0) {
      differenceHtml = `<span class="text-success">+TSh ${Math.abs(difference).toLocaleString()}</span>`;
    } else if (difference < 0) {
      differenceHtml = `<span class="text-danger">TSh ${difference.toLocaleString()}</span>`;
    } else {
      differenceHtml = `<span class="text-muted">TSh 0</span>`;
    }
    
    Swal.fire({
      title: 'Submit Payment',
      html: `
        <div class="text-left">
          <p>Mark food orders for <strong>${waiterName}</strong> as paid.</p>
          <div class="alert alert-light border">
            <div class="row">
              <div class="col-6"><strong>Expected Amount:</strong></div>
              <div class="col-6 text-right"><strong>TSh ${parseFloat(totalAmount).toLocaleString()}</strong></div>
            </div>
            ${recordedAmount > 0 ? `
            <div class="row mt-2">
              <div class="col-6"><strong>Recorded Amount:</strong></div>
              <div class="col-6 text-right text-info"><strong>TSh ${recordedAmount.toLocaleString()}</strong></div>
            </div>
            ` : ''}
            ${submittedAmount > 0 ? `
            <div class="row mt-2">
              <div class="col-6"><strong>Already Submitted:</strong></div>
              <div class="col-6 text-right text-success"><strong>TSh ${submittedAmount.toLocaleString()}</strong></div>
            </div>
            ` : ''}
            <div class="row mt-2">
              <div class="col-6"><strong>Difference:</strong></div>
              <div class="col-6 text-right"><strong>${differenceHtml}</strong></div>
            </div>
            ${submittedAmount > 0 ? `
            <div class="row mt-2">
              <div class="col-6"><strong>Remaining Amount:</strong></div>
              <div class="col-6 text-right"><strong class="text-primary">TSh ${remainingAmount.toLocaleString()}</strong></div>
            </div>
            ` : ''}
          </div>
          <hr>
          <div class="form-group">
            <label for="payment-amount">Amount to Submit:</label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">TSh</span>
              </div>
              <input type="number" 
                     id="payment-amount" 
                     class="form-control" 
                     value="${defaultSubmitAmount > 0 ? defaultSubmitAmount : ''}" 
                     min="0" 
                     max="${submittedAmount > 0 ? remainingAmount : parseFloat(totalAmount)}" 
                     step="0.01"
                     placeholder="${submittedAmount > 0 ? 'Enter remaining amount (max: TSh ' + remainingAmount.toLocaleString() + ')' : 'Enter amount'}">
            </div>
            <small class="form-text text-muted">
              ${submittedAmount > 0 
                ? `Enter the additional amount to submit. Maximum remaining: TSh ${remainingAmount.toLocaleString()}.`
                : 'Enter the amount the waiter has collected. You can submit the full amount or a partial amount.'}
              ${difference < 0 ? '<br><span class="text-danger"><i class="fa fa-exclamation-triangle"></i> Note: There is a shortfall of TSh ' + Math.abs(difference).toLocaleString() + '.</span>' : ''}
            </small>
          </div>
          <div class="btn-group btn-group-sm w-100 mt-2" role="group">
            ${submittedAmount > 0 ? `
            <button type="button" class="btn btn-outline-primary" id="btn-remaining-amount">
              Remaining Amount (TSh ${remainingAmount.toLocaleString()})
            </button>
            ` : `
            <button type="button" class="btn btn-outline-primary" id="btn-full-amount">
              Full Amount (TSh ${parseFloat(totalAmount).toLocaleString()})
            </button>
            `}
            ${recordedAmount > 0 && submittedAmount === 0 ? `
            <button type="button" class="btn btn-outline-info" id="btn-recorded-amount">
              Recorded Amount (TSh ${recordedAmount.toLocaleString()})
            </button>
            ` : ''}
            <button type="button" class="btn btn-outline-secondary" id="btn-custom-amount">
              Custom Amount
            </button>
          </div>
          <small class="text-muted d-block mt-2">Note: Only food orders will be marked as paid. Bar orders (drinks) are handled separately.</small>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#28a745',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Submit Payment',
      cancelButtonText: 'Cancel',
      focusConfirm: false,
      preConfirm: () => {
        const amount = parseFloat(document.getElementById('payment-amount').value);
        if (!amount || amount <= 0) {
          Swal.showValidationMessage('Please enter a valid amount greater than 0');
          return false;
        }
        const maxAmount = submittedAmount > 0 ? remainingAmount : parseFloat(totalAmount);
        if (amount > maxAmount) {
          Swal.showValidationMessage(`Amount cannot exceed ${submittedAmount > 0 ? 'the remaining amount' : 'the expected amount'} (TSh ${maxAmount.toLocaleString()})`);
          return false;
        }
        return amount;
      },
      didOpen: () => {
        // Ensure default value is set when modal opens
        const paymentInput = document.getElementById('payment-amount');
        if (paymentInput && !paymentInput.value && defaultSubmitAmount > 0) {
          paymentInput.value = defaultSubmitAmount;
        }
        
        // Remaining amount button (if already submitted)
        const remainingBtn = document.getElementById('btn-remaining-amount');
        if (remainingBtn) {
          remainingBtn.addEventListener('click', function() {
            paymentInput.value = remainingAmount;
          });
        }
        
        // Full amount button (if not yet submitted)
        const fullAmountBtn = document.getElementById('btn-full-amount');
        if (fullAmountBtn) {
          fullAmountBtn.addEventListener('click', function() {
            paymentInput.value = parseFloat(totalAmount);
          });
        }
        
        // Recorded amount button (if exists and not yet submitted)
        const recordedBtn = document.getElementById('btn-recorded-amount');
        if (recordedBtn) {
          recordedBtn.addEventListener('click', function() {
            paymentInput.value = recordedAmount;
          });
        }
        
        // Custom amount button - focus on input
        document.getElementById('btn-custom-amount').addEventListener('click', function() {
          paymentInput.focus();
          paymentInput.select();
        });
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const submittedAmount = result.value;
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
          url: '{{ route("bar.chef.mark-all-food-paid") }}',
          method: 'POST',
          data: {
            _token: '{{ csrf_token() }}',
            waiter_id: waiterId,
            date: date,
            submitted_amount: submittedAmount
          },
          success: function(response) {
            if (response.success) {
              // Store row reference before removing button
              const row = btn.closest('tr');
              
              // Get expected amount from response or row
              const expectedAmount = parseFloat(response.expected_amount || 0);
              const submittedAmount = parseFloat(response.submitted_amount || response.total_amount || 0);
              
              // Hide the button (remove just the button, not the entire div)
              btn.remove();
              
              // Update the Submitted column
              const submittedCell = row.find('td:nth-child(9)'); // Submitted column
              submittedCell.html('<strong class="text-success">TSh ' + submittedAmount.toLocaleString() + '</strong><br><small class="text-muted">Reconciled</small>');
              
              // Update the Difference column
              const differenceCell = row.find('td:nth-child(10)'); // Difference column
              const difference = submittedAmount - expectedAmount;
              let differenceHtml = '';
              if (difference > 0) {
                differenceHtml = '<span class="text-success">+TSh ' + difference.toLocaleString() + '</span>';
              } else if (difference < 0) {
                differenceHtml = '<span class="text-danger">TSh ' + difference.toLocaleString() + '</span>';
              } else {
                differenceHtml = '<span class="text-success">TSh 0</span>';
              }
              differenceCell.html(differenceHtml);
              
              // Update status if partial payment
              if (submittedAmount < expectedAmount) {
                const statusCell = row.find('td:nth-child(11)'); // Status column
                statusCell.html('<span class="badge badge-warning">Partial</span>');
              } else if (submittedAmount >= expectedAmount) {
                const statusCell = row.find('td:nth-child(11)'); // Status column
                statusCell.html('<span class="badge badge-info">Submitted</span>');
              }
              
              // Show success message
              let successMessage = response.message || 'Payment submitted successfully.';
              if (submittedAmount < expectedAmount) {
                successMessage = `Partial payment submitted: TSh ${submittedAmount.toLocaleString()} (Expected: TSh ${expectedAmount.toLocaleString()})`;
              }
              
              Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: successMessage,
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
              }).then(() => {
                // Reload page to show updated reconciliation data
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to submit payment';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
            btn.prop('disabled', false).html('<i class="fa fa-money"></i> Submit Payment');
          }
        });
      }
    });
  });
});
</script>
@endpush


