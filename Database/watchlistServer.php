<?php

require_once('path.inc');              
require_once('get_host_info.inc');     
require_once('rabbitMQLib.inc');       
require_once('connection.php');// db connection file



// This function is supposed to handle incoming requests made by MQ 
function requestProcessor($request)
{ global $conn;

echo "Received Watchlist Request:\n";   // echo for debugging or catching where errors may occur
print_r($request);



// checks if the type request exists
if (!isset($request['type'])) {
  return ['returnCode' => 0, 'message' => 'Invalid request type'];
    }



    switch ($request['type']) {



    // Adding a movie to a user's watchlist
case "addWatchlist":

  // storing incoming movie data from the frontend request
      $username= $request['username'];
       $movie_id= $request['movie_id'];
       $title= $request['title'];
      $release_date= $request['release_date'] ?? null;

  echo "Adding to watchlist for user: $username\n";
  echo "Movie ID: $movie_id — Title: $title\n";

            
  $stmt = $conn->prepare("
  INSERT IGNORE INTO watchlist (username, movie_id, title, release_date)
   VALUES (?, ?, ?, ?)
  ");

 $stmt->bind_param("ssss", $username, $movie_id, $title, $release_date);
    $success = $stmt->execute();

            // return message on insert attempt
    return $success
      ? ['returnCode'=> 1,'message'=> 'Added to watchlist']
      : ['returnCode'=> 0,'message'=> 'Failed to add'];


        // retrieving everything tied to user’s watchlist — used by watchlist.php
    case "getWatchlist":

    $username= $request['username'];

    echo "Fetching watchlist for: $username\n";

    $stmt= $conn->prepare("SELECT * FROM watchlist WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

            // grab all rows and return them
    $data= $result->fetch_all(MYSQLI_ASSOC);

    return ['returnCode'=> 1, 'data'=> $data];




  // delete function for a movie in the watchlist
  case "removeWatchlist":

  $username= $request['username'];
  $movie_id= $request['movie_id'];

  echo "Attempting to remove movie from $username's watchlist\n";
  echo "Movie ID: $movie_id\n";

  if (empty($username) || empty($movie_id)) {
return ['returnCode'=> 0, 'message'=> 'Missing creds'];}

  // delete the exact movie for that user
  $stmt = $conn->prepare("
      DELETE FROM watchlist
        WHERE username = ? AND movie_id = ?
    ");

  $stmt->bind_param("ss", $username, $movie_id);
  $success = $stmt->execute();


if ($success && $stmt->affected_rows > 0) {
  return ['returnCode'=> 1, 'message'=> 'Movie Removed from watchlist'];
    }


return ['returnCode' => 0, 'message' => 'Movie not in Watchlist'];




  // default case if the type provided is not valid
  default:
     echo "Unknown request type: " . $request['type'] . "\n";
    return ['returnCode'=> 0, 'message'=> 'Unknown request type'];
    }
}



// referencing MQ connection
$server = new rabbitMQServer("dbRabbitMQ.ini", "watchlistServer");
echo "Watchlist Server Running\n";
$server->process_requests('requestProcessor');

?>
