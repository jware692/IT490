<?php

require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


// sessions to make sure the user is logged in before accessing 
session_start();
if (!isset($_SESSION['username'])) {
	header("Location: index.php");
    	exit();
}

$username = $_SESSION['username'];


// MQ helper function to send requests to reviewServer
function mq($req) {
	$client = new rabbitMQClient("testRabbitMQ.ini", "reviewServer");
    	return $client->send_request($req);
}


// converts movie_id to title
function formatMovieTitleFromID($movie_id) {
	$parts = explode('-', $movie_id);
    	$year = array_pop($parts);
    	$title = implode(' ', $parts);
    	$title = ucwords($title);
    	return $title . " (" . $year . ")";
}


// grabs personalized reviews
$resp = mq([
"type" => "getReviewsByUser", "username" => $username]);


// funciton to store successful reviews
$reviews = ($resp["returnCode"] ?? 0) == 1 ? ($resp["data"] ?? []) : [];
// pagination 
$per_page = 5;
$total_reviews = count($reviews);
$total_pages = max(1, ceil($total_reviews / $per_page));


// determine which page user is currently on
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $total_pages) $page = $total_pages;



$start = ($page - 1) * $per_page;
$reviews_on_page = array_slice($reviews, $start, $per_page);
?>

<!DOCTYPE html>
<html>
<head>
<title>My Reviews</title>

<!-- Bootstrap implementation -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">
<style>
  
	body {
  background:#f8f9fa;
 padding:20px;
  color:#333;
    	}

  .container-box {
  max-width:700px;
  margin:auto;
  background:white;
  padding:25px;
  border-radius:12px;
  border:1px solid #ddd;
  box-shadow:0 4px 10px rgba(0,0,0,0.1);
  
      }

  h1 {
  text-align:center;
  font-size:26px;
  font-weight:700;
  color:#0b7285;
    }

    
.btn-black {
  background:#000;
  color:#fff;
border:none;
    	}

.btn-black:hover {
  background:#222
    	}

  .box {
        background:white;
        border:1px solid #e0e0e0;
        padding:15px;
        border-radius:10px;
        margin-bottom:20px;
    	}

.rating {
    font-weight:bold;
    font-size:16px;
    color:#0b7285;
    }

a {
  color:#0b7285;
  font-weight:bold;
   text-decoration:none;
    	}

    a:hover {
        text-decoration:underline;
    	}

  .meta {
    font-size:12px;
    color:#777;
      argin-bottom:10px;
    	}

    
.pagination {
  text-align:center;
  margin-top:25px;
    	}

    .pagination a,
    .pagination span {
    	display:inline-block;
     	padding:8px 12px;
        margin:2px;
        border-radius:6px;
        border:1px solid #ccc;
        background:white;
        color:#333;
        text-decoration:none;
    	}

  .pagination .active {
        background:#000;
        color:white;
        border-color:#000;
        font-weight:bold;
    }

  .pagination .disabled {
    opacity:0.5;
    pointer-events:none;
    }
</style>
</head>
<body>
<div class="container-box">

<!-- back button to return to search page -->
<a href="search.php" class="btn btn-black mb-3">← Back to Search</a>


<h1>My Reviews</h1>

<!-- message if user has no reviews -->
<?php if (empty($reviews)): ?>
<p>You haven't written any reviews yet.</p>
<?php endif; ?>


<!-- loop through the paginated reviews -->
<?php foreach ($reviews_on_page as $r): ?>

<?php
  
// converts movie ID to movie title
$cleanTitle = formatMovieTitleFromID($r['movie_id']);
?>
	<div class="box">
    	<div class="rating">
    		Rating: <?php echo (int)$r['rating']; ?>/5 for 


            	<a href="reviews.php?movie_id=<?php echo urlencode($r['movie_id']); ?>
                &movie_title=<?php echo urlencode($cleanTitle); ?>">
                "<?php echo htmlspecialchars($cleanTitle); ?>"
            </a>
        	</div>

        	<!-- timestamp  -->
    <div class="meta"><?php echo htmlspecialchars($r['created_at']); ?></div>

        	
  <p><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></p>
    	</div>

<?php endforeach; ?>


<!-- pagination controls -->
<?php if ($total_pages > 1): ?>
<div class="pagination">

<!-- Previous button -->
<?php if ($page > 1): ?>
<a class="btn-black" href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
<?php else: ?>
   <span class="disabled btn-black">&laquo; Previous</span>
<?php endif; ?>


<?php for ($i = 1; $i <= $total_pages; $i++): ?>
<?php if ($i == $page): ?>
  <span class="active"><?php echo $i; ?></span>
<?php else: ?>
  <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
<?php endif; ?>
  <?php endfor; ?>

<!-- Next button -->
<?php if ($page < $total_pages): ?>
   <a class="btn-black" href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
<?php else: ?>
   <span class="disabled btn-black">Next &raquo;</span>
<?php endif; ?>

</div>
<?php endif; ?>

</div>
</body>
</html>
