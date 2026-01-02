<?php
session_start();
include('db.php');

// Only allow Employers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $conn->real_escape_string($_POST['title']);
    $company = $conn->real_escape_string($_POST['company']);
    $location = $conn->real_escape_string($_POST['location']);
    $job_type = $conn->real_escape_string($_POST['job_type']);
    $description = $conn->real_escape_string($_POST['description']);

    $sql = "INSERT INTO jobs (employer_id, title, company, location, job_type, description)
            VALUES ('$employer_id', '$title', '$company', '$location', '$job_type', '$description')";

    if ($conn->query($sql) === TRUE) {
        $message = "<p style='color:green;'>Job posted successfully!</p>";
    } else {
        $message = "<p style='color:red;'>Error: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Post New Job</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>Post New Job</h1>
        <nav>
            <ul>
                <li><a href="employer_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="signup-form">
        <h2>New Job Listing</h2>
        <?php echo $message; ?>
        <form action="add_job.php" method="POST">
            <label for="title">Job Title</label>
            <input type="text" id="title" name="title" required>

            <label for="company">Company</label>
            <input type="text" id="company" name="company" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" required>

            <label for="job_type">Job Type</label>
            <select id="job_type" name="job_type" required>
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Internship">Internship</option>
                <option value="Remote">Remote</option>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" cols="47" required></textarea>

            <button type="submit">Post Job</button>
        </form>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>
</body>
</html>
