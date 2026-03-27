
$(document).ready(function() {
  // Initialize DataTables
  if ($('#waiters-table').length > 0) {
    const table = $('#waiters-table').DataTable({
      "pageLength": 25,
      "responsive": true,
      "language": {
        "search": "_INPUT_",
        "searchPlaceholder": "Search Waiter..."
      }
    });

    // Custom Status Filter
    $('#status-filter').on('change', function() {
      const statusValue = $(this).val();
      if (statusValue) {
        // Regex exactly matches the selected status (case-insensitive) in the 12th column (Status)
        table.column(11).search('^' + statusValue + '$', true, false).draw();
      } else {
        // Clear filter
        table.column(11).search('').draw();
      }
    });
  }
  
  // View orders button
  $(document).on('click', '.view-orders-btn', function() {
    const waiterId = $(this).data('waiter-id');
    const waiterName = $(this).data('waiter-name');
    const date = '{{ $date }}';
    
    $('#modal-waiter-name').text(waiterName);
    $('#orders-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading orders...</p></div>');
    $('#ordersModal').modal('show');
    
    $.ajax({
      url: '{{ Route::currentRouteName() === "accountant.counter.reconciliation" ? route("accountant.counter.reconciliation.waiter-orders", ":id") : route("bar.counter.reconciliation.waiter-orders", ":id") }}'.replace(':id', waiterId),
      method: 'GET',
      data: { date: date },
      success: function(response) {
        if (response.success && response.orders.length > 0) {
          let html = '<div class="table-responsive"><table class="table table-sm">';
          html += '<thead><tr><th>Order #</th><th>Time</th><th>Platform</th><th>Bar Items (Drinks)</th><th>Food Items</th><th>Bar Amount</th><th>Food Amount</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>';
          
          response.orders.forEach(function(order) {
            // Calculate bar amount (from items - drinks)
            let barAmount = 0;
            if (order.items && order.items.length > 0) {
              barAmount = order.items.reduce(function(sum, item) {
                return sum + (parseFloat(item.total_price) || 0);
              }, 0);
            }
            
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
            
            if (order.items && order.items.length > 0) {
              order.items.forEach(function(item) {
                html += '<span class="badge badge-primary">' + item.quantity + 'x ' + (item.product_variant?.product?.name || 'N/A') + '</span> ';
              });
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
            html += '<td><strong>TSh ' + barAmount.toLocaleString() + '</strong></td>';
            html += '<td><strong>TSh ' + foodAmount.toLocaleString() + '</strong></td>';
            html += '<td><strong>TSh ' + parseFloat(order.total_amount).toLocaleString() + '</strong></td>';
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
              if (order.paid_by_waiter && order.paid_by_waiter.full_name) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter.full_name + '</small>';
              } else if (order.paid_by_waiter) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter + '</small>';
              }
            } else if (order.payment_status === 'partial') {
              html += '<span class="badge badge-warning">Partial</span>';
              if (order.paid_by_waiter && order.paid_by_waiter.full_name) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter.full_name + '</small>';
              } else if (order.paid_by_waiter) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter + '</small>';
              }
            } else if ((order.order_payments && order.order_payments.length > 0) || order.paid_by_waiter_id) {
              // Payment has been recorded by waiter but not yet reconciled
              html += '<span class="badge badge-info">Paid</span>';
              if (order.paid_by_waiter && order.paid_by_waiter.full_name) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter.full_name + '</small>';
              } else if (order.paid_by_waiter) {
                html += '<br><small class="text-muted">Paid by ' + order.paid_by_waiter + '</small>';
              } else if (order.order_payments && order.order_payments.length > 0) {
                html += '<br><small class="text-muted">Paid by waiter</small>';
              }
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
      error: function(xhr) {
        console.error('Error loading orders:', xhr);
        const errorMsg = xhr.responseJSON?.error || xhr.statusText || 'Error loading orders';
        $('#orders-content').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMsg + '</div>');
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
          url: '{{ Route::currentRouteName() === "accountant.counter-reconciliation" ? route("accountant.counter.verify-reconciliation", ":id") : route("bar.counter.verify-reconciliation", ":id") }}'.replace(':id', reconciliationId),
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
                confirmButtonText: 'OK',
                timer: 2000,
                timerProgressBar: true
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
  
  // Mark all orders as paid
  $(document).on('click', '.mark-all-paid-btn', function() {
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
          <p>Mark bar orders (drinks) for <strong>${waiterName}</strong> as paid.</p>
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
          <small class="text-muted d-block mt-2">Note: Only bar orders (drinks) will be marked as paid. Food orders are handled separately.</small>
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
          url: '{{ route("bar.counter.mark-all-paid") }}',
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
              submittedCell.html('<strong>TSh ' + submittedAmount.toLocaleString() + '</strong>');
              
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
            const error = xhr.responseJSON?.error || 'Failed to mark orders as paid';
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

  // Auto-calculate handover total
  $('.handover-input').on('input', function() {
    let total = 0;
    $('.handover-input').each(function() {
      const val = parseFloat($(this).val()) || 0;
      total += val;
    });
    $('#handover-total').text('TSh ' + total.toLocaleString());
  });
});
