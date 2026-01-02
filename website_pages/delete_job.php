<?php
session_start();
include('db.php');

// Only allow Employers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = $_SESSION['user_id'];

// Check if job ID is provided
if (isset($_GET['id'])) {
    $job_id = (int) $_GET['id'];

    // Ensure the job belongs to this employer before deleting
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
    $stmt->bind_param("ii", $job_id, $employer_id);

    if ($stmt->execute()) {
        // Redirect back to employer dashboard with success message
        header("Location: employer_dashboard.php?deleted=1");
        exit();
    } else {
        echo "Error deleting job: " . $conn->error;
    }
} else {
    echo "No job ID provided.";
}
?>
