<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_POST['job_id'])) {
    header("Location: index.php");
    exit();
}

$job_id = (int)$_POST['job_id'];
$user_id = $_SESSION['user_id'] ?? null; // null if guest

// Try inserting the report
$stmt = $conn->prepare("
    INSERT INTO job_reports (job_id, user_id, report_date)
    VALUES (?, ?, NOW())
");
$stmt->bind_param("ii", $job_id, $user_id);

if ($stmt->execute()) {
    // Success
    header("Location: job_details.php?id=$job_id&reported=1");
} else {
    // Duplicate report or error
    header("Location: job_details.php?id=$job_id&already_reported=1");
}

exit();
?>