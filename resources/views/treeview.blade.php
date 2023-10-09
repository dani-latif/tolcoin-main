<?php
// Replace with your database connection information
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tolcoin";

// Create a database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to retrieve data from the 'referrals' table
$sql = "SELECT * FROM referrals";
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


<!DOCTYPE html>
<html lang="en" >
<head>
    <meta charset="UTF-8">
    <title>Tree view from unordered list</title>
    <link rel="stylesheet" href="./style.css">

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
</head>
<body>
<!-- partial:index.partial.html -->
<figure>
    <figcaption>Example Tree from Database</figcaption>
    <ul class="tree">
        <?php generateTree($tree, 0); ?>
    </ul>
</figure>
{{--<figure>--}}
{{--    <figcaption>Example DOM structure diagram</figcaption>--}}
{{--    <ul class="tree">--}}
{{--        <li><code>html</code>--}}
{{--            <ul>--}}
{{--                <li><code>head</code>--}}
{{--                    <ul>--}}
{{--                        <li><code>title</code></li>--}}
{{--                    </ul>--}}
{{--                </li>--}}
{{--                <li><code>body</code>--}}
{{--                    <ul>--}}
{{--                        <li><code>header</code>--}}
{{--                            <ul>--}}
{{--                                <li><code>h1</code></li>--}}
{{--                                <li><code>p</code></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><code>nav</code>--}}
{{--                            <ul>--}}
{{--                                <li><code>a</code></li>--}}
{{--                                <li><code>a</code></li>--}}
{{--                                <li><code>a</code></li>--}}
{{--                                <li><code>a</code></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><code>main</code>--}}
{{--                            <ul>--}}
{{--                                <li><code>h1</code></li>--}}
{{--                                <li><code>article</code>--}}
{{--                                    <ul>--}}
{{--                                        <li><code>h2</code></li>--}}
{{--                                        <li><code>p</code></li>--}}
{{--                                        <li><code>p</code></li>--}}
{{--                                    </ul>--}}
{{--                                </li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><code>aside</code>--}}
{{--                            <ul>--}}
{{--                                <li><code>h2</code></li>--}}
{{--                                <li><code>p</code></li>--}}
{{--                                <li><code>p</code>--}}
{{--                                    <ul>--}}
{{--                                        <li><code>a</code></li>--}}
{{--                                    </ul>--}}
{{--                                </li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><code>footer</code>--}}
{{--                            <ul>--}}
{{--                                <li><code>nav</code>--}}
{{--                                    <ul>--}}
{{--                                        <li><code>a</code></li>--}}
{{--                                        <li><code>a</code></li>--}}
{{--                                        <li><code>a</code></li>--}}
{{--                                        <li><code>a</code></li>--}}
{{--                                    </ul>--}}
{{--                                </li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </li>--}}
{{--            </ul>--}}
{{--        </li>--}}
{{--    </ul>--}}
{{--    --}}
{{--</figure>--}}
{{--<figure>--}}
{{--    <figcaption>Example sitemap</figcaption>--}}
{{--    <ul class="tree">--}}
{{--        <li><span>Home</span>--}}
{{--            <ul>--}}
{{--                <li><span>About us</span>--}}
{{--                    <ul>--}}
{{--                        <li><span>Our history</span>--}}
{{--                            <ul>--}}
{{--                                <li><span>Foudnder</span></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><span>Our board</span>--}}
{{--                            <ul>--}}
{{--                                <li><span>Brad Whiteman</span></li>--}}
{{--                                <li><span>Cynthia Tolken</span></li>--}}
{{--                                <li><span>Bobby Founderson</span></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </li>--}}
{{--                <li><span>Our products</span>--}}
{{--                    <ul>--}}
{{--                        <li><span>The Widget 2000™</span>--}}
{{--                            <ul>--}}
{{--                                <li><span>Order form</span></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                        <li><span>The McGuffin V2</span>--}}
{{--                            <ul>--}}
{{--                                <li><span>Order form</span></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </li>--}}
{{--                <li><span>Contact us</span>--}}
{{--                    <ul>--}}
{{--                        <li><span>Social media</span>--}}
{{--                            <ul>--}}
{{--                                <li><span>Facebook</span></li>--}}
{{--                            </ul>--}}
{{--                        </li>--}}
{{--                    </ul>--}}
{{--                </li>--}}
{{--            </ul>--}}
{{--        </li>--}}
{{--    </ul>--}}
{{--</figure>--}}
<p><a href="https://medium.com/@ross.angus/sitemaps-and-dom-structure-from-nested-unordered-lists-eab2b02950cf" target="_blank">Full writeup</a></p>
<!-- partial -->

</body>
</html>
