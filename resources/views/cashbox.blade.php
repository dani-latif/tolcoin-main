@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


<div class="card row col-md-12">
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>

                <h5 class="text-success">
                    {{$cbAccount['current_balance']}}(USD)
                    <small class="text-muted">Current Balance</small>
                </h5>
                <h5 class="text-success">
                    {{$cbAccount['bnb_balance']}}(BNB)
                    <small class="text-muted">Current Balance</small>
                </h5>
                <h5 class="text-success">
                    {{$cbAccount['tolcoin_balance']}}(TolCoin)
                    <small class="text-muted">Current Balance</small>
                </h5>
{{--                <h5 class="text-success">--}}
{{--                    {{$cbAccount['public_address']}}--}}
{{--                    <small class="text-muted">Public Address</small>--}}
{{--                </h5>--}}
{{--                <h5 class="text-success">--}}
{{--                    {{$cbAccount['private_address']}}--}}
{{--                    <small class="text-muted">Private Key</small>--}}
{{--                </h5>--}}
                <small>
                    <p class="text-muted mt-3">{{$cbAccount['public_address']}} -----Public Address</p>
{{--                    <p class="text-muted mt-3">{{$cbAccount['private_key']}}</p><p> Private Key</p>--}}
                </small>

            </div>
            <div>

                @if($cbAccount['public_address'])
                @else
                    <a href="{{url('/make_wallet')}}" class="btn btn-primary btn-sm"> Create Wallet</a>
                @endif
                    <a href="{{url('/get_wallet')}}" class="btn btn-primary btn-sm"> Update Wallet</a>
                    {{--                <a href="#" class="btn btn-primary btn-sm">⇆ Transfer</a>--}}
                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#depositModal">↓ Cash
                    In</a>
{{--                <a href="#" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#withdrawalModal">↓--}}
{{--                    Cash Out</a>--}}


            </div>

        </div>

    </div>
</div>



<!-- Model  Cash In  -->
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="depositModalLabel">Cash In (Credit)</h5>
                <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">Close</span>
                </button>

            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <form action="{{ route('save.credit') }}" method="POST">
                    @csrf

                    <input type="hidden" name="cb_account_id" value="{{ $cbAccount->id }}">

                    <div class="row">

                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="date" class="form-label">Amount:</label>
                                <input type="text" name="amount" class="form-control">
                            </div>
                        </div>
                    </div>




                    <div class="row">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->

        </div>
    </div>
</div>




<!-- Model Cash Out s -->



    <div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalModalLabel">Cash Out (Debit)</h5>
                    <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">Close</span>
                    </button>

                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form action="{{ route('save.debit') }}" method="POST">
                        @csrf


                    <input type="hidden" name="cb_account_id" value="{{ $cbAccount->id }}">


                        <div class="row">

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount:</label>
                                    <input type="text" id="date" name="amount" class="form-control">
                                </div>
                            </div>
                        </div>


                        <div class="row d-flex justify-content-end">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer -->

            </div>
        </div>
    </div>




    <!-- Table -->

    <div class="card row col-md-12 p-4 mt-4">

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a style="color:black;" class="nav-link active" id="home-tab" data-toggle="tab"
                    href="#tab-home" role="tab" aria-controls="tab-home" aria-selected="true">Debit</a>
            </li>
            <li class="nav-item"><a style="color:black;" class="nav-link" id="profile-tab" data-toggle="tab"
                    href="#tab-profile" role="tab" aria-controls="tab-profile" aria-selected="false">Credit</a>
            </li>
        </ul>




        <div class="tab-content border-x border-bottom p-3" id="myTabContent">
            <div class="tab-pane fade show active" id="tab-home" role="tabpanel" aria-labelledby="home-tab">
                <div class="card-body bg-light">
                    <div class="row">
                        <div class="col-12">
                            <table class="table table-sm table-dashboard
                                    data-table display responsive no-wrap mb-0 fs--1 w-100">
                                <thead class="bg-200">
                                    <tr>
                                        <th> Sr. #</th>
                                        <th>Amount</th>
                                        <th>Deduction Fee</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Transaction At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php $count=1?>

                                    @foreach($cb_debits as $cb_debit)
                                    <tr>
                                        <td>{{$count++}}</td>
                                        <td>{{$cb_debit->amount}}</td>
                                        <td>0c</td>
                                        <td>active</td>
                                        <td>invest</td>
                                        <td>noting</td>
                                        <td>12-25-2025</td>
                                        <td></td>


                                    </tr>
                                    @endforeach
                                </tbody>


                            </table>
                        </div>

                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">
                <div class="card-body bg-light">
                    <div class="row">
                        <div class="col-12">
                            <table
                                class="table table-sm table-dashboard data-table display responsive no-wrap mb-0 fs--1 w-100">
                                <thead class="bg-200">
                                    <tr>
                                        <th>Sr. #</th>
                                        <th>Amount</th>
                                        <th>Deduction Fee</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Transaction At</th>
                                        <th>Action</th>



                                    </tr>
                                </thead>

                                <tbody class="bg-white">

                                    <?php $count=1?>


                                    @foreach($cb_credits as $cb_credit)
                                    <tr>
                                        <td>{{$count++}}</td>
                                        <td>{{$cb_credit->amount}}</td>
                                        <td>1212</td>
                                        <td>0</td>
                                        <td>active</td>
                                        <td>invest</td>
                                        <td>noting</td>
                                        <td>12-25-2025</td>


                                    </tr>
                                    @endforeach


                                </tbody>




                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <script src="{{ asset('pages/assets/lib/datatables/js/jquery.dataTables.min.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables.net-responsive/dataTables.responsive.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.js')}}"></script>


        @endsection


        @push('js')

        <script src="{{ asset('pages/assets/lib/datatables/js/jquery.dataTables.min.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables.net-responsive/dataTables.responsive.js')}}"></script>
        <script src="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.js')}}"></script>





        @endpush
