<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Cart - Medalion Restaurant and Bar</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&family=Nunito:wght@600;700;800&family=Pacifico&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('lib/restaurant/animate/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('lib/restaurant/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('lib/restaurant/tempusdominus/css/tempusdominus-bootstrap-4.min.css') }}" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('css/restaurant/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('css/restaurant/style.css') }}" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* Fix hero header background image path */
        .hero-header {
            background: linear-gradient(rgba(15, 23, 43, .9), rgba(15, 23, 43, .9)), url({{ asset('img/restaurant/bg-hero.jpg') }}) !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-size: cover !important;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
            transition: all 0.3s;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .cart-item:hover {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 0 -15px;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .quantity-btn {
            width: 35px;
            height: 35px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quantity-btn:hover {
            background: #f8f9fa;
            border-color: #0d6efd;
        }
        .quantity-btn:active {
            transform: scale(0.95);
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        #cartLoadingSpinner {
            min-height: 200px;
        }
    </style>
</head>
<body>
    <div class="container-xxl bg-white p-0">
        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Navbar & Hero Start -->
        <div class="container-xxl position-relative p-0">
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 px-lg-5 py-3 py-lg-0">
                <a href="{{ route('home') }}" class="navbar-brand p-0">
                    <h1 class="text-primary m-0"><i class="fa fa-utensils me-3"></i>Medalion</h1>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0 pe-4">
                        <a href="{{ route('home') }}" class="nav-item nav-link">Home</a>
                        <a href="{{ route('about') }}" class="nav-item nav-link">About</a>
                        <a href="{{ route('services') }}" class="nav-item nav-link">Services</a>
                        <a href="{{ route('menu') }}" class="nav-item nav-link">Menu</a>
                        <a href="{{ route('contact') }}" class="nav-item nav-link">Contact</a>
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-outline-light py-2 px-4 me-2">
                        <i class="fa fa-sign-in-alt me-2"></i>Login
                    </a>
                    <a href="{{ route('customer.order') }}" class="btn btn-primary py-2 px-4">Order Now</a>
                </div>
            </nav>

            <div class="container-xxl py-5 bg-dark hero-header mb-5">
                <div class="container text-center my-5 pt-5 pb-4">
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Shopping Cart</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.order') }}">Order</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Cart</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->

        <!-- Cart Section Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                    <h5 class="section-title ff-secondary text-center text-primary fw-normal">Shopping Cart</h5>
                    <h1 class="mb-5">Review Your Order</h1>
                </div>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow-lg border-0">
                            <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fa fa-shopping-cart me-2"></i>Your Selected Items</h5>
                                <button type="button" class="btn btn-sm btn-light rounded-pill" id="clearCartBtn" style="display: none;">
                                    <i class="fa fa-trash me-1"></i>Clear Cart
                                </button>
                            </div>
                            <div class="card-body p-4 position-relative" id="cartItemsContainer">
                                <div id="cartLoadingSpinner" class="text-center py-5">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="text-muted mt-3">Loading cart items...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card shadow-lg border-0">
                            <div class="card-header bg-primary text-white py-3">
                                <h5 class="mb-0"><i class="fa fa-calculator me-2"></i>Order Summary</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Subtotal:</span>
                                    <strong id="subtotal">Tsh 0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Discount:</span>
                                    <span id="discountAmount" class="text-success">Tsh 0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-4">
                                    <strong>Total:</strong>
                                    <strong class="text-primary fs-4" id="total">Tsh 0</strong>
                                </div>
                                <button type="button" class="btn btn-success btn-lg w-100 py-3" id="checkoutBtn" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                                    <i class="fa fa-shopping-bag me-2"></i>Proceed to Checkout
                                </button>
                                <a href="{{ route('customer.order') }}" class="btn btn-outline-primary btn-lg w-100 mt-2 py-3">
                                    <i class="fa fa-arrow-left me-2"></i>Continue Shopping
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Cart Section End -->

        <!-- Checkout Modal Start -->
        <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="checkoutModalLabel">
                            <i class="fa fa-shopping-bag me-2"></i>Complete Your Order
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="orderForm" action="{{ route('customer.order.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="items_json" id="orderItemsInput">
                            
                            <div class="mb-3">
                                <label class="form-label">Your Name *</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control" required>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="tel" name="customer_phone" id="customer_phone" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Order Type *</label>
                                    <select name="order_type" id="order_type" class="form-select" required>
                                        <option value="dine_in">Dine In</option>
                                        <option value="takeaway">Takeaway</option>
                                        <option value="delivery">Delivery</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Table Number Field (for Dine In) -->
                            <div class="mb-3" id="tableNumberField" style="display: none;">
                                <label class="form-label">Table Number *</label>
                                <select name="table_number" id="table_number" class="form-select" required>
                                    <option value="">-- Select Table --</option>
                                    @foreach($tables as $table)
                                        <option value="{{ $table->table_number }}">
                                            {{ $table->table_number }}
                                            @if($table->table_name)
                                                - {{ $table->table_name }}
                                            @endif
                                            @if($table->capacity)
                                                ({{ $table->capacity }} seats)
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Please select your table number so we can serve you correctly.</small>
                            </div>

                            <!-- Location Field -->
                            <div class="mb-2" id="locationFieldLabel">
                                <label class="form-label">Your Location *</label>
                                <small class="text-muted d-block mb-1" id="locationHelpText">We use your location to deliver or prepare your order correctly.</small>
                            </div>
                            <div class="mb-3" id="deliveryMapField">
                                <div class="map-container">
                                    <div id="map" style="height: 300px; width: 100%; border-radius: 8px; border: 1px solid #ddd;"></div>
                                    <div class="location-buttons d-flex flex-wrap gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-primary" id="useCurrentLocation">
                                            <i class="fa fa-location-arrow me-1"></i>Use My Current Location
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="searchLocation">
                                            <i class="fa fa-search me-1"></i>Search Location
                                        </button>
                                    </div>
                                    <input type="text" id="locationSearch" class="form-control mt-2" placeholder="Type to search address..." style="display: none;">
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                    <input type="hidden" name="customer_location" id="customer_location">
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label">Any extra message or special instructions (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Type any extra message or special instructions for your order..."></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                        <button type="submit" form="orderForm" class="btn btn-primary">
                            <i class="fa fa-check me-2"></i>Place Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Checkout Modal End -->

        <!-- Footer Start -->
        <div class="container-fluid bg-dark text-light footer pt-5 mt-5 wow fadeIn" data-wow-delay="0.1s">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Company</h4>
                        <a class="btn btn-link" href="{{ route('about') }}">About Us</a>
                        <a class="btn btn-link" href="{{ route('contact') }}">Contact Us</a>
                        <a class="btn btn-link" href="#reservation">Reservation</a>
                        <a class="btn btn-link" href="{{ route('menu') }}">Menu</a>
                        <a class="btn btn-link" href="{{ route('services') }}">Services</a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Contact</h4>
                        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>Ben Bella Street, Moshi, Tanzania</p>
                        <p class="mb-2"><i class="fa fa-phone-alt me-3"></i>+255 749 719 998</p>
                        <p class="mb-2"><i class="fa fa-envelope me-3"></i>info@medalionrestaurant.co.tz</p>
                        <div class="d-flex pt-2">
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-twitter"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-facebook-f"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-youtube"></i></a>
                            <a class="btn btn-outline-light btn-social" href=""><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Opening Hours</h4>
                        <h5 class="text-light fw-normal">Monday - Saturday</h5>
                        <p>08:00 AM - 11:00 PM</p>
                        <h5 class="text-light fw-normal">Sunday</h5>
                        <p>10:00 AM - 10:00 PM</p>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <h4 class="section-title ff-secondary text-start text-primary fw-normal mb-4">Newsletter</h4>
                        <p>Subscribe to our newsletter for special offers and updates on new menu items.</p>
                        <div class="position-relative mx-auto" style="max-width: 400px;">
                            <input class="form-control border-primary w-100 py-3 ps-4 pe-5" type="text" placeholder="Your email">
                            <button type="button" class="btn btn-primary py-2 position-absolute top-0 end-0 mt-2 me-2">SignUp</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container">
                <div class="copyright">
                    <div class="row">
                        <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                            &copy; <a class="border-bottom" href="{{ route('home') }}">Medalion Restaurant and Bar</a>, All Right Reserved.
                        </div>
                        <div class="col-md-6 text-center text-md-end">
                            <div class="footer-menu">
                                <a href="{{ route('home') }}">Home</a>
                                <a href="{{ route('about') }}">About</a>
                                <a href="{{ route('contact') }}">Contact</a>
                                <a href="{{ route('menu') }}">Menu</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->

        <!-- Back to Top -->
        <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAAYvuupufSY4CHeJ3-kGe5WOChLkt1C3o&libraries=places&callback=initMap" async defer></script>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('lib/restaurant/wow/wow.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/easing/easing.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/counterup/counterup.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/owlcarousel/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/tempusdominus/js/moment.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/tempusdominus/js/moment-timezone.min.js') }}"></script>
    <script src="{{ asset('lib/restaurant/tempusdominus/js/tempusdominus-bootstrap-4.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('js/restaurant/main.js') }}"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        let cart = [];
        let discountAmount = 0;
        
        // Load cart from localStorage
        $(document).ready(function() {
            // Show loading spinner
            $('#cartLoadingSpinner').show();
            
            // Simulate loading delay for better UX
            setTimeout(() => {
                const saved = localStorage.getItem('medalion_selected_items');
                if (saved) {
                    try {
                        cart = JSON.parse(saved);
                        updateCart();
                    } catch(e) {
                        console.error('Error loading cart:', e);
                        $('#cartLoadingSpinner').hide();
                        $('#cartItemsContainer').html('<p class="text-danger text-center py-5">Error loading cart. Please try again.</p>');
                    }
                } else {
                    updateCart(); // Will show empty cart
                }
            }, 500);
        });
        
        function updateCart() {
            const container = $('#cartItemsContainer');
            const spinner = $('#cartLoadingSpinner');
            
            // Hide spinner
            spinner.hide();
            
            if (cart.length === 0) {
                container.html(`
                    <div class="text-center py-5">
                        <i class="fa fa-shopping-cart fa-4x text-muted mb-4 opacity-50"></i>
                        <h5 class="text-muted mb-3">Your cart is empty</h5>
                        <p class="text-muted mb-4">Looks like you haven't added any items yet.</p>
                        <a href="{{ route('customer.order') }}" class="btn btn-primary btn-lg">
                            <i class="fa fa-arrow-left me-2"></i>Continue Shopping
                        </a>
                    </div>
                `);
                $('#checkoutBtn').prop('disabled', true);
                $('#clearCartBtn').hide();
                return;
            }
            
            // Show clear cart button
            $('#clearCartBtn').show();
            
            let html = '';
            let subtotal = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                // Get product image - check if it's a food item or regular product
                const isFoodItem = typeof item.variant_id === 'string' && item.variant_id.startsWith('food-');
                let itemImage = '';
                
                const isJuiceItem = typeof item.variant_id === 'string' && item.variant_id.startsWith('juice-');
                
                if (isFoodItem) {
                    // Food items - use default images based on food type
                    const foodImages = {
                        'chicken': '{{ asset("img/restaurant/menu-1.jpg") }}',
                        'spring': '{{ asset("img/restaurant/menu-2.jpg") }}',
                        'samosa': '{{ asset("img/restaurant/menu-3.jpg") }}',
                        'nachos': '{{ asset("img/restaurant/menu-4.jpg") }}',
                        'grilled': '{{ asset("img/restaurant/menu-5.jpg") }}',
                        'beef': '{{ asset("img/restaurant/menu-6.jpg") }}',
                        'fish': '{{ asset("img/restaurant/menu-7.jpg") }}',
                        'biryani': '{{ asset("img/restaurant/menu-8.jpg") }}',
                        'pizza': '{{ asset("img/restaurant/menu-1.jpg") }}',
                        'pasta': '{{ asset("img/restaurant/menu-2.jpg") }}',
                        'ice': '{{ asset("img/restaurant/menu-3.jpg") }}',
                        'chocolate': '{{ asset("img/restaurant/menu-4.jpg") }}'
                    };
                    
                    const foodKey = Object.keys(foodImages).find(key => 
                        item.product_name.toLowerCase().includes(key)
                    );
                    itemImage = foodKey ? foodImages[foodKey] : '{{ asset("img/restaurant/menu-1.jpg") }}';
                } else if (isJuiceItem) {
                    // Juice items - use default images based on juice type
                    const juiceImages = {
                        'orange': '{{ asset("img/restaurant/menu-1.jpg") }}',
                        'mango': '{{ asset("img/restaurant/menu-2.jpg") }}',
                        'passion': '{{ asset("img/restaurant/menu-3.jpg") }}',
                        'pineapple': '{{ asset("img/restaurant/menu-4.jpg") }}',
                        'watermelon': '{{ asset("img/restaurant/menu-5.jpg") }}',
                        'avocado': '{{ asset("img/restaurant/menu-6.jpg") }}',
                        'strawberry': '{{ asset("img/restaurant/menu-7.jpg") }}',
                        'mixed': '{{ asset("img/restaurant/menu-8.jpg") }}',
                        'lemonade': '{{ asset("img/restaurant/menu-1.jpg") }}',
                        'carrot': '{{ asset("img/restaurant/menu-2.jpg") }}'
                    };
                    
                    const juiceKey = Object.keys(juiceImages).find(key => 
                        item.variant_id.toLowerCase().includes(key) || item.product_name.toLowerCase().includes(key)
                    );
                    itemImage = juiceKey ? juiceImages[juiceKey] : '{{ asset("img/restaurant/menu-1.jpg") }}';
                } else {
                    // Regular products - use image from item data if available
                    if (item.image && item.image.startsWith('http')) {
                        itemImage = item.image;
                    } else if (item.image && item.image.includes('storage/')) {
                        itemImage = `{{ asset('') }}${item.image}`;
                    } else if (item.image) {
                        itemImage = `{{ asset('storage/') }}/${item.image}`;
                    } else {
                        itemImage = '{{ asset("img/restaurant/menu-1.jpg") }}';
                    }
                }
                
                html += `
                    <div class="cart-item">
                        <div class="d-flex align-items-start">
                            <img src="${itemImage}" alt="${item.product_name}" class="cart-item-image me-3" onerror="this.src='{{ asset('img/restaurant/menu-1.jpg') }}'">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold">${item.product_name}</h6>
                                        <small class="text-muted d-block">${item.variant_name}</small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger remove-item ms-2" data-variant-id="${item.variant_id}" title="Remove item">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <div class="row g-2 align-items-center mt-3">
                                    <div class="col-12 col-md-6">
                                        <label class="small text-muted mb-1 d-block">Quantity:</label>
                                        <div class="quantity-controls">
                                            <button type="button" class="quantity-btn decrease-qty" data-variant-id="${item.variant_id}" ${item.quantity <= 1 ? 'disabled' : ''}>
                                                <i class="fa fa-minus"></i>
                                            </button>
                                            <input type="number" class="form-control form-control-sm cart-quantity" 
                                                   data-variant-id="${item.variant_id}" 
                                                   value="${item.quantity}" 
                                                   min="1" 
                                                   max="${item.stock}"
                                                   readonly>
                                            <button type="button" class="quantity-btn increase-qty" data-variant-id="${item.variant_id}" ${item.quantity >= item.stock ? 'disabled' : ''}>
                                                <i class="fa fa-plus"></i>
                                            </button>
                                        </div>
                                        ${item.stock < 10 ? `<small class="text-warning d-block mt-1"><i class="fa fa-exclamation-triangle"></i> Only ${item.stock} left in stock</small>` : ''}
                                    </div>
                                    <div class="col-12 col-md-6 text-start text-md-end">
                                        <div class="small text-muted mb-1">Unit Price</div>
                                        <div class="small text-muted">Tsh ${item.price.toLocaleString()} × <span class="item-qty">${item.quantity}</span></div>
                                        <strong class="text-primary fs-5 item-total">Tsh ${itemTotal.toLocaleString()}</strong>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <input type="text" class="form-control form-control-sm item-notes" 
                                           data-variant-id="${item.variant_id}"
                                           placeholder="Add note (optional)" 
                                           value="${item.notes || ''}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
            
            const finalTotal = Math.max(0, subtotal - discountAmount);
            $('#subtotal').text('Tsh ' + subtotal.toLocaleString());
            $('#discountAmount').text(discountAmount > 0 ? '-Tsh ' + discountAmount.toLocaleString() : 'Tsh 0');
            $('#total').text('Tsh ' + finalTotal.toLocaleString());
            $('#checkoutBtn').prop('disabled', false);
            
            // Update order items input
            const orderItems = cart.map(item => {
                const isFoodItem = typeof item.variant_id === 'string' && item.variant_id.startsWith('food-');
                const isJuiceItem = typeof item.variant_id === 'string' && item.variant_id.startsWith('juice-');
                return {
                    product_variant_id: (isFoodItem || isJuiceItem) ? null : item.variant_id,
                    food_item_id: isFoodItem ? item.variant_id : null,
                    juice_item_id: isJuiceItem ? item.variant_id : null,
                    product_name: item.product_name,
                    variant_name: item.variant_name,
                    quantity: item.quantity,
                    price: item.price,
                    notes: item.notes || ''
                };
            });
            $('#orderItemsInput').val(JSON.stringify(orderItems));
        }
        
        // Update quantity
        function updateQuantity(variantId, newQuantity) {
            const item = cart.find(i => i.variant_id == variantId);
            if (item) {
                item.quantity = Math.max(1, Math.min(newQuantity, item.stock));
                saveCart();
                updateCart();
            }
        }
        
        // Increase quantity
        $(document).on('click', '.increase-qty', function() {
            const variantId = $(this).data('variant-id');
            const item = cart.find(i => i.variant_id == variantId);
            if (item && item.quantity < item.stock) {
                updateQuantity(variantId, item.quantity + 1);
            }
        });
        
        // Decrease quantity
        $(document).on('click', '.decrease-qty', function() {
            const variantId = $(this).data('variant-id');
            const item = cart.find(i => i.variant_id == variantId);
            if (item && item.quantity > 1) {
                updateQuantity(variantId, item.quantity - 1);
            }
        });
        
        // Update quantity on input change
        $(document).on('change', '.cart-quantity', function() {
            const variantId = $(this).data('variant-id');
            const quantity = parseInt($(this).val()) || 1;
            updateQuantity(variantId, quantity);
        });
        
        // Remove item
        $(document).on('click', '.remove-item', function() {
            const variantId = $(this).data('variant-id');
            const item = cart.find(i => i.variant_id == variantId);
            
            if (!item) return;
            
            Swal.fire({
                title: 'Remove Item?',
                html: `Are you sure you want to remove <strong>${item.product_name}</strong> from your cart?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-trash me-2"></i>Yes, Remove It',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    cart = cart.filter(item => item.variant_id != variantId);
                    saveCart();
                    updateCart();
                    
                    Swal.fire({
                        title: 'Removed!',
                        text: `${item.product_name} has been removed from your cart.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            });
        });
        
        // Clear cart
        $('#clearCartBtn').on('click', function() {
            if (cart.length === 0) return;
            
            Swal.fire({
                title: 'Clear Cart?',
                html: 'Are you sure you want to clear your entire cart?<br><small class="text-muted">This action cannot be undone.</small>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fa fa-trash me-2"></i>Yes, Clear Cart',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    $('#cartLoadingSpinner').show();
                    $('#cartItemsContainer').html('');
                    
                    // Clear cart after short delay for visual feedback
                    setTimeout(() => {
                        cart = [];
                        saveCart();
                        updateCart();
                        
                        Swal.fire({
                            title: 'Cart Cleared!',
                            text: 'Your cart has been cleared successfully.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                            toast: true,
                            position: 'top-end'
                        });
                    }, 300);
                }
            });
        });
        
        // Save notes
        $(document).on('blur', '.item-notes', function() {
            const variantId = $(this).data('variant-id');
            const notes = $(this).val();
            const item = cart.find(i => i.variant_id == variantId);
            if (item) {
                item.notes = notes;
                saveCart();
            }
        });
        
        function saveCart() {
            localStorage.setItem('medalion_selected_items', JSON.stringify(cart));
        }
        
        // Google Maps
        let map, marker, geocoder, autocomplete;
        let isMapInitialized = false;
        
        window.initMap = function() {
            if (!document.getElementById('map')) return;
            
            const defaultLocation = { lat: -3.3344, lng: 37.3404 };
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 13,
                mapTypeControl: true,
                streetViewControl: true,
            });
            
            geocoder = new google.maps.Geocoder();
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });
            
            marker.addListener('dragend', function() {
                const pos = marker.getPosition();
                $('#latitude').val(pos.lat());
                $('#longitude').val(pos.lng());
                geocoder.geocode({ location: pos }, (results, status) => {
                    if (status === 'OK' && results[0]) {
                        $('#customer_location').val(results[0].formatted_address);
                    }
                });
            });
            
            map.addListener('click', function(e) {
                placeMarker(e.latLng);
            });
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const loc = { lat: position.coords.latitude, lng: position.coords.longitude };
                        placeMarker(loc);
                    },
                    function(error) {
                        console.warn('Geolocation failed:', error);
                    }
                );
            }
            
            isMapInitialized = true;
        };
        
        function placeMarker(location) {
            marker.setPosition(location);
            map.setCenter(location);
            $('#latitude').val(location.lat());
            $('#longitude').val(location.lng());
            geocoder.geocode({ location: location }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    $('#customer_location').val(results[0].formatted_address);
                }
            });
        }
        
        // Show/hide fields based on order type
        $('#order_type').on('change', function() {
            const orderType = $(this).val();
            const tableNumberField = $('#tableNumberField');
            const tableNumberInput = $('#table_number');
            const locationFieldLabel = $('#locationFieldLabel');
            const locationHelpText = $('#locationHelpText');
            const deliveryMapField = $('#deliveryMapField');
            const customerLocationInput = $('#customer_location');
            
            if (orderType === 'dine_in') {
                // Show table number field, hide location map
                tableNumberField.show();
                tableNumberInput.prop('required', true);
                deliveryMapField.hide();
                locationFieldLabel.hide();
                customerLocationInput.prop('required', false);
                customerLocationInput.val('Medalion Restaurant - Dine In');
                $('#latitude').val('');
                $('#longitude').val('');
            } else if (orderType === 'delivery') {
                // Hide table number, show location map
                tableNumberField.hide();
                tableNumberInput.prop('required', false);
                tableNumberInput.val('');
                locationFieldLabel.show();
                locationHelpText.text('We use your location to deliver your order correctly.');
                deliveryMapField.show();
                customerLocationInput.prop('required', true);
                customerLocationInput.val('');
                
                // Initialize map if not already done
                setTimeout(function() {
                    if (!isMapInitialized && typeof google !== 'undefined' && google.maps) {
                        window.initMap();
                    } else if (!isMapInitialized) {
                        const checkInterval = setInterval(function() {
                            if (typeof google !== 'undefined' && google.maps && !isMapInitialized) {
                                window.initMap();
                                clearInterval(checkInterval);
                            }
                        }, 500);
                        setTimeout(function() {
                            clearInterval(checkInterval);
                        }, 10000);
                    }
                }, 100);
            } else if (orderType === 'takeaway') {
                // Hide table number, show simplified location
                tableNumberField.hide();
                tableNumberInput.prop('required', false);
                tableNumberInput.val('');
                locationFieldLabel.show();
                locationHelpText.text('Please provide your location for order preparation.');
                deliveryMapField.show();
                customerLocationInput.prop('required', true);
                customerLocationInput.val('');
            }
        });
        
        // Trigger on page load to set initial state
        $('#order_type').trigger('change');
        
        $('#useCurrentLocation').on('click', function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const loc = { lat: position.coords.latitude, lng: position.coords.longitude };
                        placeMarker(loc);
                    }
                );
            }
        });
    </script>
</body>
</html>

