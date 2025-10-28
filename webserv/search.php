<?php
// required files for MQ communication
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


session_start(); 
// make sure user is logged to access the search.php file
if (!isset($_SESSION['username'])) {
    // redirect user if not in the right page
    header("Location: index.html");
    exit(); }


// get search term url query
$search_term = isset($_GET['q']) ? trim($_GET['q']) : '';

 // sends user's desired search query to dmz server by using MQ
function searchMoviesViaDMZ($query) {
    // MQ client to communicate with DMZ VM
    $client = new rabbitMQClient("movieRabbitMQ.ini", "DMZMovieServer");

    
    // requests sent to backend
    $request = [
        'type' => 'movieSearch', 
        'query' => $query     ];   // search term


    // request sent 
    $response = $client->send_request($request);

    // checks how legit is the response and if it is, return success and the movie data should be pulled
    if ($response && isset($response['returnCode']) && $response['returnCode'] === 1) {
        return $response['data']; // <---- Movie Data returned here
    }
    return null; // if movie data is not returned, report null
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Movie Search</title>
<style>
/* ---- Page Styling---- */
body {
    font-family: 'Times New Roman', Tahoma, Geneva, Verdana, sans-serif;
    background: #121212;
    color: #fff;
    margin: 0;
    padding: 20px;
}
	h1 {
    	text-align: center;
    	color: #e50914;
    	letter-spacing: 1px; }
	form {
    	text-align: center;
    	margin-bottom: 30px;}

	/*Search Bar*/
	input[type="text"] {
    padding: 10px;
    width: 320px;
    border-radius: 5px;
    border: 1px solid #333;
    background: #1e1e1e;
    color: #fff;
    font-size: 16px; }
	button {
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    border: none;
    background: #e50914;
    color: white;
    cursor: pointer;
    margin-left: 10px;
}


/* Movie Display section */
.movies-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 25px;
}

/* Movie Card */
.movie-card {
    background: #1e1e1e;
    border-radius: 12px;
    overflow: hidden;
    width: 200px;
    box-shadow: 0 6px 10px rgba(0,0,0,0.6);
    text-align: center;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}


.movie-card h3 {
    margin: 10px 0 5px 0;
    font-size: 16px;
    color: #e50914;
}
.movie-card p {
    margin: 0 0 10px 0;
    color: #ccc;
    font-size: 14px;
}

/*  Details Button */
.details-link {
    display: inline-block;
    margin-bottom: 12px;
    padding: 6px 12px;
    background-color: #e50914;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px; }
    

/* Browse Button*/
.browse-btn {
    padding: 10px 20px;
    font-size: 16px;
    border-radius: 5px;
    border: none;
    background: #e50914;
    color: white;
    text-decoration: none;
    cursor: pointer;
    position: absolute;
    top: 20px;
    left: 20px;
    display: inline-block;
    text-align: center;
}

</style>
</head>
<body>

<!--Browse Button -->
<a href="browse.php" class="browse-btn">Browse</a>

<!--Search Form -->
<h1>Search for Movie</h1>
<form method="GET" action="">
    <!-- sanitize input before display for security reasons-->
    <input type="text" name="q" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Enter movie...">
    <button type="submit">Search</button>
</form>

<!-- movie results -->
<div class="movies-container">
<?php
// search term entered
if ($search_term !== '') {
    // Grab movie data through the DMZ using MQ
    $movies = searchMoviesViaDMZ($search_term);

    // Checks if movies was returned successfully 
    if ($movies) {
        // Loop through each movie result
        foreach ($movies as $item) {
            if (!isset($item['movie'])) continue;
            $movie = $item['movie'];

            // extract sanitized movie info
            $id = htmlspecialchars($movie['ids']['slug']);
            $title = htmlspecialchars($movie['title']);
            $year = htmlspecialchars($movie['year']);


            // Render movie card HTML
            echo "<div class='movie-card'>";
echo "<img src='$poster' alt='$title' onerror=\"this.src='https://via.placeholder.com/200x300?text=No+Image'\">";
            echo "<h3>$title</h3>";
            echo "<p>($year)</p>";
            echo "<a class='details-link' href='details.php?id=$id'>View Details</a>";
            echo "</div>";
        }
    } 
    
    else {
        // Show error if no results or the API call failed
        // error if no results where found or if API call failed
        echo "<p style='text-align:center;'>No results found or API failed.</p>"; }
}
?>
</div>
</body>
</html>
