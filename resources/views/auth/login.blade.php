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
        background: url('{{ asset('default_images/bar_login_page_background.jpg') }}') no-repeat center center fixed;
        background-size: cover;
      }
      .btn-primary {
        background-color: #940000;
        border-color: #940000;
        box-shadow: 0 4px 10px rgba(148, 0, 0, 0.3);
      }
      .btn-primary:hover {
        background-color: #7a0000;
        border-color: #7a0000;
      }
      .logo {
        background: rgba(0, 0, 0, 0.5);
        padding: 15px 40px;
        border-radius: 4px;
        margin-bottom: 25px;
        display: inline-block;
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.1);
      }
      .logo h1 {
        color: #ffffff;
        font-family: "Century Gothic", sans-serif !important;
        letter-spacing: 5px;
        font-weight: 800;
        text-shadow: 0 4px 20px rgba(0,0,0,0.9);
        text-transform: uppercase;
        margin: 0;
      }
      /* Hide the original material half-bg to let body image shine through */
      .material-half-bg {
        display: none;
      }
      .login-box {
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.95);
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
      </div>
      <!-- Floating Support Button -->
      <a href="https://www.emca.tech/contact" target="_blank" class="reach-us-btn shadow-lg" title="Support" style="position: fixed; bottom: 85px; right: 25px; z-index: 1000; background: #940000; color: white; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 2px solid white; transition: all 0.3s ease;">
        <i class="fa fa-phone fa-lg"></i>
        <span class="reach-text" style="position: absolute; right: 60px; background: rgba(0,0,0,0.7); color: white; padding: 4px 15px; border-radius: 20px; font-size: 10px; font-weight: 900; white-space: nowrap; transition: all 0.3s ease; letter-spacing: 1.5px; border: 1px solid rgba(255,255,255,0.2);">SUPPORT</span>
      </a>

      <!-- Animated Powered By Badge -->
      <div class="powered-badge shadow-lg" style="position: fixed; bottom: 20px; right: 25px; z-index: 1000; background: rgba(255,255,255,0.95); padding: 8px 18px; border-radius: 4px; border-left: 4px solid #940000; animation: pulse-border 2s infinite;">
        <p class="small mb-0" style="color: #333; font-weight: 800; letter-spacing: 1px; font-size: 10px; text-transform: uppercase;">
          <span style="color: #888;">Powered By</span> <a href="https://www.emca.tech" target="_blank" class="animated-text" style="color: #940000; text-decoration: none; font-weight: 900; animation: color-pulse 2s infinite; text-transform: none !important;">EmCa Techonologies LTD</a>
        </p>
      </div>

      <style>
        .reach-us-btn {
          animation: float-pulse 3s infinite ease-in-out;
        }
        .reach-us-btn:hover {
          transform: scale(1.1);
          background: #7a0000;
          animation-play-state: paused;
        }
        @keyframes float-pulse {
          0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(148, 0, 0, 0.7); }
          50% { transform: scale(1.1); box-shadow: 0 0 0 15px rgba(148, 0, 0, 0); }
          100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(148, 0, 0, 0); }
        }
        @keyframes pulse-border {
          0% { box-shadow: 0 0 0 0 rgba(148, 0, 0, 0.4); }
          70% { box-shadow: 0 0 0 10px rgba(148, 0, 0, 0); }
          100% { box-shadow: 0 0 0 0 rgba(148, 0, 0, 0); }
        }
        @keyframes color-pulse {
          0% { opacity: 1; }
          50% { opacity: 0.7; }
          100% { opacity: 1; }
        }
      </style>
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

