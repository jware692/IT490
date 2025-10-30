#!/usr/bin/php
<?php
// files that might require MQ/ DB connection
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('connection.php');

//  function for processes incoming requests, validation, & log entry
function requestProcessor($request)
{

    // global $conn so the DB connection is easily accessible
    global $conn;

    // Debugging step if required, both message & contents
    echo "Received log request:\n";
    print_r($request);



    // Validating request type is actually "userLog" if not an error message
    if (!isset($request['type']) || $request['type'] != 'userLog')
    {
        return ['returnCode' => 0, 'message' => 'Invalid log request'];
        }

    // Assign values from the request or use defaults if not provided
	$username = $request['username'] ?? 'unknown';
	$action   = $request['action'] ?? 'unspecified';
	$timestamp = $request['timestamp'] ?? date('Y-m-d H:i:s');
	$details  = $request['details'] ?? '';


    // SQL statement to insert into user_logs table
    $stmt = $conn->prepare("INSERT INTO user_logs (username, action, timestamp, details) VALUES (?, ?, ?, ?)");


  	// ouput message of when an account shows up in DB
	return ['returnCode' => 1, 'message' => 'Log entry added'];	
}

	// MQ setup to handle incoming requests made by server
	$server = new rabbitMQServer("dbRabbitMQ.ini", "logServer");


// echo message to determine if the server has started or stopped
echo "Log server started\n";
$server->process_requests('requestProcessor');
echo "Log server stopped.\n";
?>
