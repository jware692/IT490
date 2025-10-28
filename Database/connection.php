<?php
//  Connection Settings
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "12345";
$DB_NAME = "authdb";

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

//Check connection
if ($conn->connect_error) 
die("Connection failed: " . $conn->connect_error);
?>
