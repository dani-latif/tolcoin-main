@extends('layouts.base')

@push('css')
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
<link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')


<!-- <div class="row">
    <div class="col-12">
        <div class="card mb-3 btn-reveal-trigger">
            <div class="card-header position-relative min-vh-25 mb-8">
            <form method="POST" action="/update_details" enctype="multipart/form-data">
                    @csrf
                    @foreach($users as $user)
                    <input name="id" type="hidden" value="{{$user->id}}" readonly>


                <div class="cover-image">
                    <div class="bg-holder rounded-soft rounded-bottom-0"
                        style="background-image: url('../pages/assets/img/generic/4.jpg');">
                    </div>
                    <!--/.bg-holder-->

                    <input class="d-none" id="upload-cover-image" type="file">
                    <label class="cover-image-file-input" for="upload-cover-image"><span
                            class="fas fa-camera mr-2"></span><span>Change cover photo</span></label>
                </div>
                <!-- <div class="avatar avatar-5xl avatar-profile shadow-sm img-thumbnail rounded-circle">
                    <div class="h-100 w-100 rounded-circle overflow-hidden position-relative">
                      <img src="{{ asset('images/Profiles-Photos/' . $user->profile_image) }}" width="200" alt="Profile">

                        <input class="d-none" id="profile-image" type="file" name="profile_image">
                        <label class="mb-0 overlay-icon d-flex flex-center" for="profile-image"><span
                                class="bg-holder overlay overlay-0"></span><span
                                class="z-index-1 text-white text-center fs--1"><span class="fas fa-camera"></span><span
                                    class="d-block">Update</span></span></label>
                    </div>
                </div> -->
              </div>
 
        </div>
    </div>
   
</div>
<div class="row no-gutters">
  <div class="col-lg-3 col-sm-3">
