<?php
session_start();
include('db.php');

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php?error=noid");
    exit();
}

$job_id = (int)$_GET['id'];

// Fetch job details
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php?error=notfound");
    exit();
}

$job = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Inline CSS just for job_details page */

        .apply-form {
            margin-top: 20px;
        }

        .apply-form button {
            width: 100%;
            padding: 10px;
            background-color: #333;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }

        .apply-form button:hover {
            background-color: #555;
        }

        /* Success/error messages */
        .message-success {
            color: green;
            margin-bottom: 15px;
        }

        .message-error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Job Details</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if (isset($_SESSION['username'])): ?>
                    <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:70%; margin:30px auto;">
        <!-- Success / error messages -->
        <?php if (isset($_GET['applied'])): ?>
            <p class="message-success">Application submitted successfully!</p>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'alreadyapplied'): ?>
            <p class="message-error">You have already applied for this job.</p>
        <?php elseif (isset($_GET['error']) && $_GET['error'] === 'applyfail'): ?>
            <p class="message-error">Error submitting application. Please try again.</p>
        <?php endif; ?>

        <div class="job-item">
            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
            <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>
            <p><strong>Posted on:</strong> <?php echo htmlspecialchars($job['created_at']); ?></p>
            <hr>
            <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        </div>

        <!-- Apply button for Jobseekers -->
        <?php
        $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;
        if ($role === 'jobseeker'): ?>
            <form action="apply_job.php" method="POST" class="apply-form">
                <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">
                <button type="submit">Apply Now</button>
            </form>
        <?php elseif (!isset($_SESSION['role'])): ?>
            <p style="margin-top:10px;">
                <a href="login.php">Log in</a> to apply for this job.
            </p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>

