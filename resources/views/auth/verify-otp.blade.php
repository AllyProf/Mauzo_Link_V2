<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Verify Phone Number - MauzoLink</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Verify your phone number with OTP" name="description">

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
                    <a href="{{ route('register') }}" class="nav-item nav-link">Register</a>
                </div>
                <a href="{{ route('login') }}" class="btn btn-outline-light py-2 px-4 ms-2">Login</a>
            </div>
        </nav>

        <div class="container-fluid bg-primary py-5 bg-header" style="margin-bottom: 90px;">
            <div class="row py-5">
                <div class="col-12 pt-lg-5 mt-lg-5 text-center">
                    <h1 class="display-4 text-white animated zoomIn">Verify Phone Number</h1>
                    <a href="{{ route('home') }}" class="h5 text-white">Home</a>
                    <i class="far fa-circle text-white px-2"></i>
                    <a href="{{ route('register') }}" class="h5 text-white">Register</a>
                    <i class="far fa-circle text-white px-2"></i>
                    <a href="#" class="h5 text-white">Verify</a>
                </div>
            </div>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- OTP Verification Start -->
    <div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container py-5">
            <div class="row g-5">
                <div class="col-lg-6 mx-auto">
                    <div class="section-title position-relative pb-3 mb-5 text-center">
                        <h5 class="fw-bold text-primary text-uppercase">Phone Verification</h5>
                        <h1 class="mb-0">Enter Verification Code</h1>
                        <p class="mt-3">We've sent a 6-digit verification code to <strong>{{ $maskedPhone }}</strong></p>
                    </div>


                    <form method="POST" action="{{ route('otp.verify') }}" class="bg-light rounded p-5">
                        @csrf

                        <div class="form-group mb-4">
                            <label class="form-label">Enter 6-Digit Code <span class="text-danger">*</span></label>
                            <input class="form-control text-center @error('otp') is-invalid @enderror" 
                                   type="text" 
                                   name="otp" 
                                   placeholder="000000" 
                                   maxlength="6" 
                                   pattern="[0-9]{6}"
                                   style="font-size: 2rem; letter-spacing: 0.5rem; font-weight: bold;"
                                   required 
                                   autofocus>
                            @error('otp')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="text-center mb-3">
                            <button type="submit" class="btn btn-primary py-3 px-5">
                                <i class="fa fa-check me-2"></i>Verify Code
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="mb-2">Didn't receive the code?</p>
                            <form method="POST" action="{{ route('otp.resend') }}" class="d-inline-block">
                                @csrf
                                <button type="submit" class="btn btn-link text-primary p-0">
                                    <i class="fa fa-redo me-1"></i>Resend OTP
                                </button>
                            </form>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- OTP Verification End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-dark text-light mt-5 wow fadeInUp" data-wow-delay="0.1s">
        <div class="container">
            <div class="row gx-5">
                <div class="col-lg-4 col-md-6 footer-about">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100 bg-primary p-4">
                        <a href="{{ route('home') }}" class="navbar-brand">
                            <h1 class="m-0 text-white"><i class="fa fa-shopping-cart me-2"></i>MauzoLink</h1>
                        </a>
                        <p class="mt-3 mb-4">MauzoLink is a comprehensive Point of Sale system designed for various business types.</p>
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
    <script>
        // SweetAlert for messages
        @if(session('success'))
          Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#940000'
          });
        @endif
        
        @if(session('error'))
          Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session('error') }}',
            confirmButtonColor: '#940000'
          });
        @endif
        
        @if($errors->any())
          Swal.fire({
            icon: 'error',
            title: 'Verification Failed!',
            html: '<ul style="text-align: left;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
            confirmButtonColor: '#940000'
          });
        @endif
        
        // Auto-format OTP input
        document.querySelector('input[name="otp"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>

</html>

