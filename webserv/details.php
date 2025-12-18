<?php

// libraries for MQ connection
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


// sessions to make sure the user is logged in when accessing the movie details page
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}



// Trakt API Client_ID used for authenticating and pulling data from Trakt API
$Client_ID = "d8f75848dca47e56ee15ccbc8658a084800c3c101d56e3ae0f2d40278dfb8943";



// checks if the movie ID is provided from the URL
if (!isset($_GET['id'])) {
    echo "<p>No movie selected.</p>";
    exit;
}


// encode the movie ID to safely pass into Trakt API URL
$id = urlencode($_GET['id']);


// endpoints for grabbing movie details and cast/crew information
$Movie_URL= "https://api.trakt.tv/movies/$id?extended=full";
$Credits_URL= "https://api.trakt.tv/movies/$id/people";



// function handles fetching from Trakt API using cURL
function fetchTraktAPI($url, $Client_ID) {

    // initialize cURL request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // attach headers required by Trakt API
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "trakt-api-key: $Client_ID",
        "trakt-api-version: 2",
        "User-Agent: MovieHubApp/1.0"
    ]);

    // execute and capture results
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // if API error it will give back null if not it will give the 200 code
    return $status === 200 ? json_decode($response, true) : null;
}



// grabs movie and credits data from the api
    $Movie= fetchTraktAPI($Movie_URL, $Client_ID);
    $Credits= fetchTraktAPI($Credits_URL, $Client_ID);


// error message if movie data does not return properly
if (!$Movie) {
    echo "<p>Error fetching movie details.</p>";
    exit;
}



// sanitize the keys for safe HTML display
$Title= htmlspecialchars($Movie['title']);
    $Year= htmlspecialchars($Movie['year']);
    $Overview= htmlspecialchars($Movie['overview']);



// arrays used for storing cast and crew information
        $Cast_List= [];
        $Crew_List= [];


// checks if credit information was successfully returned
if ($Credits) {

    // cast extraction – stored in array as "Actor Name as Character Name"
    if (!empty($Credits['cast'])) {
    foreach ($Credits['cast'] as $Cast) {
    $Cast_List[] = htmlspecialchars($Cast['person']['name']) .
                " as " . htmlspecialchars($Cast['character']);
        }
    }

 
    // crew extraction
    if (!empty($Credits['crew'])) {

     
     
     // extracting directors
        if (!empty($Credits['crew']['directing'])) {
            foreach ($Credits['crew']['directing'] as $p) {
                $Crew_List['Directors'][] = htmlspecialchars($p['person']['name']);
            }
        }


     
        // extracting writers
        if (!empty($Credits['crew']['writing'])) {
            foreach ($Credits['crew']['writing'] as $p) {
                $Crew_List['Writers'][] = htmlspecialchars($p['person']['name']);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo "$Title ($Year)"; ?></title>

 
<!-- bootswatch implementation for esponsive web design -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">

 
<style>


body {
    background:#f8f9fa;
    padding:20px;
    color:#333;
}


.container-box {
    max-width:800px;
    margin:auto;
    background:white;
    padding:30px;
    border-radius:12px;
    border:1px solid #ddd;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    position:relative;
}


        h1, h2 {
        font-weight:700;
        color:#0b7285;
        }


p, li {
 line-height:1.6;
 color:#444;
}

    ul { padding-left:20px; }

 

.btn-black {
 background:#000;
  color:white;
 border:none;
}

 
.btn-black:hover {
 background:#222;
}


.top-left-btn {
  position:absolute;
 top:20px;
  left:20px;
}

.top-right-btn {
  position:absolute;
 top:20px;
  right:20px;
}

    a {
      color:#0b7285;
     font-weight:bold;
      text-decoration:none;
    }
    a:hover { text-decoration:underline; }


@media (max-width: 600px) {
    .top-left-btn,
    .top-right-btn {
        position:static;
        display:block;
        margin-bottom:10px;
        text-align:right;
    }
}

</style>
</head>

<body>

<div class="container-box">


    <div class="top-left-btn">
        <a href="search.php">
      <button class="btn btn-black">← Back to Search</button>
        </a>
    </div>

    <!-- link to rate/review page -->
    <div class="top-right-btn">
        <a href="reviews.php?movie_id=<?php echo urlencode($id); ?>&movie_title=<?php echo urlencode($Title); ?>">
         <button class="btn btn-black">Rate / Review</button>
        </a>
    </div>


    <h1 class="mt-5"><?php echo "$Title ($Year)"; ?></h1>

   
    <p><?php echo nl2br($Overview); ?></p>

    <!-- director list -->
    <?php if (!empty($Crew_List['Directors'])): ?>
        <h2>Directors</h2>
        <ul>
             <?php foreach ($Crew_List['Directors'] as $d) echo "<li>$d</li>"; ?>
        </ul>
    <?php endif; ?>

    <!-- writer list -->
    <?php if (!empty($Crew_List['Writers'])): ?>
        <h2>Writers</h2>
        <ul>
            <?php foreach ($Crew_List['Writers'] as $w) echo "<li>$w</li>"; ?>
        </ul>
    <?php endif; ?>

  
    <?php if (!empty($Cast_List)): ?>
        <h2>Main Cast</h2>
        <ul>
            <?php foreach ($Cast_List as $c) echo "<li>$c</li>"; ?>
        </ul>
    <?php endif; ?>

</div>

</body>
</html>
