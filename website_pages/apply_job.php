<?php
session_start();
include('db.php');

// Only allow Jobseekers
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'jobseeker') {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id'];

    // Prevent duplicate applications
    $check = $conn->prepare("SELECT id FROM applications WHERE user_id=? AND job_id=?");
    $check->bind_param("ii", $user_id, $job_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: job_details.php?id=$job_id&error=alreadyapplied");
        exit();
    }

    // Insert application
    $stmt = $conn->prepare("INSERT INTO applications (user_id, job_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $job_id);

    if ($stmt->execute()) {
        header("Location: job_details.php?id=$job_id&applied=1");
        exit();
    } else {
        header("Location: job_details.php?id=$job_id&error=applyfail");
        exit();
    }
} else {
    header("Location: index.php?error=badreq");
    exit();
}
?>
