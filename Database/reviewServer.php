<?php
require_once('path.inc');              
require_once('get_host_info.inc');     
require_once('rabbitMQLib.inc');       
require_once('connection.php'); // db connection



// This function is supposed to handle incoming requests made mq
function requestProcessor($request)
{ global $conn;


 
echo "Received request:\n";   
print_r($request);


// check if request exists
if (!isset($request['type'])) {
return ['returnCode' => 0, 'message' => 'Missing request type'];
    }

switch ($request['type']) {

// adding new reviews to the DB
case 'createReview':

// storing the incoming review data 
$username= $request['username'];
$movie_id= $request['movie_id'];
$movie_title= $request['movie_title'];
$rating= (int)$request['rating'];
$review_text= $request['review_text'];

echo "Creating review for $movie_id by $username\n";

            // insert review into DB
  $stmt = $conn->prepare("
    INSERT INTO reviews (username, movie_id, movie_title, rating, review_text)
    VALUES (?, ?, ?, ?, ?) ");

  $stmt->bind_param("sssis", $username, $movie_id, $movie_title, $rating, $review_text);

            // execution check
  if ($stmt->execute()) {
    return ['returnCode' => 1, 'message' => 'Review saved'];
    }

    // DB message error
    return ['returnCode' => 0, 'message' => $conn->error];

    // retrieving all reviews for a specific movie
    case 'getReviewsByMovie':
     $movie_id = $request['movie_id'];
    echo "Fetching all reviews for movie: $movie_id\n";

    $stmt = $conn->prepare("
    SELECT id, username, movie_id, movie_title, rating, review_text, created_at
      FROM reviews
     WHERE movie_id = ?
     ORDER BY created_at DESC");

    $stmt->bind_param("s", $movie_id);
  $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
            }

  return ['returnCode' => 1, 'data' => $rows];




  // retrieving reviews made by one user — used for My Reviews page
  case 'getReviewsByUser':
    $username = $request['username'];
  echo "Fetching reviews for user: $username\n";

            $stmt = $conn->prepare("
                SELECT id, movie_id, movie_title, rating, review_text, created_at
                FROM reviews
                WHERE username = ?
                ORDER BY created_at DESC
            ");

    $stmt->bind_param("s", $username);
  $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;}

    return ['returnCode' => 1, 'data' => $rows];




  // updating an existing review
  case 'updateReview':

    $id= (int)$request['id'];
  $username = $request['username'];
  $rating = (int)$request['rating'];
  $review_text = $request['review_text'];

echo "Updating review ID $id by $username\n";

    $stmt = $conn->prepare("
    UPDATE reviews
    SET rating = ?, review_text = ?
    WHERE id = ? AND username = ?
    ");

    $stmt->bind_param("isis", $rating, $review_text, $id, $username);

    if ($stmt->execute()) {
        return ['returnCode' => 1, 'message' => 'Review updated'];
            }

    return ['returnCode' => 0, 'message' => $stmt->error];




// deleting a review if user owns it
case 'deleteReview':

  $id= (int)$request['id'];
  $username = $request['username'];
  

echo "Delete request for review: $id by $username\n";

  
  $stmt = $conn->prepare("
    DELETE FROM reviews
    WHERE id = ? AND username = ?
    ");

$stmt->bind_param("is", $id, $username);

      if ($stmt->execute()) {
      return ['returnCode' => 1, 'message' => 'Review deleted'];
    }

    return ['returnCode' => 0, 'message' => $stmt->error];

  



    // default case if request type does not match any valid command
default:
echo "Unknown request type: " . $request['type'] . "\n";
 return ['returnCode' => 0, 'message' => 'Unknown request'];
    }
}


// referencing MQ connection
$server = new rabbitMQServer("dbRabbitMQ.ini", "reviewServer");
echo "Review server START\n";
$server->process_requests('requestProcessor');
?>
