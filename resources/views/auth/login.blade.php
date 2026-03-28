<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/admin.css') }}">
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>Login - MIGLOP</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      :root {
        --primary: #940000;
        --secondary: #000000;
      }
      /* Protect Icons from Font Overrides */
      *:not(i):not([class*="fa-"]):not([class*="fa"]):not([class*="bi-"]) {
        font-family: "Century Gothic", -apple-system, sans-serif !important;
      }
      body {
        font-family: "Century Gothic", sans-serif;
      }
      .btn-primary {
        background-color: #940000;
        border-color: #940000;
      }
      .btn-primary:hover {
        background-color: #7a0000;
        border-color: #7a0000;
      }
      .logo h1 {
        color: #ffffff;
        font-family: "Century Gothic", sans-serif !important;
        letter-spacing: 5px;
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        text-transform: uppercase;
      }
      .material-half-bg .cover {
        background: linear-gradient(135deg, #940000 0%, #7a0000 100%);
      }
      /* Password toggle */
      .password-wrapper {
        position: relative;
      }
      .password-wrapper .toggle-password {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        color: #888;
        font-size: 15px;
        line-height: 1;
        outline: none;
      }
      .password-wrapper .toggle-password:hover {
        color: #940000;
      }
      /* Spinner on sign in btn */
      .btn-spinner {
        display: none;
        width: 15px;
        height: 15px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
        margin-right: 6px;
        vertical-align: middle;
      }
      @keyframes spin {
        to { transform: rotate(360deg); }
      }
    </style>
  </head>
  <body>
    <section class="material-half-bg">
      <div class="cover"></div>
    </section>
    <section class="login-content">
      <div class="logo">
        <h1>MIGLOP INVESTMENT</h1>
      </div>
      <div class="login-box">
        <form class="login-form" method="POST" action="{{ route('login') }}">
          @csrf
          <h3 class="login-head"><i class="fa fa-lg fa-fw fa-user"></i>SIGN IN</h3>
          

          <div class="form-group">
            <label class="control-label">USERNAME</label>
            <input class="form-control @error('email') is-invalid @enderror" type="text" name="email" placeholder="Email" value="{{ old('email') }}" autofocus required>
            @error('email')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
          </div>
          <div class="form-group">
            <label class="control-label">PASSWORD</label>
            <div class="password-wrapper">
              <input id="password-input" class="form-control @error('password') is-invalid @enderror" type="password" name="password" placeholder="Password" required style="padding-right: 40px;">
              <button type="button" class="toggle-password" id="toggle-password-btn" tabindex="-1">
                <i class="fa fa-eye" id="toggle-password-icon"></i>
              </button>
            </div>
            @error('password')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
          </div>
          <div class="form-group">
            <div class="utility">
              <div class="animated-checkbox">
                <label>
                  <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}><span class="label-text">Stay Signed in</span>
                </label>
              </div>
              <p class="semibold-text mb-2"><a href="#" data-toggle="flip">Forgot Password ?</a></p>
            </div>
          </div>
          <div class="form-group btn-container">
            <button type="submit" id="login-btn" class="btn btn-primary btn-block">
              <span class="btn-spinner" id="login-spinner"></span>
              <i class="fa fa-sign-in fa-lg fa-fw" id="login-icon"></i>
              <span id="login-text">SIGN IN</span>
            </button>
          </div>
        </form>
        <form class="forget-form" action="#" method="POST">
          @csrf
          <h3 class="login-head"><i class="fa fa-lg fa-fw fa-lock"></i>Forgot Password ?</h3>
          <div class="form-group">
            <label class="control-label">EMAIL</label>
            <input class="form-control" type="text" placeholder="Email">
          </div>
          <div class="form-group btn-container">
            <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-unlock fa-lg fa-fw"></i>RESET</button>
          </div>
          <div class="form-group mt-3">
            <p class="semibold-text mb-0"><a href="#" data-toggle="flip"><i class="fa fa-angle-left fa-fw"></i> Back to Login</a></p>
          </div>
        </form>
      </div>
    </section>
    <!-- Essential javascripts for application to work-->
    <script src="{{ asset('js/admin/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('js/admin/popper.min.js') }}"></script>
    <script src="{{ asset('js/admin/bootstrap.min.js') }}"></script>
    <script src="{{ asset('js/admin/main.js') }}"></script>
    <!-- The javascript plugin to display page loading on top-->
    <script src="{{ asset('js/admin/plugins/pace.min.js') }}"></script>
    <script type="text/javascript">
      // SweetAlert for messages
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
          title: 'Login Failed!',
          html: '<ul style="text-align: left;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>',
          confirmButtonColor: '#940000',
          cancelButtonColor: '#000000'
        });
      @endif
      
      // Login Page Flipbox control
      $('.login-content [data-toggle="flip"]').click(function() {
        $('.login-box').toggleClass('flipped');
        return false;
      });

      // Password toggle
      $('#toggle-password-btn').on('click', function() {
        var input = $('#password-input');
        var icon  = $('#toggle-password-icon');
        if (input.attr('type') === 'password') {
          input.attr('type', 'text');
          icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
          input.attr('type', 'password');
          icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
      });

      // Loading spinner on submit
      $('.login-form').on('submit', function() {
        var btn = $('#login-btn');
        btn.prop('disabled', true);
        $('#login-spinner').show();
        $('#login-icon').hide();
        $('#login-text').text('Signing in...');
      });
    </script>
  </body>
</html>

