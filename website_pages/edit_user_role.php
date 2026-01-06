<?php
session_start();
include('timeout_check.php');
include('db.php');

// Only Admin can edit user roles
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Make sure an ID was passed
if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Prevent admin from editing their own role
if ($user_id == $_SESSION['user_id']) {
    header("Location: admin_dashboard.php");
    exit();
}

// Fetch user info
$result = $conn->query("SELECT * FROM users WHERE id = $user_id");
if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_role = $_POST['role'];

    // Update role
    $conn->query("UPDATE users SET role = '$new_role' WHERE id = $user_id");

    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User Role</title>
    <link rel="stylesheet" href="../css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>

    <h2>Edit Role for <?php echo htmlspecialchars($user['username']); ?></h2>

    <form method="POST">
        <label for="role">Select New Role:</label>
        <select name="role" id="role">
            <option value="Admin" <?php if ($user['role'] === 'Admin') echo 'selected'; ?>>Admin</option>
            <option value="Employer" <?php if ($user['role'] === 'Employer') echo 'selected'; ?>>Employer</option>
            <option value="Jobseeker" <?php if ($user['role'] === 'Jobseeker') echo 'selected'; ?>>Jobseeker</option>
        </select>

        <br><br>
        <button type="submit">Update Role</button>
    </form>

    <br>
    <a href="admin_dashboard.php">Back to Dashboard</a>

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

</body>
</html>
