<?php
session_start();
include('timeout_check.php');
include('db.php');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST')
{
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Sanitize the input to prevent SQL injection
    $username = $conn->real_escape_string($username);
    $email = $conn->real_escape_string($email);

    // Check if username or email already exists
    $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0)
    {
        $feedback_message = "<p style='color: red;'>Username or email already taken. Please try again with different credentials.</p>";
    }
    else
    {
        // Hash the password securely before storing
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert the new user into the database
        $sql = "INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$hashed_password', '$role')";

        if ($conn->query($sql) === TRUE)
        {
            $feedback_message = "<p style='color: green;'>Registration successful! You can now <a href='login.php'>login</a>.</p>";
        }
        else
        {
            $feedback_message = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Job Portal</h1>
            <nav>
                <ul>
                    <li><a href="./index.php">Home</a></li>
                    <li><a href="./login.php">Sign In</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="signup-form">
            <h2>Sign Up</h2>
            <form action="signup.php" method="POST">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="Jobseeker">Jobseeker</option>
                    <option value="Employer">Employer</option>
                    <option value="Admin">Admin</option>
                </select>

                <button type="submit">Sign Up</button>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; 2025 Job Portal. All rights reserved.</p>
    </footer>
</body>
</html>
