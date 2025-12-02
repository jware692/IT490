<?php

// libraries for MQ connection
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// checks if user is logged in, otherwise redirects to login page
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];




// handles AJAX request to remove a movie from watchlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['movie_id'])) {

    // always return JSON for AJAX requests
    header('Content-Type: application/json');
    $movie_id = $_POST['movie_id'];

    // connects to MQ to remove movie from watchlist
    $client = new rabbitMQClient("testRabbitMQ.ini", "watchlistServer");

	$res = $client->send_request([
  'type' => 'removeWatchlist', 'username' => $username,'movie_id' => $movie_id]
);


    // respond to frontend with a success or failure
echo json_encode([
	'success' => $res['returnCode'] == 1,'message' => $res['message'] ?? 'Request processed']
);

exit;
}



// fetches the watchlist stored in backend through MQ
$client = new rabbitMQClient("testRabbitMQ.ini", "watchlistServer");
$response = $client->send_request(['type' => 'getWatchlist', 'username' => $username]);


// stores the list of movies returned
$movies = $response['data'] ?? [];

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($username) ?>'s Watchlist</title>

<!-- bootstrap implementation -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">

<style>
  body { background:#f8f9fa; }

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
  border-radius:12px;
transition:.2s;
    	}

.movie-card:hover { transform:scale(1.02); }

.movie-title {
  color:#0b7285;
font-weight:700;
  }
</style>
</head>
<body>

<!-- redirection page to go back to anticipated.php for upcoming movies -->
<div class="container mt-4">
	<a href="anticipated.php" class="btn btn-black">&larr; Back to Upcoming Movies</a>
</div>



<h1 class="text-center text-primary fw-bold mt-3">
	<?= htmlspecialchars($username) ?>'s Watchlist
</h1>


<div class="container mt-4">

<?php if (empty($movies)): ?>

<!-- message shown if watchlist is empty -->
<p class="text-center text-secondary fs-4">No movies in your watchlist yet.</p>

<?php else: ?>


<div class="row g-4" id="movieList">

	<?php foreach ($movies as $movie): ?>

        <div class="col-12 col-sm-6 col-md-4 col-lg-3 movie-card-wrapper" data-id="<?= htmlspecialchars($movie['movie_id']) ?>">
     	<div class="movie-card rounded p-3 shadow-sm text-center">

     	
     	<h4 class="movie-title"><?= htmlspecialchars($movie['title']) ?></h4>

        <!-- release date -->
        <p class="text-muted mb-3">
            	Release Date: <?= htmlspecialchars($movie['release_date'] ?? 'TBA') ?>
                </p>

        <!-- remove button triggers AJAX request -->
        <button class="btn btn-black w-100 remove-btn">
                Remove from Watchlist
        </button>

            </div>
        </div>

        <?php endforeach; ?>
    </div>

<?php endif; ?>

</div>


<script>


// handler for removing movie via AJAX
document.addEventListener('click', e => {

	if (e.target.classList.contains('remove-btn')) {

const btn = e.target;
const card = btn.closest('.movie-card-wrapper');
const movie_id = card.dataset.id;


	// send POST request to remove from watchlist
	fetch("watchlist.php", {
    method: "POST",
headers: { "Content-Type": "application/x-www-form-urlencoded" },
body: new URLSearchParams({ movie_id })
        }
)

.then(res => res.json())
.then(data => {

        	// remove movie from UI if backend removal succeeded
        	if (data.success) {
                card.remove();
            } 

            // display error if MQ fails
else {
      alert("Failed: " + (data.message || "Unknown error"));
   }
        }
)

.catch(err => console.error(err));
    }
	}
);
</script>


<!-- bootstrap javascript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

?>
