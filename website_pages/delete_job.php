<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Employer']))
{
    header("Location: login.php");
    exit();
}

if (!isset($_POST['id']))
{
    if ($_SESSION['role'] === 'Admin')
    {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: employer_dashboard.php");
    }
    exit();
}

$job_id = (int)$_POST['id'];
$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

$stmt = $conn->prepare("SELECT employer_id FROM jobs WHERE id = ?");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$res  = $stmt->get_result();

if ($res->num_rows === 0)
{
    if ($role === 'Admin')
    {
        header("Location: admin_dashboard.php?error=notfound");
    }
    else
    {
        header("Location: employer_dashboard.php?error=notfound");
    }
    exit();
}

$job = $res->fetch_assoc();

if ($role === 'Admin')
{
    $del = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $del->bind_param("i", $job_id);
    $del->execute();

    header("Location: admin_dashboard.php?deleted=1");
    exit();
}

// Employer can delete only their own job
$del = $conn->prepare("DELETE FROM jobs WHERE id = ? AND employer_id = ?");
$del->bind_param("ii", $job_id, $user_id);
$del->execute();

if ($del->affected_rows === 0)
{
    header("Location: employer_dashboard.php?error=unauthorized");
}
else
{
    header("Location: employer_dashboard.php?deleted=1");
}
exit();
?>
