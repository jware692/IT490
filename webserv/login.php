<?php

// libraries for MQ connection
require_once('path.inc');          
require_once('get_host_info.inc'); 
require_once('rabbitMQLib.inc');    

session_start(); // Session started to handle user & response data


// used to confirm MQ client connection to server
$client = new rabbitMQClient("testRabbitMQ.ini", "loginServer");



// collect username & password from POST request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';


// timestamp to track if needed
$d = time();


// request array sent to MQ server
$request = array(); 

// login request data that needs to be entered by user
$request['type'] = "login";          
$request['username'] = $username;    
$request['password'] = $password;    
$request['session'] = $d;            
$request['message'] = "login request sent"; 


//  request sent to MQ server to store responses
$response = $client->send_request($request);


// Debugging step to store raw responses if needed
$_SESSION['login_response'] = $response;


// proccessing the response from server
if ($response['returnCode'] == 1) {
    // Successful login
    $_SESSION['username'] = $username; // username stored in session

    
    // successful login logged into the logServer file
    $logClient = new rabbitMQClient("testRabbitMQ.ini", "logServer"); 
    $logRequest = [
        'type' => 'userLog',                   
        'username' => $username,               
        'action' => 'login',                   
        'timestamp' => date('Y-m-d H:i:s'),   
        'details' => 'User successfully logged in.' ];
        
    $logClient->send_request($logRequest); 

    
    // Once login successful redirect to a landing page
    $redirect = "landing.php";
    	} else 
    		{
    // Function if login fails
    $_SESSION['error'] = "Invalid username or password."; // error message if password or username don't match
    $redirect = "index.html"; // redirects to an error page if login fails
}


echo "<pre>";
print_r($response);
echo "</pre>";
echo "<meta http-equiv='refresh' content='3;url=$redirect'>";
exit(); 
?>
