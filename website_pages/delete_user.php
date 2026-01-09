<?php
session_start();
include('db.php');

// Only Admin can delete users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];

    // Prevent admin from deleting themselves
    if ($user_id !== $_SESSION['user_id']) {
        $conn->query("DELETE FROM users WHERE id = $user_id");
    }
}

header("Location: admin_dashboard.php");
exit();
?>
