<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Register - MauzoLink</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Register for MauzoLink POS System" name="keywords">
    <meta content="Create your MauzoLink account and start managing your business" name="description">

    <!-- Favicon -->
    <link href="{{ asset('img/landing/favicon.ico') }}" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="{{ asset('lib/landing/owlcarousel/assets/owl.carousel.min.css') }}" rel="stylesheet">
    <link href="{{ asset('lib/landing/animate/animate.min.css') }}" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="{{ asset('css/landing/bootstrap.min.css') }}" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="{{ asset('css/landing/style.css') }}" rel="stylesheet">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --primary: #940000;
            --secondary: #000000;
        }
        body, h1, h2, h3, h4, h5, h6, .navbar-brand, .nav-link, .btn {
            font-family: "Century Gothic", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .password-toggle:hover {
            color: #940000;
        }
        .password-strength {
            margin-top: 5px;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }
        .password-strength-weak { background: #dc3545; }
        .password-strength-fair { background: #ffc107; }
        .password-strength-good { background: #17a2b8; }
        .password-strength-strong { background: #28a745; }
        .password-strength-text {
            font-size: 0.75rem;
            margin-top: 5px;
        }
        .password-input-wrapper {
            position: relative;
        }
    </style>
</head>

<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner"></div>
    </div>
    <!-- Spinner End -->


    <!-- Topbar Start -->
    <div class="container-fluid bg-dark px-5 d-none d-lg-block">
        <div class="row gx-0">
            <div class="col-lg-8 text-center text-lg-start mb-2 mb-lg-0">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <small class="me-3 text-light"><i class="fa fa-map-marker-alt me-2"></i>123 Street, New York, USA</small>
                    <small class="me-3 text-light"><i class="fa fa-phone-alt me-2"></i>+012 345 6789</small>
                    <small class="text-light"><i class="fa fa-envelope-open me-2"></i>info@mauzolink.com</small>
                </div>
            </div>
            <div class="col-lg-4 text-center text-lg-end">
                <div class="d-inline-flex align-items-center" style="height: 45px;">
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-twitter fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-facebook-f fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-linkedin-in fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href=""><i class="fab fa-instagram fw-normal"></i></a>
                    <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href=""><i class="fab fa-youtube fw-normal"></i></a>
                </div>
            </div>
        </div>
    </div>
    <!-- Topbar End -->


    <!-- Navbar Start -->
    <div class="container-fluid position-relative p-0">
        <nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
            <a href="{{ route('home') }}" class="navbar-brand p-0">
                <h1 class="m-0"><i class="fa fa-shopping-cart me-2"></i>MauzoLink</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <div class="navbar-nav ms-auto py-0">
                    <a href="{{ route('home') }}" class="nav-item nav-link">Home</a>
                    <a href="#" class="nav-item nav-link">About</a>
                    <a href="#" class="nav-item nav-link">Services</a>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Pages</a>
                        <div class="dropdown-menu m-0">
                            <a href="{{ route('plans.index') }}" class="dropdown-item">Pricing Plan</a>
                            <a href="#" class="dropdown-item">Our features</a>
                        </div>
                    </div>
                    <a href="#" class="nav-item nav-link">Contact</a>
                </div>
                <button type="button" class="btn text-primary ms-3" data-bs-toggle="modal" data-bs-target="#searchModal"><i class="fa fa-search"></i></button>
                <a href="{{ route('login') }}" class="btn btn-outline-light py-2 px-4 ms-2">Login</a>
            </div>
        </nav>

        <div class="container-fluid bg-primary py-5 bg-header" style="margin-bottom: 90px;">
            <div class="row py-5">
                <div class="col-12 pt-lg-5 mt-lg-5 text-center">
                    <h1 class="display-4 text-white animated zoomIn">Create Account</h1>
                    <a href="{{ route('home') }}" class="h5 text-white">Home</a>
                    <i class="far fa-circle text-white px-2"></i>
                    <a href="{{ route('register') }}" class="h5 text-white">Register</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar End -->


    <!-- Full Screen Search Start -->
    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content" style="background: rgba(9, 30, 62, .7);">
                <div class="modal-header border-0">
                    <button type="button" class="btn bg-white btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body d-flex align-items-center justify-content-center">
                    <div class="input-group" style="max-width: 600px;">
                        <input type="text" class="form-control bg-transparent border-primary p-3" placeholder="Type search keyword">
                        <button class="btn btn-primary px-4"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Full Screen Search End -->


    <!-- Registration Form Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-10 mx-auto">
                    <div class="section-title position-relative pb-3 mb-5 text-center">
                        <h5 class="fw-bold text-primary text-uppercase">Sign Up</h5>
                        <h1 class="mb-0">Create Your MauzoLink Account</h1>
                        <p class="mt-3">Start your free trial today. No credit card required.</p>
                    </div>


                    <form method="POST" action="{{ route('register') }}" class="bg-light rounded p-5">
                        @csrf

                        <!-- Plan Selection -->
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fa fa-check text-primary me-2"></i>Select Your Plan</h5>
                            <div class="row g-3">
                                @foreach($plans as $plan)
                                    <div class="col-md-4">
                                        <div class="card h-100 plan-selection-card {{ $selectedPlan == $plan->slug ? 'border-primary border-2' : '' }}" style="cursor: pointer;" onclick="selectPlan({{ $plan->id }}, '{{ $plan->slug }}')">
                                            <div class="card-body text-center">
                                                <input type="radio" name="plan_id" value="{{ $plan->id }}" id="plan_{{ $plan->id }}" {{ $selectedPlan == $plan->slug ? 'checked' : '' }} required style="display: none;">
                                                <h5 class="card-title text-primary">{{ $plan->name }}</h5>
                                                <h3 class="text-primary my-3">
                                                    @if($plan->price == 0)
                                                        BURE
                                                    @else
                                                        TSh {{ number_format($plan->price, 0) }}
                                                    @endif
                                                </h3>
                                                <p class="text-muted small">/mwezi</p>
                                                @if($plan->trial_days > 0)
                                                    <p class="small text-success"><i class="fa fa-gift me-1"></i>{{ $plan->trial_days }} siku za majaribio bure</p>
                                                @else
                                                    <p class="small text-info"><i class="fa fa-info-circle me-1"></i>Payment required to activate</p>
                                                @endif
                                                @if($plan->slug === 'basic')
                                                    <p class="small text-muted mt-2"><i class="fa fa-user me-1"></i>For Sole Proprietors</p>
                                                @elseif($plan->slug === 'pro')
                                                    <p class="small text-muted mt-2"><i class="fa fa-users me-1"></i>For Businesses with Staff</p>
                                                @elseif($plan->slug === 'free')
                                                    <p class="small text-muted mt-2"><i class="fa fa-star me-1"></i>All Features Available</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Personal Information -->
                        <h5 class="mb-3"><i class="fa fa-user text-primary me-2"></i>Personal Information</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input class="form-control @error('name') is-invalid @enderror" type="text" name="name" value="{{ old('name') }}" placeholder="Your Full Name" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input class="form-control @error('email') is-invalid @enderror" type="email" name="email" value="{{ old('email') }}" placeholder="your@email.com" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Password <span class="text-danger">*</span></label>
                                    <div class="password-input-wrapper">
                                        <input class="form-control @error('password') is-invalid @enderror" 
                                               type="password" 
                                               name="password" 
                                               id="password"
                                               placeholder="Password" 
                                               required
                                               onkeyup="checkPasswordStrength(this.value)">
                                        <span class="password-toggle" onclick="togglePassword('password')">
                                            <i class="fa fa-eye" id="password-eye"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="password-strength-bar"></div>
                                    </div>
                                    <div class="password-strength-text" id="password-strength-text"></div>
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <div class="password-input-wrapper">
                                        <input class="form-control" 
                                               type="password" 
                                               name="password_confirmation" 
                                               id="password_confirmation"
                                               placeholder="Confirm Password" 
                                               required>
                                        <span class="password-toggle" onclick="togglePassword('password_confirmation')">
                                            <i class="fa fa-eye" id="password_confirmation-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Business Information -->
                        <h5 class="mb-3"><i class="fa fa-building text-primary me-2"></i>Business Information</h5>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Business Name <span class="text-danger">*</span></label>
                                    <input class="form-control @error('business_name') is-invalid @enderror" type="text" name="business_name" value="{{ old('business_name') }}" placeholder="Your Business Name" required>
                                    @error('business_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">You will select your business type(s) during the configuration wizard after registration.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">+255</span>
                                        <input class="form-control @error('phone') is-invalid @enderror" type="tel" name="phone" value="{{ old('phone') }}" placeholder="123 456 789" pattern="[0-9]{9}" maxlength="9" required>
                                    </div>
                                    <small class="text-muted">Enter 9 digits after +255 (e.g., 123456789)</small>
                                    @error('phone')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <input class="form-control" type="text" name="address" value="{{ old('address') }}" placeholder="Street Address">
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">City <span class="text-danger">*</span></label>
                                    <select class="form-control @error('city') is-invalid @enderror" name="city" required>
                                        <option value="">Select City</option>
                                        <option value="Dar es Salaam" {{ old('city') == 'Dar es Salaam' ? 'selected' : '' }}>Dar es Salaam</option>
                                        <option value="Arusha" {{ old('city') == 'Arusha' ? 'selected' : '' }}>Arusha</option>
                                        <option value="Mwanza" {{ old('city') == 'Mwanza' ? 'selected' : '' }}>Mwanza</option>
                                        <option value="Dodoma" {{ old('city') == 'Dodoma' ? 'selected' : '' }}>Dodoma</option>
                                        <option value="Mbeya" {{ old('city') == 'Mbeya' ? 'selected' : '' }}>Mbeya</option>
                                        <option value="Morogoro" {{ old('city') == 'Morogoro' ? 'selected' : '' }}>Morogoro</option>
                                        <option value="Tanga" {{ old('city') == 'Tanga' ? 'selected' : '' }}>Tanga</option>
                                        <option value="Zanzibar" {{ old('city') == 'Zanzibar' ? 'selected' : '' }}>Zanzibar</option>
                                        <option value="Kigoma" {{ old('city') == 'Kigoma' ? 'selected' : '' }}>Kigoma</option>
                                        <option value="Mtwara" {{ old('city') == 'Mtwara' ? 'selected' : '' }}>Mtwara</option>
                                        <option value="Tabora" {{ old('city') == 'Tabora' ? 'selected' : '' }}>Tabora</option>
                                        <option value="Iringa" {{ old('city') == 'Iringa' ? 'selected' : '' }}>Iringa</option>
                                        <option value="Sumbawanga" {{ old('city') == 'Sumbawanga' ? 'selected' : '' }}>Sumbawanga</option>
                                        <option value="Musoma" {{ old('city') == 'Musoma' ? 'selected' : '' }}>Musoma</option>
                                        <option value="Bukoba" {{ old('city') == 'Bukoba' ? 'selected' : '' }}>Bukoba</option>
                                        <option value="Singida" {{ old('city') == 'Singida' ? 'selected' : '' }}>Singida</option>
                                        <option value="Shinyanga" {{ old('city') == 'Shinyanga' ? 'selected' : '' }}>Shinyanga</option>
                                        <option value="Lindi" {{ old('city') == 'Lindi' ? 'selected' : '' }}>Lindi</option>
                                        <option value="Songe" {{ old('city') == 'Songe' ? 'selected' : '' }}>Songe</option>
                                        <option value="Moshi" {{ old('city') == 'Moshi' ? 'selected' : '' }}>Moshi</option>
                                        <option value="Tukuyu" {{ old('city') == 'Tukuyu' ? 'selected' : '' }}>Tukuyu</option>
                                        <option value="Bagamoyo" {{ old('city') == 'Bagamoyo' ? 'selected' : '' }}>Bagamoyo</option>
                                        <option value="Kibaha" {{ old('city') == 'Kibaha' ? 'selected' : '' }}>Kibaha</option>
                                        <option value="Korogwe" {{ old('city') == 'Korogwe' ? 'selected' : '' }}>Korogwe</option>
                                        <option value="Same" {{ old('city') == 'Same' ? 'selected' : '' }}>Same</option>
                                        <option value="Babati" {{ old('city') == 'Babati' ? 'selected' : '' }}>Babati</option>
                                        <option value="Other" {{ old('city') == 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Country</label>
                                    <input class="form-control" type="text" name="country" value="{{ old('country', 'Tanzania') }}" placeholder="Country" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary py-3 px-5 me-3"><i class="fa fa-user-plus me-2"></i>Create Account</button>
                            <p class="mt-3 mb-0">Already have an account? <a href="{{ route('login') }}" class="text-primary">Sign In</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Registration Form End -->


    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light mt-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-4 col-md-6 footer-about">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-primary p-4">
                        <a href="{{ route('home') }}" class="navbar-brand">
                            <h1 class="m-0 text-white"><i class="fa fa-shopping-cart me-2"></i>MauzoLink</h1>
                        </a>
                        <p class="mt-3 mb-4">MauzoLink is a comprehensive Point of Sale system designed for various business types. Manage your business efficiently with our modern POS solution.</p>
                        <form action="">
                            <div class="input-group">
                                <input type="text" class="form-control border-white p-3" placeholder="Your Email">
                                <button class="btn btn-dark">Sign Up</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-lg-8 col-md-6">
                    <div class="row gx-5">
                        <div class="col-lg-4 col-md-12 pt-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Get In Touch</h3>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-geo-alt text-primary me-2"></i>
                                <p class="mb-0">Ben Bella Street, Moshi, Tanzania</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-envelope-open text-primary me-2"></i>
                                <p class="mb-0">emca@emca.tech</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-telephone text-primary me-2"></i>
                                <p class="mb-0">+255 749 719 998</p>
                            </div>
                            <div class="d-flex mb-2">
                                <i class="bi bi-globe text-primary me-2"></i>
                                <p class="mb-0"><a href="https://www.emca.tech" target="_blank" class="text-light">www.emca.tech</a></p>
                            </div>
                            <div class="d-flex mt-4">
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-twitter fw-normal"></i></a>
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-facebook-f fw-normal"></i></a>
                                <a class="btn btn-primary btn-square me-2" href="#"><i class="fab fa-linkedin-in fw-normal"></i></a>
                                <a class="btn btn-primary btn-square" href="#"><i class="fab fa-instagram fw-normal"></i></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Quick Links</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="{{ route('home') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Home</a>
                                <a class="text-light mb-2" href="#"><i class="bi bi-arrow-right text-primary me-2"></i>About Us</a>
                                <a class="text-light mb-2" href="#"><i class="bi bi-arrow-right text-primary me-2"></i>Our Services</a>
                                <a class="text-light mb-2" href="{{ route('plans.index') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Pricing Plans</a>
                                <a class="text-light mb-2" href="{{ route('register') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Register</a>
                                <a class="text-light" href="{{ route('login') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Login</a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 pt-0 pt-lg-5 mb-5">
                            <div class="section-title section-title-sm position-relative pb-3 mb-4">
                                <h3 class="text-light mb-0">Popular Links</h3>
                            </div>
                            <div class="link-animated d-flex flex-column justify-content-start">
                                <a class="text-light mb-2" href="{{ route('home') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Home</a>
                                <a class="text-light mb-2" href="#"><i class="bi bi-arrow-right text-primary me-2"></i>About Us</a>
                                <a class="text-light mb-2" href="#"><i class="bi bi-arrow-right text-primary me-2"></i>Our Services</a>
                                <a class="text-light mb-2" href="{{ route('plans.index') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Pricing Plans</a>
                                <a class="text-light mb-2" href="{{ route('register') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Register</a>
                                <a class="text-light" href="{{ route('login') }}"><i class="bi bi-arrow-right text-primary me-2"></i>Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid text-white" style="background: #061429;">
        <div class="container text-center">
            <div class="row justify-content-end">
                <div class="col-lg-8 col-md-6">
                    <div class="d-flex flex-column align-items-center justify-content-center" style="min-height: 75px; padding: 10px 0;">
                        <p class="mb-0">&copy; {{ date('Y') }} <a class="text-white border-bottom" href="{{ route('home') }}">MauzoLink</a>. All Rights Reserved. | Built by <a class="text-white border-bottom" href="https://www.emca.tech" target="_blank">EmCa Technologies</a></p>
                        <p class="mb-0 mt-2" style="font-size: 0.75rem; opacity: 0.8;">
                            Reg. No: 181103264 | TIN: 181-103-264 | License: BL01408832024-2500004066
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Footer End -->


    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('lib/landing/wow/wow.min.js') }}"></script>
    <script src="{{ asset('lib/landing/easing/easing.min.js') }}"></script>
    <script src="{{ asset('lib/landing/waypoints/waypoints.min.js') }}"></script>
    <script src="{{ asset('lib/landing/counterup/counterup.min.js') }}"></script>
    <script src="{{ asset('lib/landing/owlcarousel/owl.carousel.min.js') }}"></script>

    <!-- Template Javascript -->
    <script src="{{ asset('js/landing/main.js') }}"></script>
    <script type="text/javascript">
      // Plan selection removed - all users start with Free Plan
      function selectPlan(planId, planSlug) {
        document.getElementById('plan_' + planId).checked = true;
        // Update visual selection
        document.querySelectorAll('.plan-selection-card').forEach(card => {
          card.classList.remove('border-primary', 'border-2');
        });
        var clickedCard = event ? event.currentTarget : event.target.closest('.plan-selection-card');
        if (clickedCard) {
          clickedCard.classList.add('border-primary', 'border-2');
        }
      }
      
      // Password toggle function
      function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const eye = document.getElementById(fieldId + '-eye');
        
        if (field.type === 'password') {
          field.type = 'text';
          eye.classList.remove('fa-eye');
          eye.classList.add('fa-eye-slash');
        } else {
          field.type = 'password';
          eye.classList.remove('fa-eye-slash');
          eye.classList.add('fa-eye');
        }
      }
      
      // Password strength checker
      function checkPasswordStrength(password) {
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        
        if (!password) {
          strengthBar.style.width = '0%';
          strengthText.textContent = '';
          return;
        }
        
        let strength = 0;
        let feedback = [];
        
        // Length check
        if (password.length >= 8) {
          strength += 25;
        } else {
          feedback.push('At least 8 characters');
        }
        
        // Lowercase check
        if (/[a-z]/.test(password)) {
          strength += 25;
        } else {
          feedback.push('Lowercase letter');
        }
        
        // Uppercase check
        if (/[A-Z]/.test(password)) {
          strength += 25;
        } else {
          feedback.push('Uppercase letter');
        }
        
        // Number check
        if (/[0-9]/.test(password)) {
          strength += 15;
        } else {
          feedback.push('Number');
        }
        
        // Special character check
        if (/[^A-Za-z0-9]/.test(password)) {
          strength += 10;
        } else {
          feedback.push('Special character');
        }
        
        // Update strength bar
        strengthBar.style.width = strength + '%';
        
        // Remove all strength classes
        strengthBar.classList.remove('password-strength-weak', 'password-strength-fair', 'password-strength-good', 'password-strength-strong');
        
        // Add appropriate class and text
        if (strength < 50) {
          strengthBar.classList.add('password-strength-weak');
          strengthText.textContent = 'Weak password';
          strengthText.style.color = '#dc3545';
        } else if (strength < 75) {
          strengthBar.classList.add('password-strength-fair');
          strengthText.textContent = 'Fair password';
          strengthText.style.color = '#ffc107';
        } else if (strength < 90) {
          strengthBar.classList.add('password-strength-good');
          strengthText.textContent = 'Good password';
          strengthText.style.color = '#17a2b8';
        } else {
          strengthBar.classList.add('password-strength-strong');
          strengthText.textContent = 'Strong password';
          strengthText.style.color = '#28a745';
        }
      }
      
      // Handle radio button change
      document.addEventListener('DOMContentLoaded', function() {
        // SweetAlert for success/error messages
        @if(session('success'))
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#940000',
            cancelButtonColor: '#000000'
          });
        @endif
        
        @if(session('error'))
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            confirmButtonColor: '#940000',
            cancelButtonColor: '#000000'
          });
        @endif
        
        @if($errors->any())
          Swal.fire({
            icon: 'error',
            title: 'Validation Error!',
            html: '<ul style="text-align: left;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
            confirmButtonColor: '#940000',
            cancelButtonColor: '#000000'
          });
        @endif
        
        document.querySelectorAll('input[name="plan_id"]').forEach(function(radio) {
          radio.addEventListener('change', function() {
            document.querySelectorAll('.plan-selection-card').forEach(card => {
              card.classList.remove('border-primary', 'border-2');
            });
            var card = this.closest('.plan-selection-card');
            if (card) {
              card.classList.add('border-primary', 'border-2');
            }
          });
        });

        // Phone number formatting - only allow digits and ensure 9 digits
        var phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput) {
          phoneInput.addEventListener('input', function(e) {
            // Remove all non-digit characters
            this.value = this.value.replace(/[^0-9]/g, '');
            // Limit to 9 digits
            if (this.value.length > 9) {
              this.value = this.value.substring(0, 9);
            }
          });

          phoneInput.addEventListener('paste', function(e) {
            e.preventDefault();
            var paste = (e.clipboardData || window.clipboardData).getData('text');
            var numbers = paste.replace(/[^0-9]/g, '');
            // If starts with 255, remove it
            if (numbers.startsWith('255')) {
              numbers = numbers.substring(3);
            }
            // If starts with 0, remove it
            if (numbers.startsWith('0')) {
              numbers = numbers.substring(1);
            }
            // Limit to 9 digits
            this.value = numbers.substring(0, 9);
          });
        }
        
        // Form submission with SweetAlert confirmation
        document.querySelector('form').addEventListener('submit', function(e) {
          const password = document.getElementById('password').value;
          const passwordConfirm = document.getElementById('password_confirmation').value;
          
          if (password !== passwordConfirm) {
            e.preventDefault();
            Swal.fire({
              icon: 'error',
              title: 'Password Mismatch!',
              text: 'Passwords do not match. Please try again.',
              confirmButtonColor: '#940000'
            });
            return false;
          }
        });
      });
    </script>
</body>

</html>
