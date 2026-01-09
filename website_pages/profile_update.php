<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$country_id = intval($_POST['country_id']);
$city_id = intval($_POST['city_id']);
$password = $_POST['password'] ?? '';
$role = $_SESSION['role'];

$skills = ($role === 'Jobseeker' && isset($_POST['skills'])) ? trim($_POST['skills']) : null;

// Check duplicates
$check = $conn->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id != ?");
$check->bind_param("ssi", $username, $email, $user_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header("Location: profile.php?error=duplicate");
    exit();
}

// Update password only if provided
if (!empty($password)) {
    $hashed = password_hash($password, PASSWORD_BCRYPT);
} else {
    $hashed = null;
}

if ($role === 'Jobseeker') {
    if ($hashed) {
        $stmt = $conn->prepare("
            UPDATE users SET username=?, email=?, country_id=?, city_id=?, skills=?, password=? WHERE id=?
        ");
        $stmt->bind_param("ssisssi", $username, $email, $country_id, $city_id, $skills, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users SET username=?, email=?, country_id=?, city_id=?, skills=? WHERE id=?
        ");
        $stmt->bind_param("ssissi", $username, $email, $country_id, $city_id, $skills, $user_id);
    }
} else {
    if ($hashed) {
        $stmt = $conn->prepare("
            UPDATE users SET username=?, email=?, country_id=?, city_id=?, password=? WHERE id=?
        ");
        $stmt->bind_param("ssissi", $username, $email, $country_id, $city_id, $hashed, $user_id);
    } else {
        $stmt = $conn->prepare("
            UPDATE users SET username=?, email=?, country_id=?, city_id=? WHERE id=?
        ");
        $stmt->bind_param("ssisi", $username, $email, $country_id, $city_id, $user_id);
    }
}

$stmt->execute();

// Update session username
$_SESSION['username'] = $username;

header("Location: profile.php?updated=1");
exit();
?>
