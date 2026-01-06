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

// Fetch applicants
$applicants = $conn->prepare("
    SELECT applications.*, users.username, users.email 
    FROM applications
    JOIN users ON applications.user_id = users.id
    WHERE applications.job_id = ?
    ORDER BY applications.applied_at DESC
");
$applicants->bind_param("i", $job_id);
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
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: white;
        }

        .Pending { background: gray; }
        .Filtered { background: #6c757d; }
        .Interview { background: #17a2b8; }
        .Accepted { background: green; }
        .Rejected { background: red; }

        .pipeline-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            margin-right: 5px;
            margin-top: 5px;
        }

        .btn-filter { background: #6c757d; }
        .btn-interview { background: #17a2b8; }
        .btn-accept { background: green; }
        .btn-reject { background: red; }

        .download-btn {
            padding: 8px 12px;
            background: #007BFF;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }

        .download-btn:hover {
            background: #0056b3;
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

        <!-- DOWNLOAD CSV BUTTON -->
        <a href="download_applicants.php?job_id=<?php echo $job_id; ?>" class="download-btn">
            Download Applicants (CSV)
        </a>

        <?php if ($applicantResult->num_rows > 0): ?>
            <?php while ($row = $applicantResult->fetch_assoc()): ?>
                <div class="applicant-box">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                    <p><strong>Applied on:</strong> <?php echo htmlspecialchars($row['applied_at']); ?></p>

                    <p>
                        <strong>Status:</strong>
                        <span class="status-badge <?php echo $row['status']; ?>">
                            <?php echo $row['status']; ?>
                        </span>
                    </p>

                    <!-- PIPELINE BUTTONS -->
                    <div>
                        <form action="update_application_status.php" method="POST" style="display:inline;">
                            <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="status" value="Filtered">
                            <button class="pipeline-btn btn-filter">Filtered</button>
                        </form>

                        <form action="update_application_status.php" method="POST" style="display:inline;">
                            <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="status" value="Interview">
                            <button class="pipeline-btn btn-interview">Interview</button>
                        </form>

                        <form action="update_application_status.php" method="POST" style="display:inline;">
                            <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="status" value="Rejected">
                            <button class="pipeline-btn btn-reject">Reject</button>
                        </form>
                        
                        <form action="update_application_status.php" method="POST" style="display:inline;">
                            <input type="hidden" name="application_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="status" value="Accepted">
                            <button class="pipeline-btn btn-accept">Accept</button>
                        </form>
                    </div>
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

