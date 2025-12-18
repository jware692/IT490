<?php

// libraries for MQ connection
require_once('path.inc'); 
require_once('get_host_info.inc'); 
require_once('rabbitMQLib.inc'); 

// sessions so user can only access the browse feature if they are logged in
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php"); 
    exit(); 
}



// request goes from MQ to backend to request Trending movies for browse feature
function getTrendingMoviesFromDMZ() {

    // creates a new MQ client & connects to the dmz
    $client = new rabbitMQClient("testRabbitMQ.ini", "DMZMovieServer");

    
    // request the specific type of data being pulled and a limit installed of the max amount of movies given back
    $request = [
        "type"=>"trendingMovies","limit"=>1000
    ];

    
    // request sent to DMZ VM and the response stored
    $response = $client->send_request($request);

    
    // movie data returns if the response is valid
    if ($response && isset($response['returnCode']) && $response['returnCode'] === 1) {
        return $response['data'];
    }

    

    return null;
}



	// grabs trending movies from dmz 
	$trendingMovies = getTrendingMoviesFromDMZ();
		if ($trendingMovies) {     // shuffles movies so user does not see the same movie over & over
	shuffle($trendingMovies);
	}

?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Movies</title>

<!-- Bootswatch implementation for responsive web design -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">

<style>


	body {
    	background:#f8f9fa;
    	color:#333;
    	padding:20px;
		}
	


	h1, h2 {
    	text-align:center;
    	color:#0b7285;
    	font-weight:700;
	}


	.btn-black {
    	background:#000;
    	color:#fff;
    	border:none;
	}
.btn-black:hover {
    background:#222;
}


.movies-container {
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    gap:20px;
    margin-top:20px;
}


	.movie-card {
	    background:white;
	    border:1px solid #e0e0e0;
	    border-radius:12px;
	    width:220px;
	    padding:15px;
	    text-align:center;
	    box-shadow:0 4px 10px rgba(0,0,0,0.1);
	    transition:0.2s;
	}




.movie-card h3 {
    font-size:18px;
    margin-bottom:8px;
    color:#0b7285;
    font-weight:600;
}


.movie-card p {
    margin:0 0 12px 0;
    color:#555;
    font-size:14px;
}


.details-link {
    background:#000;
    color:white;
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    font-size:14px;
}
.details-link:hover {
    background:#222;
    color:white;
}

</style>
</head>

<body>

<h1>Browse Movies</h1>

	<!-- redirects user to search page where the nav bar is located as well -->
<div class="text-center mt-3">
<a href="search.php" class="btn btn-black">Search for Movies</a>
</div>


<h2 class="mt-4">Trending Movies</h2>
	

<!-- container to display all movie cards -->
<div class="movies-container" id="moviesContainer">

<?php
if ($trendingMovies) {

    // displays first 20 movies on page load
    $initialMovies = array_slice($trendingMovies, 0, 20);


	
    foreach ($initialMovies as $item) {

        // ensures a valid movie object returned
        if (!isset($item['movie'])) continue;

        $movie = $item['movie'];

        
        // sanitize and extract movie data
        $id= htmlspecialchars($movie['ids']['slug']);
        $title= htmlspecialchars($movie['title']);
        $year= htmlspecialchars($movie['year']);

        
        echo "
        <div class='movie-card'>
            <h3>$title</h3>
            <p>($year)</p>
            <a class='details-link' href='details.php?id=$id'>View Details</a>
        </div>";
    }

}

else {

    // message shown if API call fails or returns nothing
    echo "<p style='text-align:center;'>No trending movies available.</p>";
}
?>

</div>


<!-- load more button for expanding movie list -->
<?php if ($trendingMovies): ?>
<div class="text-center mt-4">
    <button id="loadMoreBtn" class="btn btn-black btn-lg">Load More</button>
</div>
<?php endif; ?>



<script>

// pass movie data from PHP to JS for dynamic rendering
const allMovies = <?php echo json_encode($trendingMovies); ?>;

// shows how many movies have been displayed
let displayedCount = 20;


	
const container= document.getElementById('moviesContainer');
const loadMoreBtn= document.getElementById('loadMoreBtn');


// dynamic movie rendering into the page
function renderMovies(movies) {

    movies.forEach(item => {

		
        if (!item.movie) return;

		
        const movie= item.movie;
        const id= movie.ids.slug;
        const title= movie.title;
        const year= movie.year;

        
        // create movie card dynamically
        const card = document.createElement('div');
        card.classList.add('movie-card');

        card.innerHTML = `
            <h3>${title}</h3>
            <p>(${year})</p>
            <a class="details-link" href="details.php?id=${id}">View Details</a>
        `;

        container.appendChild(card);
    });
}



// button logic for loading more movies
loadMoreBtn.addEventListener('click', () => {

    // shuffle movies so results vary
    const shuffled = allMovies.sort(() => Math.random() - 0.5);

    
    // slice next set of movies
    const newMovies = shuffled.slice(displayedCount, displayedCount + 20);

    
    // render newly loaded movies
    if (newMovies.length > 0) {
        renderMovies(newMovies);
         displayedCount += 20;
    }

    // hide button when limit is reached
    else {
        loadMoreBtn.style.display = 'none';
    }
});

</script>

</body>
</html>
