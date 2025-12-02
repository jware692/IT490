<?php

// libraries for MQ connection
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');



//Session Validation to check if user is logged in before allowing access to the discussion board

session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}


$username = $_SESSION['username'];



// function used to send different MQ requests to the discussionServer
function sendRequest($type, $data = []) {
$client = new rabbitMQClient("testRabbitMQ.ini", "discussionServer");
return $client->send_request(array_merge(['type' => $type], $data));
}



// handles posting a new message or a reply message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'post') {

	// pulls user message and optional parent ID if it's a reply
$message = trim($_POST['message']);
$parent_id = $_POST['parent_id'] ?? null;


    // checks if a message was written before sending MQ request
    if (!empty($message)) {

	// request goes to MQ discussionServer to add the new post
	sendRequest('add_post', [
  'username'=> $username,
  'message'=> $message,
  'parent_id'=> $parent_id]
	
	);

  // reloads page to refresh the discussion list
header("Location: discussion.php");
exit();
    }
}



// handles deleting a post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

	// request sent to MQ to delete the selected post
	sendRequest('delete_post', [
  'username' => $username,
  'post_id' => $_POST['post_id']
    
]
    
);

// refresh page after deletion
header("Location: discussion.php");
exit();
}



// pulls all posts from MQ discussionServer
$response = sendRequest('get_posts');

// stores posts if they return successfully
$posts = ($response && $response['status'] === 'success') ? $response['posts'] : [];



// builds threaded tree structure so replies attach under their parent posts
function buildTree($posts) {
$tree = [];

$refs = [];

    // set up reference map for each post
    foreach ($posts as &$post) {
        $post['children'] = [];
        $refs[$post['id']] = &$post;
    }

    // attach posts as either root posts or reply children
    foreach ($posts as &$post) {
        if ($post['parent_id']) 
        {
            $refs[$post['parent_id']]['children'][] = &$post;
	} 
  
        else 
	{
  $tree[] = &$post;

  }
      
    }
    return $tree;
}

// final threaded version of posts for nested display
$threadedPosts = buildTree($posts);


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Movie Discussions</title>

<!-- bootstrap implementation -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css">

<style>
	body {
        background:#f8f9fa;
        color:#333;
	}

    
    .btn-black {
        background:#000;
        color:#fff;
        border:none;
    }
  .btn-black:hover {
        background:#222;
    }

    
.card-clean {
  background:white;
 border:1px solid #e0e0e0;
  border-radius:12px;
  padding:20px;
  }

.reply-indent { margin-left:40px; }
    textarea {
  background:white;
  color:#333;
  border:1px solid #ccc;
    }

    .navbar-brand {
        font-weight:700;
        font-size:1.5rem;
    }

</style>
</head>
<body>

<!-- navbar for navigation through site -->
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
<div class="container-fluid">
<a class="navbar-brand text-primary" href="#">MovieHub</a>

  
<button class="navbar-toggler btn-black" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navMenu">
<ul class="navbar-nav me-auto">

  <!-- main navbar showin through different deliverables -->
  <li class="nav-item"><a class="nav-link" href="anticipated.php">Upcoming</a></li>
<li class="nav-item"><a class="nav-link" href="watchlist.php">Watchlist</a></li>
<li class="nav-item"><a class="nav-link active fw-bold" href="discussion.php">Discussion Board</a></li>
<li class="nav-item"><a class="nav-link" href="browse.php">Browse</a></li>
<li class="nav-item"><a class="nav-link" href="my_reviews.php">My Reviews</a></li>
</ul>

<!-- logout button inside navbar -->
<form class="ms-3" action="logout.php" method="POST">
	<button class="btn btn-black" type="submit">Logout</button>
</form>
</div>
</div>
</nav>



  
<div class="container text-center mt-4">
<h1 class="text-primary fw-bold">Movie Discussions</h1>
</div>


<div class="container mt-4" style="max-width: 900px;">

    
<!-- user posting box for new messages -->
<div class="card-clean mb-4">
<h4 class="fw-bold">Post a Message</h4>

<form method="POST" action="discussion.php">
<p><strong>Logged in as:</strong> <?= htmlspecialchars($username); ?></p>
<textarea id="messageBox" name="message" class="form-control" placeholder="Write your message..." required></textarea>

<input type="hidden" name="action" value="post">
<input type="hidden" id="parent_id" name="parent_id">

<button type="submit" class="btn btn-black mt-3">Post</button>
</form>
</div>



<hr>
<h3 class="fw-bold mb-3 text-primary">All Posts</h3>



<?php

// function to display replies and posts
function renderPosts($posts, $isReply, $username) {

  foreach ($posts as $post) {

    
    echo '<div class="card-clean mb-3 '.($isReply ? "reply-indent" : "").'">';

            // username and timestamp showing when post is posted
    echo '<div class="d-flex justify-content-between">';
    echo '<span class="fw-bold text-primary">'.htmlspecialchars($post['username']).'</span>';
    echo '<span class="text-muted" style="font-size:0.8rem;">'.htmlspecialchars($post['created_at']).'</span>';
    echo '</div>';

            // Content of message
            echo '<p class="mt-2">'.nl2br(htmlspecialchars($post['message'])).'</p>';

            // reply & delete options
            echo '<div class="mt-2 d-flex">';
            echo '<button type="button" class="btn btn-black btn-sm me-2" onclick="replyTo(\''.htmlspecialchars($post['username']).'\','.$post['id'].')">Reply</button>';



	// delete option only shown if post belongs to logged-in user
	if ($post['username'] === $username) {
    echo '<form method="POST" action="discussion.php" style="display:inline-block;">';
    echo '<input type="hidden" name="action" value="delete">';
    echo '<input type="hidden" name="post_id" value="'.$post['id'].'">';
    echo '<button class="btn btn-black btn-sm" onclick="return confirm(\'Delete this post?\')">Delete</button>';
    echo '</form>';
            }

            echo '</div>';

            
            if (!empty($post['children'])) {
                renderPosts($post['children'], true, $username);
            }

            echo '</div>';
        }
}



// print all threaded posts or message when empty
if (!empty($threadedPosts)) {
    renderPosts($threadedPosts, false, $username);
} 
else {
    echo "<p class='text-muted'>No discussions yet. Be the first to post!</p>";
}

?>

</div>


<script>
// attaches @username for the reply message
function replyTo(username, postId) {
	document.getElementById('messageBox').value = '@' + username + ' ';
    	document.getElementById('parent_id').value = postId;
}
</script>

<!-- bootstrap implementation JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


