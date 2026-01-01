<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Jobseeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$applications = $conn->query("
    SELECT jobs.* FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    WHERE applications.user_id = $user_id
");

$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Jobseeker Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="container">
        <h1>Jobseeker Dashboard</h1>
        <nav>
            <ul>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">

        <h2>Jobs You Applied To</h2>
        <?php if ($applications->num_rows > 0): ?>
            <?php while($job = $applications->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo $job['title']; ?></h4>
                    <p><strong>Company:</strong> <?php echo $job['company']; ?></p>
                    <a href="job-details.php?id=<?php echo $job['id']; ?>">View Job</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't applied to any jobs yet.</p>
        <?php endif; ?>

        <h2 style="margin-top:40px;">Browse All Jobs</h2>
        <?php while($job = $jobs->fetch_assoc()): ?>
            <div class="job-item">
                <h4><?php echo $job['title']; ?></h4>
                <p><strong>Company:</strong> <?php echo $job['company']; ?></p>
                <a href="job-details.php?id=<?php echo $job['id']; ?>">View Job</a>
            </div>
        <?php endwhile; ?>

    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
