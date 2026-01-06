<?php
session_start();
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

</body>
</html>
