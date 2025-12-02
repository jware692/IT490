<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('connection.php');   
require_once('mailer.php'); // mailer for email push notifications



// This function handles sending push email notifications when someone replies to a discussion post
function notifyUserOfReply($replyingUser, $message, $parent_id)
{

  
global $conn;  
if (!$parent_id) return;

    // looks up username & email of whom they are replying to
    $q = $conn->prepare("
        SELECT u.email, u.username
        FROM users u
        JOIN discussions d ON u.username = d.username
        WHERE d.id = ?
    ");

    $q->bind_param("i", $parent_id);
    $q->execute();
    $q->bind_result($recipientEmail, $postOwner);

    // if the parent exists, then email notification is sent
    if ($q->fetch()) {

 // format of email push notifs
  $subject = "Someone replied to you";
  $body = "From: {$replyingUser}\nMessage:\n{$message}";

  // validates and sends email if it exists 
  if (!empty($recipientEmail)) {
  sendEmailNotification($recipientEmail, $subject, $body);
  echo " Email sent to $recipientEmail\n"; 
        }
    }

$q->close();
}
// handle incoming requests made by MQ 
function processRequest($request)
{   global $conn;

echo "\n\nrequest handler\n";    
print_r($request);              

    // confirms type request
 if (!isset($request['type'])) {
echo "request type not found\n";
return ['status' => 'error', 'message' => 'Missing request type'];
    }
    switch ($request['type']) {


// adding a new post or replying to another user
case 'add_post':

$username  = $conn->real_escape_string($request['username']);
$message   = $conn->real_escape_string($request['message']);

// parent_id only exists if the message is a reply
$parent_id = (isset($request['parent_id']) && is_numeric($request['parent_id']))
? (int)$request['parent_id']

  : NULL;

echo "Processing post from: $username\n";
echo "Message: $message\n";


// using @username for reply request
if (preg_match('/^@(\w+)/', $message, $matches)) {

$replyToUser = $matches[1];
echo "Detected reply to user: $replyToUser\n";

// lookup their latest post so replies connect properly
$findParent = $conn->prepare("
SELECT id FROM discussions
WHERE username = ?
ORDER BY created_at DESC
LIMIT 1");

$findParent->bind_param("s", $replyToUser);
$findParent->execute();
$findParent->bind_result($foundParentId);

if ($findParent->fetch()) {
$parent_id = $foundParentId;
  echo "Reply linked to post ID: $parent_id\n";
  }

$findParent->close();
}

// insert message into database
$stmt = $conn->prepare("
INSERT INTO discussions (username, message, parent_id)
VALUES (?, ?, ?)
");

$stmt->bind_param("ssi", $username, $message, $parent_id);

if ($stmt->execute()) {

echo "Post successful — ID: " . $stmt->insert_id . "\n";

// send email if this was a reply
  notifyUserOfReply($username, $message, $parent_id);

return [ 'status'  => 'success','message' => 'Post added successfully','post_id' => $stmt->insert_id];
            }

else {
        echo "Post failed: " . $stmt->error . "\n";
        return ['status' => 'error', 'message' => $stmt->error];
            }




        // retrieving all posts
    case 'get_posts':

    $sql = "SELECT * FROM discussions ORDER BY created_at ASC";
    $result = $conn->query($sql);

    $posts = [];
    while ($row = $result->fetch_assoc()) {
    $posts[] = $row;
            }

    echo "Retrieved " . count($posts) . " posts\n";

    return ['status' => 'success', 'posts' => $posts];




        // deleting a post
  case 'delete_post':

  $post_id  = (int)$request['post_id'];
  $username = $conn->real_escape_string($request['username']);

  echo "Delete request for post: $post_id by user: $username\n";

  // verify that logged-in user is actually the owner of the post
  $check = $conn->prepare("SELECT username FROM discussions WHERE id = ?");
  $check->bind_param("i", $post_id);
  $check->execute();
  $check->bind_result($owner);

  // if post exists
  if ($check->fetch()) {

                
  if ($owner === $username) {
  $check->close();

// proceed deleting the post
  $delete = $conn->prepare("DELETE FROM discussions WHERE id = ?");
  $delete->bind_param("i", $post_id);

  if ($delete->execute()) {
  echo "Post deleted successfully\n";
  return ['status' => 'success', 'message' => 'Post deleted'];
  }


    
            else {
                        echo "Deletion failed: " . $delete->error . "\n";
                        return ['status' => 'error', 'message' => $delete->error];
                    }
                }

                // unauthorized attempt
                else {
                    echo "Unauthorized deletion attempt\n";
                    return ['status' => 'error', 'message' => 'Unauthorized deletion'];
                }
            }

            // no post found
            else {
                echo "Post not found\n";
                return ['status' => 'error', 'message' => 'Post does not exist'];
            }

        
        default:
            echo "Unknown request type: " . $request['type'] . "\n";
            return ['status' => 'error', 'message' => 'Unknown request type'];
    }
}






// referencing MQ connection & echo statement when server is workin
$server = new rabbitMQServer("dbRabbitMQ.ini", "discussionServer");
echo "Discussion Server ACTIVE\n";
$server->process_requests('processRequest');

?>
