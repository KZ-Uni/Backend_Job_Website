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

<header>
    <div class="container">
        <h1>Edit User Role</h1>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>

    <div class="signup-form">
        <h2 style="text-align:center; margin-bottom:20px;">
            Edit Role for <?php echo htmlspecialchars($user['username']); ?>
        </h2>

        <form method="POST">

            <label for="role">Select New Role:</label>
            <select name="role" id="role" required>
                <option value="Admin"     <?= $user['role'] === 'Admin' ? 'selected' : '' ?>>Admin</option>
                <option value="Employer"  <?= $user['role'] === 'Employer' ? 'selected' : '' ?>>Employer</option>
                <option value="Jobseeker" <?= $user['role'] === 'Jobseeker' ? 'selected' : '' ?>>Jobseeker</option>
            </select>

            <button type="submit">Update Role</button>
        </form>

        <a href="admin_dashboard.php" style="display:block; text-align:center; margin-top:15px; color:#007BFF;">
            Back to Dashboard
        </a>
    </div>

</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
