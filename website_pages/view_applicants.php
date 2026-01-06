<?php
session_start();
include('db.php');

// Ensure employer is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];

// Validate job_id
if (!isset($_GET['job_id'])) {
    header("Location: employer_dashboard.php?error=nojobid");
    exit();
}

$job_id = (int)$_GET['job_id'];

// Verify the job belongs to this employer
$checkJob = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND employer_id = ?");
$checkJob->bind_param("ii", $job_id, $employer_id);
$checkJob->execute();
$jobResult = $checkJob->get_result();

if ($jobResult->num_rows === 0) {
    header("Location: employer_dashboard.php?error=unauthorized");
    exit();
}

$job = $jobResult->fetch_assoc();

// Search + Sort
$search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%";
$sort = isset($_GET['sort']) ? $_GET['sort'] : "DESC";

$applicants = $conn->prepare("
    SELECT applications.*, users.username, users.email 
    FROM applications
    JOIN users ON applications.user_id = users.id
    WHERE applications.job_id = ?
    AND (users.username LIKE ? OR users.email LIKE ?)
    ORDER BY applications.applied_at $sort
");
$applicants->bind_param("iss", $job_id, $search, $search);
$applicants->execute();
$applicantResult = $applicants->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Applicants for <?php echo htmlspecialchars($job['title']); ?></title>
    <link rel="stylesheet" href="../css/style.css">

    <style>
        .page-header {
            background: #f2f2f2;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 25px;
        }

        .applicant-box {
            background: #fff;
            border: 1px solid #ddd;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 6px;
            transition: 0.2s;
        }

        .applicant-box:hover {
            background: #f9f9f9;
            border-color: #bbb;
        }

        .search-sort {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .search-sort input {
            width: 60%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .search-sort select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            background: #fafafa;
            border: 1px dashed #ccc;
            border-radius: 6px;
            color: #777;
        }

        .back-link {
            margin-top: 20px;
            display: inline-block;
            color: #007BFF;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<header>
    <div class="container">
        <h1>Applicants</h1>
        <nav>
            <ul>
                <li><a href="employer_dashboard.php">Dashboard</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">

        <div class="page-header">
            <h2><?php echo htmlspecialchars($job['title']); ?></h2>
            <p><strong>Total Applicants:</strong> <?php echo $applicantResult->num_rows; ?></p>
        </div>

        <form method="GET" class="search-sort">
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">

            <input type="text" name="search" placeholder="Search applicants..."
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">

            <select name="sort">
                <option value="DESC" <?php if ($sort === "DESC") echo "selected"; ?>>Newest First</option>
                <option value="ASC" <?php if ($sort === "ASC") echo "selected"; ?>>Oldest First</option>
            </select>

            <button type="submit" style="padding:8px 12px;">Apply</button>
        </form>

        <?php if ($applicantResult->num_rows > 0): ?>
            <?php while ($row = $applicantResult->fetch_assoc()): ?>
                <div class="applicant-box">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Applied on:</strong> <?php echo htmlspecialchars($row['applied_at']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No applicants yet</h3>
                <p>Check back later — applicants will appear here as they apply.</p>
            </div>
        <?php endif; ?>

        <a href="employer_dashboard.php" class="back-link">← Back to Dashboard</a>

    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
