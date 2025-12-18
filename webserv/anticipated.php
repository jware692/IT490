<?php
session_start();
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');




// Redirect if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
      }



$username = $_SESSION['username'];

		// AJAX handler for when user is adding to watchlist
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {
  			header('Content-Type: application/json');
  				$movie_id = $_POST['movie_id'];
  			$movie_title = $_POST['movie_title'] ?? 'Unknown';
  			$release_date = $_POST['release_date'] ?? null;


  			// sends to watchlistServer MQ connection
  				$client = new rabbitMQClient("testRabbitMQ.ini", "watchlistServer");
      				$res = $client->send_request([
          			'type' => 'addWatchlist',
          			'username' => $username,
            		'movie_id' => $movie_id,
          		'title' => $movie_title,
          			'release_date' => $release_date]
					);


  		//JSON response
			echo json_encode([
				'success' => $res['returnCode'] == 1,'message' => $res['message'] ?? 'Request successful'	]
				);

			exit;
				}

		// Getting Watchlist from Backend and MQ Connection
		$client = new rabbitMQClient("testRabbitMQ.ini", "watchlistServer");
			$response = $client->send_request(['type' => 'getWatchlist', 'username' => $username]);
			$watchlist = array_column($response['data'] ?? [], 'movie_id');

		// Fetch Upcoming Movies from the DMZ VM
		function getUpcomingMoviesFromDMZ() {
			$client = new rabbitMQClient("testRabbitMQ.ini", "DMZMovieServer");
    			$res = $client->send_request(['type' => 'upcomingMovies', 'limit' => 1000]);  //Capped limit of the movies being pulled
    	return $res['data'] ?? [];
		}	

	$upcomingMovies = getUpcomingMoviesFromDMZ();
		if ($upcomingMovies)
			shuffle($upcomingMovies);

  // OVerall styling for the page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Upcoming Movies</title>

<!-- Bootstrap implementation -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">

<style>
    body { background-color:#f8f9fa; }
    .btn-black {
        background-color:#000;
        color:white;
        border:none;
    }
	.btn-black:hover {
        background-color:#222;
        color:white;
    }

.movie-card {
        background:white;
        border:1px solid #e0e0e0;
        border-radius: 12px;
        transition: .2s;
    	}
    .movie-card:hover { transform: scale(1.02); }

.movie-title {
        color:#0b7285;
        font-weight:700; }

  #searchBar {
        background:#ffffff;
        color:#333;
        border:1px solid #cccccc;
    }
</style>

</head>
<body>

<!-- Back button -->
<div class="container mt-4">
    <a href="search.php" class="btn btn-black">&larr; Back</a>
</div>

<!--confirmed upcoming movies -->
<h1 class="text-center text-primary fw-bold mt-4">Upcoming Confirmed Movies</h1>
<!-- search bar -->
<div class="container text-center mt-3">
    <input 
        type="text" 
        id="searchBar" 
        class="form-control mx-auto w-50"
        placeholder="Search upcoming movies...">
</div>

<!-- movie grid -->
<div class="container mt-4">
    <div class="row g-4" id="moviesContainer"></div>
</div>

<!-- load more -->
<div class="text-center my-4">
<?php if ($upcomingMovies): ?>
<button id="loadMoreBtn" class="btn btn-black btn-lg d-none">Load More</button>
<?php endif; ?>
</div>
<script>
const allMovies= <?php echo json_encode($upcomingMovies); ?>;
let watchlist= <?php echo json_encode($watchlist); ?>;
const today= "<?= date('Y-m-d') ?>";


let displayedCount =0;
const loadCount =20;
const grid= document.getElementById('moviesContainer');
const loadMoreBtn= document.getElementById('loadMoreBtn');
const searchBar= document.getElementById('searchBar');

// Render Movies
function renderMovies(movies) {
	movies.forEach(item =>{
        if (!item.movie) return;
        	const movie=item.movie;
        	const mid=movie.ids.trakt;
        	const title= movie.title || 'Unknown';
        	const rd =movie.released || 'TBA';
        	const isReleased= (rd !== 'TBA' && rd <= today);
        	const inWatchlist= watchlist.includes(mid);
        const col =document.createElement('div');
        col.className= "col-12 col-sm-6 col-md-4 col-lg-3";
        col.innerHTML= `
		<div class="movie-card p-3 shadow-sm text-center">
                
				
				${
		isReleased
            ? `<button class="btn btn-secondary w-100 mb-2" disabled>Released</button>`
             : inWatchlist
            ? `<button class="btn btn-secondary w-100 mb-2" disabled>In Watchlist</button>`
                : `<button 
                    class="btn btn-black w-100 mb-2 add-watchlist-btn"
                    data-movieid="${mid}"
                        data-title="${title}"
                    data-release="${rd}"
                        >Add to Watchlist</button>`
                }

                <h4 class="movie-title mt-2">${title}</h4>
                <p class="text-muted mb-3">Release: ${rd}</p>
                <a href="details.php?id=${mid}" class="btn btn-black w-100">View Details</a>
            </div>`;
        grid.appendChild(col);
    }
				  
				  );
}

	// Initial Load
		renderMovies(allMovies.slice(displayedCount, displayedCount + loadCount));
			displayedCount+=loadCount;

	// Load More Button
		loadMoreBtn.addEventListener('click',() => {
    	const next = allMovies.slice(displayedCount, displayedCount + loadCount);
    		renderMovies(next);
    			displayedCount += next.length;



			if (displayedCount >= allMovies.length) {
        			loadMoreBtn.classList.add("d-none");
    }
}
);

	// Scroll to show loading feature
		window.addEventListener('scroll', ()=> {
			if (window.scrollY+window.innerHeight>= document.body.offsetHeight-100 &&
        	displayedCount<allMovies.length) {
        loadMoreBtn.classList.remove("d-none");
    	}
	}
);

// Search filtering
searchBar.addEventListener('input', () => {
    const q =searchBar.value.toLowerCase();
    const filtered =allMovies.filter(m => m.movie.title.toLowerCase().includes(q));

    	grid.innerHTML ="";
    		displayedCount =0;

    		renderMovies(filtered.slice(0, loadCount));
    			displayedCount = loadCount;

    			loadMoreBtn.classList.toggle("d-none", filtered.length <= loadCount);
		}
	);

// Adding to watchlist feature with AJAx
document.addEventListener('click',e =>{
	if (e.target.classList.contains("add-watchlist-btn")) {
        const btn=e.target;
        	const movie_id=btn.dataset.movieid;
        	const title=btn.dataset.title;
        	const release=btn.dataset.release;

// // Sends a POST request when movie is added
fetch("anticipated.php", {
	method: "POST",
      	headers: {"Content-Type": "application/x-www-form-urlencoded"},
   	body: new URLSearchParams({
        	movie_id,
                movie_title: title,
                release_date: release
		} 
							 
							 )
  
} 
	 
	 )

	// convert to JSON response
.then(res => res.json())
.then(data => {
	if (data.success) {
      	btn.disabled = true;
     	btn.classList.remove("btn-black");
       	btn.classList.add("btn-secondary");
      	btn.textContent = "In Watchlist";
     	watchlist.push(movie_id);
       	}
 

  
      else {
                alert("Error: " + data.message);
            }
        }
	 
	 )

	// catch/handle network errors
        .catch(() => alert("Network error"));
    		}
	}
);
</script>
<!-- Bootstrap js-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
