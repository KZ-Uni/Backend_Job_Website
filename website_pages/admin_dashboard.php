<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");

$applications = $conn->query("
    SELECT 
        a.id,
        a.applied_at,
        u.username AS applicant_name,
        u.email AS applicant_email,
        j.title AS job_title,
        j.company AS job_company
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    ORDER BY a.applied_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>

<header>
    <div class="container">
        <h1>Admin Dashboard</h1>
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

        <!-- USERS SECTION -->
        <h2>All Users</h2>
        <?php while($u = $users->fetch_assoc()): ?>
            <div class="job-item">
                <p><strong><?php echo $u['username']; ?></strong> (<?php echo $u['role']; ?>)</p>

                <a href="edit_user_role.php?id=<?php echo $u['id']; ?>">Edit Role</a> | 
                <a href="delete_user.php?id=<?php echo $u['id']; ?>">Delete User</a>
            </div>
        <?php endwhile; ?>


        <!-- JOBS SECTION -->
        <h2 style="margin-top:40px;">All Jobs</h2>
        <?php while($j = $jobs->fetch_assoc()): ?>
            <div class="job-item">
                <h4><?php echo $j['title']; ?></h4>
                <p><strong>Company:</strong> <?php echo $j['company']; ?></p>

                <a href="edit_job.php?id=<?php echo $j['id']; ?>">Edit Job</a> | 
                <a href="delete_job.php?id=<?php echo $j['id']; ?>">Delete Job</a>
            </div>
        <?php endwhile; ?>


        <!-- APPLICATIONS SECTION -->
        <h2 style="margin-top:40px;">All Applications</h2>

        <?php if ($applications && $applications->num_rows > 0): ?>
            <?php while($a = $applications->fetch_assoc()): ?>
                <div class="job-item">
                    <p>
                        <strong>Applicant:</strong>
                        <?php echo htmlspecialchars($a['applicant_name']); ?>
                        (<?php echo htmlspecialchars($a['applicant_email']); ?>)
                    </p>
                    <p>
                        <strong>Job:</strong>
                        <?php echo htmlspecialchars($a['job_title']); ?>
                        at <?php echo htmlspecialchars($a['job_company']); ?>
                    </p>
                    <p>
                        <strong>Applied at:</strong> <?php echo $a['applied_at']; ?>
                    </p>

                    <!-- NEW: Delete Application link added -->
                    <a href="delete_applications.php?id=<?php echo $a['id']; ?>">Delete Application</a>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No applications found.</p>
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
