<?php
session_start();
include('timeout_check.php');
include('db.php');

// Only Admin or Employer can delete jobs
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Employer'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$job_id = (int)$_GET['id'];
$user_id = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// Fetch job info
$check = $conn->query("SELECT employer_id FROM jobs WHERE id = $job_id");

if ($check->num_rows === 0) {
    // Job doesn't exist
    if ($role === 'Admin') {
        header("Location: admin_dashboard.php?error=notfound");
    } else {
        header("Location: employer_dashboard.php?error=notfound");
    }
    exit();
}

$job = $check->fetch_assoc();

// Employers can only delete their own jobs
if ($role === 'Employer' && $job['employer_id'] !== $user_id) {
    header("Location: employer_dashboard.php?error=unauthorized");
    exit();
}

// Delete the job
$conn->query("DELETE FROM jobs WHERE id = $job_id");

// Redirect based on role
if ($role === 'Admin') {
    header("Location: admin_dashboard.php?deleted=1");
} else {
    header("Location: employer_dashboard.php?deleted=1");
}
exit();
