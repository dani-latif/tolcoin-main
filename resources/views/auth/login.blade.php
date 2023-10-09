<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title> TolCoin </title>


    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="manifest" href="{{ asset('pages/assets/img/favicons/manifest.json') }}">
    <meta name="msapplication-TileImage" content="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">

    <meta name="theme-color" content="#ffffff">


    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <link href="{{ asset('pages/assets/css/theme.css') }}" rel="stylesheet">

</head>


<body>

<!-- ===============================================-->
<!--    Main Content-->
<!-- ===============================================-->
<main class="main" id="top">


    <div class="container">
        <div class="row flex-center min-vh-100 py-6">
            <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
                <a class="d-flex flex-center mb-4"
                   href="{{ url('/') }}">
                    <img class="mr-2" style="width: 111px;" src="{{ asset('pages/assets/img/favicons/tolCoin.png') }}"
                         alt="" width="58" />
                         <h1 class="text-sans-serif" style="color:#00E38F;">ToLCoin</h1>
<!-- {{--                    <span class="text-sans-serif font-weight-extra-bold fs-5 d-inline-block">--}}
{{--                        falcon</span>--}} -->
                </a>
                <div class="card">
                    <div class="card-body p-4 p-sm-5">
                        <div class="row text-left justify-content-between align-items-center mb-2">
                            <div class="col-auto">
                                <h5>Log in</h5>
                            </div>
                            <div class="col-auto">
                                <p class="fs--1 text-600 mb-0">or <a href="{{ route('register') }}">Create an account</a></p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            @if(session('Deletesuccess'))
    <div class="alert alert-success">
        {{ session('Deletesuccess') }}
    </div>
@endif

                            <div class="form-group">
                                <label for="email" class="col-md-12 col-form-label text-md-left">{{ __('E-Mail Address') }}</label>

                                <div class="col-md-12">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="password" class="col-md-12 col-form-label text-md-left">{{ __('Password') }}</label>

                                <div class="col-md-12">
                                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-md-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row mb-0">
                                <div class="col-md-8 offset-md-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Login') }}
                                    </button>

                                    @if (Route::has('password.request'))
{{--                                        <a class="btn btn-link" href="{{ route('password.request') }}">--}}
{{--                                            {{ __('Forgot Your Password?') }}--}}
{{--                                        </a>--}}
                                    @endif
                                </div>
                            </div>
                        </form>
                        <div class="w-100 position-relative mt-4">
                            <hr class="text-300" />
{{--                            <div class="position-absolute absolute-centered t-0 px-3 bg-white text-sans-serif fs--1 text-500 text-nowrap">or sign-in with</div>--}}
                        </div>
                        <div class="form-group mb-0">
{{--                            <div class="row no-gutters">--}}
{{--                                <div class="col-sm-6 pr-sm-1"><a class="btn btn-outline-google-plus btn-sm btn-block mt-2" href="#"><span class="fab fa-google-plus-g mr-2" data-fa-transform="grow-8"></span> google</a></div>--}}
{{--                                <div class="col-sm-6 pl-sm-1"><a class="btn btn-outline-facebook btn-sm btn-block mt-2" href="#"><span class="fab fa-facebook mr-2" data-fa-transform="grow-8"></span> facebook</a></div>--}}
{{--                            </div>--}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<!-- ===============================================-->
<!--    End of Main Content-->
<!-- ===============================================-->




<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->
<script src="{{ asset('pages/assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('pages/assets/js/popper.min.js') }}"></script>
<script src="{{ asset('pages/assets/js/bootstrap.js') }}"></script>
<script src="{{ asset('pages/assets/lib/stickyfilljs/stickyfill.min.js') }}"></script>
<script src="{{ asset('pages/assets/lib/sticky-kit/sticky-kit.min.js') }}"></script>
<script src="{{ asset('pages/assets/lib/is_js/is.min.js') }}"></script>
<script src="{{ asset('pages/assets/lib/@fortawesome/all.min.js') }}"></script>
<script src="{{ asset('pages/assets/js/theme.js') }}"></script>

</body>

</html>
