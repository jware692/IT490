<?php

// MQ connection
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

// session validation
session_start();
if (!isset($_SESSION['username'])) {
	header("Location: index.php");
    	exit();
}

	// readming movie ID from api
	$username = $_SESSION['username'];
	$movie_id = $_GET['movie_id'] ?? '';
	$movie_title = null;


	// supports possible title names
	if (!empty($_GET['movie_title'])) {
	$movie_title = trim($_GET['movie_title']);
	}

elseif (!empty($_GET['title'])) {
$movie_title = trim($_GET['title']);
}

elseif (!empty($_GET['movie_name'])) {
$movie_title = trim($_GET['movie_name']);
}

	// If movie is not in Trakt Database or included in api
	if (!$movie_title) {
	$movie_title = "Unknown Movie";
	}
	

// fucntion to connect to MQ
function mq($req) {
	$client = new rabbitMQClient("testRabbitMQ.ini", "reviewServer");
    	return $client->send_request($req);
}

// handles the add review function
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
mq([
	"type"=>"createReview",
   
        "movie_id"=> $movie_id,
        "movie_title" => $movie_title,
        "username"=> $username,
        "rating"=> (int)$_POST['rating'],
        "review_text"=> $_POST['review_text']
    ]
);

  


header("Location: reviews.php?movie_id=" . urlencode($movie_id) .
	"&movie_title=" . urlencode($movie_title));
    	exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_review') {
mq(

  // handles editing a review
[
    "type"=> "updateReview",
    "id"=> (int)$_POST['review_id'],
    "username"=> $username,
    "rating"=> (int)$_POST['rating'],
    "review_text"=> $_POST['review_text']
    ]
);


	header("Location: reviews.php?movie_id=" . urlencode($movie_id) .
	 "&movie_title=" . urlencode($movie_title));
	exit();
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_review') {
	    mq(

      // handles deleting a review
[
  "type"=> "deleteReview","id"=> (int)$_POST['review_id'],
  "username"=> $username
] );


header("Location: reviews.php?movie_id=" . urlencode($movie_id) .
	"&movie_title=" . urlencode($movie_title));
exit();

}

$resp = mq(

	[
    "type"=> "getReviewsByMovie", "movie_id"=> $movie_id
	]

);

$reviews = ($resp["returnCode"] ?? 0) == 1 ? ($resp["data"] ?? []) : [];

?>
<!DOCTYPE html>
<html>
<head>
<title>Reviews — <?php echo htmlspecialchars($movie_title); ?></title>
	
<!-- bootswatch implementation -->
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
	   margin-bottom:25px;
	    	}
	
.box {
	     background:white;
	     border:1px solid #e0e0e0;
	     padding:15px;
	      border-radius:10px;
	     margin-bottom:20px;
	   }

  
textarea, select {
      width:100%;
      padding:10px;
     border-radius:6px;
      border:1px solid #ccc;
      margin-top:8px;
    	}

  
.btn-black {
      background:#000;
     color:white;
      border:none;
    }

  
   .btn-black:hover {
     background:#222;
    	}

  
   .meta {
    font-size:12px; 
     color:#777;
    }

  
#editModal {
     display:none;
    position:fixed;
     left:0; top:0; right:0; bottom:0;
     background:rgba(0,0,0,0.4);
      padding-top:80px;
    z-index:9999;
    }

  
#editModal .modal-inner {
     background:white;
    max-width:450px;
     margin:auto;
     padding:20px;
        border-radius:10px;
        border:1px solid #ccc;
        box-shadow:0 4px 12px rgba(0,0,0,0.2);
    	}
  
</style>
</head>
<body>
<div class="container-box">
<h1>Reviews for "<?php echo htmlspecialchars($movie_title); ?>"</h1>



<!-- Add Review form -->
<div class="box">
<h4 class="fw-bold">Write a Review</h4>
<form method="post">
        <input type="hidden" name="action" value="add_review">


Rating:
        <select name="rating" required>
        	<option value="">Select…</option>
            <?php for($i=1;$i<=5;$i++): ?>
                <option value="<?php echo $i; ?>"><?php echo $i; ?>/5</option>
            <?php endfor; ?>
        </select>
        <textarea name="review_text" placeholder="Write your review..." required></textarea>
        <button type="submit" class="btn btn-black mt-2">Submit</button>
    </form>
</div>
<hr>



<!-- Reviews -->
<?php if (empty($reviews)): ?>
	<p>No reviews yet.</p>
<?php endif; ?>
	<?php foreach ($reviews as $r): ?>
	    <div class="box">
	        <strong><?php echo htmlspecialchars($r['username']); ?></strong>
	        — <?php echo (int)$r['rating']; ?>/5  
	        <div class="meta"><?php echo htmlspecialchars($r['created_at']); ?></div>
	        <p class="mt-2"><?php echo nl2br(htmlspecialchars($r['review_text'])); ?></p>
	        <?php if ($r['username'] === $username): ?>
	        	<button class="btn btn-black btn-sm"
	          onclick="openEditForm(
	          '<?php echo $r['id']; ?>',
	            '<?php echo $r['rating']; ?>',
	            `<?php echo htmlspecialchars($r['review_text']); ?>`
	          )">
	            Edit
	            </button>
	            <form method="post" style="display:inline;">
	                <input type="hidden" name="action" value="delete_review">
	                <input type="hidden" name="review_id" value="<?php echo $r['id']; ?>">
	                <button class="btn btn-black btn-sm" onclick="return confirm('Delete your review?')">Delete</button>
	            	</form>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>




<!-- Edit reviews -->
<div id="editModal">
<div class="modal-inner">
<h4 class="fw-bold">Edit Review</h4>


<form method="post">
<input type="hidden" name="action" value="edit_review">
<input type="hidden" id="edit_review_id" name="review_id">

            Rating:
            <select id="edit_rating" name="rating" required>
            <?php for($i=1;$i<=5;$i++): ?>
            <option value="<?php echo $i; ?>"><?php echo $i; ?>/5</option>
            <?php endfor; ?>

              
  </select>
  <textarea id="edit_review_text" name="review_text" required></textarea>
  <button type="submit" class="btn btn-black mt-2">Save</button>
  <button type="button" class="btn btn-black mt-2" onclick="closeEditForm()">Cancel</button>
	</form>
	</div>
	</div>


	<script>
	function openEditForm(id, rating, text) {
		document.getElementById('edit_review_id').value = id;
		document.getElementById('edit_rating').value = rating;
		document.getElementById('edit_review_text').value = text;
		document.getElementById('editModal').style.display = 'block';}
	
	
	function closeEditForm() {
	document.getElementById('editModal').style.display = 'none';
	}
	</script>
	</body>
	</html>
