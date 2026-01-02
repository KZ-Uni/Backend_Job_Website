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
    <style>
        /* Make the delete control look like a link */
        .link-button {
            background: none;
            border: none;
            color: #007BFF;
            padding: 0;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
        }
        .link-button:hover { text-decoration: underline; }
        .inline-form { display: inline; }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Employer Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <li><a href="add_job.php">Post Job</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">
        <h2>Your Job Listings</h2>

        <?php if (isset($_GET['deleted'])): ?>
            <p style="color: green;">Job deleted successfully!</p>
        <?php elseif (isset($_GET['error'])): ?>
            <p style="color: red;">Could not delete the job. Error code: <?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($job = $result->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
                    <p><strong>Type:</strong> <?php echo htmlspecialchars($job['job_type']); ?></p>

                    <a href="edit_job.php?id=<?php echo $job['id']; ?>">Edit</a>
                    |
                    <form action="delete_job.php" method="POST" class="inline-form" onsubmit="return confirm('Delete this job?');">
                        <input type="hidden" name="id" value="<?php echo (int)$job['id']; ?>">
                        <button type="submit" class="link-button">Delete</button>
                    </form>
                    |
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
