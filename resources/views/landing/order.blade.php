<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Order Online - Medalion Restaurant and Bar</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="restaurant, bar, tanzania, order, food, drinks, medallion" name="keywords">
    <meta content="Order food and beverages online from Medalion Restaurant and Bar" name="description">
    <meta name="csrf-token" id="csrf_meta_token" content="{{ csrf_token() }}">

    <!-- Favicon -->
    <link href="{{ asset('favicon.ico') }}" rel="icon">

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
    
    <style>
        /* Fix hero header background image path */
        .hero-header {
            background: linear-gradient(rgba(15, 23, 43, .9), rgba(15, 23, 43, .9)), url({{ asset('img/restaurant/bg-hero.jpg') }}) !important;
            background-position: center center !important;
            background-repeat: no-repeat !important;
            background-size: cover !important;
        }
        
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .cart-item {
            transition: all 0.3s;
        }
        .cart-item:hover {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px !important;
            margin-left: -10px !important;
            margin-right: -10px !important;
        }
        .cart-summary {
            position: sticky;
            top: 20px;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
            font-size: 16px;
            padding: 8px;
        }
        #map {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .map-container {
            margin-top: 15px;
        }
        .location-buttons {
            margin-top: 10px;
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
            transition: transform 0.3s;
        }
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
        }
        .search-filter-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .input-group-lg .form-control,
        .input-group-lg .input-group-text {
            border-radius: 8px;
        }
        .form-select-lg {
            border-radius: 8px;
        }
        .min-height-100 {
            min-height: 100px;
        }
        .product-checkbox-wrapper {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .product-checkbox-wrapper:hover {
            background-color: #e3f2fd !important;
            border-color: #0d6efd !important;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.2);
        }
        .product-checkbox-wrapper input[type="checkbox"]:checked ~ label {
            color: #0d6efd !important;
        }
        .product-checkbox-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-top: 2px;
        }
        .product-checkbox-wrapper input[type="checkbox"]:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .cursor-pointer {
            cursor: pointer;
        }
        .product-item:hover .product-checkbox-wrapper {
            background-color: #e3f2fd;
        }
        .tab-class .nav-pills .nav-link {
            border-radius: 0;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab-class .nav-pills .nav-link:hover {
            border-bottom-color: #0d6efd;
        }
        .tab-class .nav-pills .nav-link.active {
            border-bottom-color: #0d6efd;
            background-color: transparent;
        }
        .cart-item-notes {
            margin-top: 8px;
            font-size: 0.85rem;
        }
        .estimated-time {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #0d6efd;
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
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Order Online</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Order</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->


        <!-- Order Section Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row">
                    <!-- Products Section -->
                    <div class="col-lg-8">
                        <div class="mb-4">
                            <div class="text-center wow fadeInUp" data-wow-delay="0.1s">
                                <h5 class="section-title ff-secondary text-center text-primary fw-normal">Order Online</h5>
                                <h1 class="mb-4">Select Your Items</h1>
                            </div>
                            
                            <!-- Search and Filter Section -->
                            <div class="mb-4 wow fadeInUp" data-wow-delay="0.2s">
                                <div class="row g-3 justify-content-center">
                                    <div class="col-lg-6 col-md-8">
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-primary text-white border-primary">
                                                <i class="fa fa-search"></i>
                                            </span>
                                            <input type="text" id="productSearch" class="form-control border-primary" placeholder="Search for food or drinks...">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-4">
                                        <select id="categoryFilter" class="form-select form-select-lg border-primary">
                                            <option value="">All Categories</option>
                                            <option value="Food">Food</option>
                                            @foreach($productsByCategory->keys() as $category)
                                                <option value="{{ $category }}">{{ $category ?: 'Uncategorized' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab Navigation -->
                            <div class="tab-class text-center wow fadeInUp" data-wow-delay="0.1s">
                                <ul class="nav nav-pills d-inline-flex justify-content-center border-bottom mb-5">
                                    <li class="nav-item">
                                        <a class="d-flex align-items-center text-start mx-3 ms-0 pb-3 active" data-bs-toggle="pill" href="#tab-food">
                                            <i class="fa fa-utensils fa-2x text-primary"></i>
                                            <div class="ps-3">
                                                <small class="text-body">Delicious</small>
                                                <h6 class="mt-n1 mb-0">Food Menu</h6>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="d-flex align-items-center text-start mx-3 pb-3" data-bs-toggle="pill" href="#tab-beverages">
                                            <i class="fa fa-wine-glass fa-2x text-primary"></i>
                                            <div class="ps-3">
                                                <small class="text-body">Premium</small>
                                                <h6 class="mt-n1 mb-0">Drinks & Bar</h6>
                                            </div>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="d-flex align-items-center text-start mx-3 me-0 pb-3" data-bs-toggle="pill" href="#tab-juice">
                                            <i class="fa fa-glass-water fa-2x text-primary"></i>
                                            <div class="ps-3">
                                                <small class="text-body">Fresh</small>
                                                <h6 class="mt-n1 mb-0">Juice</h6>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <!-- Food Tab -->
                                    <div id="tab-food" class="tab-pane fade show p-0 active">
                                        <div class="row g-4">
                                            <!-- Food Items in Menu Page Style -->
                                            <div class="col-lg-6 product-item" data-product-name="chicken wings" data-category="Food">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-1.jpg') }}" alt="Chicken Wings" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Chicken Wings</span>
                                                            <span class="text-primary">Tsh 15,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Crispy fried chicken wings served with your choice of sauce</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="food-chicken-wings"
                                                                   data-variant-id="food-chicken-wings" 
                                                                   data-product-name="Chicken Wings" 
                                                                   data-variant-name="6 pieces" 
                                                                   data-price="15000" 
                                                                   data-stock="999"
                                                                   id="food-chicken-wings">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-chicken-wings">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="spring rolls" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-2.jpg') }}" alt="Spring Rolls" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Spring Rolls</span>
                                                        <span class="text-primary">Tsh 8,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Fresh vegetable spring rolls with sweet chili sauce</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-spring-rolls"
                                                               data-variant-id="food-spring-rolls" 
                                                               data-product-name="Spring Rolls" 
                                                               data-variant-name="4 pieces" 
                                                               data-price="8000" 
                                                               data-stock="999"
                                                               id="food-spring-rolls">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-spring-rolls">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="samosa" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-3.jpg') }}" alt="Samosa" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Samosa</span>
                                                        <span class="text-primary">Tsh 5,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Crispy pastry filled with spiced meat or vegetables</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-samosa"
                                                               data-variant-id="food-samosa" 
                                                               data-product-name="Samosa" 
                                                               data-variant-name="3 pieces" 
                                                               data-price="5000" 
                                                               data-stock="999"
                                                               id="food-samosa">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-samosa">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="nachos" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-4.jpg') }}" alt="Nachos" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Nachos</span>
                                                        <span class="text-primary">Tsh 12,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Crispy tortilla chips with cheese, jalapeños, and salsa</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-nachos"
                                                               data-variant-id="food-nachos" 
                                                               data-product-name="Nachos" 
                                                               data-variant-name="Regular" 
                                                               data-price="12000" 
                                                               data-stock="999"
                                                               id="food-nachos">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-nachos">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="grilled chicken" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-5.jpg') }}" alt="Grilled Chicken" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Grilled Chicken</span>
                                                        <span class="text-primary">Tsh 25,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Tender grilled chicken marinated in herbs and spices, served with chips and salad</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-grilled-chicken"
                                                               data-variant-id="food-grilled-chicken" 
                                                               data-product-name="Grilled Chicken" 
                                                               data-variant-name="Full" 
                                                               data-price="25000" 
                                                               data-stock="999"
                                                               id="food-grilled-chicken">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-grilled-chicken">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="beef steak" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-6.jpg') }}" alt="Beef Steak" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Beef Steak</span>
                                                        <span class="text-primary">Tsh 30,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Premium beef steak cooked to perfection, served with vegetables and chips</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-beef-steak"
                                                               data-variant-id="food-beef-steak" 
                                                               data-product-name="Beef Steak" 
                                                               data-variant-name="250g" 
                                                               data-price="30000" 
                                                               data-stock="999"
                                                               id="food-beef-steak">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-beef-steak">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="fish curry" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-7.jpg') }}" alt="Fish Curry" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Fish Curry</span>
                                                        <span class="text-primary">Tsh 18,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Fresh fish in rich coconut curry sauce, served with rice</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-fish-curry"
                                                               data-variant-id="food-fish-curry" 
                                                               data-product-name="Fish Curry" 
                                                               data-variant-name="Regular" 
                                                               data-price="18000" 
                                                               data-stock="999"
                                                               id="food-fish-curry">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-fish-curry">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="chicken biryani" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-8.jpg') }}" alt="Chicken Biryani" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Chicken Biryani</span>
                                                        <span class="text-primary">Tsh 20,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Fragrant basmati rice with spiced chicken, served with raita</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-chicken-biryani"
                                                               data-variant-id="food-chicken-biryani" 
                                                               data-product-name="Chicken Biryani" 
                                                               data-variant-name="Regular" 
                                                               data-price="20000" 
                                                               data-stock="999"
                                                               id="food-chicken-biryani">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-chicken-biryani">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="pizza" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-1.jpg') }}" alt="Pizza" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Pizza</span>
                                                        <span class="text-primary">Tsh 22,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Freshly baked pizza with your choice of toppings</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-pizza"
                                                               data-variant-id="food-pizza" 
                                                               data-product-name="Pizza" 
                                                               data-variant-name="Medium" 
                                                               data-price="22000" 
                                                               data-stock="999"
                                                               id="food-pizza">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-pizza">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="pasta" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-2.jpg') }}" alt="Pasta" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Pasta</span>
                                                        <span class="text-primary">Tsh 15,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Creamy pasta with your choice of sauce and toppings</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-pasta"
                                                               data-variant-id="food-pasta" 
                                                               data-product-name="Pasta" 
                                                               data-variant-name="Regular" 
                                                               data-price="15000" 
                                                               data-stock="999"
                                                               id="food-pasta">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-pasta">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="ice cream" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-3.jpg') }}" alt="Ice Cream" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Ice Cream</span>
                                                        <span class="text-primary">Tsh 6,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Creamy ice cream in various flavors</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-ice-cream"
                                                               data-variant-id="food-ice-cream" 
                                                               data-product-name="Ice Cream" 
                                                               data-variant-name="2 scoops" 
                                                               data-price="6000" 
                                                               data-stock="999"
                                                               id="food-ice-cream">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-ice-cream">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-6 product-item" data-product-name="chocolate cake" data-category="Food">
                                            <div class="d-flex align-items-center">
                                                <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-4.jpg') }}" alt="Chocolate Cake" style="width: 80px;">
                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                        <span>Chocolate Cake</span>
                                                        <span class="text-primary">Tsh 8,000</span>
                                                    </h5>
                                                    <small class="fst-italic">Rich chocolate cake with cream frosting</small>
                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                        <input class="form-check-input product-checkbox" 
                                                               type="checkbox" 
                                                               value="food-chocolate-cake"
                                                               data-variant-id="food-chocolate-cake" 
                                                               data-product-name="Chocolate Cake" 
                                                               data-variant-name="Slice" 
                                                               data-price="8000" 
                                                               data-stock="999"
                                                               id="food-chocolate-cake">
                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="food-chocolate-cake">
                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    
                                    <!-- Beverages Tab (Alcoholic) -->
                                    <div id="tab-beverages" class="tab-pane fade p-0">
                                        @if($alcoholicBeverages->isEmpty())
                                            <div class="alert alert-info">
                                                <i class="fa fa-info-circle"></i> No alcoholic beverages available at the moment. Please check back later.
                                            </div>
                                        @else
                                            <div class="row g-4">
                                                @foreach($alcoholicBeverages as $product)
                                                    @foreach($product['variants'] as $variant)
                                                        <div class="col-lg-6 product-item" 
                                                             data-product-name="{{ strtolower($product['name']) }}"
                                                             data-category="{{ $product['category'] }}">
                                                            <div class="d-flex align-items-center">
                                                                @if(!empty($product['image']))
                                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('storage/' . $product['image']) }}" alt="{{ $product['name'] }}" style="width: 80px;" onerror="this.src='{{ asset('img/restaurant/menu-1.jpg') }}'">
                                                                @else
                                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-' . (($loop->index % 8) + 1) . '.jpg') }}" alt="{{ $product['name'] }}" style="width: 80px;" onerror="this.src='{{ asset('img/restaurant/menu-1.jpg') }}'">
                                                                @endif
                                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                                        <span>{{ $product['name'] }}@if($product['brand']) <small class="text-muted">({{ $product['brand'] }})</small>@endif</span>
                                                                        <span class="text-primary">Tsh {{ number_format($variant['selling_price'], 0) }}</span>
                                                                    </h5>
                                                                    <small class="fst-italic">
                                                                        @if($product['description'])
                                                                            {{ Str::limit($product['description'], 80) }}
                                                                        @else
                                                                            {{ $variant['measurement'] }} of premium {{ strtolower($product['name']) }}@if($product['brand']), {{ $product['brand'] }}@endif, served chilled for the perfect drinking experience
                                                                        @endif
                                                                    </small>
                                                                    <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                                        <input class="form-check-input product-checkbox" 
                                                                               type="checkbox" 
                                                                               value="{{ $variant['id'] }}"
                                                                               data-variant-id="{{ $variant['id'] }}"
                                                                               data-product-name="{{ $product['name'] }}"
                                                                               data-variant-name="{{ $variant['measurement'] }}"
                                                                               data-price="{{ $variant['selling_price'] }}"
                                                                               data-stock="{{ $variant['counter_quantity'] }}"
                                                                               id="product-{{ $variant['id'] }}"
                                                                               {{ $variant['counter_quantity'] == 0 ? 'disabled' : '' }}>
                                                                        <label class="form-check-label fw-bold text-primary cursor-pointer" for="product-{{ $variant['id'] }}">
                                                                            <i class="fa fa-check-square me-2"></i>Select this item
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <!-- Juice Tab -->
                                    <div id="tab-juice" class="tab-pane fade p-0">
                                        <div class="row g-4">
                                            <!-- Hardcoded Juice Items -->
                                            <div class="col-lg-6 product-item" data-product-name="fresh orange juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-1.jpg') }}" alt="Fresh Orange Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Fresh Orange Juice</span>
                                                            <span class="text-primary">Tsh 5,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Freshly squeezed orange juice, rich in vitamin C and natural sweetness</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-orange"
                                                                   data-variant-id="juice-orange" 
                                                                   data-product-name="Fresh Orange Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="5000" 
                                                                   data-stock="999"
                                                                   id="juice-orange">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-orange">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="mango juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-2.jpg') }}" alt="Mango Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Mango Juice</span>
                                                            <span class="text-primary">Tsh 6,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Sweet and creamy mango juice, made from fresh ripe mangoes</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-mango"
                                                                   data-variant-id="juice-mango" 
                                                                   data-product-name="Mango Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="6000" 
                                                                   data-stock="999"
                                                                   id="juice-mango">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-mango">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="passion fruit juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-3.jpg') }}" alt="Passion Fruit Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Passion Fruit Juice</span>
                                                            <span class="text-primary">Tsh 5,500</span>
                                                        </h5>
                                                        <small class="fst-italic">Tart and refreshing passion fruit juice, perfect for a tropical taste</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-passion"
                                                                   data-variant-id="juice-passion" 
                                                                   data-product-name="Passion Fruit Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="5500" 
                                                                   data-stock="999"
                                                                   id="juice-passion">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-passion">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="pineapple juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-4.jpg') }}" alt="Pineapple Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Pineapple Juice</span>
                                                            <span class="text-primary">Tsh 5,500</span>
                                                        </h5>
                                                        <small class="fst-italic">Sweet and tangy pineapple juice, bursting with tropical flavor</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-pineapple"
                                                                   data-variant-id="juice-pineapple" 
                                                                   data-product-name="Pineapple Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="5500" 
                                                                   data-stock="999"
                                                                   id="juice-pineapple">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-pineapple">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="watermelon juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-5.jpg') }}" alt="Watermelon Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Watermelon Juice</span>
                                                            <span class="text-primary">Tsh 4,500</span>
                                                        </h5>
                                                        <small class="fst-italic">Cool and refreshing watermelon juice, perfect for hot days</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-watermelon"
                                                                   data-variant-id="juice-watermelon" 
                                                                   data-product-name="Watermelon Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="4500" 
                                                                   data-stock="999"
                                                                   id="juice-watermelon">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-watermelon">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="avocado smoothie" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-6.jpg') }}" alt="Avocado Smoothie" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Avocado Smoothie</span>
                                                            <span class="text-primary">Tsh 7,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Creamy and nutritious avocado smoothie, blended to perfection</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-avocado"
                                                                   data-variant-id="juice-avocado" 
                                                                   data-product-name="Avocado Smoothie" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="7000" 
                                                                   data-stock="999"
                                                                   id="juice-avocado">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-avocado">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="strawberry smoothie" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-7.jpg') }}" alt="Strawberry Smoothie" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Strawberry Smoothie</span>
                                                            <span class="text-primary">Tsh 6,500</span>
                                                        </h5>
                                                        <small class="fst-italic">Sweet and creamy strawberry smoothie, made with fresh strawberries</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-strawberry"
                                                                   data-variant-id="juice-strawberry" 
                                                                   data-product-name="Strawberry Smoothie" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="6500" 
                                                                   data-stock="999"
                                                                   id="juice-strawberry">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-strawberry">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="mixed fruit juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-8.jpg') }}" alt="Mixed Fruit Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Mixed Fruit Juice</span>
                                                            <span class="text-primary">Tsh 6,000</span>
                                                        </h5>
                                                        <small class="fst-italic">A delightful blend of seasonal fruits, refreshing and nutritious</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-mixed"
                                                                   data-variant-id="juice-mixed" 
                                                                   data-product-name="Mixed Fruit Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="6000" 
                                                                   data-stock="999"
                                                                   id="juice-mixed">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-mixed">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="lemonade" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-1.jpg') }}" alt="Lemonade" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Fresh Lemonade</span>
                                                            <span class="text-primary">Tsh 4,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Classic fresh lemonade, perfectly balanced between sweet and tart</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-lemonade"
                                                                   data-variant-id="juice-lemonade" 
                                                                   data-product-name="Fresh Lemonade" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="4000" 
                                                                   data-stock="999"
                                                                   id="juice-lemonade">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-lemonade">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <div class="col-lg-6 product-item" data-product-name="carrot juice" data-category="Juice">
                                                <div class="d-flex align-items-center">
                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-2.jpg') }}" alt="Carrot Juice" style="width: 80px;">
                                                    <div class="w-100 d-flex flex-column text-start ps-4">
                                                        <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                            <span>Carrot Juice</span>
                                                            <span class="text-primary">Tsh 5,000</span>
                                                        </h5>
                                                        <small class="fst-italic">Healthy and nutritious carrot juice, rich in vitamins and antioxidants</small>
                                                        <div class="form-check mt-3 p-2 bg-light rounded border border-primary product-checkbox-wrapper">
                                                            <input class="form-check-input product-checkbox" 
                                                                   type="checkbox" 
                                                                   value="juice-carrot"
                                                                   data-variant-id="juice-carrot" 
                                                                   data-product-name="Carrot Juice" 
                                                                   data-variant-name="500ml" 
                                                                   data-price="5000" 
                                                                   data-stock="999"
                                                                   id="juice-carrot">
                                                            <label class="form-check-label fw-bold text-primary cursor-pointer" for="juice-carrot">
                                                                <i class="fa fa-check-square me-2"></i>Select this item
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        
                                            <!-- Database Juice Items (if any) -->
                                            @if(!$juices->isEmpty())
                                                @foreach($juices as $product)
                                                    @foreach($product['variants'] as $variant)
                                                        <div class="col-lg-6 product-item" 
                                                             data-product-name="{{ strtolower($product['name']) }}"
                                                             data-category="{{ $product['category'] }}">
                                                            <div class="d-flex align-items-center">
                                                                <div class="form-check me-3 product-checkbox-wrapper p-2 bg-light rounded border border-primary">
                                                                    <input class="form-check-input product-checkbox" 
                                                                           type="checkbox" 
                                                                           value="{{ $variant['id'] }}"
                                                                           data-variant-id="{{ $variant['id'] }}"
                                                                           data-product-name="{{ $product['name'] }}"
                                                                           data-variant-name="{{ $variant['measurement'] }}"
                                                                           data-price="{{ $variant['selling_price'] }}"
                                                                           data-stock="{{ $variant['counter_quantity'] }}"
                                                                           id="product-juice-{{ $variant['id'] }}"
                                                                           {{ $variant['counter_quantity'] == 0 ? 'disabled' : '' }}>
                                                                    <label class="form-check-label fw-bold text-primary cursor-pointer ms-2" for="product-juice-{{ $variant['id'] }}">
                                                                        <i class="fa fa-check-square me-1"></i>Select
                                                                    </label>
                                                                </div>
                                                                @if(!empty($product['image']))
                                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('storage/' . $product['image']) }}" alt="{{ $product['name'] }}" style="width: 80px;" onerror="this.src='{{ asset('img/restaurant/menu-1.jpg') }}'">
                                                                @else
                                                                    <img class="flex-shrink-0 img-fluid rounded" src="{{ asset('img/restaurant/menu-' . (($loop->index % 8) + 1) . '.jpg') }}" alt="{{ $product['name'] }}" style="width: 80px;" onerror="this.src='{{ asset('img/restaurant/menu-1.jpg') }}'">
                                                                @endif
                                                                <div class="w-100 d-flex flex-column text-start ps-4">
                                                                    <h5 class="d-flex justify-content-between border-bottom pb-2">
                                                                        <span>{{ $product['name'] }}@if($product['brand']) <small class="text-muted">({{ $product['brand'] }})</small>@endif</span>
                                                                        <span class="text-primary">Tsh {{ number_format($variant['selling_price'], 0) }}</span>
                                                                    </h5>
                                                                    <small class="fst-italic">
                                                                        @if($product['description'])
                                                                            {{ Str::limit($product['description'], 80) }}
                                                                        @else
                                                                            {{ $variant['measurement'] }} of refreshing {{ strtolower($product['name']) }}@if($product['brand']), {{ $product['brand'] }}@endif, perfect for any occasion
                                                                        @endif
                                                                    </small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cart Section -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <div class="card shadow-lg border-0 wow fadeInUp" data-wow-delay="0.3s">
                                <div class="card-header bg-primary text-white text-center py-4">
                                    <h5 class="mb-0 fw-bold">
                                        <i class="fa fa-shopping-cart me-2"></i>Selected Items
                                    </h5>
                                    <small class="d-block mt-2 opacity-75" id="selectedCount">0 items selected</small>
                                </div>
                                <div class="card-body p-4 text-center">
                                    <div id="selectedItemsPreview" class="mb-4 min-height-100">
                                        <p class="text-muted mb-0">Select items from the menu to add to your cart</p>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="button"
                                                class="btn btn-success btn-lg rounded-pill py-3 fw-bold"
                                                id="viewCartBtn"
                                                disabled>
                                            <i class="fa fa-shopping-bag me-2"></i>View Cart (<span id="cartItemCount">0</span>)
                                        </button>
                                        <small class="text-muted mt-2">
                                            <i class="fa fa-info-circle me-1"></i>Review and checkout your selected items
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Order Section End -->

        <!-- Checkout Modal Start -->
        <div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="checkoutModalLabel">
                            <i class="fa fa-user me-2"></i>Complete Your Order
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="orderForm" action="{{ route('customer.order.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="_token" id="csrf_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="items_json" id="orderItemsInput">

                            <!-- Step 1: Basic details -->
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="customer_name" id="customer_name" class="form-control" placeholder="Your name" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number *</label>
                                    <input type="text" name="customer_phone" id="customer_phone" class="form-control" placeholder="e.g. 07XXXXXXXX" required>
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

                            <!-- Step 2: Location -->
                            <div class="mb-2" id="locationFieldLabel">
                                <label class="form-label">Your Location *</label>
                                <small class="text-muted d-block mb-1" id="locationHelpText">We use your location to deliver or prepare your order correctly.</small>
                            </div>
                            <div class="mb-3" id="deliveryMapField">
                                <div class="map-container">
                                    <div id="map"></div>
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

                            <!-- Extra message / special instructions -->
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
        // Set up CSRF token for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        let selectedItems = [];
        let appliedPromoCode = null;
        let discountAmount = 0;
        
        // Load selected items from localStorage
        function loadSelectedItems() {
            const saved = localStorage.getItem('medalion_selected_items');
            if (saved) {
                try {
                    selectedItems = JSON.parse(saved);
                    updateSelectedItemsDisplay();
                } catch(e) {
                    console.error('Error loading selected items:', e);
                }
            }
        }
        
        // Save selected items to localStorage
        function saveSelectedItems() {
            localStorage.setItem('medalion_selected_items', JSON.stringify(selectedItems));
        }
        
        // Update selected items display
        function updateSelectedItemsDisplay() {
            const count = selectedItems.length;
            $('#selectedCount').text(count + ' item' + (count !== 1 ? 's' : '') + ' selected');
            $('#cartItemCount').text(count);
            
            if (count > 0) {
                $('#viewCartBtn').prop('disabled', false);
                let previewHtml = '<div class="list-group list-group-flush">';
                selectedItems.slice(0, 3).forEach(item => {
                    previewHtml += `
                        <div class="list-group-item px-0 py-2 border-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <small class="fw-bold">${item.product_name}</small><br>
                                    <small class="text-muted">${item.variant_name}</small>
                                </div>
                                <small class="text-primary">Tsh ${parseFloat(item.price).toLocaleString()}</small>
                            </div>
                        </div>
                    `;
                });
                if (count > 3) {
                    previewHtml += `<div class="list-group-item px-0 py-2 border-0 text-center"><small class="text-muted">+${count - 3} more items</small></div>`;
                }
                previewHtml += '</div>';
                $('#selectedItemsPreview').html(previewHtml);
            } else {
                $('#viewCartBtn').prop('disabled', true);
                $('#selectedItemsPreview').html('<p class="text-muted mb-0">Select items from the menu to add to your cart</p>');
            }
        }
        
        // Handle checkbox changes
        $(document).on('change', '.product-checkbox', function() {
            const $checkbox = $(this);
            const variantId = $checkbox.data('variant-id');
            const productName = $checkbox.data('product-name');
            const variantName = $checkbox.data('variant-name');
            const price = parseFloat($checkbox.data('price'));
            const stock = parseInt($checkbox.data('stock'));
            
            if ($checkbox.is(':checked')) {
                // Add to selected items
                const existingIndex = selectedItems.findIndex(item => item.variant_id == variantId);
                if (existingIndex === -1) {
                    // Try to get image from the product card
                    const $productCard = $checkbox.closest('.product-item');
                    let productImage = '';
                    if ($productCard.length) {
                        const $img = $productCard.find('img');
                        if ($img.length) {
                            productImage = $img.attr('src') || '';
                        }
                    }
                    
                    selectedItems.push({
                        variant_id: variantId,
                        product_name: productName,
                        variant_name: variantName,
                        price: price,
                        quantity: 1,
                        stock: stock,
                        notes: '',
                        image: productImage
                    });
                }
            } else {
                // Remove from selected items
                selectedItems = selectedItems.filter(item => item.variant_id != variantId);
            }
            
            saveSelectedItems();
            updateSelectedItemsDisplay();
        });
        
        // View Cart button click
        $('#viewCartBtn').on('click', function() {
            // Navigate to cart page - items are already in localStorage
            window.location.href = '{{ route("customer.cart") }}';
        });
        
        // Load customer info from localStorage
        function loadCustomerInfo() {
            const savedInfo = localStorage.getItem('medalion_customer_info');
            if (savedInfo) {
                try {
                    const info = JSON.parse(savedInfo);
                    $('#customer_name').val(info.name || '');
                    $('#customer_phone').val(info.phone || '');
                    $('#customer_location').val(info.location || '');
                } catch(e) {
                    console.error('Error loading customer info:', e);
                }
            }
        }
        
        // Save customer info to localStorage
        function saveCustomerInfo() {
            const info = {
                name: $('#customer_name').val(),
                phone: $('#customer_phone').val(),
                location: $('#customer_location').val()
            };
            localStorage.setItem('medalion_customer_info', JSON.stringify(info));
        }
        
        // Load customer info on page load and try to auto-detect location
        $(document).ready(function() {
            loadCustomerInfo();
            loadSelectedItems();
            
            // Restore checkbox states
            selectedItems.forEach(item => {
                $(`.product-checkbox[data-variant-id="${item.variant_id}"]`).prop('checked', true);
            });
            
            // Save customer info on blur
            $('#customer_name, #customer_phone, #customer_location').on('blur', function() {
                saveCustomerInfo();
            });

            // Attempt to capture user location automatically
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        $('#latitude').val(lat);
                        $('#longitude').val(lng);

                        // If map is ready, center it; otherwise customer_location gets simple coordinates
                        if (typeof google !== 'undefined' && google.maps && isMapInitialized && map) {
                            const loc = { lat: lat, lng: lng };
                            placeMarker(loc);
                        } else {
                            if (!$('#customer_location').val()) {
                                $('#customer_location').val('Lat: ' + lat.toFixed(6) + ', Lng: ' + lng.toFixed(6));
                            }
                        }
                    },
                    function(error) {
                        console.warn('Automatic geolocation failed:', error);
                    }
                );
            }
        });
        
        // Product search functionality
        $('#productSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase().trim();
            const selectedCategory = $('#categoryFilter').val();
            
            $('.product-item').each(function() {
                const productName = $(this).data('product-name') || '';
                const itemCategory = $(this).data('category') || '';
                
                // Check if matches search term
                const matchesSearch = searchTerm === '' || productName.includes(searchTerm);
                // Check if matches category filter
                const matchesCategory = selectedCategory === '' || itemCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Hide empty categories
            $('.product-category-section').each(function() {
                const visibleItems = $(this).find('.product-item:visible').length;
                if (visibleItems === 0) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
            
        });
        
        // Handle tab switching
        $('a[data-bs-toggle="pill"]').on('shown.bs.tab', function (e) {
            const targetTab = $(e.target).attr('href');
            // If switching tabs and filter is set to a specific category, show all items in that tab
            if ($('#categoryFilter').val() === '') {
                // Show all items in the active tab
                $(targetTab).find('.product-item').show();
            }
        });
        
        // Category filter functionality
        $('#categoryFilter').on('change', function() {
            const selectedCategory = $(this).val();
            const searchTerm = $('#productSearch').val().toLowerCase().trim();
            
            // Auto-switch to appropriate tab based on category
            if (selectedCategory === 'Food') {
                $('a[href="#tab-food"]').tab('show');
            } else if (selectedCategory !== '' && selectedCategory !== 'Food') {
                $('a[href="#tab-beverages"]').tab('show');
            }
            
            $('.product-item').each(function() {
                const productName = $(this).data('product-name') || '';
                const itemCategory = $(this).data('category') || '';
                
                // Check if matches search term
                const matchesSearch = searchTerm === '' || productName.includes(searchTerm);
                // Check if matches category filter
                const matchesCategory = selectedCategory === '' || itemCategory === selectedCategory;
                
                if (matchesSearch && matchesCategory) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Hide empty categories
            $('.product-category-section').each(function() {
                const visibleItems = $(this).find('.product-item:visible').length;
                if (visibleItems === 0) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });
        
        // Clear cart functionality
        $(document).on('click', '#clearCartBtn', function() {
            if (confirm('Are you sure you want to clear your cart?')) {
                cart = [];
                appliedPromoCode = null;
                discountAmount = 0;
                $('#promo_code').val('');
                $('#promoMessage').html('');
                updateCart();
            }
        });
        
        // Add to cart functionality
        $(document).on('click', '.add-to-cart-btn', function() {
            const variantId = $(this).data('variant-id');
            const card = $(this).closest('.product-card');
            const productName = card.data('product-name');
            const variantName = card.data('variant-name');
            const price = parseFloat(card.data('price'));
            const stock = parseInt(card.data('stock'));
            
            // Check if item already in cart
            const existingItem = cart.find(item => item.variant_id === variantId);
            
            if (existingItem) {
                // If item exists, just focus on quantity field
                updateCart();
                // Focus on the quantity input for this item
                setTimeout(function() {
                    $('.cart-quantity[data-variant-id="' + variantId + '"]').focus().select();
                }, 100);
            } else {
                cart.push({
                    variant_id: variantId,
                    product_name: productName,
                    variant_name: variantName,
                    price: price,
                    quantity: 0, // Start with 0, user must enter quantity
                    stock: stock,
                    notes: '' // Initialize notes field
                });
                updateCart();
                // Focus on the quantity input
                setTimeout(function() {
                    $('.cart-quantity[data-variant-id="' + variantId + '"]').focus();
                }, 100);
            }
        });
        
        // Remove from cart
        $(document).on('click', '.remove-from-cart', function() {
            const variantId = $(this).data('variant-id');
            cart = cart.filter(item => item.variant_id !== variantId);
            updateCart();
        });
        
        // Update quantity - real-time calculation as user types
        $(document).on('input change blur', '.cart-quantity', function() {
            const variantId = $(this).data('variant-id');
            let quantity = parseInt($(this).val()) || 0;
            const item = cart.find(item => item.variant_id === variantId);
            const cartItem = $(this).closest('.cart-item');
            
            if (item) {
                // If field is empty, keep it empty but don't update quantity
                if ($(this).val() === '' || $(this).val() === null) {
                    return;
                }
                
                if (quantity > 0 && quantity <= item.stock) {
                    item.quantity = quantity;
                    // Update item total in real-time without refreshing entire cart
                    const itemTotal = item.price * quantity;
                    cartItem.find('.item-qty-display').text(quantity);
                    cartItem.find('.item-total-display').text('Tsh ' + itemTotal.toLocaleString());
                    // Update grand total
                    updateCartTotals();
                } else if (quantity > item.stock) {
                    alert('Maximum stock available: ' + item.stock);
                    $(this).val(item.quantity || '');
                    updateCart();
                    return;
                } else if (quantity <= 0) {
                    // Set quantity to 0 but keep item in cart
                    item.quantity = 0;
                    cartItem.find('.item-qty-display').text('0');
                    cartItem.find('.item-total-display').text('Tsh 0');
                    updateCartTotals();
                }
            }
        });
        
        // Update cart totals only (without regenerating entire cart HTML)
        function updateCartTotals() {
            let subtotal = 0;
            cart.forEach(item => {
                subtotal += item.price * item.quantity;
            });
            const finalTotal = Math.max(0, subtotal - discountAmount);
            $('#subtotal').text('Tsh ' + subtotal.toLocaleString());
            $('#discountAmount').text(discountAmount > 0 ? '-Tsh ' + discountAmount.toLocaleString() : 'Tsh 0');
            $('#total').text('Tsh ' + finalTotal.toLocaleString());
            
            // Update order items input
            const orderItems = cart.map(item => {
                // Check if it's a food item (starts with "food-") or juice item (starts with "juice-")
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
        
        // Promo code application (basic validation - can be enhanced with backend validation)
        $('#applyPromoBtn').on('click', function() {
            const promoCode = $('#promo_code').val().toUpperCase().trim();
            const promoMessage = $('#promoMessage');
            
            if (!promoCode) {
                promoMessage.html('<span class="text-danger">Please enter a promo code</span>');
                return;
            }
            
            // Basic promo codes (can be moved to backend later)
            const promoCodes = {
                'WELCOME10': { discount: 0.10, type: 'percent' },
                'SAVE5000': { discount: 5000, type: 'fixed' },
                'MEDALION20': { discount: 0.20, type: 'percent' }
            };
            
            if (promoCodes[promoCode]) {
                const promo = promoCodes[promoCode];
                appliedPromoCode = promoCode;
                
                let subtotal = 0;
                cart.forEach(item => {
                    subtotal += item.price * item.quantity;
                });
                
                if (promo.type === 'percent') {
                    discountAmount = Math.round(subtotal * promo.discount);
                } else {
                    discountAmount = Math.min(promo.discount, subtotal);
                }
                
                promoMessage.html('<span class="text-success"><i class="fa fa-check-circle me-1"></i>Promo code applied! Discount: Tsh ' + discountAmount.toLocaleString() + '</span>');
                updateCartTotals();
            } else {
                appliedPromoCode = null;
                discountAmount = 0;
                promoMessage.html('<span class="text-danger"><i class="fa fa-times-circle me-1"></i>Invalid promo code</span>');
                updateCartTotals();
            }
        });
        
        // Update cart display
        function updateCart() {
            const cartItemsDiv = $('#cartItems');
            const cartSummary = $('#cartSummary');
            const orderFormCard = $('#orderFormCard');
            
            if (cart.length === 0) {
                cartItemsDiv.html(`
                    <div class="text-center py-5">
                        <i class="fa fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">Your cart is empty</p>
                        <small class="text-muted">Add items from the menu to get started</small>
                    </div>
                `);
                cartSummary.hide();
                orderFormCard.hide();
                $('#clearCartBtn').hide();
                return;
            }
            
            let html = '';
            let subtotal = 0;
            
            cart.forEach(item => {
                const itemQuantity = item.quantity || 0;
                const itemTotal = item.price * itemQuantity;
                if (itemQuantity > 0) {
                    subtotal += itemTotal;
                }
                
                html += `
                    <div class="cart-item border-bottom pb-3 mb-3" data-variant-id="${item.variant_id}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">${item.product_name}</h6>
                                <small class="text-muted d-block">${item.variant_name}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger rounded-circle remove-from-cart ms-2" 
                                    data-variant-id="${item.variant_id}"
                                    style="width: 30px; height: 30px; padding: 0; line-height: 30px;">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="row g-2 align-items-end">
                            <div class="col-6">
                                <label class="small text-muted mb-1 d-block">Quantity:</label>
                                <input type="number" class="form-control form-control-sm cart-quantity" 
                                       data-variant-id="${item.variant_id}" 
                                       value="${itemQuantity > 0 ? itemQuantity : ''}" 
                                       min="1" 
                                       max="${item.stock}"
                                       placeholder="Qty">
                            </div>
                            <div class="col-6 text-end">
                                <div class="small text-muted mb-1">Price</div>
                                ${itemQuantity > 0 ? `
                                    <div class="small text-muted">Tsh ${item.price.toLocaleString()} × <span class="item-qty-display">${itemQuantity}</span></div>
                                    <strong class="text-primary item-total-display">Tsh ${itemTotal.toLocaleString()}</strong>
                                ` : `
                                    <div class="small text-muted">Enter quantity</div>
                                    <strong class="text-muted item-total-display">Tsh 0</strong>
                                `}
                            </div>
                        </div>
                        <div class="mt-2">
                            <input type="text" class="form-control form-control-sm item-notes" 
                                   data-variant-id="${item.variant_id}"
                                   placeholder="Add note (optional)" 
                                   value="${item.notes || ''}">
                        </div>
                    </div>
                `;
            });
            
            cartItemsDiv.html(html);
            
            // Calculate totals with discount
            const finalTotal = Math.max(0, subtotal - discountAmount);
            $('#subtotal').text('Tsh ' + subtotal.toLocaleString());
            $('#discountAmount').text(discountAmount > 0 ? '-Tsh ' + discountAmount.toLocaleString() : 'Tsh 0');
            $('#total').text('Tsh ' + finalTotal.toLocaleString());
            cartSummary.show();
            orderFormCard.show();
            $('#clearCartBtn').show();
            
            // Update order items input
            const orderItems = cart.map(item => {
                // Check if it's a food item (starts with "food-") or juice item (starts with "juice-")
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
            
            // Load item notes from cart
            $('.item-notes').each(function() {
                const variantId = $(this).data('variant-id');
                const cartItem = cart.find(item => item.variant_id == variantId);
                if (cartItem && cartItem.notes) {
                    $(this).val(cartItem.notes);
                }
            });
        }
        
        // Save item notes
        $(document).on('blur', '.item-notes', function() {
            const variantId = $(this).data('variant-id');
            const notes = $(this).val();
            const cartItem = cart.find(item => item.variant_id == variantId);
            if (cartItem) {
                cartItem.notes = notes;
                // Update order items input
                const orderItems = cart.map(item => ({
                    product_variant_id: item.variant_id,
                    quantity: item.quantity,
                    notes: item.notes || ''
                }));
                $('#orderItemsInput').val(JSON.stringify(orderItems));
            }
        });
        
        // Google Maps variables
        let map;
        let marker;
        let geocoder;
        let autocomplete;
        let isMapInitialized = false;

        // Initialize Google Map (global function for callback)
        window.initMap = function() {
            // Check if map container exists
            if (!document.getElementById('map')) {
                return;
            }
            
            // Default location: Moshi, Tanzania
            const defaultLocation = { lat: -3.3344, lng: 37.3404 };
            
            // Create map
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultLocation,
                zoom: 13,
                mapTypeControl: true,
                streetViewControl: true,
            });

            // Initialize geocoder
            geocoder = new google.maps.Geocoder();

            // Create marker
            marker = new google.maps.Marker({
                map: map,
                draggable: true,
                animation: google.maps.Animation.DROP,
            });

            // Add click listener to map
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
            });

            // Add drag listener to marker
            marker.addListener('dragend', function(event) {
                updateLocationFromCoordinates(event.latLng.lat(), event.latLng.lng());
            });

            isMapInitialized = true;

            // If we already have coordinates (from automatic geolocation), use them
            const existingLat = parseFloat($('#latitude').val());
            const existingLng = parseFloat($('#longitude').val());
            if (!isNaN(existingLat) && !isNaN(existingLng)) {
                const currentLoc = { lat: existingLat, lng: existingLng };
                placeMarker(currentLoc);
            } else {
                // Otherwise, try to use current location now
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const loc = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            placeMarker(loc);
                        },
                        function(error) {
                            console.warn('Geolocation inside initMap failed:', error);
                        }
                    );
                }
            }
        }

        // Place marker on map
        function placeMarker(location) {
            marker.setPosition(location);
            map.setCenter(location);
            updateLocationFromCoordinates(location.lat(), location.lng());
        }

        // Update location field from coordinates
        function updateLocationFromCoordinates(lat, lng) {
            $('#latitude').val(lat);
            $('#longitude').val(lng);
            
            geocoder.geocode({ location: { lat: lat, lng: lng } }, function(results, status) {
                if (status === 'OK' && results[0]) {
                    $('#customer_location').val(results[0].formatted_address);
                } else {
                    $('#customer_location').val('Lat: ' + lat + ', Lng: ' + lng);
                }
            });
        }

        // Use current location
        function useCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const location = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };
                        placeMarker(location);
                    },
                    function(error) {
                        alert('Unable to get your location. Please select on the map or enter manually.');
                        console.error('Geolocation error:', error);
                    }
                );
            } else {
                alert('Geolocation is not supported by your browser. Please select on the map or enter manually.');
            }
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
                        // If Google Maps API hasn't loaded yet, wait a bit more
                        const checkInterval = setInterval(function() {
                            if (typeof google !== 'undefined' && google.maps && !isMapInitialized) {
                                window.initMap();
                                clearInterval(checkInterval);
                            }
                        }, 500);
                        // Stop checking after 10 seconds
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

        // Use current location button
        $('#useCurrentLocation').on('click', function() {
            useCurrentLocation();
        });

        // Search location button
        $('#searchLocation').on('click', function() {
            $('#locationSearch').toggle();
            if ($('#locationSearch').is(':visible')) {
                if (!autocomplete) {
                    autocomplete = new google.maps.places.Autocomplete(
                        document.getElementById('locationSearch'),
                        { types: ['geocode'] }
                    );
                    
                    autocomplete.addListener('place_changed', function() {
                        const place = autocomplete.getPlace();
                        if (place.geometry) {
                            placeMarker(place.geometry.location);
                        }
                    });
                }
                $('#locationSearch').focus();
            }
        });
        
        // Form submission
        $('#orderForm').on('submit', function(e) {
            if (cart.length === 0) {
                e.preventDefault();
                alert('Please add items to your cart before placing an order.');
                return false;
            }
            
            // Check if all items have valid quantities
            let hasInvalidQuantity = false;
            cart.forEach(item => {
                if (!item.quantity || item.quantity <= 0) {
                    hasInvalidQuantity = true;
                }
            });
            
            if (hasInvalidQuantity) {
                e.preventDefault();
                alert('Please enter valid quantities for all items in your cart.');
                return false;
            }
            
            // Ensure CSRF token is present before submission
            let token = $('#csrf_token').val() || $('input[name="_token"]').val();
            const metaToken = $('#csrf_meta_token').attr('content') || $('meta[name="csrf-token"]').attr('content');
            
            // Use meta token if form token is missing
            if (!token && metaToken) {
                token = metaToken;
                $('#csrf_token').val(metaToken);
                $('input[name="_token"]').val(metaToken);
            }
            
            if (!token) {
                e.preventDefault();
                console.error('CSRF token missing!', {
                    formToken: $('input[name="_token"]').val(),
                    csrfToken: $('#csrf_token').val(),
                    metaToken: metaToken
                });
                alert('CSRF token missing. Please refresh the page and try again.');
                window.location.reload();
                return false;
            }
            
            // Ensure all token inputs have the same value
            $('input[name="_token"]').val(token);
            $('#csrf_token').val(token);
            
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-2"></i>Processing...');
            
            // Allow form to submit normally
            return true;
        });
    </script>
</body>

</html>