</div>
    <div class="col-lg-7 pr-lg-2 col-sm-8">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Profile Settings</h5>
            </div>
            <div class="card-body bg-light">



                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="first-name">User ID</label>
                                <input class="form-control" name="u_id" type="text" value="{{$user->u_id}}" readonly>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="first-name">Upline ID</label>
                                <input class="form-control" name="upline_id" type="text" value="{{$user->upline_id}}"
                                    readonly>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label for="first-name">Your Level</label>
                                <input class="form-control" name="level_no" type="text" value="{{$user->level_no}}"
                                    readonly>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="last-name">Email Address</label>
                                <input class="form-control" name="email" type="email" value="{{$user->email}}" readonly>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="email1">First Name</label>
                                <input class="form-control" name="fName" type="text" value="{{$user->fName}}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="phone">Last Name</label>
                                <input class="form-control" name="lName" type="text" value="{{$user->lName}}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="heading">Mobile Number</label>
                                <input class="form-control" name="mobile_number" type="text"
                                    value="{{$user->mobile_number}}">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select class="form-control" name="gender">
                                    <option value="M" {{ $user->gender === 'M' ? 'selected' : '' }}>Male</option>
                                    <option value="F" {{ $user->gender === 'F' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>
                        </div>




                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="email1">Country</label>
                                <input class="form-control" name="country" type="text" value="{{$user->country}}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="phone">State</label>
                                <input class="form-control" name="state" type="text" value="{{$user->state}}">
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label for="email1">Address</label>
                                <input class="form-control" name="address" type="text" value="{{$user->address}}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="phone">City</label>
                                <input class="form-control" name="city" type="text" value="{{$user->city}}">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label for="phone">ZIP Code</label>
                                <input class="form-control" name="zip_code" type="text" value="{{$user->zip_code}}">
                            </div>
                        </div>










                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">Update </button>
                        </div>
                    </div>
                    @endforeach
                </form>
            </div>
        </div>
        <div class="card mb-3">
            <!-- <div class="card-header">
                  <h5 class="mb-0">Experiences</h5>
                </div>
                <div class="card-body bg-light"><a class="mb-4 d-block d-flex align-items-center" href="#experience-form" data-toggle="collapse" aria-expanded="false" aria-controls="experience-form"><span class="circle-dashed"><span class="fas fa-plus"></span></span><span class="ml-3">Add new experience</span></a>
                  <div class="collapse" id="experience-form">
                    <form>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="company">Company</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="company" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="position">Position</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="position" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="city">City</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="city" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="exp-description">Description</label>
                          </div>
                          <div class="col-lg-7">
                            <textarea class="form-control form-control-sm" id="exp-description" rows="3"></textarea>
                          </div>
                        </div>
                      </div>
                      <div class="row">
                        <div class="col-lg-7 offset-lg-3">
                          <div class="form-group form-check">
                            <input class="form-check-input" id="exampleCheck1" type="checkbox" checked="" />
                            <label class="form-check-label" for="exampleCheck1">I currently work here</label>
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="exp-from">From</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm datetimepicker" id="exp-from" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="exp-to">To</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm datetimepicker" id="exp-to" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-7 offset-lg-3">
                            <button class="btn btn-primary" type="button">Save</button>
                          </div>
                        </div>
                      </div>
                    </form>
                    <hr class="border-dashed border-bottom-0 my-4" />
                  </div>
                  <div class="media"><a href="#!"> <img class="img-fluid" src="../assets/img/logos/g.png" alt="" width="56" /></a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0">Big Data Engineer<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small>
                      </h6>
                      <p class="mb-1"> <a href="#!">Google</a></p>
                      <p class="text-1000 mb-0">Apr 2012 - Present &bull; 6 yrs 9 mos</p>
                      <p class="text-1000 mb-0">California, USA</p>
                      <hr class="border-dashed border-bottom-0" />
                    </div>
                  </div>
                  <div class="media"><a href="#!"> <img class="img-fluid" src="../assets/img/logos/apple.png" alt="" width="56" /></a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0">Software Engineer<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small>
                      </h6>
                      <p class="mb-1"> <a href="#!">Apple</a></p>
                      <p class="text-1000 mb-0">Jan 2012 - Apr 2012 &bull; 4 mos</p>
                      <p class="text-1000 mb-0">California, USA</p>
                      <hr class="border-dashed border-bottom-0" />
                    </div>
                  </div>
                  <div class="media"><a href="#!"> <img class="img-fluid" src="../assets/img/logos/nike.png" alt="" width="56" /></a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0">Mobile App Developer<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small>
                      </h6>
                      <p class="mb-1"> <a href="#!">Nike</a></p>
                      <p class="text-1000 mb-0">Jan 2011 - Apr 2012 &bull; 1 yr 4 mos</p>
                      <p class="text-1000 mb-0">Beaverton, USA</p>
                    </div>
                  </div>
                </div>  -->
        </div>




        <div class="mb-3 mb-lg-0" style="background:transparent;">
            <!-- <div class="card-header">
                  <h5 class="mb-0">Educations</h5>
                </div>
                <div class="card-body bg-light"><a class="mb-4 d-block d-flex align-items-center" href="#education-form" data-toggle="collapse" aria-expanded="false" aria-controls="education-form"><span class="circle-dashed"><span class="fas fa-plus"></span></span><span class="ml-3">Add new education</span></a>
                  <div class="collapse" id="education-form">
                    <form>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="school">School</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="school" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="degree">Degree</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="degree" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="field">Field</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm" id="field" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="edu-from">From</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm datetimepicker" id="edu-from" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-3 text-lg-right">
                            <label class="mb-0" for="edu-to">To</label>
                          </div>
                          <div class="col-lg-7">
                            <input class="form-control form-control-sm datetimepicker" id="edu-to" type="text" />
                          </div>
                        </div>
                      </div>
                      <div class="form-group">
                        <div class="row">
                          <div class="col-lg-7 offset-lg-3">
                            <button class="btn btn-primary" type="button">Save</button>
                          </div>
                        </div>
                      </div>
                    </form>
                    <hr class="border-dashed border-bottom-0 my-4" />
                  </div>
                  <div class="media"><a href="#!">
                      <div class="avatar avatar-3xl">
                        <div class="avatar-name rounded-circle"><span>SU</span></div>
                      </div>
                    </a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0"> <a href="#!">Stanford University<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small></a></h6>
                      <p class="mb-1">Computer Science and Engineering</p>
                      <p class="text-1000 mb-0">2010 - 2014 • 4 yrs</p>
                      <p class="text-1000 mb-0">California, USA</p>
                      <hr class="border-dashed border-bottom-0" />
                    </div>
                  </div>
                  <div class="media"><a href="#!"> <img class="img-fluid" src="../assets/img/logos/staten.png" alt="" width="56" /></a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0"> <a href="#!">Staten Island Technical High School<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small></a></h6>
                      <p class="mb-1">Higher Secondary School Certificate, Science</p>
                      <p class="text-1000 mb-0">2008 - 2010 &bull; 2 yrs</p>
                      <p class="text-1000 mb-0">New York, USA</p>
                      <hr class="border-dashed border-bottom-0" />
                    </div>
                  </div>
                  <div class="media"><a href="#!"> <img class="img-fluid" src="../assets/img/logos/tj-heigh-school.png" alt="" width="56" /></a>
                    <div class="media-body position-relative pl-3">
                      <h6 class="fs-0 mb-0"> <a href="#!">Thomas Jefferson High School for Science and Technology<small class="fas fa-check-circle text-primary ml-1" data-toggle="tooltip" data-placement="top" title="Verified" data-fa-transform="shrink-4 down-2"></small></a></h6>
                      <p class="mb-1">Secondary School Certificate, Science</p>
                      <p class="text-1000 mb-0">2003 - 2008 &bull; 5 yrs</p>
                      <p class="text-1000 mb-0">Alexandria, USA</p>
                    </div>
                  </div>
                </div> -->

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body bg-light">
                    @if(session('Passwordsuccess'))
                    <div class="alert alert-success">
                        {{ session('Passwordsuccess') }}
                    </div>
                    @endif
                    @if(session('Passworderror'))
                    <div class="alert alert-danger">
                        {{ session('Passworderror') }}
                    </div>
                    @endif


                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <div class="form-group">
                            <label for="old-password">Old Password</label>
                            <input class="form-control" id="old-password" type="password" name="old_password">
                        </div>
                        <div class="form-group">
                            <label for="new-password">New Password</label>
                            <input class="form-control" id="new-password" type="password" name="new_password">
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirm Password</label>
                            <input class="form-control" id="confirm-password" type="password" name="confirm_password">
                        </div>
                                            
                      </form>
                      
                      <div class="col-3 d-flex justify-content-end">
                        <button class="btn btn-primary btn-block mr-1" type="submit">Update Password</button>
                        </div>
                </div>
            </div>
            <!-- <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Danger Zone</h5>
                </div>
                <div class="card-body bg-light">

                    <h5 class="fs-0">Transfer Ownership</h5>
                    <p class="fs--1">Transfer this account to another user or to an organization where you have the
                        ability to create repositories.</p><a class="btn btn-falcon-warning d-block"
                        href="#!">Transfer</a>
                    <hr class="border border-dashed my-4">

                    <h5 class="fs-0">Delete this account</h5>

                    @foreach($users as $user)

                    <input type="hidden" name="id" value="{{$user->id}}">
                    <p class="fs--1">Once you delete a account, there is no going back. Please be certain.</p><a
                        class="btn btn-falcon-danger d-block" href="/delete_Account/{{$user->id}}">Delete Account</a>
                    @endforeach
                    </form>
                </div> -->
            </div>
        </div>
    </div>
    <!-- <div class="col-lg-4 pl-lg-2">
        <div class="sticky-top sticky-sidebar">
            <div class="card mb-3 overflow-hidden">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body bg-light">
                    <h6 class="font-weight-bold">Who can see your profile ?<span class="fs--2 ml-1 text-primary"
                            data-toggle="tooltip" data-placement="top"
                            title="Only The group of selected people can see your profile"><span
                                class="fas fa-question-circle"></span></span></h6>
                    <div class="pl-2">
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="view-settings" id="everyone" />
                            <label class="custom-control-label" for="everyone">Everyone
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="view-settings" id="my-followers"
                                checked="checked" />
                            <label class="custom-control-label" for="my-followers">My followers
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="view-settings" id="only-me" />
                            <label class="custom-control-label" for="only-me">Only me
                            </label>
                        </div>
                    </div>
                    <h6 class="mt-2 font-weight-bold">Who can tag you ?<span class="fs--2 ml-1 text-primary"
                            data-toggle="tooltip" data-placement="top"
                            title="Only The group of selected people can tag you"><span
                                class="fas fa-question-circle"></span></span></h6>
                    <div class="pl-2">
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="tag-settings" id="tag-everyone" />
                            <label class="custom-control-label" for="tag-everyone">Everyone
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input class="custom-control-input" type="radio" name="tag-settings" id="group-members"
                                checked="checked" />
                            <label class="custom-control-label" for="group-members">Group Members
                            </label>
                        </div>
                    </div>
                    <hr class="border-dashed border-bottom-0">
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input" type="checkbox" id="checkbox1" checked="checked" />
                        <label class="custom-control-label" for="checkbox1">Allow users to show your followers
                        </label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input" type="checkbox" id="checkbox2" checked="checked" />
                        <label class="custom-control-label" for="checkbox2">Allow users to show your email
                        </label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input" type="checkbox" id="checkbox3" />
                        <label class="custom-control-label" for="checkbox3">Allow users to show your experiences
                        </label>
                    </div>
                    <hr class="border-dashed border-bottom-0">
                    <div class="custom-control custom-switch">
                        <input class="custom-control-input" type="checkbox" id="switch1" checked="checked" />
                        <label class="custom-control-label" for="switch1">Make your phone number visible
                        </label>
                    </div>
                    <div class="custom-control custom-switch">
                        <input class="custom-control-input" type="checkbox" id="switch2" />
                        <label class="custom-control-label" for="switch2">Allow user to follow you
                        </label>
                    </div>
                </div>
            </div> -->
            <!-- <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Billing Setting</h5>
                </div>
                <div class="card-body bg-light">
                    <h5>Plan</h5>
                    <p class="fs-0"><strong>Developer</strong>- Unlimited private repositories</p><a
                        class="btn btn-falcon-default btn-sm" href="#!">Update Plan</a>
                </div>
                <div class="card-body bg-light border-top">
                    <h5>Payment</h5>
                    <p class="fs-0">You have not added any payment.</p><a class="btn btn-falcon-default btn-sm"
                        href="#!">Add Payment </a>
                </div>
            </div> -->


        </div>
    </div>
</div>
@endsection


@push('js')
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>
<script src="{{ asset('jquery-org-chart/js/taffy.js') }}"></script>
<script src="{{ asset('jquery-org-chart/js/jquery.jOrgChart.js') }}"></script>

<!-- 
<script>
$(document).ready(function() {
    function loadjson() {
        $.ajax({
            type: 'post',
            url: "{{url('/matrix_get_user')}}",
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                process_data(response.data);
            }
        });
    }

    function process_data(dataa) {
        var datab = TAFFY(dataa);
        var data = datab();
        data({
            "parent": ""
        }).each(function(record, recordnumber) {
            loops(record);
        });

        $("<ul>", {
            "id": "org",
            "style": "float:right;",
            "html": items.join("")
        }).appendTo("body");

        init_tree();
    }

    var items = [];

    function loops(root) {
        if (root.parent === "") {
            items.push("<li class='unic" + root.id + " root' id='" + root.username + "'><div class='person'><div class='person_img'><img class='img-tree' src='{{asset('/Icons/avatar-big.png')}}'></div><div class='person_title label_node'>" + root.username + "</br></div></div><div class='details'><p><strong>Email:</strong>" + root.email + "</p></div>");
        } else {
            items.push("<li class='child unic" + root.id + "' id='" + root.username + "'><div class='person'><div class='person_img'><img class='img-tree' src='{{asset('/Icons/avatar-big.png')}}'></div><div class='person_title label_node'>" + root.username + "</br></div></div><div class='details'><p><strong>Email:</strong>" + root.email + "</p></div>");
        }

        var c = data({
            "parent": root.id
        }).count();

        if (c !== 0) {
            items.push("<ul>");
            data({
                "parent": root.id
            }).each(function(record, recordnumber) {
                loops(record);
            });
            items.push("</ul></li>");
        } else {
            items.push("</li>");
        }
    }

    function init_tree() {
        var opts = {
            chartElement: '#chart',
            dragAndDrop: true,
            expand: true,
            control: true,
            rowcolor: false
        };

        $("#chart").html("");
        $("#org").jOrgChart(opts);
    }

    function scroll() {
        $(".node").click(function() {
            $("#chart").scrollTop(0);
            $("#chart").scrollTop($(this).offset().top - 140);
        });
    }

    loadjson();
    scroll();
});
</script> -->
@endpush