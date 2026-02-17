@extends('layouts.dashboard')

@section('title', 'Create New Order')

@section('content')
<div class="app-title">
  <div>
    <h1><i class="fa fa-shopping-cart"></i> Create New Order</h1>
    <p>Create a new customer order from counter stock</p>
  </div>
  <ul class="app-breadcrumb breadcrumb">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bar.orders.index') }}">Orders</a></li>
    <li class="breadcrumb-item">Create Order</li>
  </ul>
</div>

<div class="row">
  <!-- Left Column: Customer & Table Info + Order Summary -->
  <div class="col-md-4">
    <div class="tile">
      <h4 class="tile-title">Customer & Table Information</h4>
      
      <form action="{{ route('bar.orders.store') }}" method="POST" id="orderForm">
        @csrf

        <div class="form-group">
          <label for="number_of_people">Number of People *</label>
          <input type="number" name="number_of_people" id="number_of_people" class="form-control" 
                 value="{{ old('number_of_people', 1) }}" 
                 min="1" 
                 max="100" 
                 required>
          @error('number_of_people')
            <div class="text-danger">{{ $message }}</div>
          @enderror
          <small class="form-text text-muted">How many people will be seated at this table?</small>
        </div>

        <div class="form-group">
          <label for="table_id">Table <span class="text-muted">(Optional)</span></label>
          <select name="table_id" id="table_id" class="form-control">
            <option value="">Select Table</option>
            @foreach($tables as $table)
              @php
                $remaining = $table->remaining_capacity;
                $current = $table->current_people;
              @endphp
              <option value="{{ $table->id }}" 
                      data-location="{{ $table->location ?? 'N/A' }}"
                      data-capacity="{{ $table->capacity }}"
                      data-remaining="{{ $remaining }}"
                      data-current="{{ $current }}">
                {{ $table->table_number }} - {{ $table->table_name ?? 'Table ' . $table->table_number }} 
                (Capacity: {{ $table->capacity }}, Available: {{ $remaining }}, Occupied: {{ $current }})
                @if($table->location)
                  - {{ $table->location }}
                @endif
              </option>
            @endforeach
          </select>
          @error('table_id')
            <div class="text-danger">{{ $message }}</div>
          @enderror
          
          <!-- Table Information Display -->
          <div id="tableInfo" class="mt-2" style="display: none;">
            <div class="card card-body bg-light">
              <h6 class="mb-2"><i class="fa fa-table"></i> Table Information</h6>
              <div class="row">
                <div class="col-6">
                  <small class="text-muted">Table Number:</small>
                  <div id="tableNumber" class="font-weight-bold">-</div>
                </div>
                <div class="col-6">
                  <small class="text-muted">Capacity:</small>
                  <div id="tableCapacity" class="font-weight-bold">-</div>
                </div>
                <div class="col-6 mt-2">
                  <small class="text-muted">Currently Occupied:</small>
                  <div id="tableCurrent" class="font-weight-bold">-</div>
                </div>
                <div class="col-6 mt-2">
                  <small class="text-muted">Remaining Seats:</small>
                  <div id="tableRemaining" class="font-weight-bold text-success">-</div>
                </div>
                <div class="col-12 mt-2">
                  <small class="text-muted">Location:</small>
                  <div id="tableLocation" class="font-weight-bold">-</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="customer_name">Customer Name <span class="text-muted">(Optional)</span></label>
          <input type="text" name="customer_name" id="customer_name" class="form-control" value="{{ old('customer_name') }}" placeholder="Enter customer name">
          @error('customer_name')
            <div class="text-danger">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label for="customer_phone">Customer Phone <span class="text-muted">(Optional)</span></label>
          <input type="text" name="customer_phone" id="customer_phone" class="form-control" value="{{ old('customer_phone') }}" placeholder="Enter phone number">
          @error('customer_phone')
            <div class="text-danger">{{ $message }}</div>
          @enderror
        </div>

        <div class="form-group">
          <label for="notes">Order Notes <span class="text-muted">(Optional)</span></label>
          <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any special instructions...">{{ old('notes') }}</textarea>
          @error('notes')
            <div class="text-danger">{{ $message }}</div>
          @enderror
        </div>

        <hr>

        <!-- Order Summary -->
        <div class="order-summary" style="position: sticky; top: 20px;">
          <h5>Order Summary</h5>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Item</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="orderItemsBody">
                <!-- Items will be added here dynamically -->
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="2" class="text-right"><strong>Total:</strong></td>
                  <td colspan="2"><strong id="totalAmount" class="text-success">TSh 0.00</strong></td>
                </tr>
              </tfoot>
            </table>
          </div>

          @error('items')
            <div class="alert alert-danger mt-2">{{ $message }}</div>
          @enderror

          <div class="mt-3">
            <button type="submit" class="btn btn-success btn-block btn-lg" id="submitOrderBtn" disabled>
              <i class="fa fa-check"></i> Create Order
            </button>
            <a href="{{ route('bar.orders.index') }}" class="btn btn-secondary btn-block">
              <i class="fa fa-times"></i> Cancel
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Right Column: Available Products in Cards -->
  <div class="col-md-8">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="tile-title">Available Products at Counter</h3>
        <a href="{{ route('bar.orders.index') }}" class="btn btn-secondary btn-sm">
          <i class="fa fa-arrow-left"></i> Back
        </a>
      </div>

      <div class="tile-body">
        @if($productsWithStock->count() > 0)
          <div class="row">
            @foreach($productsWithStock as $product)
              <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm" style="border-left: 4px solid #007bff;">
                  <div class="card-body">
                    <h5 class="card-title mb-2">
                      <strong>{{ $product['name'] }}</strong>
                      @if($product['brand'])
                        <br><small class="text-muted">{{ $product['brand'] }}</small>
                      @endif
                    </h5>

                    <div class="mb-3">
                      <span class="badge badge-primary">
                        <i class="fa fa-tags"></i> {{ $product['variants']->count() }} Variant(s) Available
                      </span>
                    </div>

                    <hr>

                    <div class="variants-section">
                      @foreach($product['variants'] as $variant)
                        <div class="variant-card mb-3 p-3" style="background-color: #f8f9fa; border-radius: 5px; border: 1px solid #dee2e6;">
                          <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                              <strong>{{ $variant['measurement'] }}</strong>
                              <br><small class="text-muted">{{ $variant['packaging'] }}</small>
                            </div>
                            <span class="badge badge-success">
                              {{ $variant['counter_quantity'] }} available
                            </span>
                          </div>
                          
                          <div class="row mb-2">
                            <div class="col-6">
                              <small class="text-muted">Stock:</small><br>
                              <strong>{{ number_format($variant['counter_quantity']) }} units</strong>
                            </div>
                            <div class="col-6">
                              <small class="text-muted">Price:</small><br>
                              <strong class="text-success">TSh {{ number_format($variant['selling_price'], 2) }}</strong>
                            </div>
                          </div>

                          <button type="button" 
                                  class="btn btn-primary btn-sm btn-block add-to-order-btn" 
                                  data-variant-id="{{ $variant['id'] }}"
                                  data-product-name="{{ $variant['name'] ?: $product['name'] . ' ' . $variant['measurement'] }}"
                                  data-measurement="{{ $variant['measurement'] }}"
                                  data-packaging="{{ $variant['packaging'] }}"
                                  data-available="{{ $variant['counter_quantity'] }}"
                                  data-price="{{ $variant['selling_price'] }}">
                            <i class="fa fa-plus"></i> Add to Order
                          </button>
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        @else
          <div class="alert alert-info text-center">
            <i class="fa fa-info-circle fa-3x mb-3"></i>
            <h4>No Products Available</h4>
            <p>There are no products with stock available at the counter.</p>
            <p>Please <a href="{{ route('bar.stock-transfers.available') }}">transfer stock from warehouse</a> to counter first.</p>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script type="text/javascript">
  let orderItems = [];
  let itemCounter = 0;

  document.addEventListener('DOMContentLoaded', function() {
    const orderItemsBody = document.getElementById('orderItemsBody');
    const totalAmountEl = document.getElementById('totalAmount');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    const orderForm = document.getElementById('orderForm');
    const tableSelect = document.getElementById('table_id');
    const tableInfo = document.getElementById('tableInfo');
    
    // Table selection handler - display table information
    if (tableSelect && tableInfo) {
      const numberOfPeopleInput = document.getElementById('number_of_people');
      
      function updateTableInfo() {
        const selectedOption = tableSelect.options[tableSelect.selectedIndex];
        const numberOfPeople = parseInt(numberOfPeopleInput.value) || 1;
        
        if (tableSelect.value && selectedOption) {
          const location = selectedOption.getAttribute('data-location') || 'N/A';
          const capacity = selectedOption.getAttribute('data-capacity') || '0';
          const remaining = parseInt(selectedOption.getAttribute('data-remaining')) || 0;
          const current = parseInt(selectedOption.getAttribute('data-current')) || 0;
          const tableText = selectedOption.text.split(' - ');
          const tableNumber = tableText[0];
          
          // Update table info display
          document.getElementById('tableNumber').textContent = tableNumber;
          document.getElementById('tableCapacity').textContent = capacity + ' seats';
          document.getElementById('tableCurrent').textContent = current + ' people';
          document.getElementById('tableRemaining').textContent = remaining + ' seats available';
          document.getElementById('tableLocation').textContent = location;
          
          // Warn if not enough seats
          if (remaining < numberOfPeople) {
            document.getElementById('tableRemaining').classList.remove('text-success');
            document.getElementById('tableRemaining').classList.add('text-danger');
            document.getElementById('tableRemaining').textContent = remaining + ' seats available (Not enough for ' + numberOfPeople + ' people)';
          } else {
            document.getElementById('tableRemaining').classList.remove('text-danger');
            document.getElementById('tableRemaining').classList.add('text-success');
          }
          
          // Show table info
          tableInfo.style.display = 'block';
        } else {
          // Hide table info if no table selected
          tableInfo.style.display = 'none';
        }
      }
      
      tableSelect.addEventListener('change', updateTableInfo);
      if (numberOfPeopleInput) {
        numberOfPeopleInput.addEventListener('input', updateTableInfo);
        numberOfPeopleInput.addEventListener('change', updateTableInfo);
      }
      
      // Trigger change event if table is pre-selected (e.g., from old input)
      if (tableSelect.value) {
        updateTableInfo();
      }
    }

    // Calculate total
    function calculateTotal() {
      const total = orderItems.reduce((sum, item) => sum + (item.quantity * item.unitPrice), 0);
      totalAmountEl.textContent = 'TSh ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      submitOrderBtn.disabled = orderItems.length === 0;
    }

    // Add item to order from card button
    document.querySelectorAll('.add-to-order-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const variantId = this.dataset.variantId;
        const productName = this.dataset.productName;
        const measurement = this.dataset.measurement;
        const packaging = this.dataset.packaging;
        const available = parseInt(this.dataset.available);
        const price = parseFloat(this.dataset.price);

        // Check if item already exists
        const existingItem = orderItems.find(item => item.variantId == variantId);
        if (existingItem) {
          // If item exists, just focus on the quantity input for user to update
          const row = document.getElementById('item-row-' + existingItem.id);
          if (row) {
            const qtyInput = row.querySelector('.item-quantity');
            qtyInput.focus();
            qtyInput.select();
          }
          return;
        }
        
        // Add new item with empty quantity (user must enter quantity)
        const newItem = {
          id: itemCounter++,
          variantId: variantId,
          productName: productName,
          measurement: measurement,
          packaging: packaging,
          quantity: 0, // Start with 0, user must enter quantity
          unitPrice: price,
          availableQuantity: available,
        };
        orderItems.push(newItem);
        addItemRow(newItem);
        
        // Focus on the quantity input so user can type immediately
        setTimeout(function() {
          const row = document.getElementById('item-row-' + newItem.id);
          if (row) {
            const qtyInput = row.querySelector('.item-quantity');
            qtyInput.focus();
          }
        }, 100);

        calculateTotal();
      });
    });

    // Add item row to table
    function addItemRow(item) {
      const row = document.createElement('tr');
      row.id = 'item-row-' + item.id;
      row.innerHTML = `
        <td>
          <small>${item.productName}</small><br>
          <small class="text-muted">${item.measurement} - ${item.packaging}</small>
        </td>
        <td>
          <input type="number" class="form-control form-control-sm item-quantity" 
                 data-item-id="${item.id}" 
                 value="" 
                 min="1" 
                 max="${item.availableQuantity}" 
                 placeholder="Enter qty"
                 required
                 style="width: 80px;">
        </td>
        <td class="item-total">TSh 0.00</td>
        <td>
          <button type="button" class="btn btn-danger btn-sm remove-item" data-item-id="${item.id}" title="Remove">
            <i class="fa fa-trash"></i>
          </button>
        </td>
      `;
      orderItemsBody.appendChild(row);

      // Add event listeners for real-time calculation
      const quantityInput = row.querySelector('.item-quantity');
      quantityInput.addEventListener('input', function() {
        handleQuantityChange(this, item);
      });
      quantityInput.addEventListener('change', function() {
        handleQuantityChange(this, item);
      });
      
      function handleQuantityChange(input, item) {
        // Allow empty value while typing
        if (input.value === '' || input.value === null || input.value === '0') {
          // Set quantity to 0 if empty
          item.quantity = 0;
          updateItemRow(item);
          calculateTotal();
          return;
        }
        
        let quantity = parseInt(input.value) || 0;
        
        // Validate quantity
        if (quantity > item.availableQuantity) {
          quantity = item.availableQuantity;
          input.value = quantity;
          alert('Quantity cannot exceed available stock (' + item.availableQuantity + ' units)');
        }
        if (quantity < 1) {
          quantity = 0;
          input.value = '';
        }
        
        // Update item quantity
        item.quantity = quantity;
        
        // Update row display immediately
        updateItemRow(item);
        
        // Recalculate total in real-time
        calculateTotal();
      }

      row.querySelector('.remove-item').addEventListener('click', function() {
        orderItems = orderItems.filter(i => i.id !== item.id);
        row.remove();
        calculateTotal();
      });
    }

    // Update item row with real-time calculation
    function updateItemRow(item) {
      const row = document.getElementById('item-row-' + item.id);
      if (row) {
        const itemTotal = item.quantity > 0 ? item.quantity * item.unitPrice : 0;
        row.querySelector('.item-total').textContent = 'TSh ' + itemTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        // Don't update the input value if user is typing (only update if it's 0 and input is empty)
        const quantityInput = row.querySelector('.item-quantity');
        if (quantityInput && item.quantity === 0 && quantityInput.value === '') {
          // Keep it empty
        } else if (quantityInput && item.quantity > 0 && parseInt(quantityInput.value) !== item.quantity) {
          // Only update if there's a mismatch and quantity is valid
          quantityInput.value = item.quantity;
        }
      }
    }

    // Form submission
    orderForm.addEventListener('submit', function(e) {
      if (orderItems.length === 0) {
        e.preventDefault();
        alert('Please add at least one item to the order');
        return;
      }

      // Validate all items have quantity > 0
      const invalidItems = orderItems.filter(item => item.quantity <= 0);
      if (invalidItems.length > 0) {
        e.preventDefault();
        alert('Please enter quantity for all items. Items with empty or zero quantity cannot be ordered.');
        return;
      }

      // Create hidden inputs for order items (only items with quantity > 0)
      let itemIndex = 0;
      orderItems.forEach((item) => {
        if (item.quantity > 0) {
          const variantInput = document.createElement('input');
          variantInput.type = 'hidden';
          variantInput.name = `items[${itemIndex}][product_variant_id]`;
          variantInput.value = item.variantId;
          orderForm.appendChild(variantInput);

          const quantityInput = document.createElement('input');
          quantityInput.type = 'hidden';
          quantityInput.name = `items[${itemIndex}][quantity]`;
          quantityInput.value = item.quantity;
          orderForm.appendChild(quantityInput);
          
          itemIndex++;
        }
      });
      
      // Check if we have any valid items after filtering
      if (itemIndex === 0) {
        e.preventDefault();
        alert('Please enter quantity for at least one item');
        return;
      }
    });
  });
</script>

@push('styles')
<style>
  .variant-card {
    transition: all 0.3s ease;
  }
  .variant-card:hover {
    background-color: #e9ecef !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  }
  .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15) !important;
  }
  .order-summary {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #dee2e6;
  }
</style>
@endpush
@endsection
