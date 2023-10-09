@extends('layouts.base')

@push('css')


<link href="{{ asset('pages/assets/lib/datatables-bs4/dataTables.bootstrap4.min.css')}}" rel="stylesheet">
<link href="{{ asset('pages/assets/lib/datatables.net-responsive-bs4/responsive.bootstrap4.css')}}" rel="stylesheet">


@endpush

@section('content')
<div class="content">

    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Refferals</h5>
        </div>


        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="home-tab" data-toggle="tab" href="#tab-home" role="tab"
                    aria-controls="tab-home" aria-selected="true">Level 1</a>
            </li>
            <li class="nav-item"><a class="nav-link" id="profile-tab" data-toggle="tab" href="#tab-profile" role="tab"
                    aria-controls="tab-profile" aria-selected="false">Level 2</a>
            </li>
            <li class="nav-item"><a class="nav-link" id="contact-tab" data-toggle="tab" href="#tab-contact" role="tab"
                    aria-controls="tab-contact" aria-selected="false">Level 3</a>
            </li>
{{--            <li class="nav-item"><a class="nav-link" id="level4" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 4</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level5" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 5</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level6" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 6</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level7" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 7</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level8" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 8</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level9" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 9</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level10" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 10</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level11" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 11</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level12" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 12</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level13" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 13</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level14" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 14</a>--}}
{{--            </li>--}}
{{--            <li class="nav-item"><a class="nav-link" id="level15" data-toggle="tab" href="#tab-contact" role="tab"--}}
{{--                    aria-controls="tab-contact" aria-selected="false">Level 15</a>--}}
{{--            </li>--}}
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
                                        <th>id</th>
                                        <th>Parent Id</th>
                                        <th>Parent Name</th>
                                        <th>Parent Email</th>
                                        <th>Child Id</th>
                                        <th>Child Name</th>
                                        <th>Child Email</th>
                                        <th>level</th>


                                    </tr>
                                </thead>
                                <tbody class="bg-white">

                                    <?php $count=1?>
                                    @foreach($refferals1 as $refferal)



                                    <tr>
                                        <td>{{$count++}}</td>
                                        <td>{{$refferal->parent_u_id}}</td>
                                        <td>{{ $refferal->parent_name }}</td>
                                        <td>{{ $refferal->parent_email }}</td>
                                        <td>{{$refferal->child_u_id}}</td>
                                        <td>{{ $refferal->child_name }}</td>
                                        <td>{{ $refferal->child_email }}</td>
                                        <td>{{$refferal->level}}</td>

                                    </tr>



                                    @endforeach
                                </tbody>


                            </table>
                        </div>

                    </div>
                </div>
            </div>
{{--            <div class="tab-pane fade tab-content show " id="tab-profile" role="tabpanel" aria-labelledby="profile-tab">--}}
{{--                <div class="card-body bg-light" >--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table display--}}
{{--                                     responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}

{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals2 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}



{{--                                    @endforeach--}}
{{--                                </tbody>--}}




{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="tab-contact" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals3 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $refferal->parent_name }}</td>--}}
{{--                                        <td>{{ $refferal->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $refferal->child_name }}</td>--}}
{{--                                        <td>{{ $refferal->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}


{{--                                </tbody>--}}




{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level4" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals4 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $refferal->parent_name }}</td>--}}
{{--                                        <td>{{ $refferal->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $refferal->child_name }}</td>--}}
{{--                                        <td>{{ $refferal->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}



{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level5" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals5 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}



{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level6" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals6 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}



{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level7" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals7 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}


{{--                                    @endforeach--}}
{{--                                </tbody>--}}
{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level8" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals8 as $refferal)--}}



{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level9" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}

{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals9 as $refferal)--}}

{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}



{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level10" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals10 as $refferal)--}}


{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level11" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}

{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}

{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals11 as $refferal)--}}


{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}


{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level12" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals12 as $refferal)--}}


{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level13" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals13 as $refferal)--}}

{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level14" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals14 as $refferal)--}}

{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}

{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--            <div class="tab-pane fade" id="level15" role="tabpanel" aria-labelledby="contact-tab">--}}
{{--                <div class="card-body bg-light">--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-12">--}}
{{--                            <table class="table table-sm table-dashboard data-table--}}
{{--                                     display responsive no-wrap mb-0 fs--1 w-100">--}}
{{--                                <thead class="bg-200">--}}
{{--                                    <tr>--}}
{{--                                        <th>id</th>--}}
{{--                                        <th>Parent Id</th>--}}
{{--                                        <th>Parent Name</th>--}}
{{--                                        <th>Parent Email</th>--}}
{{--                                        <th>Child Id</th>--}}
{{--                                        <th>Child Name</th>--}}
{{--                                        <th>Child Email</th>--}}
{{--                                        <th>level</th>--}}


{{--                                    </tr>--}}
{{--                                </thead>--}}
{{--                                <tbody class="bg-white">--}}

{{--                                    <?php $count=1?>--}}
{{--                                    @foreach($refferals15 as $refferal)--}}


{{--                                    <tr>--}}
{{--                                        <td>{{$count++}}</td>--}}
{{--                                        <td>{{$refferal->parent_u_id}}</td>--}}
{{--                                        <td>{{ $referral->parent_name }}</td>--}}
{{--                                        <td>{{ $referral->parent_email }}</td>--}}
{{--                                        <td>{{$refferal->child_u_id}}</td>--}}
{{--                                        <td>{{ $referral->child_name }}</td>--}}
{{--                                        <td>{{ $referral->child_email }}</td>--}}
{{--                                        <td>{{$refferal->level}}</td>--}}

{{--                                    </tr>--}}


{{--                                    @endforeach--}}
{{--                                </tbody>--}}

{{--                            </table>--}}
{{--                        </div>--}}

{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
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
