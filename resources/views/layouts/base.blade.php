<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- ===============================================-->
    <!--    Document Title-->
    <!-- ===============================================-->
    <title> TolCoin </title>


    <!-- ===============================================-->
    <!--    Favicons-->
    <!-- ===============================================-->
    {{--
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <link rel="manifest" href="{{ asset('pages/assets/img/favicons/manifest.json') }}">
    <meta name="msapplication-TileImage" content="{{ asset('pages/assets/img/favicons/tolCoin.png') }}">
    <meta name="theme-color" content="#ffffff">
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- ===============================================-->
    <!--    Stylesheets-->
    <!-- ===============================================-->
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:100,200,300,400,500,600,700,800,900"
        rel="stylesheet">
    <link href="{{ asset('pages/assets/lib/jqvmap/jqvmap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.css') }}"
        rel="stylesheet">
    <link href="{{ asset('pages/assets/css/theme.css') }}" rel="stylesheet">
    @stack('css')
</head>


<body>

    <!-- ===============================================-->
    <!--    Main Content-->
    <!-- ===============================================-->
    <main class="main" id="top">


        <div class="container">
            <nav class="navbar navbar-vertical navbar-expand-xl navbar-light navbar-glass">
                <a class="navbar-brand text-left" href="index.html">
                    <div class="d-flex align-items-center py-3">
                    <span class="text-sans-serif">ToLCoin</span>
                    </div>
                </a>
                <div class="collapse navbar-collapse" id="navbarVerticalCollapse">

                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{url('/dashboard')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-chart-pie">

                                            </span></span><span>Dashboard</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
                    <hr class="border-300 my-2" />
                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/cashbox')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-code-branch">

                                            </span></span><span>Cash Box</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
                    <hr class="border-300 my-2" />
                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{url('/tree')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-code-branch">

                                            </span></span><span>Refferal Tree</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
{{--                    <hr class="border-300 my-2" />--}}
{{--                    <ul class="navbar-nav flex-column">--}}
{{--                        <ul class="navbar-nav flex-column">--}}

{{--                            <li class="nav-item">--}}
{{--                                <a class="nav-link" href="{{url('/matrix')}}">--}}
{{--                                    <div class="d-flex align-items-center">--}}
{{--                                        <span class="nav-link-icon"><span class="fas fa-code-branch">--}}

{{--                                            </span></span><span>Wide Tree</span>--}}
{{--                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>--}}
{{--                                    </div>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                        </ul>--}}

{{--                    </ul>--}}
                    <hr class="border-300 my-2" />
                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{url('/tree_tabs')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-code-branch">

                                            </span></span><span>Tab Refferals</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>

{{--                    <hr class="border-300 my-2" />--}}
{{--                    <ul class="navbar-nav flex-column">--}}
{{--                        <ul class="navbar-nav flex-column">--}}

{{--                            <li class="nav-item">--}}
{{--                                <a class="nav-link" href="{{url('/packages')}}">--}}
{{--                                    <div class="d-flex align-items-center">--}}
{{--                                        <span class="nav-link-icon"><span class="fas fa-bookmark">--}}

{{--                                            </span></span><span>Packages</span>--}}
{{--                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>--}}
{{--                                    </div>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                        </ul>--}}

{{--                    </ul>--}}
                    <hr class="border-300 my-2" />
                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{url('/reffer_friend')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa box">

                                            </span></span><span>Reffer A Friend</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
                    <hr class="border-300 my-2" />

                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">
                            <li class="nav-item">
                                {{-- @foreach($users as $user)--}}
                                {{-- @if($user->id === auth()->user()->id)--}}
                                <a class="nav-link"
                                    href="{{ url('/edit_details/' . \Illuminate\Support\Facades\Auth::user()->id) }}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon">
                                            <span class="fas fa-user  "></span>
                                        </span>
                                        <span>Profile &amp; Account</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                                {{-- @endif--}}
                                {{-- @endforeach--}}
                            </li>
                        </ul>
                    </ul>
                    <hr class="border-300 my-2" />

                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/deposit') }}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-code-branch">

                                            </span></span><span>Deposit</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
                    <hr class="border-300 my-2" />


                    <ul class="navbar-nav flex-column">
                        <ul class="navbar-nav flex-column">

                            <li class="nav-item">
                                <a class="nav-link" href="{{ url('/profit')}}">
                                    <div class="d-flex align-items-center">
                                        <span class="nav-link-icon"><span class="fas fa-code-branch">

                                            </span></span><span>Profit ROI (Rewards)</span>
                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>
                                    </div>
                                </a>
                            </li>
                        </ul>

                    </ul>
{{--                    <hr class="border-300 my-2" />--}}

{{--                    <ul class="navbar-nav flex-column">--}}
{{--                        <ul class="navbar-nav flex-column">--}}

{{--                            <li class="nav-item">--}}
{{--                                <a class="nav-link" href="{{ url('/withdrawal/' . \Illuminate\Support\Facades\Auth::user()->id) }}">--}}
{{--                                    <div class="d-flex align-items-center">--}}
{{--                                        <span class="nav-link-icon"><span class="fas fa-code-branch">--}}

{{--                                            </span></span><span>Withdrawal</span>--}}
{{--                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>--}}
{{--                                    </div>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                        </ul>--}}

{{--                    </ul>--}}



{{--                    <hr class="border-300 my-2" />--}}


{{--                    <ul class="navbar-nav flex-column">--}}
{{--                        <ul class="navbar-nav flex-column">--}}

{{--                            <li class="nav-item">--}}
{{--                                <a class="nav-link" href="{{ url('/bonus')}}">--}}
{{--                                    <div class="d-flex align-items-center">--}}
{{--                                        <span class="nav-link-icon"><span class="fas fa-code-branch">--}}

{{--                                            </span></span><span>Bonus</span>--}}
{{--                                        <span class="badge badge-pill ml-2 badge-soft-primary"></span>--}}
{{--                                    </div>--}}
{{--                                </a>--}}
{{--                            </li>--}}
{{--                        </ul>--}}

{{--                    </ul>--}}

                    <hr class="border-300 my-2" />




                    <ul class="navbar-nav flex-column">

                        <li class="nav-item">
                            {{-- <a class="nav-link" href="{{url('/logout')}}">--}}
                                {{-- <div class="d-flex align-items-center">--}}
                                    {{-- <span class="nav-link-icon">--}}
                                        {{-- <span class="fas fancybox-active">--}}

                                            {{-- </span>--}}
                                        {{-- </span>--}}
                                    {{-- <span>Logout</span>--}}
                                    {{-- <span class="badge badge-pill ml-2 badge-soft-primary">--}}

                                        {{-- </span>--}}
                                    {{-- </div>--}}
                                {{-- </a>--}}

                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                {{ __('Logout') }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    </ul>
                    {{-- <a class="btn btn-primary btn-sm m-3" href="https://.com/themes/falcon/"
                        target="_blank">Purchase</a>--}}
                </div>
            </nav>
            <div class="content">
                <nav
                    class="navbar navbar-light navbar-glass fs--1 font-weight-semi-bold row navbar-top sticky-kit navbar-expand">
                    <button class="navbar-toggler collapsed" type="button" data-toggle="collapse"
                        data-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
                        aria-expanded="false" aria-label="Toggle navigation"><span
                            class="navbar-toggler-icon"></span></button><a class="navbar-brand text-left ml-3"
                        href="index.html">
                        <div class="d-flex align-items-center"><img class="mr-2"
                                src="assets/img/illustrations/falcon.png" alt="" width="40" />
                                <span
                                class="text-sans-serif">ToLCoin</span>
                        </div> 
                    </a>
                    <div class="collapse navbar-collapse" id="navbarNavDropdown1">
                        <ul class="navbar-nav align-items-center d-none d-lg-block">
                            <li class="nav-item">
                                <form class="form-inline search-box">
                                    <!-- <input class="form-control rounded-pill search-input" type="search"
                                        placeholder="Search..." aria-label="Search" /><span
                                        class="position-absolute fas fa-search text-400 search-box-icon"></span> -->
                                </form>
                            </li>
                        </ul>
                        <ul class="navbar-nav align-items-center ml-auto">
                            {{-- <li class="nav-item dropdown"><a class="nav-link px-0"
                                    href="e-commerce/shopping-cart.html"><span class="fas fa-cart-plus fs-4"
                                        data-fa-transform="shrink-7"></span></a></li>--}}
                            <li class="nav-item dropdown"><a class="nav-link unread-indicator px-0"
                                    id="navbarDropdownNotification" href="#" role="button" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false"><span class="fas fa-bell fs-4"
                                        data-fa-transform="shrink-6"></span></a>
                                <div class="dropdown-menu dropdown-menu-right dropdown-menu-card"
                                    aria-labelledby="navbarDropdownNotification">
                                    <div class="card card-notification shadow-none" style="max-width: 20rem">
                                        <div class="card-header">
                                            <div class="row justify-content-between align-items-center">
                                                <div class="col-auto">
                                                    <h6 class="card-header-title mb-0">Notifications</h6>
                                                </div>
                                                <div class="col-auto"><a class="card-link font-weight-normal"
                                                        href="#">Mark all as read</a></div>
                                            </div>
                                        </div>
                                        {{-- <div class="list-group list-group-flush font-weight-normal fs--1">--}}
                                            {{-- <div class="list-group-title">NEW</div>--}}
                                            {{-- <div class="list-group-item">--}}
                                                {{-- <a class="notification notification-flush bg-200" href="#!">--}}
                                                    {{-- <div class="notification-avatar">--}}
                                                        {{-- <div class="avatar avatar-2xl mr-3">--}}
                                                            {{-- <img class="rounded-circle" src="assets/img/team/1.jpg"
                                                                alt="" />--}}

                                                            {{-- </div>--}}
                                                        {{-- </div>--}}
                                                    {{-- <div class="notification-body">--}}
                                                        {{-- <p class="mb-1"><strong>Emma Watson</strong> replied to
                                                            your comment : "Hello world 😍"</p>--}}
                                                        {{-- <span class="notification-time"><span class="mr-1"
                                                                role="img" aria-label="Emoji">💬</span>Just
                                                            now</span>--}}

                                                        {{-- </div>--}}
                                                    {{-- </a>--}}

                                                {{-- </div>--}}
                                            {{-- <div class="list-group-item">--}}
                                                {{-- <a class="notification notification-flush bg-200" href="#!">--}}
                                                    {{-- <div class="notification-avatar">--}}
                                                        {{-- <div class="avatar avatar-2xl mr-3">--}}
                                                            {{-- <div class="avatar-name rounded-circle"><span>AB</span>
                                                            </div>--}}
                                                            {{-- </div>--}}
                                                        {{-- </div>--}}
                                                    {{-- <div class="notification-body">--}}
                                                        {{-- <p class="mb-1"><strong>Albert Brooks</strong> reacted to
                                                            <strong>Mia Khalifa's</strong> status</p>--}}
                                                        {{-- <span class="notification-time"><span
                                                                class="mr-1 fab fa-gratipay text-danger"></span>9hr</span>--}}

                                                        {{-- </div>--}}
                                                    {{-- </a>--}}

                                                {{-- </div>--}}
                                            {{-- <div class="list-group-title">EARLIER</div>--}}
                                            {{-- <div class="list-group-item">--}}
                                                {{-- <a class="notification notification-flush" href="#!">--}}
                                                    {{-- <div class="notification-avatar">--}}
                                                        {{-- <div class="avatar avatar-2xl mr-3">--}}
                                                            {{-- <img class="rounded-circle"
                                                                src="assets/img/icons/weather.jpg" alt="" />--}}

                                                            {{-- </div>--}}
                                                        {{-- </div>--}}
                                                    {{-- <div class="notification-body">--}}
                                                        {{-- <p class="mb-1">The forecast today shows a low of 20&#8451;
                                                            in California. See today's weather.</p>--}}
                                                        {{-- <span class="notification-time"><span class="mr-1"
                                                                role="img" aria-label="Emoji">🌤️</span>1d</span>--}}

                                                        {{-- </div>--}}
                                                    {{-- </a>--}}

                                                {{-- </div>--}}
                                            {{-- </div>--}}
                                        <div class="card-footer text-center border-top-0"><a class="card-link d-block"
                                                href="pages/notifications.html">View all</a></div>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown"><a class="nav-link pr-0" id="navbarDropdownUser" href="#"
                                    role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <div class="avatar avatar-xl">
                                        <img class="rounded-circle" src="{{ asset('pages/assets/img/team/user2.png') }}"
                                            alt="" />

                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right py-0"
                                    aria-labelledby="navbarDropdownUser">
                                    <div class="bg-white rounded-soft py-2">
                                        <!-- <a class="dropdown-item font-weight-bold text-warning" href="#!"><span
                                                class="fas fa-crown mr-1"></span><span>Go Pro</span></a> -->
                                        <!--
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item" href="#!">Set status</a>
                                                                        <a class="dropdown-item" href="pages/profile.html">Profile &amp; account</a>
                                                                        <a class="dropdown-item" href="#!">Feedback</a>-->

                                        <!-- <div class="dropdown-divider"></div> -->

                                        {{-- @foreach($users as $user)--}}
                                        {{-- @if($user->id === auth()->user()->id)--}}
                                        <a class="dropdown-item"
                                            href="{{ url('/') }}/edit_details/{{ \Illuminate\Support\Facades\Auth::id() }}">Profile
                                            &amp; Account</a>
                                        {{-- @endif--}}
                                        {{--@endforeach--}}
                                        <div class="dropdown-divider"></div>

                                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            {{ __('Logout') }}
                                        </a>
                                        <!-- <a class="dropdown-item" href="{{url('/logout')}}">Logout</a> -->

                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>

                @yield('content')

                <footer>
                    <div class="row no-gutters justify-content-between fs--1 mt-4 mb-3">
                        <div class="col-12 col-sm-auto text-center">
                            <p class="mb-0 text-600">Copyrights <span class="d-none d-sm-inline-block">| </span><br
                                    class="d-sm-none" /> 2023 &copy; <a href="https://.com"></a></p>
                        </div>
                        <div class="col-12 col-sm-auto text-center">
                            <p class="mb-0 text-600">v1.8.1</p>
                        </div>
                    </div>
                </footer>
            </div>
            <div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog"
                aria-labelledby="authentication-modal-label" aria-hidden="true">
                <div class="modal-dialog mt-6" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header px-5 text-white position-relative modal-shape-header">
                            <div class="position-relative z-index-1">
                                <div>
                                    <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
                                    <p class="fs--1 mb-0">Please create your free Falcon account</p>
                                </div>
                            </div>
                            <button class="close text-white position-absolute t-0 r-0 mt-1 mr-1" data-dismiss="modal"
                                aria-label="Close"><span class="font-weight-light"
                                    aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body py-4 px-5">
                            <form>
                                <div class="form-group">
                                    <label for="modal-auth-name">Name</label>
                                    <input class="form-control" type="text" id="modal-auth-name" />
                                </div>
                                <div class="form-group">
                                    <label for="modal-auth-email">Email</label>
                                    <input class="form-control" type="email" id="modal-auth-email" />
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label for="modal-auth-password">Password</label>
                                        <input class="form-control" type="password" id="modal-auth-password" />
                                    </div>
                                    <div class="form-group col-6">
                                        <label for="modal-auth-confirm-password">Confirm Password</label>
                                        <input class="form-control" type="password" id="modal-auth-confirm-password" />
                                    </div>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input class="custom-control-input" type="checkbox"
                                        id="modal-auth-register-checkbox" />
                                    <label class="custom-control-label" for="modal-auth-register-checkbox">I accept the
                                        <a href="#!">terms </a>and <a href="#!">privacy policy</a></label>
                                </div>
                                <div class="form-group">
                                    <button class="btn btn-primary btn-block mt-3" type="submit"
                                        name="submit">Register</button>
                                </div>
                            </form>
                            <div class="w-100 position-relative mt-5">
                                <hr class="text-300" />
                                <div
                                    class="position-absolute absolute-centered t-0 px-3 bg-white text-sans-serif fs--1 text-500 text-nowrap">
                                    or sign-up with</div>
                            </div>
                            <div class="form-group mb-0">
                                <div class="row no-gutters">
                                    <div class="col-sm-6 pr-sm-1"><a
                                            class="btn btn-outline-google-plus btn-sm btn-block mt-2" href="#"><span
                                                class="fab fa-google-plus-g mr-2" data-fa-transform="grow-8"></span>
                                            google</a></div>
                                    <div class="col-sm-6 pl-sm-1"><a
                                            class="btn btn-outline-facebook btn-sm btn-block mt-2" href="#"><span
                                                class="fab fa-facebook mr-2" data-fa-transform="grow-8"></span>
                                            facebook</a></div>
                                </div>
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
    <script src="{{ asset('pages/assets/lib/chart.js/Chart.min.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/jqvmap/jquery.vmap.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/jqvmap/maps/jquery.vmap.world.js') }}" charset="utf-8"></script>
    <script src="{{ asset('pages/assets/lib/jqvmap/maps/jquery.vmap.usa.js') }}" charset="utf-8"></script>
    <script src="{{ asset('pages/assets/lib/datatables/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/datatables.net-responsive/dataTables.responsive.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.js') }}"></script>
    <script src="{{ asset('pages/assets/js/theme.js') }}"></script>
    @stack('js')
</body>

</html>
