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

// Only jobseekers use the new skill system
$skill_ids = isset($_POST['skill_ids']) ? explode(",", $_POST['skill_ids']) : [];

// Remove empty values
$skill_ids = array_filter($skill_ids, function($v) { return $v !== ""; });

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

/* ---------------------------------------------------------
   UPDATE USER PROFILE
--------------------------------------------------------- */

if ($hashed) {
    $stmt = $conn->prepare("
        UPDATE users 
        SET username=?, email=?, country_id=?, city_id=?, password=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssissi", $username, $email, $country_id, $city_id, $hashed, $user_id);
} else {
    $stmt = $conn->prepare("
        UPDATE users 
        SET username=?, email=?, country_id=?, city_id=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssisi", $username, $email, $country_id, $city_id, $user_id);
}

$stmt->execute();

/* ---------------------------------------------------------
   UPDATE SKILLS (ONLY FOR JOBSEEKERS)
--------------------------------------------------------- */

if ($role === "Jobseeker") {

    // Clear old skills
    $conn->query("DELETE FROM user_skills WHERE user_id = $user_id");

    // Insert new skills
    foreach ($skill_ids as $sid) {
        $sid = intval($sid);
        if ($sid > 0) {
            $conn->query("
                INSERT INTO user_skills (user_id, skill_id) 
                VALUES ($user_id, $sid)
            ");
        }
    }
}

/* ---------------------------------------------------------
   UPDATE SESSION
--------------------------------------------------------- */

$_SESSION['username'] = $username;

/* ---------------------------------------------------------
   REDIRECT BACK
--------------------------------------------------------- */

header("Location: profile.php?updated=1");
exit();
?>
