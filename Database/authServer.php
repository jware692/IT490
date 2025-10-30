#!/usr/bin/php
<?php
// files that might require MQ/ DB connection
require_once('path.inc');              
require_once('get_host_info.inc');     
require_once('rabbitMQLib.inc');       
require_once('connection.php');        


// handles incoming requests for both login & registration
function requestProcessor($request)
{
    

   
    // Debugging step where the request data would get printed
    	echo "Received request:\n";
    	print_r($request);

    
    // checks request to see if it contains a type parameter & if not returns an error message
    	if (!isset($request['type'])) {
        return ['returnCode' => 0, 'message' => 'Invalid request type'];
    }

    
    // requests processes depending on type field
    switch ($request['type']) {
        case "login": // case handles the login requests
            
            
            $username = $request['username']; // Get the username from the request
            $password = $request['password']; // Get the password from the request

            
            // sql query to fetch hashed passwords 
            $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);  // binding username to query
            $stmt->execute();  // Executes the query
            $stmt->store_result();  // Stores results

            
            // error message if not user is found
            if ($stmt->num_rows === 0) {
                $stmt->close();
                return ['returnCode' => 0, 'message' => 'User not found'];
            }

           
         
            // grab password hashes that are stored in the DB
            $stmt->bind_result($dbpass);
            $stmt->fetch();
            $stmt->close();

            // Verifying if password matches with password stored in DB
            if (password_verify($password, $dbpass)) {
                return ['returnCode' => 1, 'message' => 'Login success'];  // returns when login is successful
            } else {
                return ['returnCode' => 0, 'message' => 'Incorrect password'];  // returns when password is incorrect
            }

        // case handles registration
		case "register": 
        
        
            $email = $request['email'];      
            $f_name = $request['f_name'];    
            $l_name = $request['l_name'];    
            $username = $request['username'];  
            $password = $request['password'];  

            
            // checks to see if username exists within DB
            $stmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);  
            $stmt->execute();  
            $stmt->store_result();  

            
            // Error message displayed if the username exists 
            if ($stmt->num_rows > 0) {
                $stmt->close();
                return ['returnCode' => 0, 'message' => 'Username already exists'];
            }
            $stmt->close(); 

            
            // inserts new user to DB if username is not present in DB already
            $stmt = $conn->prepare("INSERT INTO users (email, first_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);  // Hashing the password
            $stmt->bind_param("sssss", $email, $f_name, $l_name, $username, $hashed_password);  

           
// executing the inserted query & return a success or failure message depending on the success or failure of the function
        if ($stmt->execute()) {
        $stmt->close();
	return ['returnCode' => 1, 'message' => 'Registration successful'];  } 
           else {
        	$stmt->close();
        return ['returnCode' => 0, 'message' => 'DB insert failed: ' . $stmt->error];  } 
            

        default: // if unknown requests happens
            return ['returnCode' => 0, 'message' => 'Unknown request type'];
    }
}



// Setting up MQ servers for both login & register queues
	$loginServer = new rabbitMQServer("dbRabbitMQ.ini", "loginServer");
	$registerServer = new rabbitMQServer("dbRabbitMQ.ini", "registerServer");

// Output message showing that the server is listening on both queues
echo "Auth consumer listening on loginQueue & registerQueue...\n";


// Function to process the request from either one of the queues
function processBothQueues($request) {
    return requestProcessor($request);  
    
}

// loop listens to both login & register queues
while (true) {
    
    // Process the requests for login & register queues
	$loginServer->process_requests('processBothQueues');
    	$registerServer->process_requests('processBothQueues');
}
?>
