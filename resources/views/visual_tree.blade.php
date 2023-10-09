<?php
// Replace with your database connection information
//$servername = "localhost";
//$username = "root";
//$password = "";
//$dbname = "tolcoin";
//
//// Create a database connection
//$conn = new mysqli($servername, $username, $password, $dbname);

function isSimilarToExampleURL($url) {
    // Define the example URL
    $exampleURL = "http://127.0.0.1:8000/tree";

    // Normalize both URLs by removing trailing slashes and converting to lowercase
    $normalizedURL = rtrim(strtolower($url), '/');
    $normalizedExampleURL = rtrim(strtolower($exampleURL), '/');

    // Compare the normalized URLs
    return $normalizedURL === $normalizedExampleURL;
}

// Test the function
$urlToCheck = "http://127.0.0.1:8000/tree";
if (isSimilarToExampleURL($urlToCheck)) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tolcoin";
} else {
    $servername = "localhost";
    $username = "servloll_noman";
    $password = "P@kistan123";
    $dbname = "servloll_mlm";
}
$servername = "localhost";
$username = "servloll_noman";
$password = "P@kistan123";
$dbname = "servloll_mlm";
//
//$servername = "localhost";
//$username = "root";
//$password = "";
//$dbname = "tolcoin";
$conn = new mysqli($servername, $username, $password, $dbname);
// Query to retrieve data from the 'referrals' table
$sql = "SELECT * FROM referrals where level = 1 " ;
$result = $conn->query($sql);

// Create an associative array to organize the data as a tree
$tree = [];
while ($row = $result->fetch_assoc()) {
    $parentId = $row['parent_id'];
    $row['children'] = [];
    $tree[$parentId][] = $row;
}

// Function to recursively generate the HTML tree structure
function generateTree($tree, $parentId) {
    if (isset($tree[$parentId])) {
        echo '<ul>';
        foreach ($tree[$parentId] as $item) {
            echo '<li><span>' . $item['child_u_id'] . '</span>';
            generateTree($tree, $item['id']);
            echo '</li>';
        }
        echo '</ul>';
    }
}

// Output the HTML tree
?>



@extends('layouts.base')


@push('css')
    <style>
        body {
            font-family: Calibri, Segoe, "Segoe UI", "Gill Sans", "Gill Sans MT", sans-serif;
        }

        /* It's supposed to look like a tree diagram */
        .tree, .tree ul, .tree li {
            list-style: none;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .tree {
            margin: 0 0 1em;
            text-align: center;
        }
        .tree, .tree ul {
            display: table;
        }
        .tree ul {
            width: 100%;
        }
        .tree li {
            display: table-cell;
            padding: .5em 0;
            vertical-align: top;
        }
        /* _________ */
        .tree li:before {
            outline: solid 1px #666;
            content: "";
            left: 0;
            position: absolute;
            right: 0;
            top: 0;
        }
        .tree li:first-child:before {left: 50%;}
        .tree li:last-child:before {right: 50%;}

        .tree code, .tree span {
            border: solid .1em #666;
            border-radius: .2em;
            display: inline-block;
            margin: 0 .2em .5em;
            padding: .2em .5em;
            position: relative;
        }
        /* If the tree represents DOM structure */
        .tree code {
            font-family: monaco, Consolas, 'Lucida Console', monospace;
        }

        /* | */
        .tree ul:before,
        .tree code:before,
        .tree span:before {
            outline: solid 1px #666;
            content: "";
            height: .5em;
            left: 50%;
            position: absolute;
        }
        .tree ul:before {
            top: -.5em;
        }
        .tree code:before,
        .tree span:before {
            top: -.55em;
        }

        /* The root node doesn't connect upwards */
        .tree > li {margin-top: 0;}
        .tree > li:before,
        .tree > li:after,
        .tree > li > code:before,
        .tree > li > span:before {
            outline: none;
        }

    </style>
@endpush

@section('content')
    <div class="row">
        <div class="col-md-6">
{{--            <h1 style="color:#348EFE;">Matrix Tree</h1>--}}
        </div>
        <div class="col-md-6">
{{--            <h1 style="color:#348EFE;">Reffer Url</h1>--}}
{{--            <a href="{{url('/register?sponsor=adminuser2')}}" target="_blank">--}}
{{--                {{url('/register?sponsor=adminuser2')}}--}}
{{--            </a>--}}
        </div>

    </div>

    <figure>
        <figcaption>TREE  --- {{ \Illuminate\Support\Facades\Auth::user()->u_id}}</figcaption>


        <br>
        <ul class="tree">
            <?php generateTree($tree, \Illuminate\Support\Facades\Auth::id()) ?>
        </ul>
    </figure>
@endsection


@push('js')

@endpush
