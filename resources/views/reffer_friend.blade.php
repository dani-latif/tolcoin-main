@extends('layouts.base')

@push('css')
    <link rel="stylesheet" href="{{ asset('jquery-org-chart/css/jquery.jOrgChart.css') }}">
    <link rel="stylesheet" href="{{ asset('jquery-org-chart/css/custom.css') }}">
@endpush

@section('content')
<style>
    .copy-button {
        display: inline-flex;
        align-items: center;
        background-color: #f5f5f5;
        border: none;
        padding: 5px;
        border-radius: 5px;
        cursor: pointer;
        color:green;

    }

    .copy-button:hover {
        background-color: #e0e0e0;
    }

    .copy-icon {
        margin-right: 5px;
        color:green;
    }

    .referral-input {
        border: 1px solid #ccc;
        padding: 5px;
        border-radius: 5px;
        width: 100%;
        margin-bottom: 10px;
        font-size: 16px;
    }
</style>

<div class="row">
   
    <div class="col-md-12">
        <center><h1 style="color:#348EFE;">Reffer Url</h1></center>
        <div class="mt-4">
            <input readonly  type="text" id="referral-url" value="{{ url('/register?sponsor=') }}{{ \Illuminate\Support\Facades\Auth::user()->username }}" readonly class="referral-input">
            <button class="mt-2 copy-button" onclick="copyToClipboard()">
                <i class="far fa-copy copy-icon"></i>
                Copy
            </button>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        var referralUrl = document.getElementById("referral-url");
        referralUrl.select();
        referralUrl.setSelectionRange(0, 99999);
        document.execCommand("copy");
        alert("Link copied to clipboard!");
    }
</script>

@endsection


@push('js')
    <!-- <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script> -->
{{--    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.0/jquery.min.js"></script>--}}
{{--    <script src="{{ asset('jquery-org-chart/js/taffy.js') }}"></script>--}}
{{--    <script src="{{ asset('jquery-org-chart/js/jquery.jOrgChart.js') }}"></script>--}}

{{--    <script>--}}
{{--        function loadjson() {--}}
{{--            var data=TAFFY([]);--}}
{{--            $.ajax({--}}
{{--                type:'post',--}}
{{--                url: "{{url('/matrix_get_user')}}",--}}
{{--                dataType: 'json',--}}
{{--                headers: {--}}
{{--                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')--}}
{{--                },--}}

{{--                success:function(response){--}}
{{--                    process_data(response.data);--}}

{{--                }--}}
{{--            });--}}
{{--            function process_data(dataa){--}}
{{--                var datab= TAFFY(dataa);--}}
{{--                data=datab;--}}
{{--                data({--}}
{{--                    "parent": ""--}}
{{--                }).each(function(record, recordnumber) {--}}
{{--                    loops(record);--}}
{{--                });--}}

{{--                $("<ul>", {--}}
{{--                    "id": "org",--}}
{{--                    "style": "float:right;",--}}
{{--                    html: items.join("")--}}
{{--                }).appendTo("body");--}}

{{--                init_tree();--}}
{{--            }--}}
{{--            var items = [];--}}

{{--            function loops(root) {--}}

{{--                if (root.parent == "") {--}}
{{--                    items.push("<li class='unic" + root.id + " root' id='" + root.username + "'><div class='person'><div class='person_img'><img class='img-tree' src='{{asset('/Icons/avatar-big.png')}}''></div>  <div class='person_title label_node'>" + root.username + "</br></div></div><div class='details'><p><strong>Email:</strong>" + root.email + "</p></div>");--}}
{{--                } else {--}}
{{--                    items.push("<li class='child unic" + root.id + "' id='" + root.username + "'><div class='person'><div class='person_img'><img class='img-tree' src='{{asset('/Icons/avatar-big.png')}}''></div>  <div class='person_title label_node'>" + root.username + "</br></div></div><div class='details'><p><strong>Email:</strong>" + root.email + "</p></div>");--}}
{{--                }--}}
{{--                var c = data({--}}
{{--                    "parent": root.id--}}
{{--                }).count();--}}
{{--                if (c != 0) {--}}
{{--                    items.push("<ul>");--}}
{{--                    data({--}}
{{--                        "parent": root.id--}}
{{--                    }).each(function(record, recordnumber) {--}}
{{--                        loops(record);--}}
{{--                    });--}}
{{--                    items.push("</ul></li>");--}}
{{--                } else {--}}
{{--                    items.push("</li>");--}}
{{--                }--}}

{{--            } // End the generate html code--}}


{{--            //push to html code--}}
{{--        }--}}

{{--        //////////////////////////////////////////////////--}}

{{--    </script>--}}
{{--    <script type="text/javascript">--}}
{{--        function init_tree() {--}}
{{--            var opts = {--}}
{{--                chartElement: '#chart',--}}
{{--                dragAndDrop: true,--}}
{{--                expand: true,--}}
{{--                control: true,--}}
{{--                rowcolor: false--}}
{{--            };--}}
{{--            $("#chart").html("");--}}
{{--            $("#org").jOrgChart(opts);--}}
{{--        }--}}

{{--        function scroll() {--}}
{{--            $(".node").click(function() {--}}
{{--                $("#chart").scrollTop(0)--}}
{{--                $("#chart").scrollTop($(this).offset().top - 140);--}}
{{--            })--}}
{{--        }--}}

{{--        $(document).ready(function() {--}}
{{--            loadjson();--}}

{{--            scroll()--}}
{{--        });--}}
{{--    </script>--}}
@endpush




