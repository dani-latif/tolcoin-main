@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')


<div class="row">

    <div class="col-md-12">
        <center>
            <h1 style="color:#348EFE;">Bonus</h1>
        </center>

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
                        <th>SN</th>
                        <th>Amount</th>
                        <th>Date</th>



                    </tr>
                </thead>
                <tbody class="bg-white">


                    @php
                    $count = 1;
                    @endphp

                    <tr>
                        <td>{{$count++}}</td>
                        <td>800 $</td>
                        <td>14-06-2023</td>

                    </tr>


                </tbody>

            </table>
        </div>

    </div>
</div>




    @endsection


    @push('js')
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>
    <script src="{{ asset('jquery-org-chart/js/taffy.js') }}"></script>
    <script src="{{ asset('jquery-org-chart/js/jquery.jOrgChart.js') }}"></script>
    <script src="{{ asset('pages/assets/lib/datatables/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{ asset('pages/assets/lib/datatables.net-responsive/dataTables.responsive.js')}}"></script>
    <script src="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.js')}}"></script>


    @endpush