<?php


session_start(); // starting session

// checks if user is actually logged in from their username
if (!isset($_SESSION['username'])) {
    // If not redirect them to another page
    header("Location: index.html");
    exit();  }


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login Successful</title>
    <style>
        /* Page Styling */
        body {
            font-family: Times New Roman, sans-serif;
            background-color: white; 
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; 
        }

        /* success message  */
        .success-container {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2); 
            text-align: center; }

        /* Styling for header*/
        h1 {
            color: #28a745; }

        /* Styling for paragraph */
        p {
            font-size: 24px;
            color: #333; }

        /* standard link styling */
        a {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: #007bff; }

        a:hover {
            text-decoration: underline; }

        /* styling for explore button */
        .explore-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 25px;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            background-color: #e50914; 
            color: white;
            text-decoration: none;
            transition: background 0.3s ease; }

        .explore-btn:hover {
            background-color: #b0060f; }
    </style>
</head>
<body>
    <div class="success-container">
        <h1>Welcome!</h1>
        <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>.</p>
        <!-- Button to explore (Search & then browse) -->
        <a href="search.php" class="explore-btn">Explore</a><br>
        <!-- logout link-->
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
