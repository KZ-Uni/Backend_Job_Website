<?php
session_start();
include('timeout_check.php');
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
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>

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

    
    <div id="timeout-overlay" style="
        display:none;
        position:fixed;
        top:0;
        left:0;
        width:100%;
        height:100%;
        background:rgba(0,0,0,0.5);
        z-index:9998;
    "></div>

    <!-- Timeout Popup -->
    <div id="timeout-popup" style="
        display:none;
        position:fixed;
        top:50%;
        left:50%;
        transform:translate(-50%, -50%);
        background:white;
        padding:25px 30px;
        width:320px;
        border-radius:12px;
        box-shadow:0 8px 25px rgba(0,0,0,0.25);
        z-index:9999;
        text-align:center;
        opacity:0;
        transition:opacity 0.3s ease;
    ">
        <h3 style="margin-top:0; font-size:20px; color:#333;">Session Timeout</h3>
        <p style="font-size:14px; color:#555; margin-bottom:20px;">
            You’ve been inactive for a while.  
            You will be logged out soon.
        </p>

        <button onclick="stayLoggedIn()" style="
            padding:10px 18px;
            background:#007BFF;
            color:white;
            border:none;
            border-radius:6px;
            font-size:14px;
            cursor:pointer;
            width:100%;
        ">Stay Logged In</button>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>
</body>
</html>
