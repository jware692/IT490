<?php

// libraries for MQ connection
require_once('path.inc'); 
require_once('get_host_info.inc'); 
require_once('rabbitMQLib.inc'); 

// sessions so user can only access the the browse feature if they are logged in

session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.html"); 
    exit(); 
}

 
 // requests goes from MQ to DMZMovieServer to request Trending movies for browse feature
function getTrendingMoviesFromDMZ() {
    
    // creates a new MQ client & connects to the DMZ VM
    $client = new rabbitMQClient("movieRabbitMQ.ini", "DMZMovieServer");

    
    // request the specific type of data being pulled and a limit installed of the max amount of movies given back
    // will be able to adjust the limit
    $request = [
        "type" => "trendingMovies",
        "limit" => 1000	];

    
    // request sent to DMZ VM and that response stored
    $response = $client->send_request($request);

    
    
    // movie data returns if the response is valid
    if ($response && isset($response['returnCode']) && $response['returnCode'] === 1) {
        return $response['data']; }

    
    // if response is not valid, return null
    return null; 
    	}


// grabs trending movies from DMZ VM 
$trendingMovies = getTrendingMoviesFromDMZ();

// Shuffles trending movies so same movies don't keep showing
if ($trendingMovies) {
    shuffle($trendingMovies);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse</title>
    <style>
        /* frontend styling */
	body {
	font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #121212;
        color: #fff;
        margin: 0;
        padding: 20px;	}

        h1 { 
            text-align: center; 
            color: #e50914;
            letter-spacing: 1px; 
        }

        /* button styling */
	button {
	padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        border: none;
        background: #e50914;
        color: white;
        cursor: pointer;
        display: block;
        margin: 20px auto;
        }




        /* movie cards held */
        .movies-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px; }

        /* movie card styling */
        .movie-card {
            background: #1e1e1e;
            border-radius: 12px;
            overflow: hidden;
            width: 200px;
            box-shadow: 0 6px 10px rgba(0,0,0,0.6);
            text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }



        /* Movie title */
        .movie-card h3 { 
            margin: 10px 0 5px 0; 
            font-size: 16px; 
            color: #e50914;
        }

        /* Movie yr */
        .movie-card p { 
            margin: 0 0 10px 0; 
            color: #ccc;
            font-size: 14px;
        }

        /* "View Details" button styling */
        .details-link {
            display: inline-block;
            margin-bottom: 12px;
            padding: 6px 12px;
            background-color: #e50914;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        /* hover effect for details */
        .details-link:hover { 
            background-color: #b0060f; 
        }
    </style>
</head>
<body>
<h1>Browse for Movies</h1>

<!-- redirects to search -->
<a href="search.php">
    <button>Search for Movies</button>
</a>

<h2 style="text-align:center;">Trending Movies</h2>

<!-- container to display all movie cards -->
<div class="movies-container" id="moviesContainer">
    <?php
    if ($trendingMovies) {
        // Show first 20 movies
        $initialMovies = array_slice($trendingMovies, 0, 20);

        
        foreach ($initialMovies as $item) {
            if (!isset($item['movie'])) continue;
            $movie = $item['movie'];

            
            // Sanitize and extract movie data
            $id = htmlspecialchars($movie['ids']['slug']);
            $title = htmlspecialchars($movie['title']);
            $year = htmlspecialchars($movie['year']);

           

            // Output movie card HTML
            echo "<div class='movie-card'>";
            echo "<img src='$poster' alt='$title' onerror=\"this.src='https://via.placeholder.com/200x300?text=No+Image'\">";
            echo "<h3>$title</h3>";
            echo "<p>($year)</p>";
            echo "<a class='details-link' href='details.php?id=$id'>View Details</a>";
            echo "</div>";
        }
    } else {
        
        // message if API call fails
        echo "<p style='text-align:center;'>No trending movies found or API failed.</p>";
    }
    ?>
</div>

<!-- "Load More" button for searching more movies through JS -->
<?php if ($trendingMovies): ?>
    <button id="loadMoreBtn">Load More</button>
<?php endif; ?>

<script>
    // Pass PHP movie data to JavaScript
    // pass the movie data from php to javascript
    const allMovies = <?php echo json_encode($trendingMovies); ?>;

    // track the amount of movies displayed at each time
    let displayedCount = 20;

    const container = document.getElementById('moviesContainer');
    const loadMoreBtn = document.getElementById('loadMoreBtn');


    function renderMovies(movies) {
        movies.forEach(item => {
            if (!item.movie) return;
            const movie = item.movie;
            const id = movie.ids.slug;
            const title = movie.title;
            const year = movie.year;
            const poster = movie.ids.tmdb 
                ? `https://image.tmdb.org/t/p/w300/${movie.ids.tmdb}.jpg` 
                : 'https://via.placeholder.com/200x300?text=No+Image';

            
            // creating the movie cards dynamically
            const card = document.createElement('div');
            card.classList.add('movie-card');
            card.innerHTML = `
	<img src="${poster}" alt="${title}" onerror="this.src='https://via.placeholder.com/200x300text=No+Image'">
                <h3>${title}</h3>
                <p>(${year})</p>
                <a class="details-link" href="details.php?id=${id}">View Details</a>
            `;
            container.appendChild(card);
        });
    }

    // logic for load more button and making sure movies are shuffled so the same movie doesn't keep showing up
    loadMoreBtn.addEventListener('click', () => {
        
        const shuffled = allMovies.sort(() => Math.random() - 0.5);

        
        const newMovies = shuffled.slice(displayedCount, displayedCount + 20);

        
        // rendering new movies
        if (newMovies.length > 0) {
            renderMovies(newMovies);
            displayedCount += 20;
        } else {
            // when limit is reached button should not appear
            loadMoreBtn.style.display = 'none';
        }
    });
</script>
</body>
</html>
