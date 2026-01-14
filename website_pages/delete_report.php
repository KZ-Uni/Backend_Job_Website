<?php
session_start();
require "db.php";

if (!isset($_POST['id']))
{
    header("Location: admin_dashboard.php?error=noid");
    exit;
}

$id = (int)$_POST['id'];

$conn->query("DELETE FROM job_reports WHERE id = $id");

header("Location: admin_dashboard.php?deleted=1");
exit;
?>
