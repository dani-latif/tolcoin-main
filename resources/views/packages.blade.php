@extends('layouts.base')


@push('css')
{{--    <link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">--}}
{{--    <link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">--}}
@endpush

@section('content')
    <div class="content">
        <div class="card overflow-hidden mb-3">
            <div class="bg-holder d-none d-lg-block bg-card" style="background-image:url(../assets/img/illustrations/corner-4.png);">
            </div>
            <!--/.bg-holder-->

            <div class="card-body position-relative">
                <h6 class="text-600">Plans tolcoin</h6>
                <h2>For teams of all sizes</h2>
                <p class="mb-0">Get the power, control, and customization you need to manage your<br class="d-none d-md-block"> team’s and organization’s projects.</p><a class="btn btn-link pl-0 btn-sm mt-3" href="#!"> Have questions? Chat with us</a>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-body">
                <div class="row no-gutters">
                    <div class="col-12 mb-3">
                        <div class="row justify-content-center justify-content-sm-between">
                            <div class="col-sm-auto text-center">
                                <h5 class="d-inline-block">Daily Profit</h5><span class="badge badge-soft-success badge-pill ml-2">Save 25%</span>
                            </div>
{{--                            <div class="col-sm-auto d-flex flex-center fs--1 mt-1 mt-sm-0">--}}
{{--                                <label class="mr-2 mb-0" for="customSwitch1">Monthly</label>--}}
{{--                                <div class="custom-control custom-switch">--}}
{{--                                    <input class="custom-control-input" id="customSwitch1" type="checkbox" checked>--}}
{{--                                    <label class="custom-control-label" for="customSwitch1">Yearly</label>--}}
{{--                                </div>--}}
{{--                            </div>--}}
                        </div>
                    </div>
                    <div class="col-lg-4 border-top border-bottom">
                        <div class="h-100">
                            <div class="text-center p-4">
                                <h3 class="font-weight-normal my-0">Single</h3>
                                <p class="mt-3">For teams that need to create project plans with confidence.</p>
                                <h2 class="font-weight-medium my-4">
                                    <sup class="font-weight-normal fs-2 mr-1">&dollar;
                            </div>
                            <hr class="border-bottom-0 m-0">
                            <div class="text-left px-sm-4 py-4">
                                <h5 class="font-weight-medium fs-0">Track team projects with free:</h5>
                                <ul class="list-unstyled mt-3">
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success">

                                        </span> Timeline</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success">

                                        </span> Advanced Search</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success">

                                        </span> Custom fields
                                        <span class="badge badge-soft-success badge-pill ml-1">New</span></li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success">

                                        </span> Task dependencies</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success">

                                        </span> Private teams & projects</li>
                                </ul><a class="btn btn-link" href="#">More about Single</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 border-top border-bottom">
                        <div class="h-100" style="background-color: rgba(115, 255, 236, 0.18)">
                            <div class="text-center p-4">
                                <h3 class="font-weight-normal my-0">Business</h3>
                                <p class="mt-3">For teams and companies that need to manage work across initiatives.</p>
                                <h2 class="font-weight-medium my-4"> <sup class="font-weight-normal fs-2 mr-1">&dollar;</sup>39<small class="fs--1 text-700">/ year</small></h2><a class="btn btn-primary" href="../pages/billing.html">Get Business</a>
                            </div>
                            <hr class="border-bottom-0 m-0">
                            <div class="text-left px-3 px-sm-4 py-4">
                                <h5 class="font-weight-medium fs-0">Everything in Premium, plus:</h5>
                                <ul class="list-unstyled mt-3">
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Portfolios </li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Lock custom fields </li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Onboarding plan</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Resource Management</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Lock custom fields</li>
                                </ul><a class="btn btn-link" href="#">More about Business</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 border-top border-bottom">
                        <div class="h-100">
                            <div class="text-center p-4">
                                <h3 class="font-weight-normal my-0">Extended</h3>
                                <p class="mt-3">For organizations that need additional security and support.</p>
                                <h2 class="font-weight-medium my-4"> <sup class="font-weight-normal fs-2 mr-1">&dollar;</sup>99<small class="fs--1 text-700">/ year</small></h2><a class="btn btn-outline-primary" href="../pages/billing.html">Purchase</a>
                            </div>
                            <hr class="border-bottom-0 m-0">
                            <div class="text-left px-sm-4 py-4">
                                <h5 class="font-weight-medium fs-0">Everything in Business, plus:</h5>
                                <ul class="list-unstyled mt-3">
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Portfolios </li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Tags<span class="badge badge-soft-primary badge-pill ml-1">Coming soon</span></li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Onboarding plan</li>
                                    <li class="py-1"><span class="mr-2 fas fa-check text-success"></span> Resource Management</li>
                                </ul><a class="btn btn-link" href="#">More about Extended</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 text-center">
                        <h5 class="mt-5">Looking for personal or small team task management?</h5>
                        <p class="fs-1">Try the <a href="#">basic version</a> of Falcon</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="text-center mb-0">Frequently asked questions</h4>
            </div>
            <div class="card-body bg-light">
                <div class="ui styled fluid accordion" data-options='{"exclusive":false}'>
                    <div class="active title"><i class="dropdown icon"></i>
                        <h5 class="d-inline-block fs-0">How long do payouts take?</h5>
                    </div>
                    <div class="active content">
                        <p>Once you’re set up, payouts arrive in your bank account on a 2-day rolling basis. Or you can opt to receive payouts weekly or monthly</p>
                    </div>
                    <div class="title"><i class="dropdown icon"></i>
                        <h5 class="d-inline-block fs-0">How do refunds work?</h5>
                    </div>
                    <div class="content">
                        <p>You can issue either partial or full refunds. There are no fees to refund a charge, but the fees from the original charge are not returned.</p>
                    </div>
                    <div class="title"><i class="dropdown icon"></i>
                        <h5 class="d-inline-block fs-0">How much do disputes costs?</h5>
                    </div>
                    <div class="content">
                        <p>Disputed payments (also known as chargebacks) incur a $15.00 fee. If the customer’s bank resolves the dispute in your favor, the fee is fully refunded</p>
                    </div>
                    <div class="title"><i class="dropdown icon"></i>
                        <h5 class="d-inline-block fs-0">Is there a fee to use Apple Pay or Google Pay?</h5>
                    </div>
                    <div class="content">
                        <p>There are no additional fees for using our mobile SDKs or to accept payments using consumer wallets like Apple Pay or Google Pay.</p>
                    </div>
                </div>
            </div>
        </div>
        <footer>
            <div class="row no-gutters justify-content-between fs--1 mt-4 mb-3">
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 text-600">Thank you<span class="d-none d-sm-inline-block">| </span><br class="d-sm-none" /> 2023 &copy; <a href="https://.com"></a></p>
                </div>
                <div class="col-12 col-sm-auto text-center">
                    <p class="mb-0 text-600">v1.8.1</p>
                </div>
            </div>
        </footer>
    </div>
@endsection
