<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin')
{
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']))
{
    $id = intval($_POST['id']);

    $stmt = $conn->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute())
    {
        header("Location: admin_dashboard.php?app_deleted=1");
        exit();
    }
    else
    {
        header("Location: admin_dashboard.php?app_error=1");
        exit();
    }
}

header("Location: admin_dashboard.php");
exit();
?>
