<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="main.css">
    <title>Register</title>
</head>
<body>
    <div class="auth-container">
        <h1>Register</h1>
        <form action="registration.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" required />
            </div>
            <div class="form-group">
                <label for="f_name">First Name</label>
                <input type="text" id="f_name" name="f_name" required />
            </div>
            <div class="form-group">
                <label for="l_name">Last Name</label>
                <input type="text" id="l_name" name="l_name" required />
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required />
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required />
            </div>
            <input type="submit" value="REGISTER" class="submit-btn"/>
        </form>
    </div>
<?php
	// Function runs once form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $errors = []; // array stores any errors that may come up
        
        // sanitation to prevent any malicous code or whatnot
        $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
        $f_name = htmlspecialchars(trim($_POST["f_name"]));
        $l_name = htmlspecialchars(trim($_POST["l_name"]));
        $username = htmlspecialchars(trim($_POST["username"]));
        $password = trim($_POST["password"]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format."; // returns when email is invalid 
        }
        if (empty($f_name) || empty($l_name)) {
         $errors[] = "First and last name are required."; // returns if first and/or last name names are not provided
        }
        if (empty($username)) {
            $errors[] = "Username is required."; // returns if username is not entered
        }
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long."; // returns if password is under 6 characters
        }
    }
?>
</body>
</html>
