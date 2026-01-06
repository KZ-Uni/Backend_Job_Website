<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $job_id = (int)$_POST['id'];

    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $employer_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        header("Location: employer_dashboard.php?deleted=1");
        exit();
    } else {
        header("Location: employer_dashboard.php?error=notfound");
        exit();
    }
} else {
    header("Location: employer_dashboard.php?error=noid");
    exit();
}
