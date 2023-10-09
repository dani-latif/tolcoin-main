@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


<div class="row mt-4">
    <div class="col-md-12 text-center">
        <h1 style="color:#348EFE;">Your Deposits</h1>
    </div>
</div>

<div class="d-flex justify-content-end">
    <!-- Button to open the modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
        Create New Deposit
    </button>
</div>

<div class="card">

    <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="depositModalLabel">Deposit Form</h5>
                    <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">Close</span>
                    </button>

                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form action="{{route('deposit_save')}}" method="POST">
                        @csrf
                        <div class="row">
{{--                            <div class="col-md-6">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="id" class="form-label">ID:</label>--}}
{{--                                    <input type="text" id="id" name="id" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date" class="form-label">Amount:</label>
                                    <input type="text" id="amount" name="amount" class="form-control">
                                </div>


                            </div>
                        </div>

{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Payment Type:</label>--}}
{{--                                    <input type="text" id="amount" name="payment_type" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}


{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Rate:</label>--}}
{{--                                    <input type="text" id="amount" name="rate" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Deposit Fee:</label>--}}
{{--                                    <input type="text" id="amount" name="deposit_fee" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}


{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Total Deposit:</label>--}}
{{--                                    <input type="text" id="amount" name="total_deposit" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Plan Name:</label>--}}
{{--                                    <input type="text" id="amount" name="plan_name" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}

{{--                        <div class="row">--}}
{{--                            <div class="col-md-12">--}}
{{--                                <div class="mb-3">--}}
{{--                                    <label for="amount" class="form-label">Withdrawal Limit</label>--}}
{{--                                    <input type="text" id="amount" name="withdrawal_limit" class="form-control">--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}


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

</div>



<!-- Table -->

<div class="card bg-light p-4 mt-4">
    @if(\Illuminate\Support\Facades\Session::has('success'))
        <p class="alert alert-info">{{ \Illuminate\Support\Facades\Session::get('success') }}</p>
    @endif
    @if(\Illuminate\Support\Facades\Session::has('error'))
        <p class="alert alert-danger">{{ \Illuminate\Support\Facades\Session::get('error') }}</p>
    @endif
    <div class="row">

        <div class="col-12">
            <table class="table table-sm table-dashboard data-table
                                     display responsive no-wrap mb-0 fs--1 w-100">
                <thead class="bg-200">
                    <tr>
                        <th>Sr.#</th>
{{--                        <th>ID</th>--}}
                        <th>Payment Type</th>
                        <th>Amount</th>
{{--                        <th>Rate</th>--}}
                        <th>Deposit Fee</th>
                        <th>Total Deposit</th>
                        <th>Status</th>
{{--                        <th>Plan Name</th>--}}
{{--                        <th>Withdrawable Limit</th>--}}
                        <th>Created</th>
                        <th>Action</th>



                    </tr>
                </thead>
                <tbody class="bg-white">


                    @php
                    $count = 1;
                    @endphp

                    @foreach($deposits as $deposit)
                    <tr>
                        <td>{{$count++}}</td>
                        <td>investment</td>
                        <td>{{$deposit->amount}}</td>
{{--                        <td>234$</td>--}}
                        <td>0</td>
                        <td>0</td>
                        <td>Approved</td>
                        <td>{{$deposit->created_at}}</td>
                        {{--                        <td>TriconV</td>--}}
{{--                        <td>1000$</td>--}}
                        <td></td>


                    </tr>
                    @endforeach

                </tbody>

            </table>
        </div>

    </div>
</div>













@endsection


@push('js')


<script src="{{ asset('pages/assets/lib/datatables/js/jquery.dataTables.min.js')}}"></script>
<script src="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{ asset('pages/assets/lib/datatables.net-responsive/dataTables.responsive.js')}}"></script>
<script src="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.js')}}"></script>


@endpush
