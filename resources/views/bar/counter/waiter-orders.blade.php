@extends('layouts.dashboard')

@section('title', 'Waiter Orders')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-list-alt"></i> Waiter Orders</h1>
    <p>Manage orders from waiters</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Counter</li>
    <li class="breadcrumb-item">Waiter Orders</li>
  </ul>
</div>

<div class="row">
  <!-- Status Summary Cards -->
  <div class="col-md-3">
    <div class="widget-small primary coloured-icon shadow-sm mb-3 bg-primary text-white">
      <i class="icon fa fa-bell fa-2x"></i>
      <div class="info text-white">
        <h4 class="text-white">Active</h4>
        <p class="text-white"><b>{{ $pendingCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small warning coloured-icon shadow-sm mb-3">
      <i class="icon fa fa-money fa-2x"></i>
      <div class="info">
        <h4>Wait Pay</h4>
        <p><b>{{ $servedCount }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small info coloured-icon shadow-sm mb-3">
      <i class="icon fa fa-check-circle fa-2x"></i>
      <div class="info">
        <h4>Paid</h4>
        <p><b>{{ $paidCount ?? 0 }}</b></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="widget-small danger coloured-icon shadow-sm mb-3">
      <i class="icon fa fa-list-ul fa-2x"></i>
      <div class="info">
        <h4>Total</h4>
        <p><b>{{ $orders->total() }}</b></p>
      </div>
    </div>
  </div>
</div>



<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="tile-title-w-btn">
        <h3 class="title">All Waiter Orders</h3>
        <div class="d-flex flex-wrap align-items-center">
            <div class="input-group input-group-sm mr-2 mb-2" style="width: 250px;">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-search"></i></span></div>
                <input type="text" id="orderSearch" class="form-control" placeholder="Search Order # or Waiter...">
            </div>
            <select id="filterWaiter" class="form-control form-control-sm mr-2 mb-2" style="width: 150px;">
                <option value="">All Waiters</option>
                @foreach($waiters as $waiter)
                    <option value="{{ $waiter->full_name }}">{{ $waiter->full_name }}</option>
                @endforeach
            </select>
            <select id="filterStatus" class="form-control form-control-sm mr-2 mb-2" style="width: 130px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="served">Served</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <select id="filterPayment" class="form-control form-control-sm mb-2" style="width: 130px;">
                <option value="">All Payment</option>
                <option value="paid">Paid</option>
                <option value="unpaid">Unpaid</option>
            </select>
        </div>
      </div>
      <div class="tile-body">
        <div class="table-responsive">
          <table class="table table-hover table-bordered" id="orders-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Waiter</th>
                <th>Source</th>
                <th>Items</th>
                <th>Total</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $order)
              <tr data-status="{{ $order->status }}" data-order-id="{{ $order->id }}" class="{{ $order->payment_status === 'paid' ? 'table-success' : ($order->status === 'cancelled' ? 'table-danger opacity-75' : '') }}">
                <td><strong>{{ $order->order_number }}</strong></td>
                <td>
                  @if($order->waiter)
                    <i class="fa fa-user"></i> {{ $order->waiter->full_name }}<br>
                    <small class="text-muted">{{ $order->waiter->staff_id }}</small>
                  @else
                    <span class="text-muted">N/A</span>
                  @endif
                </td>
                <td>
                  @if($order->order_source === 'kiosk')
                    <span class="badge badge-info"><i class="fa fa-desktop"></i> Kiosk</span>
                  @elseif($order->order_source === 'counter')
                    <span class="badge badge-warning"><i class="fa fa-shopping-cart"></i> Counter</span>
                  @elseif($order->waiter_id)
                    <span class="badge badge-primary"><i class="fa fa-user"></i> Waiter</span>
                  @else
                    <span class="badge badge-secondary"><i class="fa fa-globe"></i> Web</span>
                  @endif
                </td>
                <td>
                  <ul class="list-unstyled mb-0">
                    @foreach($order->items->take(3) as $item)
                    <li>
                      @if($item->productVariant)
                        <small>
                          {{ $item->quantity }}x 
                          @if($item->sell_type === 'tot')
                            {{ $item->productVariant->portion_unit_name ?? 'Glass' }}
                          @else
                            Btl
                          @endif
                          of {{ \App\Helpers\ProductHelper::generateDisplayName($item->productVariant->product->name ?? 'N/A', ($item->productVariant->measurement ?? '') . ' - ' . ($item->productVariant->packaging ?? ''), $item->productVariant->name) }}
                        </small>
                      @elseif($item->food_item_name)
                        <small>{{ $item->quantity }}x {{ $item->food_item_name }}</small>
                      @else
                        <small>{{ $item->quantity }}x N/A</small>
                      @endif
                    </li>
                    @endforeach
                    @if($order->items->count() > 3)
                    <li><small class="text-muted">+{{ $order->items->count() - 3 }} more</small></li>
                    @endif
                  </ul>
                </td>
                <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                <td>
                  <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'served' ? 'success' : 'secondary') }}">
                    {{ ucfirst($order->status) }}
                  </span>
                </td>
                <td>
                  @if($order->payment_status === 'paid')
                    <span class="badge badge-success">
                      <i class="fa fa-check"></i> Paid
                    </span>
                    @if($order->payment_method)
                      <br><small class="text-muted">
                        <i class="fa fa-{{ $order->payment_method === 'cash' ? 'money' : ($order->payment_method === 'bank' ? 'university' : ($order->payment_method === 'card' ? 'credit-card' : 'mobile')) }}"></i> 
                        {{ $order->mobile_money_number ? strtoupper($order->mobile_money_number) : ucfirst(str_replace('_', ' ', $order->payment_method)) }}
                      </small>
                    @endif
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">By {{ $order->paidByWaiter->full_name }}</small>
                    @endif
                  @elseif($order->payment_status === 'partial')
                    <span class="badge badge-warning">
                      Partial: TSh {{ number_format($order->paid_amount, 2) }}
                    </span>
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">By {{ $order->paidByWaiter->full_name }}</small>
                    @endif
                  @elseif($order->orderPayments && $order->orderPayments->count() > 0 || $order->paid_by_waiter_id)
                    {{-- Payment has been recorded by waiter but not yet reconciled --}}
                    <span class="badge badge-info">
                      <i class="fa fa-check"></i> Paid
                    </span>
                    @if($order->paidByWaiter)
                      <br><small class="text-muted">Paid by {{ $order->paidByWaiter->full_name }}</small>
                    @elseif($order->orderPayments && $order->orderPayments->count() > 0)
                      <br><small class="text-muted">Paid by waiter</small>
                    @endif
                  @else
                    <span class="badge badge-danger">Pending</span>
                  @endif
                </td>
                <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                <td>
                  <div class="">
                    {{-- Always: View --}}
                    <button class="btn btn-sm btn-secondary view-order-btn mr-1 mb-1" data-order-id="{{ $order->id }}">
                      <i class="fa fa-eye"></i> View
                    </button>

                    @if($order->payment_status !== 'paid' && $order->status !== 'cancelled')
                      <a href="{{ route('bar.counter.dashboard', ['add_item_to_order' => $order->id]) }}" class="btn btn-sm btn-primary mr-1 mb-1">
                        <i class="fa fa-plus"></i> Add
                      </a>
                      <button class="btn btn-sm btn-danger cancel-order-btn mr-1 mb-1"
                              data-order-id="{{ $order->id }}"
                              data-order-num="{{ $order->order_number }}">
                        <i class="fa fa-times"></i> Cancel
                      </button>
                      @if($order->status === 'served')
                        <button class="btn btn-sm btn-success pay-order-btn mr-1 mb-1"
                                data-order-id="{{ $order->id }}"
                                data-total="{{ $order->total_amount }}">
                          <i class="fa fa-money"></i> PAY
                        </button>
                      @endif
                    @endif

                    @if($order->payment_status === 'paid')
                      <button class="btn btn-sm btn-success mr-1 mb-1" disabled style="opacity: 1;">
                        <i class="fa fa-check-circle"></i> Paid
                      </button>
                    @elseif($order->status === 'cancelled')
                      <button class="btn btn-sm btn-secondary" disabled style="opacity: 1;">
                        <i class="fa fa-ban"></i> Cancelled
                      </button>
                    @endif

                    @if($order->status !== 'cancelled')
                      <a href="{{ route('bar.counter.print-receipt', $order->id) }}" target="_blank" class="btn btn-sm btn-dark mr-1 mb-1" title="Print Docket">
                        <i class="fa fa-print"></i> Print
                      </a>
                    @endif
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="9" class="text-center">
                  <p class="text-muted">No orders found</p>
                </td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          @if($orders->hasPages())
            <ul class="pagination justify-content-center">
              {{-- Previous Page Link --}}
              @if($orders->onFirstPage())
                <li class="page-item disabled">
                  <span class="page-link">«</span>
                </li>
              @else
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->previousPageUrl() }}" rel="prev">«</a>
                </li>
              @endif

              {{-- Pagination Elements --}}
              @foreach($orders->getUrlRange(max(1, $orders->currentPage() - 2), min($orders->lastPage(), $orders->currentPage() + 2)) as $page => $url)
                @if($page == $orders->currentPage())
                  <li class="page-item active">
                    <span class="page-link">{{ $page }}</span>
                  </li>
                @else
                  <li class="page-item">
                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                  </li>
                @endif
              @endforeach

              {{-- Ellipsis for pages after current range --}}
              @if($orders->currentPage() + 2 < $orders->lastPage())
                @if($orders->currentPage() + 3 < $orders->lastPage())
                  <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->url($orders->lastPage()) }}">{{ $orders->lastPage() }}</a>
                </li>
              @endif

              {{-- Next Page Link --}}
              @if($orders->hasMorePages())
                <li class="page-item">
                  <a class="page-link" href="{{ $orders->nextPageUrl() }}" rel="next">»</a>
                </li>
              @else
                <li class="page-item disabled">
                  <span class="page-link">»</span>
                </li>
              @endif
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="order-details-modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body" id="order-details-content">
        <!-- Order details will be loaded here -->
      </div>
    </div>
  </div>
