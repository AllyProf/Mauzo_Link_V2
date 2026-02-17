<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Order Confirmed - Medalion Restaurant and Bar</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="restaurant, bar, tanzania, order, confirmation, medallion" name="keywords">
    <meta content="Your order has been confirmed at Medalion Restaurant and Bar" name="description">

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
                    <h1 class="display-3 text-white mb-3 animated slideInDown">Order Confirmed!</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb justify-content-center text-uppercase">
                            <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('customer.order') }}">Order</a></li>
                            <li class="breadcrumb-item text-white active" aria-current="page">Confirmation</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->


        <!-- Success Section Start -->
        <div class="container-xxl py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card shadow-lg border-0 wow fadeInUp" data-wow-delay="0.1s">
                            <div class="card-body p-5">
                                <!-- Success Icon -->
                                <div class="text-center mb-4">
                                    <div class="mb-4">
                                        <i class="fa fa-check-circle text-success" style="font-size: 100px;"></i>
                                    </div>
                                    <h1 class="text-success mb-3">Order Confirmed!</h1>
                                    <p class="lead mb-4">Thank you for your order. We have received it and will process it shortly.</p>
                                </div>
                                
                                <!-- Order Details -->
                                <div class="row mb-4">
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-primary h-100">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0"><i class="fa fa-info-circle me-2"></i>Order Information</h5>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-2"><strong>Order Number:</strong><br><span class="text-primary fs-5">{{ $order->order_number }}</span></p>
                                                <p class="mb-2"><strong>Customer Name:</strong><br>{{ $order->customer_name }}</p>
                                                <p class="mb-2"><strong>Phone:</strong><br>{{ $order->customer_phone }}</p>
                                                @if($order->customer_location)
                                                <p class="mb-2"><strong>Location:</strong><br>{{ $order->customer_location }}</p>
                                                @endif
                                                <p class="mb-0"><strong>Status:</strong><br>
                                                    <span class="badge bg-warning text-dark fs-6">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card border-success h-100">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0"><i class="fa fa-receipt me-2"></i>Payment Summary</h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between mb-2">
                                                    <span>Subtotal:</span>
                                                    <strong>Tsh {{ number_format($order->total_amount, 0) }}</strong>
                                                </div>
                                                <hr>
                                                <div class="d-flex justify-content-between">
                                                    <span class="fs-5"><strong>Total Amount:</strong></span>
                                                    <span class="fs-4 text-success"><strong>Tsh {{ number_format($order->total_amount, 0) }}</strong></span>
                                                </div>
                                                <p class="text-muted small mt-2 mb-0">
                                                    <i class="fa fa-info-circle me-1"></i>
                                                    Payment will be collected upon delivery or pickup.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Order Items -->
                                <div class="mb-4">
                                    <h5 class="mb-3"><i class="fa fa-shopping-bag me-2"></i>Order Items</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover">
                                            <thead class="table-primary">
                                                <tr>
                                                    <th>Item</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-end">Unit Price</th>
                                                    <th class="text-end">Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($order->items as $item)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $item->productVariant->product->name }}</strong><br>
                                                            <small class="text-muted">{{ $item->productVariant->measurement }}</small>
                                                        </td>
                                                        <td class="text-center">{{ $item->quantity }}</td>
                                                        <td class="text-end">Tsh {{ number_format($item->unit_price, 0) }}</td>
                                                        <td class="text-end"><strong>Tsh {{ number_format($item->total_price, 0) }}</strong></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <th colspan="3" class="text-end">Grand Total:</th>
                                                    <th class="text-end text-success">Tsh {{ number_format($order->total_amount, 0) }}</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="text-center mt-4">
                                    <a href="{{ route('home') }}" class="btn btn-primary btn-lg me-2">
                                        <i class="fa fa-home me-2"></i>Back to Home
                                    </a>
                                    <a href="{{ route('customer.order') }}" class="btn btn-outline-primary btn-lg">
                                        <i class="fa fa-shopping-cart me-2"></i>Order Again
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Success Section End -->


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
</body>

</html>
