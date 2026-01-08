<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

// Update username + email
if (!empty($password)) {
    // Update with password
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, password=? WHERE id=?");
    $stmt->bind_param("sssi", $username, $email, $hashed, $user_id);
} else {
    // Update without password
    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $user_id);
}

$stmt->execute();

// Update session username
$_SESSION['username'] = $username;

header("Location: profile.php?updated=1");
exit();
?>
