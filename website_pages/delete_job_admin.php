<?php
session_start();
include('db.php');

// Only Admin or Employer can delete jobs
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Employer'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $job_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    // If employer, make sure they own the job
    if ($role === 'Employer') {
        $check = $conn->query("SELECT employer_id FROM jobs WHERE id = $job_id");
        if ($check->num_rows === 0) {
            header("Location: admin_dashboard.php");
            exit();
        }

        $job = $check->fetch_assoc();

        // Employer cannot delete jobs they don't own
        if ($job['employer_id'] !== $user_id) {
            header("Location: admin_dashboard.php");
            exit();
        }
    }

    // Delete the job (applications will auto-delete because of ON DELETE CASCADE)
    $conn->query("DELETE FROM jobs WHERE id = $job_id");
}

header("Location: admin_dashboard.php");
exit();

