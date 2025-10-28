<?php

// Sessions to make sure the User is logged in when accessing page
session_start(); 

/* 

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); // Redirect back to login page if not logged in
    exit();
}

*/


// Client_ID used for grabbing the proper ID from the Trakt API
$Client_ID = "d8f75848dca47e56ee15ccbc8658a084800c3c101d56e3ae0f2d40278dfb8943";

// Check if a movie ID was provided in the URL (GET parameter)
// Checks if ID is present from the movie URL
if (!isset($_GET['id'])) {
    echo "<p>No movie selected.</p>"; // Message if no movie chosen
    exit; }


// encode url for safe API use
$id = urlencode($_GET['id']);

// Endpoints from Trakt to grab movie details and credits (cast & crew)
$Movie_URL = "https://api.trakt.tv/movies/$id?extended=full";
$Credits_URL = "https://api.trakt.tv/movies/$id/people"; 



 // fetching the TraktAPI to use in cURL method
function fetchTraktAPI($url, $Client_ID) {
    $ch = curl_init($url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, [ 
        "Content-Type: application/json",
        "trakt-api-key: $Client_ID",
        "trakt-api-version: 2",
        "User-Agent: MyMovieApp/1.0"
    ]);
    
    $response = curl_exec($ch); 
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    curl_close($ch); 

// null returned if the API failed to call
    if ($http_code !== 200) return null; 
    return json_decode($response, true); // decode & respond to json
}

// grabs movie and credits data from Trakt API
$Movie = fetchTraktAPI($Movie_URL, $Client_ID);
$Credits = fetchTraktAPI($Credits_URL, $Client_ID);


// error message if movie data could not be fetched
if (!$Movie) {
    echo "<p>Error fetching movie details.</p>";
    exit;
}


// sanitize the keys to safely display
$Title = htmlspecialchars($Movie['title']);
$Year = htmlspecialchars($Movie['year']);
$Overview = htmlspecialchars($Movie['overview']);


// array to store cast & crew info
$Cast_List = $Crew_List = [];


// displays if credits are fetched successfully
if ($Credits) {
    
    if (isset($Credits['cast'])) {
        foreach ($Credits['cast'] as $Cast) {
            // Format as "Actor Name as Character Name"
            $Cast_List[] = htmlspecialchars($Cast['person']['name']) . " as " . htmlspecialchars($Cast['character']);
        } }

  
    if (isset($Credits['crew'])) {
        foreach ($Credits['crew'] as $Department => $People) {
            // extract director info
            if ($Department === 'directing') {
                foreach ($People as $p) $Crew_List['Directors'][] = htmlspecialchars($p['person']['name']);
            }
            
            // extract writing info
            if ($Department === 'writing') {
                foreach ($People as $p) $Crew_List['Writers'][] = htmlspecialchars($p['person']['name']);
            } }
            }
         }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $Title; ?> Details of Film</title>
<style>
/* Page Styling */
body { background-color: #000; color: #fff; font-family: Roboto, sans-serif; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; background: #1e1e1e; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px #e50914; }
img { width: 100%; border-radius: 10px; margin-bottom: 15px; }
h1 { color: #e50914; }
h2 { color: #e50914; margin-top: 20px; }
p, li { color: #ccc; }
a { display: inline-block; margin-top: 20px; color: #e50914; text-decoration: none; }
a:hover { text-decoration: underline; }
ul { padding-left: 20px; }
</style>
</head>
<body>
<div class="container">
    <!-- movie poster(need to find a way to have pictures or not) -->
    <img src="<?php echo $Poster; ?>" alt="Poster" onerror="this.src='https://via.placeholder.com/500x750?text=No+Image'">

    <!-- Movie Title & yr -->
    <h1><?php echo "$Title ($Year)"; ?></h1>

    <!-- Movie Description -->
    <p><?php echo nl2br($Overview); ?></p>

    <!-- Directors Section -->
    <?php if (!empty($Crew_List['Directors'])): ?>
        <h2>Directors:</h2>
        <ul>
            <?php foreach ($Crew_List['Directors'] as $Director) echo "<li>$Director</li>"; ?>
        </ul>
    <?php endif; ?>

    <!-- Writers Section -->
    <?php if (!empty($Crew_List['Writers'])): ?>
        <h2>Writers:</h2>
        <ul>
            <?php foreach ($Crew_List['Writers'] as $Writer) echo "<li>$Writer</li>"; ?>
        </ul>
    <?php endif; ?>

    <!-- Cast Section -->
    <?php if (!empty($Cast_List)): ?>
        <h2>Main Cast:</h2>
        <ul>
            <?php foreach ($Cast_List as $Cast_Member) echo "<li>$Cast_Member</li>"; ?>
        </ul>
    <?php endif; ?>

    <!-- Back to Search Link -->
    <a href="search.php">← Back to Search</a>
</div>
</body>
</html>
