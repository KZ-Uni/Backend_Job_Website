<?php
session_start();
include('timeout_check.php');
include('db.php');

// Must be logged in to report
if (!isset($_SESSION['user_id']))
{
    header("Location: login.php?error=login_required");
    exit();
}

if (!isset($_POST['job_id']))
{
    header("Location: index.php?error=invalid");
    exit();
}

$job_id = (int)$_POST['job_id'];
$user_id = (int)$_SESSION['user_id'];

// insert the report
$stmt = $conn->prepare("
    INSERT INTO job_reports (job_id, user_id, report_date)
    VALUES (?, ?, NOW())
");
$stmt->bind_param("ii", $job_id, $user_id);

if ($stmt->execute())
{
    header("Location: job_details.php?id=$job_id&reported=1");
}
else
{
    header("Location: job_details.php?id=$job_id&already_reported=1");
}

exit();
?>
