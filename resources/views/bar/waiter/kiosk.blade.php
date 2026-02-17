<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Kiosk - Order Products</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('css/restaurant/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('css/restaurant/style.css') }}" rel="stylesheet">
    
    <style>
        :root {
            --primary: #940000;
            --secondary: #06a3da;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Nunito', sans-serif;
        }
        
        .kiosk-header {
            background: linear-gradient(135deg, var(--primary) 0%, #7a0000 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .kiosk-header h1 {
            font-weight: 800;
            margin: 0;
            font-size: 2.5rem;
        }
        
        .cart-badge {
            position: relative;
            display: inline-block;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--secondary);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .products-section {
            padding: 40px 0;
        }
        
        .product-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            height: 100%;
            border: 2px solid transparent;
            cursor: pointer;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(148, 0, 0, 0.2);
            border-color: var(--primary);
        }
        
        .product-card.selected {
            border-color: var(--primary);
            background: #fff5f5;
        }
        
        .product-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .product-variant {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .product-price {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 800;
            margin-bottom: 10px;
        }
        
        .product-stock {
            color: #28a745;
            font-size: 0.9rem;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }
        
        .quantity-input {
            width: 80px;
            text-align: center;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .quantity-input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .add-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            flex: 1;
            transition: all 0.3s;
        }
        
        .add-btn:hover {
            background: #7a0000;
            transform: scale(1.05);
        }
        
        .add-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .cart-section {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            padding: 20px;
            z-index: 1000;
            display: none;
        }
        
        .cart-section.active {
            display: block;
        }
        
        .cart-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
        }
        
        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-total {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
        }
        
        .checkout-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .checkout-btn:hover {
            background: #7a0000;
            transform: scale(1.02);
        }
        
        .order-summary-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .order-summary-modal .modal-header {
            background: var(--primary);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        
        .order-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .waiter-login-form {
            margin-top: 20px;
        }
        
        .waiter-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        
        .waiter-input:focus {
            border-color: var(--primary);
            outline: none;
        }
        
        .send-order-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
        }
        
        .send-order-btn:hover {
            background: #7a0000;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .selected-count {
            background: var(--secondary);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="kiosk-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fa fa-utensils me-3"></i>Order Kiosk</h1>
                </div>
                <div class="col-md-6 text-end">
                    <div class="cart-badge">
                        <button class="btn btn-light btn-lg" id="view-cart-btn" style="display: none;">
                            <i class="fa fa-shopping-cart"></i> View Cart
                            <span class="cart-count" id="cart-count">0</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Section -->
    <div class="products-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-primary">Available Products</h2>
                <p class="text-muted">Select items to add to your order</p>
                <span class="selected-count" id="selected-count" style="display: none;">0 items selected</span>
            </div>
            
            @if(count($variants) > 0)
                <div class="row g-4" id="products-container">
                    @foreach($variants as $variant)
                    <div class="col-lg-3 col-md-4 col-sm-6">
                        <div class="product-card" data-variant-id="{{ $variant['id'] }}" data-max-quantity="{{ $variant['quantity'] }}">
                            <div class="product-name">{{ $variant['product_name'] }}</div>
                            <div class="product-variant">{{ $variant['variant'] }}</div>
                            <div class="product-price">TSh {{ number_format($variant['selling_price'], 2) }}</div>
                            <div class="product-stock">
                                <i class="fa fa-check-circle"></i> {{ number_format($variant['quantity']) }} {{ $variant['packaging_type'] }} available
                            </div>
                            <div class="quantity-controls">
                                <input type="number" class="quantity-input" 
                                       min="1" max="{{ $variant['quantity'] }}" 
                                       value="1" 
                                       data-variant-id="{{ $variant['id'] }}">
                                <button class="add-btn add-to-cart-btn" 
                                        data-variant-id="{{ $variant['id'] }}"
                                        data-product-name="{{ $variant['product_name'] }}"
                                        data-variant="{{ $variant['variant'] }}"
                                        data-price="{{ $variant['selling_price'] }}"
                                        data-max-quantity="{{ $variant['quantity'] }}">
                                    <i class="fa fa-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="fa fa-box-open"></i>
                    <h3>No products available</h3>
                    <p>There are no products in counter stock at the moment.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Cart Section -->
    <div class="cart-section" id="cart-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-3"><i class="fa fa-shopping-cart"></i> Shopping Cart</h5>
                    <div class="cart-items" id="cart-items">
                        <!-- Cart items will be inserted here -->
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="total-amount">TSh 0.00</span>
                    </div>
                    <button class="checkout-btn" id="checkout-btn">
                        <i class="fa fa-check"></i> Finish & Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Summary Modal -->
    <div class="modal fade order-summary-modal" id="order-summary-modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-receipt"></i> Order Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="order-summary-content">
                        <!-- Order summary will be inserted here -->
                    </div>
                    <div class="waiter-login-form">
                        <h6 class="mb-3"><i class="fa fa-user"></i> Enter Waiter ID to Send Order</h6>
                        <form id="waiter-order-form">
                            <input type="text" class="waiter-input" id="waiter-staff-id" 
                                   placeholder="Enter Waiter Staff ID" required autofocus>
                            <input type="password" class="waiter-input" id="waiter-password" 
                                   placeholder="Enter Password" required>
                            <button type="submit" class="send-order-btn">
                                <i class="fa fa-paper-plane"></i> Send Order to Counter
                            </button>
                        </form>
                        <div id="waiter-error" class="text-danger mt-2" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let cart = [];
        let orderSummary = null;

        // Add to cart
        $(document).on('click', '.add-to-cart-btn', function() {
            const variantId = $(this).data('variant-id');
            const productName = $(this).data('product-name');
            const variant = $(this).data('variant');
            const price = parseFloat($(this).data('price'));
            const maxQuantity = parseInt($(this).data('max-quantity'));
            const quantity = parseInt($(this).closest('.product-card').find('.quantity-input').val()) || 1;

            if (quantity > maxQuantity) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Insufficient Stock',
                    text: 'Only ' + maxQuantity + ' available',
                    confirmButtonColor: '#940000'
                });
                return;
            }

            const existingItem = cart.find(item => item.variant_id === variantId);
            if (existingItem) {
                const newQuantity = existingItem.quantity + quantity;
                if (newQuantity > maxQuantity) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Stock',
                        text: 'Only ' + maxQuantity + ' available',
                        confirmButtonColor: '#940000'
                    });
                    return;
                }
                existingItem.quantity = newQuantity;
            } else {
                cart.push({
                    variant_id: variantId,
                    product_name: productName,
                    variant: variant,
                    price: price,
                    quantity: quantity
                });
            }

            updateCart();
            updateSelectedCount();
            
            // Visual feedback
            const card = $(this).closest('.product-card');
            card.addClass('selected');
            setTimeout(() => {
                card.removeClass('selected');
            }, 500);
        });

        // Remove from cart
        $(document).on('click', '.remove-from-cart', function() {
            const variantId = $(this).data('variant-id');
            cart = cart.filter(item => item.variant_id !== variantId);
            updateCart();
            updateSelectedCount();
        });

        // Update quantity in cart
        $(document).on('change', '.cart-quantity', function() {
            const variantId = $(this).data('variant-id');
            const quantity = parseInt($(this).val()) || 1;
            const item = cart.find(item => item.variant_id === variantId);
            if (item) {
                item.quantity = quantity;
                updateCart();
            }
        });

        function updateCart() {
            const cartCount = $('#cart-count');
            const viewCartBtn = $('#view-cart-btn');
            const cartSection = $('#cart-section');
            const cartItems = $('#cart-items');
            const totalAmount = $('#total-amount');

            if (cart.length === 0) {
                cartCount.text('0');
                viewCartBtn.hide();
                cartSection.removeClass('active');
                return;
            }

            let total = 0;
            let html = '';

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                html += `
                    <div class="cart-item">
                        <div>
                            <strong>${item.product_name}</strong><br>
                            <small class="text-muted">${item.variant}</small>
                        </div>
                        <div>
                            <input type="number" class="form-control cart-quantity" 
                                   data-variant-id="${item.variant_id}" 
                                   value="${item.quantity}" min="1" style="width: 80px; display: inline-block;">
                        </div>
                        <div>
                            <strong class="text-primary">TSh ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                        </div>
                        <div>
                            <button class="btn btn-sm btn-danger remove-from-cart" data-variant-id="${item.variant_id}">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            cartItems.html(html);
            totalAmount.text('TSh ' + total.toLocaleString('en-US', {minimumFractionDigits: 2}));
            cartCount.text(cart.length);
            viewCartBtn.show();
            cartSection.addClass('active');
        }

        function updateSelectedCount() {
            const selectedCount = $('#selected-count');
            if (cart.length > 0) {
                selectedCount.text(cart.length + ' item(s) selected').show();
            } else {
                selectedCount.hide();
            }
        }

        // View cart button
        $('#view-cart-btn').on('click', function() {
            $('html, body').animate({
                scrollTop: $(document).height()
            }, 500);
        });

        // Checkout button
        $('#checkout-btn').on('click', function() {
            if (cart.length === 0) return;

            // Build order summary
            let total = 0;
            let summaryHtml = '<div class="order-item"><h6 class="fw-bold mb-3">Order Items:</h6><ul class="list-unstyled">';
            
            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                summaryHtml += `
                    <li class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>${item.quantity}x ${item.product_name}</span>
                            <strong class="text-primary">TSh ${itemTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong>
                        </div>
                        <small class="text-muted">${item.variant}</small>
                    </li>
                `;
            });
            
            summaryHtml += `</ul></div>
                <div class="order-item border-top pt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Total Amount:</h5>
                        <h4 class="mb-0 text-primary fw-bold">TSh ${total.toLocaleString('en-US', {minimumFractionDigits: 2})}</h4>
                    </div>
                </div>`;

            $('#order-summary-content').html(summaryHtml);
            orderSummary = {
                items: cart.map(item => ({
                    variant_id: item.variant_id,
                    quantity: item.quantity
                })),
                total: total
            };

            const modal = new bootstrap.Modal(document.getElementById('order-summary-modal'));
            modal.show();
        });

        // Submit order
        $('#waiter-order-form').on('submit', function(e) {
            e.preventDefault();
            
            const staffId = $('#waiter-staff-id').val();
            const password = $('#waiter-password').val();
            const errorDiv = $('#waiter-error');

            if (!staffId || !password) {
                errorDiv.text('Please enter both Waiter ID and Password').show();
                return;
            }

            // First login the waiter
            $.ajax({
                url: '{{ route("bar.kiosk.login") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                data: {
                    staff_id: staffId,
                    password: password
                },
                success: function(response) {
                    if (response.success) {
                        // Now create the order
                        $.ajax({
                            url: '{{ route("bar.waiter.create-order") }}',
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            data: {
                                items: orderSummary.items,
                                order_source: 'kiosk'
                            },
                            success: function(orderResponse) {
                                if (orderResponse.success) {
                                    const modal = bootstrap.Modal.getInstance(document.getElementById('order-summary-modal'));
                                    modal.hide();
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Order Placed Successfully!',
                                        text: 'Your order has been sent to the counter.',
                                        confirmButtonColor: '#940000',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        // Reset cart and redirect
                                        cart = [];
                                        updateCart();
                                        updateSelectedCount();
                                        $('#waiter-staff-id').val('');
                                        $('#waiter-password').val('');
                                        errorDiv.hide();
                                        
                                        // Scroll to top
                                        $('html, body').animate({
                                            scrollTop: 0
                                        }, 500);
                                    });
                                }
                            },
                            error: function(xhr) {
                                const error = xhr.responseJSON?.error || 'Failed to place order';
                                errorDiv.text(error).show();
                            }
                        });
                    }
                },
                error: function(xhr) {
                    const error = xhr.responseJSON?.error || 'Invalid waiter credentials';
                    errorDiv.text(error).show();
                }
            });
        });

        // Initialize
        $(document).ready(function() {
            updateCart();
            updateSelectedCount();
        });
    </script>
</body>
</html>
