<?php
session_start();
include('db.php');

// Only allow Employers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = (int)$_SESSION['user_id'];

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: employer_dashboard.php?error=noid");
    exit();
}

$job_id = (int)$_GET['id'];

// Fetch job details (ensure it belongs to this employer)
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$stmt->bind_param("ii", $job_id, $employer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: employer_dashboard.php?error=notfound");
    exit();
}

$job = $result->fetch_assoc();
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = $_POST['title'];
    $company     = $_POST['company'];
    $location    = $_POST['location'];
    $job_type    = $_POST['job_type'];
    $description = $_POST['description'];

    $update = $conn->prepare("UPDATE jobs SET title=?, company=?, location=?, job_type=?, description=? WHERE id=? AND employer_id=?");
    $update->bind_param("ssssiii", $title, $company, $location, $job_type, $description, $job_id, $employer_id);

    if ($update->execute()) {
        header("Location: employer_dashboard.php?updated=1");
        exit();
    } else {
        $message = "<p style='color:red;'>Error updating job: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Job</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<header>
    <div class="container">
        <h1>Edit Job</h1>
        <nav>
            <ul>
                <li><a href="employer_dashboard.php">Dashboard</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:60%; margin:30px auto;">
        <h2>Edit Job Listing</h2>
        <?php echo $message; ?>
        <form action="edit_job.php?id=<?php echo $job_id; ?>" method="POST" class="signup-form">
            <label for="title">Job Title</label>
            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" required>

            <label for="company">Company</label>
            <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($job['company']); ?>" required>

            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>

            <label for="job_type">Job Type</label>
            <select id="job_type" name="job_type" required>
                <option value="Full-time" <?php if($job['job_type']=="Full-time") echo "selected"; ?>>Full-time</option>
                <option value="Part-time" <?php if($job['job_type']=="Part-time") echo "selected"; ?>>Part-time</option>
                <option value="Internship" <?php if($job['job_type']=="Internship") echo "selected"; ?>>Internship</option>
                <option value="Remote" <?php if($job['job_type']=="Remote") echo "selected"; ?>>Remote</option>
            </select>

            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" cols="47" required><?php echo htmlspecialchars($job['description']); ?></textarea>

            <button type="submit">Update Job</button>
        </form>
    </div>
</main>


<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>
</body>
</html>
