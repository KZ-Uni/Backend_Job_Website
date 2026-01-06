<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Jobseeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch applications WITH STATUS + APPLIED DATE
$applications = $conn->prepare("
    SELECT jobs.*, applications.status, applications.applied_at
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    WHERE applications.user_id = ?
    ORDER BY applications.applied_at DESC
");
$applications->bind_param("i", $user_id);
$applications->execute();
$appResults = $applications->get_result();

// Fetch all jobs
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Jobseeker Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .status-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            color: white;
        }

        .Pending { background: gray; }
        .Filtered { background: #6c757d; }
        .Interview { background: #17a2b8; }
        .Accepted { background: green; }
        .Rejected { background: red; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Jobseeker Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">

        <h2>Jobs You Applied To</h2>

        <?php if ($appResults->num_rows > 0): ?>
            <?php while($job = $appResults->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
                    <p><strong>Applied on:</strong> <?php echo htmlspecialchars($job['applied_at']); ?></p>

                    <p>
                        <strong>Status:</strong>
                        <span class="status-badge <?php echo $job['status']; ?>">
                            <?php echo $job['status']; ?>
                        </span>
                    </p>

                    <a href="job_details.php?id=<?php echo $job['id']; ?>">View Job</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't applied to any jobs yet.</p>
        <?php endif; ?>

        <h2 style="margin-top:40px;">Browse All Jobs</h2>

        <?php while($job = $jobs->fetch_assoc()): ?>
            <div class="job-item">
                <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
                <a href="job_details.php?id=<?php echo $job['id']; ?>">View Job</a>
            </div>
        <?php endwhile; ?>

    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
