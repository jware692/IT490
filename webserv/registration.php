<?php
echo "registering..";  // debug step to see where code might break


// required files for MQ client
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');


// new MQ client that connects to registerServer in ini file
$client = new rabbitMQClient("testRabbitMQ.ini", "registerServer");


if (isset($argv[1])){
  $msg = $argv[1];
} 

else {
  $msg = "registration request sent";
}


// request array to send registration data to backend
$request = array();

// Define the request type so the server knows this is a registration operation
$request['type'] = "register";


// user input collection for registration form
$request['email'] = $_POST['email'];
$request['f_name'] = $_POST['f_name'];
$request['l_name'] = $_POST['l_name'];
$request['username'] = $_POST['username'];
$request['password'] = $_POST['password']; 



$request['message'] = $msg;


// registration request to MQ server, then wait for the response
$response = $client->send_request($request);




// function for successful registration
if ($response['returnCode'] == 1) {
  // Redirects to login page if registration is successful 
  header("Location: index.php");
}

// function if registration fails
else if ($response['returnCode'] == 0) {
  
  echo $response;
  // redirects to register page if registration fails
  header("Location: register.php");
}

// Print response details to the browser/terminal for debugging
echo "client received response: " . PHP_EOL;
print_r($response);
echo "\n\n";


echo $argv[0] . " END" . PHP_EOL;
?>
