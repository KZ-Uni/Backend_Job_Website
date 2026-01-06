<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    exit("Unauthorized");
}

if (!isset($_POST['application_id']) || !isset($_POST['status'])) {
    exit("Invalid request");
}

$application_id = (int)$_POST['application_id'];
$status = $_POST['status'];

// Allowed statuses
$allowed = ['Filtered', 'Interview', 'Accepted', 'Rejected'];

if (!in_array($status, $allowed)) {
    exit("Invalid status");
}

// Ensure employer owns the job for this application
$check = $conn->prepare("
    SELECT applications.id 
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    WHERE applications.id = ? AND jobs.employer_id = ?
");
$check->bind_param("ii", $application_id, $_SESSION['user_id']);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    exit("Unauthorized");
}

// Update status
$update = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
$update->bind_param("si", $status, $application_id);
$update->execute();

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
