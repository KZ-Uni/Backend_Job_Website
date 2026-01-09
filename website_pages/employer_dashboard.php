<?php
session_start();
include('timeout_check.php');
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
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
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
                <li><a href="profile.php">Profile</a></li>
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