</div>

<!-- Checkout / Payment Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title font-weight-bold"><i class="fa fa-credit-card"></i> Process Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body p-4">
        <div class="bg-light p-3 rounded mb-4 text-center border">
          <small class="text-muted d-block text-uppercase font-weight-bold">Total Amount Due</small>
          <h2 class="mb-0 text-dark font-weight-bold" id="checkout-total-display">TSh 0</h2>
          <input type="hidden" id="checkout-order-id" value="">
        </div>

        <div class="form-group">
          <label class="font-weight-bold">Select Payment Mode</label>
          <div class="btn-group btn-group-toggle d-flex flex-wrap" data-toggle="buttons">
            <label class="btn btn-outline-success flex-fill active p-3">
              <input type="radio" name="payment_method" value="cash" checked>
              <i class="fa fa-money fa-2x mb-2 d-block"></i> CASH
            </label>
            <label class="btn btn-outline-info flex-fill p-3">
              <input type="radio" name="payment_method" value="mobile_money">
              <i class="fa fa-mobile fa-3x mb-2 d-block"></i> MOBILE MONEY
            </label>
            <label class="btn btn-outline-primary flex-fill p-3">
              <input type="radio" name="payment_method" value="bank">
              <i class="fa fa-university fa-2x mb-2 d-block"></i> BANK
            </label>
            <label class="btn btn-outline-dark flex-fill p-3">
              <input type="radio" name="payment_method" value="card">
              <i class="fa fa-credit-card fa-2x mb-2 d-block"></i> CARD
            </label>
          </div>
        </div>

        {{-- Mobile Money --}}
        <div id="mobile-money-details" style="display: none;" class="mt-3 p-3 bg-light border-info border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">MM Provider</label>
            <select class="form-control" id="mobile-money-provider">
              <option value="Halopesa">Halopesa</option>
              <option value="Mixx By Yas">Mixx By Yas</option>
              <option value="M-Pesa">M-Pesa</option>
              <option value="Airtel Money">Airtel Money</option>
              <option value="T-Pesa">T-Pesa</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Transaction Reference / Receipt #</label>
            <input type="text" id="mobile-money-ref" class="form-control" placeholder="Enter Reference ID">
          </div>
        </div>

        {{-- Bank Transfer --}}
        <div id="bank-details" style="display: none;" class="mt-3 p-3 bg-light border-primary border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">Bank Name</label>
            <select class="form-control" id="bank-provider">
              <option value="CRDB Bank">CRDB Bank</option>
              <option value="NMB Bank">NMB Bank</option>
              <option value="NBC Bank">NBC Bank</option>
              <option value="Stanbic Bank">Stanbic Bank</option>
              <option value="Equity Bank">Equity Bank</option>
              <option value="Absa Bank">Absa Bank</option>
              <option value="DTB Bank">DTB Bank</option>
              <option value="KCB Bank">KCB Bank</option>
              <option value="Exim Bank">Exim Bank</option>
              <option value="Azania Bank">Azania Bank</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Bank Slip / Reference #</label>
            <input type="text" id="bank-ref" class="form-control" placeholder="Enter bank slip or reference number">
          </div>
        </div>

        {{-- Card Payment --}}
        <div id="card-details" style="display: none;" class="mt-3 p-3 bg-light border-dark border rounded">
          <div class="form-group">
            <label class="font-weight-bold small">Card Type</label>
            <select class="form-control" id="card-provider">
              <option value="Visa">Visa</option>
              <option value="Mastercard">Mastercard</option>
              <option value="Amex">American Express</option>
              <option value="UnionPay">UnionPay</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold small">Card Approval Code</label>
            <input type="text" id="card-ref" class="form-control" placeholder="Enter approval / authorization code">
          </div>
        </div>

      </div>
      <div class="modal-footer border-0 p-4 pt-0">
          <button type="button" class="btn btn-success btn-lg btn-block font-weight-bold py-3 shadow-sm" id="btn-place-order-final">
              <i class="fa fa-check-circle"></i> COMPLETE & PROCESS PAYMENT
          </button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // ============================================
  // Real-time Order Detection with Swahili TTS
  // ============================================
  
  // Debug mode - set to false in production
  const DEBUG_MODE = true; // Temporarily enabled for debugging TTS issues
  
  // Initialize state
  let lastOrderId = {{ $orders->count() > 0 ? $orders->first()->id : 0 }};
  let announcedOrders = new Set();
  let isPolling = true;
  let pollInterval = null;
  let errorCount = 0;

  // Debug logging
  function debugLog(message, data = null) {
    if (DEBUG_MODE) {
      const timestamp = new Date().toLocaleTimeString();
      console.log(`[${timestamp}] ${message}`, data || '');
    }
  }
  
  // Error logging
  function errorLog(message, error = null) {
    errorCount++;
    const timestamp = new Date().toLocaleTimeString();
    console.error(`[${timestamp}] ERROR: ${message}`, error || '');
  }
  
  // Show debug message on screen (only in console when DEBUG_MODE is true)
  function showDebugMessage(message, type = 'info') {
    if (DEBUG_MODE) {
      debugLog(message, type);
    }
  }
   /**
   * Check for new orders
   */
  function checkForNewOrders() {
    if (!isPolling) return;

    pollCount++;
    const csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';
    
    $.ajax({
      url: '{{ route("bar.counter.latest-orders") }}',
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      data: {
        last_order_id: lastOrderId
      },
      timeout: 10000,
      success: function(response) {
        errorCount = 0;
        
        if (response && response.success) {
          if (response.new_orders && response.new_orders.length > 0) {
            response.new_orders.forEach(function(order) {
              if (announcedOrders.has(order.id)) return;
              announcedOrders.add(order.id);

              showOrderNotification(order);

              if (order.id > lastOrderId) {
                lastOrderId = order.id;
              }
            });

            // Update pending count in UI
            updatePendingCount();
          }

          if (response.latest_order_id && response.latest_order_id > lastOrderId) {
            lastOrderId = response.latest_order_id;
          }
        }
      },
      error: function() {
        errorCount++;
        if (errorCount > 10) {
          isPolling = false;
          console.error('Order polling stopped due to persistent errors.');
        }
      }
    });
  }

  /**
   * Show visual notification for new order
   */
  function showOrderNotification(order) {
    const notification = $(`
      <div class="alert alert-success alert-dismissible fade show position-fixed" 
           style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
        <h5><i class="fa fa-bell"></i> Oda Mpya!</h5>
        <p class="mb-1"><strong>Oda #:</strong> ${order.order_number}</p>
        <p class="mb-1"><strong>Mhudumu:</strong> ${order.waiter_name}</p>
        <p class="mb-0"><strong>Bidhaa:</strong> ${order.items.map(i => `${i.quantity}x ${i.name}`).join(', ')}</p>
        <button type="button" class="close" data-dismiss="alert">
          <span>&times;</span>
        </button>
      </div>
    `);

    $('body').append(notification);

    setTimeout(function() {
      notification.fadeOut(function() {
        $(this).remove();
      });
    }, 10000);

    const orderRow = $(`tr[data-order-id="${order.id}"]`);
    if (orderRow.length) {
      orderRow.addClass('table-success');
      setTimeout(function() {
        orderRow.removeClass('table-success');
      }, 5000);
    }
  }

  /**
   * Update pending orders count
   */
  function updatePendingCount() {
    $.ajax({
      url: '{{ route("bar.counter.orders-by-status") }}',
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      },
      success: function(response) {
        if (response.success && response.counts) {
          const pendingCount = response.counts.pending || 0;
          $('#pending-orders-count').text(pendingCount);
          const badge = $('#pending-orders-badge');
          if (pendingCount > 0) {
            badge.text(pendingCount).show();
          } else {
            badge.hide();
          }
        }
      }
    });
  }

  // Initialize: Start polling
  $(document).ready(function() {
    debugLog('System initialized, starting order polling...');
    pollInterval = setInterval(checkForNewOrders, 5000);
    setTimeout(checkForNewOrders, 1000);
  });

  // Pause polling when page is hidden (browser tab)
  document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
      isPolling = false;
      debugLog('Page hidden, polling paused');
    } else {
      isPolling = true;
      debugLog('Page visible, resuming polling');
      checkForNewOrders();
    }
  });

  // Add CSS for pulse animation
  if (!$('#order-notification-styles').length) {
    $('head').append(`
      <style id="order-notification-styles">
        @keyframes pulse {
          0% { background-color: #d4edda; }
          50% { background-color: #c3e6cb; }
          100% { background-color: #d4edda; }
        }
        .table-success {
          background-color: #d4edda !important;
          animation: pulse 2s;
        }
      </style>
    `);
  }
  // Advanced Search and Filter Functionality
  function applyFilters() {
    const searchText = $('#orderSearch').val().toLowerCase();
    const waiterFilter = $('#filterWaiter').val();
    const statusFilter = $('#filterStatus').val();
    const paymentFilter = $('#filterPayment').val();

    $('#orders-table tbody tr').each(function() {
      const row = $(this);
      const orderNum = row.find('td:first').text().toLowerCase();
      const waiterName = row.find('td:nth-child(2)').text().toLowerCase();
      const status = row.data('status');
      const paymentStatus = row.find('td:nth-child(7)').text().toLowerCase().includes('paid') ? 'paid' : 'unpaid';

      const matchesSearch = orderNum.includes(searchText) || waiterName.includes(searchText);
      const matchesWaiter = !waiterFilter || waiterName.includes(waiterFilter.toLowerCase());
      const matchesStatus = !statusFilter || status === statusFilter;
      const matchesPayment = !paymentFilter || paymentStatus === paymentFilter;

      if (matchesSearch && matchesWaiter && matchesStatus && matchesPayment) {
        row.show();
      } else {
        row.hide();
      }
    });

    // Handle empty state
    if ($('#orders-table tbody tr:visible').length === 0) {
      if (!$('#no-results-row').length) {
        $('#orders-table tbody').append('<tr id="no-results-row"><td colspan="9" class="text-center py-4 text-muted">No orders found matching your filters.</td></tr>');
      }
    } else {
      $('#no-results-row').remove();
    }
  }

  $('#orderSearch, #filterWaiter, #filterStatus, #filterPayment').on('change keyup', applyFilters);

  // Filter orders by status (legacy buttons support if any left)
  $('.filter-btn').on('click', function() {
    const status = $(this).data('status');
    $('#filterStatus').val(status === 'all' ? '' : status).trigger('change');
    $('.filter-btn').removeClass('btn-primary').addClass('btn-outline-primary');
    $(this).removeClass('btn-outline-primary').addClass('btn-primary');
  });

  // Update order status
  $(document).on('click', '.update-status-btn', function() {
    const orderId = $(this).data('order-id');
    const status = $(this).data('status');
    
    Swal.fire({
      title: 'Update Order Status?',
      text: 'Change status to ' + status + '?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Update',
      cancelButtonText: 'Cancel'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '/bar/counter/orders/' + orderId + '/update-status',
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          data: {
            status: status
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                icon: 'success',
                title: 'Order status updated successfully'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to update order status';
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error
            });
          }
        });
      }
    });
  });

  // View order details
  $(document).on('click', '.view-order-btn', function() {
    const orderId = $(this).data('order-id');
    
    // Show loading state
    $('#order-details-content').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading order details...</p></div>');
    $('#order-details-modal').modal('show');
    
    // Fetch full order details from API
    $.ajax({
      url: '/bar/orders/' + orderId + '/details',
      method: 'GET',
      success: function(response) {
        if (response.order) {
          const order = response.order;
          let content = '<div class="row">';
          
          // Order Information
          content += '<div class="col-md-6">';
          content += '<h6>Order Information</h6>';
          content += '<p><strong>Order #:</strong> ' + order.order_number + '</p>';
          if (order.table) {
            content += '<p><strong>Table:</strong> ' + order.table.table_name + '</p>';
          }
          if (order.customer_name) {
            content += '<p><strong>Customer:</strong> ' + order.customer_name + '</p>';
          }
          if (order.customer_phone) {
            content += '<p><strong>Phone:</strong> ' + order.customer_phone + '</p>';
          }
          content += '<p><strong>Date:</strong> ' + order.created_at + '</p>';
          content += '</div>';
          
          // Status & Payment
          content += '<div class="col-md-6">';
          content += '<h6>Status & Payment</h6>';
          
          // Status
          let statusBadge = '';
          if (order.status === 'pending') {
            statusBadge = '<span class="badge badge-warning">Pending</span>';
          } else if (order.status === 'served') {
            statusBadge = '<span class="badge badge-success">Served</span>';
          } else if (order.status === 'cancelled') {
            statusBadge = '<span class="badge badge-danger">Cancelled</span>';
          } else {
            statusBadge = '<span class="badge badge-secondary">' + order.status + '</span>';
          }
          content += '<p><strong>Status:</strong> ' + statusBadge + '</p>';
          
          // Payment Status
          let paymentStatusBadge = '';
          if (order.payment_status === 'paid') {
            paymentStatusBadge = '<span class="badge badge-success">Paid</span>';
          } else if (order.payment_status === 'partial') {
            paymentStatusBadge = '<span class="badge badge-warning">Partial</span>';
          } else {
            paymentStatusBadge = '<span class="badge badge-danger">Pending</span>';
          }
          content += '<p><strong>Payment Status:</strong> ' + paymentStatusBadge + '</p>';
          
          // Payment Method & Details
          if (order.payment_status === 'paid' || order.payment_status === 'partial') {
            if (order.payment_method === 'mobile_money') {
              const providerName = order.mobile_money_number || 'MOBILE MONEY';
              let displayProvider = providerName.toUpperCase();
              // Handle special cases
              if (providerName.toLowerCase().includes('mixx')) {
                displayProvider = 'MIXX BY YAS';
              } else if (providerName.toLowerCase().includes('halopesa')) {
                displayProvider = 'HALOPESA';
              } else if (providerName.toLowerCase().includes('tigo')) {
                displayProvider = 'TIGO PESA';
              } else if (providerName.toLowerCase().includes('airtel')) {
                displayProvider = 'AIRTEL MONEY';
              }
              
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-success">' + displayProvider + '</span></p>';
              if (order.transaction_reference) {
                content += '<p><strong>Transaction Ref:</strong> <code>' + order.transaction_reference + '</code></p>';
              }
            } else if (order.payment_method === 'cash') {
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-warning">CASH</span></p>';
            } else if (order.payment_method) {
              content += '<p><strong>Payment Method:</strong> <span class="badge badge-info">' + order.payment_method.replace('_', ' ').toUpperCase() + '</span></p>';
            }
            
            if (order.paid_by_waiter) {
              content += '<p><strong>Paid by:</strong> ' + order.paid_by_waiter + '</p>';
            }
          }
          
          content += '<p><strong>Total:</strong> <strong class="text-primary">TSh ' + parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          if (order.paid_amount > 0) {
            content += '<p><strong>Paid:</strong> <strong class="text-success">TSh ' + parseFloat(order.paid_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          }
          if (order.remaining_amount > 0) {
            content += '<p><strong>Remaining:</strong> <strong class="text-danger">TSh ' + parseFloat(order.remaining_amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong></p>';
          }
          content += '</div>';
          content += '</div>';
          
          // Order Items
          content += '<hr><h6>Order Items</h6>';
          if (order.items && order.items.length > 0) {
            content += '<ul class="list-unstyled">';
            order.items.forEach(function(item) {
              const unitLabel = item.sell_type === 'tot' ? (item.portion_unit_name || 'Glass') : 'Btl';
              content += '<li class="mb-2">';
              content += '<strong>' + item.quantity + 'x ' + unitLabel + '</strong> of ' + item.product_name;
              if (item.variant) {
                content += ' <small class="text-muted">(' + item.variant + ')</small>';
              }
              content += ' - <strong>TSh ' + parseFloat(item.total_price).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</strong>';
              content += '</li>';
            });
            content += '</ul>';
          } else {
            content += '<p class="text-muted">No items found</p>';
          }
          
          $('#order-details-content').html(content);
        } else {
          $('#order-details-content').html('<div class="alert alert-danger">Failed to load order details.</div>');
        }
      },
      error: function(xhr) {
        const errorMsg = xhr.responseJSON?.error || 'Failed to load order details';
        $('#order-details-content').html('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> ' + errorMsg + '</div>');
      }
    });
  });

  // Handle Payment Options UI
  $('input[name="payment_method"]').on('change', function() {
      const val = $(this).val();
      $('#mobile-money-details, #bank-details, #card-details').slideUp();
      if (val === 'mobile_money') {
          $('#mobile-money-details').slideDown();
      } else if (val === 'bank') {
          $('#bank-details').slideDown();
      } else if (val === 'card') {
          $('#card-details').slideDown();
      }
  });

  // Open Checkout modal from PAY button
  $(document).on('click', '.pay-order-btn', function() {
      const orderId = $(this).data('order-id');
      const orderTotal = $(this).data('total');
      
      $('#checkout-order-id').val(orderId);
      $('#checkout-total-display').text('TSh ' + parseFloat(orderTotal).toLocaleString('en-US'));
      
      // Default to Cash
      $('input[name="payment_method"][value="cash"]').prop('checked', true).trigger('change');
      
      $('#checkoutModal').modal('show');
  });

  // Submit payment record
  $('#btn-place-order-final').on('click', function() {
      const btn = $(this);
      const orderId = $('#checkout-order-id').val();
      const method = $('input[name="payment_method"]:checked').val() || 'cash';
      
      if (method === 'mobile_money' && !$('#mobile-money-ref').val()) {
          Swal.fire('Required', 'Please enter reference number for mobile money', 'warning');
          return;
      }
      if (method === 'bank' && !$('#bank-ref').val()) {
          Swal.fire('Required', 'Please enter Bank Slip / Reference number', 'warning');
          return;
      }
      if (method === 'card' && !$('#card-ref').val()) {
          Swal.fire('Required', 'Please enter the Card Approval Code', 'warning');
          return;
      }

      const originalBtnText = btn.html();
      btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> PROCESSING...');
      
      $.ajax({
          url: '{{ url("bar/counter/record-payment") }}/' + orderId,
          method: 'POST',
          data: {
              payment_method: method,
              mobile_money_number: method === 'mobile_money' ? $('#mobile-money-provider').val()
                                : method === 'bank' ? $('#bank-provider').val()
                                : method === 'card' ? $('#card-provider').val()
                                : null,
              transaction_reference: method === 'mobile_money' ? $('#mobile-money-ref').val()
                                  : method === 'bank' ? $('#bank-ref').val()
                                  : method === 'card' ? $('#card-ref').val()
                                  : null,
              _token: '{{ csrf_token() }}'
          },
          success: function(payResponse) {
              $('#checkoutModal').modal('hide');
              Swal.fire({
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 1500,
                  icon: 'success',
                  title: 'Order payment recorded successfully'
              }).then(() => {
                  location.reload();
              });
          },
          error: function(err) {
              btn.prop('disabled', false).html(originalBtnText);
              let errMsg = "Payment failed";
              if (err.responseJSON) {
                  errMsg = err.responseJSON.error || err.responseJSON.message || errMsg;
              }
              Swal.fire('Error', errMsg, 'error');
          }
      });
  });
    // Cancel Order Handler
    $(document).on('click', '.cancel-order-btn', function() {
        const orderId = $(this).data('order-id');
        const orderNum = $(this).data('order-num');
        
        Swal.fire({
            title: 'Cancel Order?',
            text: `Are you sure you want to cancel order ${orderNum}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Cancel it!',
            input: 'text',
            inputPlaceholder: 'Reason for cancellation (REQUIRED)',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to write a reason!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("bar.counter.cancel-order", ":id") }}'.replace(':id', orderId),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        reason: result.value
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 1500,
                                icon: 'success',
                                title: response.message
                            }).then(() => {
                                location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON ? xhr.responseJSON.error : "Failed to cancel order", 'error');
                    }
                });
            }
        });
    });
</script>
@endpush

