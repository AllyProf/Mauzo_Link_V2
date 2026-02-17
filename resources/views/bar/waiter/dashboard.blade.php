@extends('layouts.dashboard')

@section('title', 'Waiter Dashboard')

@section('content')
<style>
  /* Mobile Optimizations */
  @media (max-width: 768px) {
    .app-title h1 {
      font-size: 1.2rem;
    }
    .app-title p {
      font-size: 0.85rem;
    }
    .product-card {
      margin-bottom: 15px;
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
    }
    .product-card .card-body {
      padding: 15px;
    }
    .product-card .card-title {
      font-size: 1rem;
      margin-bottom: 8px;
    }
    .product-card .card-img-top {
      transition: transform 0.3s;
    }
    .product-card:hover .card-img-top {
      transform: scale(1.05);
    }
    .add-to-cart-btn {
      font-size: 0.9rem;
      padding: 10px 15px;
      min-height: 44px; /* Touch-friendly */
    }
    .quantity-input {
      font-size: 1rem;
      min-height: 44px; /* Touch-friendly */
      padding: 10px;
    }
    .nav-tabs .nav-link {
      padding: 10px 12px;
      font-size: 0.85rem;
      white-space: nowrap;
    }
    .nav-tabs {
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    .nav-tabs::-webkit-scrollbar {
      display: none;
    }
    #product-search {
      font-size: 16px; /* Prevents zoom on iOS */
      min-height: 44px;
    }
    .cart-sticky-mobile {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: white;
      box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
      z-index: 1000;
      max-height: 60vh;
      overflow-y: auto;
      padding: 15px;
    }
    .cart-sticky-mobile .card-header {
      position: sticky;
      top: 0;
      background: #007bff;
      z-index: 10;
    }
    .mobile-cart-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: #007bff;
      color: white;
      border: none;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
      z-index: 999;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
    }
    .mobile-cart-toggle .badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background: #dc3545;
      border-radius: 50%;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
    }
    body {
      padding-bottom: 100px; /* Space for mobile cart button */
    }
    @media (min-width: 768px) {
      body {
        padding-bottom: 0;
      }
    }
    .table-responsive {
      font-size: 0.85rem;
    }
    .table th, .table td {
      padding: 8px 4px;
    }
    .btn-lg {
      padding: 12px 20px;
      font-size: 1rem;
      min-height: 48px;
    }
    .form-control {
      font-size: 16px; /* Prevents zoom on iOS */
      min-height: 44px;
    }
    .customer-info-row .col-md-6 {
      margin-bottom: 10px;
    }
  }
  
  /* Product grid responsive */
  @media (max-width: 576px) {
    .product-item {
      flex: 0 0 100%;
      max-width: 100%;
    }
  }
  
  @media (min-width: 577px) and (max-width: 768px) {
    .product-item {
      flex: 0 0 50%;
      max-width: 50%;
    }
  }
  
  /* Smooth scrolling */
  html {
    scroll-behavior: smooth;
  }
  
  /* Enhanced Product Card Styling */
  .product-card {
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    transition: all 0.3s ease;
  }
  
  .product-card .card-img-top {
    border-radius: 0;
    transition: transform 0.4s ease;
  }
  
  .product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    border-color: #007bff;
  }
  
  .product-card:hover .card-img-top {
    transform: scale(1.1);
  }
  
  .product-card .card-body {
    background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
  }
  
  .product-card .card-title {
    color: #2c3e50;
    font-weight: 600;
    font-size: 1.1rem;
  }
  
  .product-card .add-to-cart-btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .product-card .add-to-cart-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,123,255,0.4);
  }
  
  .product-card .add-to-cart-btn.btn-success {
    background-color: #28a745;
    border-color: #28a745;
  }
  
  /* Tab Styling */
  .nav-tabs .nav-link {
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .nav-tabs .nav-link:hover {
    background-color: #f8f9fa;
    border-color: #dee2e6 #dee2e6 #fff;
  }
  
  .nav-tabs .nav-link.active {
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
    color: #007bff;
  }
  
  /* Badge Styling */
  .badge-info {
    background-color: #17a2b8;
    padding: 5px 10px;
    border-radius: 6px;
  }
  
  /* Price Styling */
  .text-primary {
    color: #007bff !important;
    font-weight: 700;
    font-size: 1.1rem;
  }
</style>

<div class="app-title">
  <div>
    <h1><i class="fa fa-user-md"></i> <span class="d-none d-md-inline">Waiter Dashboard</span><span class="d-md-none">Orders</span></h1>
    <p class="d-none d-md-block">View counter stock and place orders</p>
  </div>
  <ul class="app-breadcrumb breadcrumb d-none d-md-flex">
    <li class="breadcrumb-item"><i class="fa fa-home fa-lg"></i></li>
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Waiter Dashboard</li>
  </ul>
</div>

<div class="row">
  <div class="col-md-12">
    <div class="tile">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h3 class="tile-title mb-2 mb-md-0">
          <i class="fa fa-shopping-cart"></i> <span class="d-none d-md-inline">Available Counter Stock</span><span class="d-md-none">Products</span>
        </h3>
        <div class="w-100 w-md-auto mt-2 mt-md-0">
          <a href="{{ route('bar.waiter.order-history') }}" class="btn btn-info btn-sm btn-block btn-md-inline-block">
            <i class="fa fa-history"></i> <span class="d-none d-sm-inline">Order History</span><span class="d-sm-none">History</span>
          </a>
        </div>
      </div>

      <div class="tile-body">
        @if(count($variants) > 0)
          <!-- Search and Filter Section -->
          <div class="row mb-3">
            <div class="col-md-12">
              <div class="card">
                <div class="card-body">
                  <!-- Search Bar -->
                  <div class="form-group mb-3">
                    <input type="text" class="form-control form-control-lg" id="product-search" 
                           placeholder="Search products by name...">
                  </div>
                  
                  <!-- Tabs for Food and Drinks -->
                  <ul class="nav nav-tabs" id="product-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active" id="food-tab" data-toggle="tab" href="#tab-food" role="tab">
                        <i class="fa fa-utensils"></i> Food Menu
                      </a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="drinks-tab" data-toggle="tab" href="#tab-drinks" role="tab">
                        <i class="fa fa-wine-glass"></i> Drinks & Bar
                      </a>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab Content -->
          <div class="tab-content mt-3" id="product-tab-content">
            <!-- Food Menu Tab -->
            <div class="tab-pane fade show active" id="tab-food" role="tabpanel">
              <div class="row" id="food-container">
                <!-- Food Items from Database -->
                @forelse($foodItems ?? [] as $foodItem)
                  <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3 product-item" data-product-name="{{ strtolower($foodItem->name) }}" data-category="Food">
                    <div class="card h-100 product-card shadow-sm">
                      @if($foodItem->image)
                        <img src="{{ asset('storage/' . $foodItem->image) }}" class="card-img-top" alt="{{ $foodItem->name }}" style="height: 120px; object-fit: cover;">
                      @else
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 120px;">
                          <i class="fa fa-cutlery fa-3x text-muted"></i>
                        </div>
                      @endif
                      <div class="card-body">
                        <h5 class="card-title mb-2" style="font-size: 0.95rem;">{{ $foodItem->name }}</h5>
                        @if($foodItem->variant_name)
                          <p class="text-muted mb-2" style="font-size: 0.8rem;"><small>{{ $foodItem->variant_name }}</small></p>
                        @endif
                        <div class="mb-2">
                          <strong style="font-size: 0.85rem;">Price:</strong> <span class="text-primary" style="font-size: 0.9rem;">TSh {{ number_format($foodItem->price, 0) }}</span>
                        </div>
                        <div class="input-group input-group-sm">
                          <input type="number" class="form-control quantity-input text-center" 
                                 min="1" value="1" 
                                 data-food-item-id="{{ $foodItem->id }}"
                                 data-price="{{ $foodItem->price }}"
                                 style="font-size: 0.85rem;">
                          <div class="input-group-append">
                            <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                    data-food-item-id="{{ $foodItem->id }}"
                                    data-product-name="{{ $foodItem->name }}"
                                    data-variant="{{ $foodItem->variant_name ?? '' }}"
                                    data-price="{{ $foodItem->price }}">
                              <i class="fa fa-plus"></i> Add
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                @empty
                  <div class="col-12">
                    <div class="alert alert-info">
                      <i class="fa fa-info-circle"></i> No food items available. Please add food items from the Chef Dashboard.
                    </div>
                  </div>
                @endforelse
                
                <!-- Old Hardcoded Food Items Removed - Now using database -->
              </div>
            </div>
            
            <!-- Drinks & Bar Tab -->
            <div class="tab-pane fade" id="tab-drinks" role="tabpanel">
              <div class="row" id="drinks-container">
                @foreach($variants as $index => $variant)
                  <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-3 product-item" 
                       data-variant-id="{{ $variant['id'] }}"
                       data-product-name="{{ strtolower($variant['product_name']) }}"
                       data-is-alcoholic="{{ $variant['is_alcoholic'] ? 'true' : 'false' }}">
                    <div class="card h-100 product-card shadow-sm">
                      @php
                        $imageIndex = ($index % 8) + 1;
                        $imageUrl = $variant['product_image'] ? asset('storage/' . $variant['product_image']) : asset('img/restaurant/menu-' . $imageIndex . '.jpg');
                      @endphp
                      <img src="{{ $imageUrl }}" class="card-img-top" alt="{{ $variant['product_name'] }}" style="height: 120px; object-fit: cover;" onerror="this.src='{{ asset('img/restaurant/menu-' . $imageIndex . '.jpg') }}'">
                      <div class="card-body">
                        @php
                          // Extract measurement (ml/litre) from variant string
                          $variantParts = explode(' - ', $variant['variant']);
                          $measurement = $variantParts[0] ?? '';
                          $packaging = isset($variantParts[1]) ? $variantParts[1] : '';
                        @endphp
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h5 class="card-title mb-0" style="font-size: 0.95rem; flex: 1; margin-right: 8px;">{{ $variant['product_name'] }}</h5>
                          <span class="badge badge-primary" style="font-size: 0.85rem; font-weight: 700; padding: 4px 8px; letter-spacing: 0.5px; white-space: nowrap;">{{ $measurement }}</span>
                        </div>
                        <div class="mb-2">
                          <strong style="font-size: 0.85rem;">Price:</strong> 
                          @if($variant['can_sell_in_tots'])
                            <div class="btn-group btn-group-toggle btn-group-sm mb-2 w-100" data-toggle="buttons">
                              <label class="btn btn-outline-primary active sell-type-label" style="font-size: 0.75rem;">
                                <input type="radio" name="sell_type_{{ $variant['id'] }}" class="sell-type-input" value="unit" checked 
                                       data-price="{{ $variant['selling_price'] }}"> Bottle
                              </label>
                              <label class="btn btn-outline-primary sell-type-label" style="font-size: 0.75rem;">
                                <input type="radio" name="sell_type_{{ $variant['id'] }}" class="sell-type-input" value="tot" 
                                       data-price="{{ $variant['selling_price_per_tot'] }}"> Shot/Tot
                              </label>
                            </div>
                            <span class="text-primary product-price-display" style="font-size: 0.9rem;">TSh {{ number_format($variant['selling_price'], 2) }}</span>
                          @else
                            <span class="text-primary" style="font-size: 0.9rem;">TSh {{ number_format($variant['selling_price'], 2) }}</span>
                          @endif
                        </div>
                        <div class="input-group input-group-sm">
                          <input type="number" class="form-control quantity-input text-center" 
                                 min="1" max="{{ $variant['quantity'] }}" 
                                 value="1" data-variant-id="{{ $variant['id'] }}"
                                 data-price="{{ $variant['selling_price'] }}"
                                 data-price-unit="{{ $variant['selling_price'] }}"
                                 data-price-tot="{{ $variant['selling_price_per_tot'] }}"
                                 style="font-size: 0.85rem;">
                          <div class="input-group-append">
                            <button class="btn btn-primary btn-sm add-to-cart-btn" 
                                    data-variant-id="{{ $variant['id'] }}"
                                    data-product-name="{{ $variant['product_name'] }}"
                                    data-variant="{{ $variant['variant'] }}"
                                    data-can-sell-in-tots="{{ $variant['can_sell_in_tots'] ? 'true' : 'false' }}"
                                    data-price="{{ $variant['selling_price'] }}">
                              <i class="fa fa-plus"></i> Add
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <!-- No Results Message -->
          <div id="no-results" class="alert alert-info" style="display: none;">
            <i class="fa fa-info-circle"></i> No products found matching your search criteria.
          </div>

          <!-- Mobile Cart Toggle Button -->
          <button class="mobile-cart-toggle d-md-none" id="mobile-cart-toggle" style="display: none;">
            <i class="fa fa-shopping-cart"></i>
            <span class="badge" id="cart-badge">0</span>
          </button>

          <!-- Shopping Cart -->
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="card" id="shopping-cart-card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                  <h5 class="mb-0"><i class="fa fa-shopping-cart"></i> Shopping Cart</h5>
                  <button class="btn btn-sm btn-light d-md-none" id="close-cart-mobile">
                    <i class="fa fa-times"></i>
                  </button>
                </div>
                <div class="card-body">
                  <div id="cart-items">
                    <p class="text-muted text-center">Your cart is empty</p>
                  </div>
                  <div id="cart-total" class="mt-3" style="display: none;">
                    <hr>
                    <!-- Customer Information (Optional) -->
                    <div class="mb-3">
                      <h6><i class="fa fa-table"></i> Table & Customer Information (Optional)</h6>
                      <div class="row customer-info-row">
                        <div class="col-12">
                          <div class="form-group">
                            <label>Table <span class="text-muted">(Optional)</span></label>
                            <select class="form-control" id="table-id">
                              <option value="">Select Table</option>
                              @foreach($tables as $table)
                                <option value="{{ $table['id'] }}" 
                                        data-capacity="{{ $table['capacity'] }}"
                                        data-remaining="{{ $table['remaining_capacity'] }}"
                                        data-current="{{ $table['current_people'] }}"
                                        data-location="{{ $table['location'] }}">
                                  {{ $table['table_number'] }} - {{ $table['table_name'] ?? 'Table ' . $table['table_number'] }}
                                  ({{ $table['capacity'] }} seats, {{ $table['remaining_capacity'] }} available)
                                  @if($table['location'] !== 'N/A')
                                    - {{ $table['location'] }}
                                  @endif
                                </option>
                              @endforeach
                            </select>
                            <small class="form-text text-muted" id="table-info"></small>
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group">
                            <label>Customer Name <span class="text-muted">(Optional)</span></label>
                            <input type="text" class="form-control" id="customer-name" placeholder="Enter customer name">
                          </div>
                        </div>
                        <div class="col-12 col-md-6">
                          <div class="form-group">
                            <label>Customer Phone</label>
                            <input type="text" class="form-control" id="customer-phone" value="+255" placeholder="+255XXXXXXXXX">
                          </div>
                        </div>
                        <div class="col-12">
                          <div class="form-group">
                            <label><i class="fa fa-sticky-note"></i> General Order Notes <span class="text-muted">(Optional - for kitchen)</span></label>
                            <textarea class="form-control" id="order-notes" rows="3" placeholder="e.g., Serve together, Extra napkins, Birthday celebration, etc."></textarea>
                            <small class="form-text text-muted">These notes will be visible to the kitchen for the entire order</small>
                          </div>
                        </div>
                        <!-- Payment will be recorded after customer finishes in Order History -->
                      </div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 class="mb-0">Total:</h5>
                      <h4 class="mb-0 text-success" id="total-amount">TSh 0.00</h4>
                    </div>
                    <button class="btn btn-success btn-lg btn-block" id="place-order-btn" disabled>
                      <i class="fa fa-check"></i> Place Order
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>


          <!-- Completed and Served Orders Section -->
          @if($completedOrders->count() > 0)
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="tile">
                <h3 class="tile-title text-success">
                  <i class="fa fa-check-circle"></i> Completed and Served Orders
                </h3>
                <div class="table-responsive">
                  <table class="table table-hover table-bordered">
                    <thead>
                      <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($completedOrders as $order)
                      <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>
                          @if($order->table)
                            <span class="badge badge-info">
                              <i class="fa fa-table"></i> {{ $order->table->table_number }}
                            </span>
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                        <td>
                          <div style="max-width: 300px;">
                            @foreach($order->items as $item)
                              <div class="mb-1">
                                <strong>{{ $item->productVariant->product->name ?? 'N/A' }}</strong>
                                <span class="badge badge-info ml-1">{{ $item->quantity }}x</span>
                              </div>
                            @endforeach
                            @foreach($order->kitchenOrderItems as $item)
                              <div class="mb-1">
                                <strong>{{ $item->food_item_name }}</strong>
                                <span class="badge badge-success ml-1">{{ $item->quantity }}x</span>
                                @if($item->variant_name)
                                  <br><small class="text-muted">{{ $item->variant_name }}</small>
                                @endif
                              </div>
                            @endforeach
                          </div>
                        </td>
                        <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                        <td>
                          <span class="badge badge-{{ $order->status === 'served' ? 'success' : 'info' }}">
                            {{ ucfirst($order->status) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                            {{ ucfirst($order->payment_status) }}
                          </span>
                        </td>
                        <td>{{ $order->updated_at->format('M d, Y H:i') }}</td>
                        <td>
                          <button class="btn btn-sm btn-info print-receipt-btn" data-order-id="{{ $order->id }}">
                            <i class="fa fa-print"></i> Print
                          </button>
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          @endif

          <!-- Recent Orders -->
          @if($recentOrders->count() > 0)
          <div class="row mt-4">
            <div class="col-md-12">
              <div class="tile">
                <h3 class="tile-title">Recent Orders</h3>
                <!-- Mobile Card View -->
                <div class="d-md-none">
                  @foreach($recentOrders as $order)
                  <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                      <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                          <strong class="text-primary">{{ $order->order_number }}</strong>
                          @if($order->table)
                            <div class="mt-1">
                              <span class="badge badge-info">
                                <i class="fa fa-table"></i> Table: {{ $order->table->table_number }}
                              </span>
                            </div>
                          @endif
                          <div class="mt-1">
                            <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'prepared' ? 'info' : ($order->status === 'served' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                              {{ ucfirst($order->status) }}
                            </span>
                            <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }} ml-1">
                              {{ ucfirst($order->payment_status) }}
                            </span>
                          </div>
                        </div>
                        <div class="text-right">
                          <small class="text-muted d-block">{{ $order->created_at->format('M d, H:i') }}</small>
                          @if($order->status === 'pending')
                            <div class="mt-2">
                              <button class="btn btn-sm btn-danger cancel-order-btn" data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}">
                                <i class="fa fa-times"></i> Cancel
                              </button>
                              <button class="btn btn-sm btn-info print-receipt-btn ml-1" data-order-id="{{ $order->id }}">
                                <i class="fa fa-print"></i> Print
                              </button>
                            </div>
                          @else
                            <button class="btn btn-sm btn-info print-receipt-btn mt-2" data-order-id="{{ $order->id }}">
                              <i class="fa fa-print"></i> Print
                            </button>
                          @endif
                        </div>
                      </div>

                      <!-- Customer Information -->
                      @if($order->customer_name || $order->customer_phone)
                      <div class="mb-3 p-2 bg-light rounded">
                        <small class="text-muted d-block mb-1"><i class="fa fa-user"></i> Customer:</small>
                        @if($order->customer_name)
                          <strong>{{ $order->customer_name }}</strong>
                        @endif
                        @if($order->customer_phone)
                          <div class="mt-1">
                            <i class="fa fa-phone"></i> <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>
                          </div>
                        @endif
                      </div>
                      @endif

                      <!-- Products Ordered -->
                      <div class="mb-3">
                        <small class="text-muted d-block mb-2"><i class="fa fa-shopping-bag"></i> Products:</small>
                        <div class="list-group list-group-flush">
                          @foreach($order->items as $item)
                          <div class="list-group-item px-0 py-2 border-left-0 border-right-0">
                            <div class="d-flex justify-content-between align-items-center">
                              <div class="flex-grow-1">
                                <strong>{{ $item->productVariant->product->name ?? 'N/A' }}</strong>
                                @if($item->productVariant)
                                  <br><small class="text-muted">{{ $item->productVariant->measurement ?? '' }} - {{ $item->productVariant->packaging ?? '' }}</small>
                                @endif
                              </div>
                              <div class="text-right">
                                <span class="badge badge-primary">Qty: {{ $item->quantity }}</span>
                                <div class="mt-1">
                                  <small class="text-muted">TSh {{ number_format($item->total_price, 2) }}</small>
                                </div>
                              </div>
                            </div>
                          </div>
                          @endforeach
                        </div>
                      </div>

                      <!-- Order Summary -->
                      <div class="border-top pt-2">
                        <div class="d-flex justify-content-between">
                          <span class="text-muted">Total:</span>
                          <strong class="text-success">TSh {{ number_format($order->total_amount, 2) }}</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                  @endforeach
                </div>
                <!-- Desktop Table View -->
                <div class="table-responsive d-none d-md-block">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                        <th>Order #</th>
                        <th>Table</th>
                        <th>Customer</th>
                        <th>Products</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($recentOrders as $order)
                      <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>
                          @if($order->table)
                            <span class="badge badge-info">
                              <i class="fa fa-table"></i> {{ $order->table->table_number }}
                            </span>
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                        <td>
                          @if($order->customer_name || $order->customer_phone)
                            @if($order->customer_name)
                              <strong>{{ $order->customer_name }}</strong><br>
                            @endif
                            @if($order->customer_phone)
                              <small class="text-muted"><i class="fa fa-phone"></i> {{ $order->customer_phone }}</small>
                            @endif
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        </td>
                        <td>
                          <div style="max-width: 300px;">
                            @foreach($order->items as $item)
                              <div class="mb-1">
                                <strong>{{ $item->productVariant->product->name ?? 'N/A' }}</strong>
                                <span class="badge badge-info ml-1">{{ $item->quantity }}x</span>
                                @if($item->productVariant)
                                  <br><small class="text-muted">{{ $item->productVariant->measurement ?? '' }} - {{ $item->productVariant->packaging ?? '' }}</small>
                                @endif
                              </div>
                            @endforeach
                          </div>
                        </td>
                        <td><strong>TSh {{ number_format($order->total_amount, 2) }}</strong></td>
                        <td>
                          <span class="badge badge-{{ $order->status === 'pending' ? 'warning' : ($order->status === 'prepared' ? 'info' : ($order->status === 'served' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'secondary'))) }}">
                            {{ ucfirst($order->status) }}
                          </span>
                        </td>
                        <td>
                          <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                            {{ ucfirst($order->payment_status) }}
                          </span>
                        </td>
                        <td>{{ $order->created_at->format('M d, Y H:i') }}</td>
                        <td>
                          @if($order->status === 'pending')
                            <button class="btn btn-sm btn-danger cancel-order-btn" data-order-id="{{ $order->id }}" data-order-number="{{ $order->order_number }}">
                              <i class="fa fa-times"></i> Cancel
                            </button>
                          @endif
                          <button class="btn btn-sm btn-info print-receipt-btn ml-1" data-order-id="{{ $order->id }}">
                            <i class="fa fa-print"></i> Print
                          </button>
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
          @endif

        @else
          <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> No products available in counter stock at the moment.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  let cart = [];
  
  // Payment method removed from order creation - will be recorded in Order History after customer finishes

  // Tab switching - Bootstrap tabs
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    // Tab is now active, search will work within active tab
    filterProducts();
  });

  // Search functionality
  $('#product-search').on('keyup', function() {
    filterProducts();
  });

  // Filter and search products within active tab
  function filterProducts() {
    const searchTerm = $('#product-search').val().toLowerCase().trim();
    const activeTab = $('.tab-pane.active');
    const $products = activeTab.find('.product-item');
    let visibleCount = 0;

    $products.each(function() {
      const $product = $(this);
      const productName = $product.data('product-name') || '';
      
      // Check search match
      const matchesSearch = !searchTerm || productName.includes(searchTerm);
      
      // Show/hide product
      if (matchesSearch) {
        $product.show();
        visibleCount++;
      } else {
        $product.hide();
      }
    });

  }

  // Handle sell type change (Bottle vs Shot)
  $(document).on('change', '.sell-type-input', function() {
    const $input = $(this);
    const $card = $input.closest('.card');
    const price = parseFloat($input.data('price')) || 0;
    
    // Update price display
    $card.find('.product-price-display').text('TSh ' + price.toLocaleString('en-US', {minimumFractionDigits: 2}));
    
    // Update data attributes on quantity input and add button
    $card.find('.quantity-input').data('price', price);
    $card.find('.add-to-cart-btn').data('price', price).data('sell-type', $input.val());
  });

  // Add to cart
  $(document).on('click', '.add-to-cart-btn', function() {
    const $btn = $(this);
    // Check if it's a food item (new database way) or regular variant
    const foodItemId = $btn.data('food-item-id');
    const variantId = $btn.data('variant-id');
    const productName = $btn.data('product-name');
    const variant = $btn.data('variant') || '';
    const price = parseFloat($btn.data('price')) || 0;
    // For food items, quantity is always 1 (can be changed in cart)
    // For drinks, get from quantity input if exists
    const quantityInput = $btn.closest('.card').find('.quantity-input');
    const quantity = quantityInput.length ? parseInt(quantityInput.val()) || 1 : 1;
    const sellType = $btn.data('sell-type') || 'unit';

    // Store original button content
    const originalHtml = $btn.html();
    const originalClass = $btn.attr('class');

    // Show "Added" feedback
    $btn.html('<i class="fa fa-check"></i> Added')
        .removeClass('btn-primary')
        .addClass('btn-success')
        .prop('disabled', true);

    // Determine if this is a food item
    const isFoodItem = !!foodItemId;

    // Check if item already in cart
    let existingItem = null;
    if (isFoodItem) {
      const foodId = parseInt(foodItemId);
      existingItem = cart.find(item => item.food_item_id == foodId);
    } else {
      const varId = parseInt(variantId);
      existingItem = cart.find(item => item.variant_id == varId && !item.food_item_id && item.sell_type == sellType);
    }

    if (existingItem) {
      existingItem.quantity += quantity;
    } else {
      const cartItem = {
        product_name: productName || 'Item',
        variant: variant || '',
        price: price || 0,
        quantity: quantity || 1,
        sell_type: sellType,
        is_food_item: isFoodItem,
        notes: '' // Initialize notes for special instructions
      };
      
      // Set appropriate ID based on item type
      if (isFoodItem) {
        const parsedFoodId = parseInt(foodItemId);
        if (isNaN(parsedFoodId) || parsedFoodId <= 0) {
          console.error('Invalid food_item_id:', foodItemId);
          alert('Error: Invalid food item ID. Please try again.');
          return;
        }
        cartItem.food_item_id = parsedFoodId;
        // Explicitly set variant_id to null for food items to avoid confusion
        cartItem.variant_id = null;
      } else {
        const parsedVariantId = parseInt(variantId);
        if (isNaN(parsedVariantId) || parsedVariantId <= 0) {
          console.error('Invalid variant_id:', variantId);
          alert('Error: Invalid product variant ID. Please try again.');
          return;
        }
        cartItem.variant_id = parsedVariantId;
        // Explicitly set food_item_id to null for drinks to avoid confusion
        cartItem.food_item_id = null;
      }
      
      cart.push(cartItem);
      console.log('Added to cart:', cartItem); // Debug log
    }

    updateCart();

    // Reset button after 1.5 seconds
    setTimeout(function() {
      $btn.html(originalHtml)
          .attr('class', originalClass)
          .prop('disabled', false);
    }, 1500);
  });

  // Remove from cart
  $(document).on('click', '.remove-from-cart', function() {
    const variantId = $(this).data('variant-id');
    const foodItemId = $(this).data('food-item-id');
    cart = cart.filter(item => {
      if (foodItemId) {
        return item.food_item_id != foodItemId && item.variant_id !== variantId;
      }
      return item.variant_id !== variantId || item.sell_type !== $(this).data('sell-type');
    });
    updateCart();
  });

  // Update quantity in cart
  $(document).on('change', '.cart-quantity', function() {
    const variantId = $(this).data('variant-id');
    const foodItemId = $(this).data('food-item-id');
    const quantity = parseInt($(this).val()) || 1;
    const item = cart.find(item => {
      if (foodItemId) {
        return item.food_item_id == foodItemId;
      }
      return item.variant_id === variantId && item.sell_type === $(this).data('sell-type');
    });
    if (item) {
      item.quantity = quantity;
      updateCart();
    }
  });

  // Update special instructions/notes for food items
  $(document).on('blur', '.item-notes', function() {
    const variantId = $(this).data('variant-id');
    const foodItemId = $(this).data('food-item-id');
    const notes = $(this).val().trim();
    const item = cart.find(item => {
      if (foodItemId) {
        return item.food_item_id == foodItemId || item.variant_id === variantId;
      }
      return item.variant_id === variantId;
    });
    if (item) {
      item.notes = notes;
      // Update cart display to show the notes are saved
      updateCart();
    }
  });
  
  // Also save notes on input (real-time)
  $(document).on('input', '.item-notes', function() {
    const variantId = $(this).data('variant-id');
    const foodItemId = $(this).data('food-item-id');
    const notes = $(this).val();
    const item = cart.find(item => {
      if (foodItemId) {
        return item.food_item_id == foodItemId || item.variant_id === variantId;
      }
      return item.variant_id === variantId;
    });
    if (item) {
      item.notes = notes;
    }
  });

  function updateCart() {
    const cartContainer = $('#cart-items');
    const cartTotal = $('#cart-total');
    const placeOrderBtn = $('#place-order-btn');
    
    // Store current notes values before re-rendering
    const notesValues = {};
    $('.item-notes').each(function() {
      const variantId = $(this).data('variant-id');
      const foodItemId = $(this).data('food-item-id');
      const sellType = $(this).data('sell-type') || 'unit';
      const key = foodItemId ? 'food-' + foodItemId : variantId + '-' + sellType;
      notesValues[key] = $(this).val();
    });

    if (cart.length === 0) {
      cartContainer.html('<p class="text-muted text-center">Your cart is empty</p>');
      cartTotal.hide();
      placeOrderBtn.prop('disabled', true);
      updateMobileCartToggle();
      return;
    }
    
    // Restore notes values to cart items
    cart.forEach(item => {
      const key = item.food_item_id ? 'food-' + item.food_item_id : item.variant_id + '-' + item.sell_type;
      if (notesValues[key] !== undefined) {
        item.notes = notesValues[key];
      }
    });

    let html = '';
    let total = 0;

    // Mobile-friendly cart display
    if ($(window).width() < 768) {
      // Mobile card view
      cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        const isFoodItem = item.food_item_id || (typeof item.variant_id === 'string' && item.variant_id.startsWith('food-'));
        html += `
          <div class="card mb-2">
            <div class="card-body p-3">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-grow-1">
                  <strong>${item.product_name}</strong><br>
                  <small class="text-muted">${item.variant} ${item.sell_type === 'tot' ? '<span class="badge badge-warning">Shot/Tot</span>' : ''}</small>
                </div>
                <button class="btn btn-sm btn-danger remove-from-cart ml-2" data-variant-id="${item.variant_id}" data-sell-type="${item.sell_type}" ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}>
                  <i class="fa fa-times"></i>
                </button>
              </div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                  <label class="small text-muted">Qty:</label>
                  <input type="number" class="form-control form-control-sm cart-quantity d-inline-block" 
                         data-variant-id="${item.variant_id}" 
                         data-sell-type="${item.sell_type}"
                         ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}
                         value="${item.quantity}" min="1" style="width: 70px;">
                </div>
                <div class="text-right">
                  <strong class="text-primary">TSh ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                </div>
              </div>
              ${isFoodItem ? `
              <div class="mt-2">
                <label class="small text-muted"><i class="fa fa-sticky-note"></i> Special Instructions:</label>
                <textarea class="form-control form-control-sm item-notes" 
                          data-variant-id="${item.variant_id}" 
                          ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}
                          rows="2" 
                          placeholder="e.g., No onions, Extra spicy, etc.">${(item.notes || '').replace(/"/g, '&quot;')}</textarea>
              </div>
              ` : ''}
            </div>
          </div>
        `;
      });
    } else {
      // Desktop table view
      html = '<table class="table table-sm">';
      cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        const isFoodItem = item.food_item_id || (typeof item.variant_id === 'string' && item.variant_id.startsWith('food-'));
        html += `
          <tr>
            <td>
              ${item.product_name}<br>
              <small class="text-muted">${item.variant} ${item.sell_type === 'tot' ? '<span class="badge badge-warning">Shot/Tot</span>' : ''}</small>
              ${isFoodItem ? `
              <div class="mt-2">
                <label class="small text-muted"><i class="fa fa-sticky-note"></i> Special Instructions:</label>
                <textarea class="form-control form-control-sm item-notes" 
                          data-variant-id="${item.variant_id}" 
                          data-sell-type="${item.sell_type}"
                          ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}
                          rows="2" 
                          placeholder="e.g., No onions, Extra spicy, etc." 
                          style="font-size: 0.85rem;">${(item.notes || '').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>
              </div>
              ` : ''}
            </td>
            <td>
              <input type="number" class="form-control form-control-sm cart-quantity" 
                     data-variant-id="${item.variant_id}" 
                     data-sell-type="${item.sell_type}"
                     ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}
                     value="${item.quantity}" min="1" style="width: 80px;">
            </td>
            <td>TSh ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td>
              <button class="btn btn-sm btn-danger remove-from-cart" data-variant-id="${item.variant_id}" data-sell-type="${item.sell_type}" ${item.food_item_id ? `data-food-item-id="${item.food_item_id}"` : ''}>
                <i class="fa fa-times"></i>
              </button>
            </td>
          </tr>
        `;
      });
      html += '</table>';
    }

    cartContainer.html(html);
    $('#total-amount').text('TSh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));
    cartTotal.show();
    placeOrderBtn.prop('disabled', false);
    
    // Update mobile cart toggle
    updateMobileCartToggle();
  }

  // Mobile cart toggle functionality
  function updateMobileCartToggle() {
    const $toggle = $('#mobile-cart-toggle');
    const $badge = $('#cart-badge');
    
    if ($(window).width() < 768) {
      if (cart.length > 0) {
        $toggle.show();
        $badge.text(cart.length);
      } else {
        $toggle.hide();
      }
    } else {
      $toggle.hide();
    }
  }

  // Toggle mobile cart
  $('#mobile-cart-toggle').on('click', function() {
    $('#shopping-cart-card').addClass('cart-sticky-mobile');
    $('html, body').animate({ scrollTop: $(document).height() }, 300);
  });

  $('#close-cart-mobile').on('click', function() {
    $('#shopping-cart-card').removeClass('cart-sticky-mobile');
  });

  // Update cart toggle on window resize
  $(window).on('resize', function() {
    updateMobileCartToggle();
    if ($(window).width() >= 768) {
      $('#shopping-cart-card').removeClass('cart-sticky-mobile');
    }
  });

  // Place order
  $('#place-order-btn').on('click', function() {
    if (cart.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Empty Cart',
        text: 'Please add items to your cart before placing an order.'
      });
      return;
    }

    console.log('=== STARTING ORDER PROCESSING ===');
    console.log('Full cart contents:', JSON.stringify(cart, null, 2));
    
    // Separate food items and regular items
    const items = cart.map((item, index) => {
      console.log(`\n--- Processing cart item ${index} ---`);
      console.log('Raw cart item:', JSON.stringify(item, null, 2));
      
      // Check if it's a food item - prioritize food_item_id
      // A food item should have food_item_id set (and it should be a valid number > 0)
      const foodItemIdRaw = item.food_item_id;
      console.log('foodItemIdRaw:', foodItemIdRaw, 'type:', typeof foodItemIdRaw);
      
      const foodItemIdParsed = foodItemIdRaw !== null && foodItemIdRaw !== undefined && foodItemIdRaw !== '' 
        ? parseInt(foodItemIdRaw) 
        : null;
      console.log('foodItemIdParsed:', foodItemIdParsed);
      
      const hasFoodItemId = foodItemIdParsed !== null && !isNaN(foodItemIdParsed) && foodItemIdParsed > 0;
      console.log('hasFoodItemId:', hasFoodItemId);
      
      console.log('Item details:', {
        food_item_id: item.food_item_id,
        variant_id: item.variant_id,
        hasFoodItemId: hasFoodItemId,
        product_name: item.product_name,
        price: item.price,
        quantity: item.quantity
      });
      
      if (hasFoodItemId) {
        // Food items - send as food_item_id with notes
        const foodId = parseInt(item.food_item_id);
        
        // Ensure foodId is a valid integer
        if (!foodId || isNaN(foodId)) {
          console.error('Invalid food_item_id at index', index, ':', item);
          return null; // Skip invalid items
        }
        
        // Validate required fields
        if (!item.product_name || !item.price || item.price <= 0) {
          console.error('Missing required fields for food item at index', index, ':', item);
          return null;
        }
        
        // Return food item object - ONLY include food_item_id, DO NOT include variant_id at all
        const foodItem = {
          food_item_id: foodId,
          product_name: item.product_name,
          quantity: parseInt(item.quantity) || 1,
          price: parseFloat(item.price) || 0
        };
        
        // Add optional fields only if they have values
        if (item.variant && item.variant.trim() !== '') {
          foodItem.variant_name = item.variant.trim();
        }
        if (item.notes && item.notes.trim() !== '') {
          foodItem.notes = item.notes.trim();
        }
        
        // Explicitly ensure variant_id is NOT included
        delete foodItem.variant_id;
        
        return foodItem;
      } else {
        // Regular product variants - only include variant_id, NOT food_item_id
        // Make sure variant_id exists and is a valid number
        if (!item.variant_id || item.variant_id === null) {
          console.error('Missing variant_id for non-food item at index', index, ':', item);
          return null;
        }
        
        // Check if variant_id is a string like 'food-123' (shouldn't happen, but just in case)
        if (typeof item.variant_id === 'string' && item.variant_id.startsWith('food-')) {
          console.error('Item has food variant_id format but no food_item_id at index', index, ':', item);
          return null;
        }
        
        const varId = parseInt(item.variant_id);
        if (!varId || isNaN(varId)) {
          console.error('Invalid variant_id at index', index, ':', item);
          return null; // Skip invalid items
        }
        
        // Return drink item object - ONLY include variant_id, DO NOT include food_item_id at all
        const drinkItem = {
          variant_id: varId,
          quantity: parseInt(item.quantity) || 1,
          sell_type: item.sell_type || 'unit'
        };
        
        // Explicitly ensure food_item_id is NOT included
        delete drinkItem.food_item_id;
        
        return drinkItem;
      }
    }).filter(item => item !== null); // Remove any null items

    // Validate that we have at least one valid item
    if (items.length === 0) {
      Swal.fire({
        icon: 'error',
        title: 'Invalid Items',
        text: 'No valid items found in cart. Please add items again.'
      });
      console.error('Cart items after processing:', cart);
      return;
    }

    console.log('Cart before processing:', cart); // Debug log
    console.log('Sending order with items:', items); // Debug log
    
    // Clean items - remove null/undefined properties and ensure only one ID type per item
    const cleanedItems = items.map((item, index) => {
      console.log(`Cleaning item ${index}:`, item);
      const cleaned = {};
      
      // Copy all properties, but skip null/undefined/empty strings
      for (const key in item) {
        const value = item[key];
        // Include the value if it's not null, undefined, or empty string
        // But allow 0 as a valid value (though IDs should be > 0)
        if (value !== null && value !== undefined && value !== '') {
          cleaned[key] = value;
        }
      }
      
      // Explicitly ensure only one ID type is present
      if (cleaned.food_item_id !== undefined && cleaned.food_item_id !== null) {
        // Food item - remove variant_id if it exists
        delete cleaned.variant_id;
        console.log(`Item ${index} is a food item with food_item_id:`, cleaned.food_item_id);
      } else if (cleaned.variant_id !== undefined && cleaned.variant_id !== null) {
        // Drink item - remove food_item_id if it exists
        delete cleaned.food_item_id;
        console.log(`Item ${index} is a drink item with variant_id:`, cleaned.variant_id);
      } else {
        console.error(`Item ${index} has neither food_item_id nor variant_id!`, cleaned);
      }
      
      return cleaned;
    });
    
    console.log('Cleaned items:', cleanedItems); // Debug log
    
    // Validate cleaned items before sending
    const invalidItems = cleanedItems.filter(item => {
      if (item.food_item_id) {
        return !item.food_item_id || !item.product_name || !item.price;
      } else {
        return !item.variant_id;
      }
    });
    
    if (invalidItems.length > 0) {
      console.error('Invalid items found:', invalidItems);
      Swal.fire({
        icon: 'error',
        title: 'Invalid Items',
        text: 'Some items in your cart are invalid. Please remove them and try again.',
        confirmButtonText: 'OK'
      });
      return;
    }
    
    // Final validation - ensure each item has exactly one ID type
    const itemsWithBothIds = cleanedItems.filter(item => item.food_item_id && item.variant_id);
    const itemsWithNoIds = cleanedItems.filter(item => !item.food_item_id && !item.variant_id);
    
    if (itemsWithBothIds.length > 0 || itemsWithNoIds.length > 0) {
      console.error('Items with invalid ID configuration:', { itemsWithBothIds, itemsWithNoIds });
      Swal.fire({
        icon: 'error',
        title: 'Invalid Items',
        text: 'Some items have invalid configuration. Please remove them and try again.',
        confirmButtonText: 'OK'
      });
      return;
    }

    // Get table and customer information (optional)
    const tableId = $('#table-id').val() || null;
    const customerName = $('#customer-name').val().trim() || null;
    let customerPhone = $('#customer-phone').val().trim();
    // If phone is just "+255" or empty, treat as null
    if (customerPhone === '+255' || customerPhone === '') {
      customerPhone = null;
    }
    const orderNotes = $('#order-notes').val().trim() || '';

    // Final check - verify each item has required ID before sending
    const itemsMissingIds = cleanedItems.filter((item, index) => {
      const hasFoodId = item.food_item_id !== undefined && item.food_item_id !== null && item.food_item_id !== '';
      const hasVariantId = item.variant_id !== undefined && item.variant_id !== null && item.variant_id !== '';
      const hasId = hasFoodId || hasVariantId;
      
      if (!hasId) {
        console.error(`Item ${index} is missing both IDs:`, item);
      }
      
      return !hasId;
    });
    
    if (itemsMissingIds.length > 0) {
      console.error('Items missing IDs:', itemsMissingIds);
      Swal.fire({
        icon: 'error',
        title: 'Invalid Items',
        html: `Some items are missing required information.<br>Please remove them from cart and try again.<br><br>Items with issues: ${itemsMissingIds.length}`,
        confirmButtonText: 'OK'
      });
      return;
    }
    
    console.log('Cleaned items structure:', cleanedItems);
    console.log('Each item check:', cleanedItems.map((item, i) => ({
      index: i,
      has_food_item_id: item.food_item_id !== undefined,
      food_item_id: item.food_item_id,
      has_variant_id: item.variant_id !== undefined,
      variant_id: item.variant_id,
      quantity: item.quantity
    })));
    
    // Prepare data object - Laravel will handle the array structure
    // Make sure all numeric values are actual numbers, not strings
    const finalItems = cleanedItems.map(item => {
      const finalItem = {};
      
      // Copy all properties, ensuring numbers are numbers
      for (const key in item) {
        const value = item[key];
        if (value !== null && value !== undefined && value !== '') {
          // Convert numeric strings to numbers for IDs and quantities
          if ((key === 'food_item_id' || key === 'variant_id' || key === 'quantity') && typeof value === 'string') {
            finalItem[key] = parseInt(value);
          } else if (key === 'price' && typeof value === 'string') {
            finalItem[key] = parseFloat(value);
          } else {
            finalItem[key] = value;
          }
        }
      }
      
      return finalItem;
    });
    
    const requestData = {
      items: finalItems,
      order_source: 'web',
      table_id: tableId || null,
      customer_name: customerName || null,
      customer_phone: customerPhone || null,
      order_notes: orderNotes || null
      // Payment method will be recorded later in Order History after customer finishes
    };
    
    // Remove null values from top level (but keep them in items array)
    Object.keys(requestData).forEach(key => {
      if (requestData[key] === null && key !== 'table_id' && key !== 'customer_name' && key !== 'customer_phone' && key !== 'order_notes') {
        delete requestData[key];
      }
    });
    
    console.log('Final request data:', JSON.stringify(requestData, null, 2));
    console.log('Items in request:', requestData.items);
    console.log('Items structure check:', finalItems.map((item, i) => ({
      index: i,
      keys: Object.keys(item),
      food_item_id: item.food_item_id,
      variant_id: item.variant_id,
      quantity: item.quantity,
      price: item.price
    })));
    if (requestData.items.length > 0) {
      console.log('First item:', requestData.items[0]);
      console.log('First item keys:', Object.keys(requestData.items[0]));
    }
    
    // Use jQuery's param with traditional: false to properly serialize nested arrays
    // This will create: items[0][food_item_id]=13&items[0][quantity]=1 format
    $.ajaxSetup({
      traditional: false // This allows nested array serialization
    });
    
    $.ajax({
      url: '{{ route("bar.waiter.create-order") }}',
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      data: requestData,
      success: function(response) {
        if (response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Order Placed!',
            text: 'Your order has been placed successfully.',
            confirmButtonText: 'OK'
          }).then(() => {
            // Clear cart and form fields
            cart = [];
            $('#table-id').val('');
            $('#customer-name').val('');
            $('#customer-phone').val('+255');
            $('#order-notes').val('');
            $('#table-info').text('');
            location.reload();
          });
        }
      },
      error: function(xhr) {
        let errorMessage = 'Failed to place order';
        
        if (xhr.responseJSON) {
          if (xhr.responseJSON.error) {
            errorMessage = xhr.responseJSON.error;
          } else if (xhr.responseJSON.errors) {
            // Validation errors
            const errors = xhr.responseJSON.errors;
            const errorList = Object.values(errors).flat().join('<br>');
            errorMessage = errorList || errorMessage;
          } else if (xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
        } else if (xhr.status === 422) {
          errorMessage = 'Validation error: Please check your order details.';
        } else if (xhr.status === 403) {
          errorMessage = 'You do not have permission to place orders.';
        } else if (xhr.status === 401) {
          errorMessage = 'Please login again to place orders.';
        }
        
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: errorMessage,
          confirmButtonText: 'OK'
        });
        
        console.error('Order creation error:', xhr.responseJSON || xhr);
      }
    });
  });

  // Cancel order
  $(document).on('click', '.cancel-order-btn', function() {
    const orderId = $(this).data('order-id');
    const orderNumber = $(this).data('order-number');
    
    Swal.fire({
      title: 'Cancel Order?',
      html: `Are you sure you want to cancel order <strong>${orderNumber}</strong>?<br><br>
             <label>Reason for cancellation:</label>
             <textarea id="cancel-reason" class="form-control mt-2" rows="3" placeholder="Enter reason (optional)" style="width: 100%;"></textarea>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, Cancel Order',
      cancelButtonText: 'No, Keep Order',
      preConfirm: () => {
        return {
          reason: document.getElementById('cancel-reason').value.trim()
        };
      }
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '{{ route("bar.waiter.cancel-order", ":id") }}'.replace(':id', orderId),
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
          },
          data: {
            reason: result.value.reason
          },
          success: function(response) {
            if (response.success) {
              Swal.fire({
                icon: 'success',
                title: 'Order Cancelled',
                text: 'The order has been cancelled successfully.',
                confirmButtonText: 'OK'
              }).then(() => {
                location.reload();
              });
            }
          },
          error: function(xhr) {
            const error = xhr.responseJSON?.error || 'Failed to cancel order';
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

  // Print receipt
  $(document).on('click', '.print-receipt-btn', function() {
    const orderId = $(this).data('order-id');
    window.open('{{ route("bar.waiter.print-receipt", ":id") }}'.replace(':id', orderId), '_blank');
  });

  // Handle table selection change
  $('#table-id').on('change', function() {
    const selectedOption = $(this).find('option:selected');
    const tableInfo = $('#table-info');
    const placeOrderBtn = $('#place-order-btn');
    
    if ($(this).val()) {
      const capacity = selectedOption.data('capacity');
      const remaining = selectedOption.data('remaining');
      const current = selectedOption.data('current');
      const location = selectedOption.data('location');
      
      let infoText = `Capacity: ${capacity} seats | `;
      infoText += `Available: ${remaining} seats | `;
      infoText += `Currently occupied: ${current} people`;
      
      if (location && location !== 'N/A') {
        infoText += ` | Location: ${location}`;
      }
      
      if (remaining === 0) {
        infoText += ' <span class="text-danger">(Table Full)</span>';
      } else if (remaining < capacity * 0.3) {
        infoText += ' <span class="text-warning">(Almost Full)</span>';
      }
      
      tableInfo.html(infoText);
    } else {
      tableInfo.text('');
    }
  });
</script>
@endpush

