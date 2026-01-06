<?php
session_start();
include('timeout_check.php');
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

// Check if user already applied
$alreadyApplied = false;

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'Jobseeker') {
    $user_id = $_SESSION['user_id'];

    $check = $conn->prepare("
        SELECT id FROM applications 
        WHERE user_id = ? AND job_id = ?
    ");
    $check->bind_param("ii", $user_id, $job_id);
    $check->execute();
    $alreadyApplied = $check->get_result()->num_rows > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
        .message-success { color: green; margin-bottom: 15px; }
        .message-error { color: red; margin-bottom: 15px; }
    </style>
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
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

        <!-- Apply button logic -->
        <?php
        $role = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : null;

        if ($role === 'jobseeker'):

            if ($alreadyApplied): ?>
                <p style="color: green; margin-top: 15px;">
                    You have already applied for this job.
                </p>
            <?php else: ?>
                <form action="apply_job.php" method="POST" class="apply-form">
                    <input type="hidden" name="job_id" value="<?php echo (int)$job['id']; ?>">
                    <button type="submit">Apply Now</button>
                </form>
            <?php endif;

        elseif (!isset($_SESSION['role'])): ?>
            <p style="margin-top:10px;">
                <a href="login.php">Log in</a> to apply for this job.
            </p>
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
