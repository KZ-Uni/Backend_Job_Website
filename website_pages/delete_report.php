<?php
session_start();
require "db.php";

// If no ID is provided, still go back to admin dashboard
if (!isset($_POST['id'])) {
    header("Location: admin_dashboard.php?error=noid");
    exit;
}

$id = (int)$_POST['id'];

// Delete the report
$conn->query("DELETE FROM job_reports WHERE id = $id");

// Redirect back to admin dashboard
header("Location: admin_dashboard.php?deleted=1");
exit;
?>
