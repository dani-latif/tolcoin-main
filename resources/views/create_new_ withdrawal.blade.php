@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>


<div class="row mt-4">
    <div class="col-md-12 text-center">
        <h1 style="color:#348EFE;"> Your Withdrawal</h1>
    </div>
</div>

<div class="d-flex justify-content-end">
    <!-- Button to open the modal -->
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawalModal">
        Create New Withdrawal 
    </button>
</div>

<div class="card">

    <div class="modal fade" id="withdrawalModal" tabindex="-1" aria-labelledby="withdrawalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="withdrawalModalLabel">Withdrawal Form</h5>
                    <button type="button" class="btn-close btn-danger" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">Close</span>
                    </button>

                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <form action="/withdrawal" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id" class="form-label">ID:</label>
                                    <input type="text" id="id" name="id" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount:</label>
                                    <input type="text" id="date" name="amount" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Currency:</label>
                                    <input type="text" id="amount" name="currency" class="form-control">
                                </div>
                            </div>
                        </div>


                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Transfer Fee:</label>
                                    <input type="text" id="amount" name="transfer_fee" class="form-control">
                                </div>
                            </div>
                        </div>



                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Donation:</label>
                                    <input type="text" id="amount" name="donation" class="form-control">
                                </div>
                            </div>
                        </div>



                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Withdrawal Mode:</label>
                                    <input type="text" id="amount" name="withdrawal_mode" class="form-control">
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Fund Receiver ID:</label>
                                    <input type="text" id="amount" name="recevier_id" class="form-control">
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

</div>





<!-- Table -->

<div class="card bg-light p-4 mt-4">
    <div class="row">
        <div class="col-12">
            <table class="table table-sm table-dashboard data-table
                                     display responsive no-wrap mb-0 fs--1 w-100">
                                     <thead class="bg-200">
                    <tr>
                        <th>Sr. #</th>
                        <th>ID</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Transfer Fee</th>
                        <th>Donation</th>
                        <th>Withdrawal Mode</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Fund Receiver ID</th>



                    </tr>
                </thead>
                <tbody class="bg-white">


                    @php
                    $count = 1;
                    @endphp

                    <tr>
                        <td>{{$count++}}</td>
                        <td>122</td>
                        <td>2 $</td>
                        <td>PKR</td>
                        <td>0</td>
                        <td>0</td>
                        <td>Investment</td>
                        <td>Success</td>
                        <td>14/06+/2023</td>
                        <td>Atgr#24</td>

                    </tr>


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