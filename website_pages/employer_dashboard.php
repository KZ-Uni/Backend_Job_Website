<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];
$sql = "SELECT * FROM jobs WHERE employer_id = $employer_id ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="container">
        <h1>Employer Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="add_job.php">Post Job</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">
        <h2>Your Job Listings</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($job = $result->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo $job['title']; ?></h4>
                    <p><strong>Location:</strong> <?php echo $job['location']; ?></p>
                    <p><strong>Type:</strong> <?php echo $job['job_type']; ?></p>

                    <a href="edit_job.php?id=<?php echo $job['id']; ?>">Edit</a> |
                    <a href="delete_job.php?id=<?php echo $job['id']; ?>">Delete</a> |
                    <a href="view_applicants.php?job_id=<?php echo $job['id']; ?>">Applicants</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't posted any jobs yet.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>

