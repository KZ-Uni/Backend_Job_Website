<?php
session_start();
include('timeout_check.php');
include('db.php');

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT sm.id, sm.name 
    FROM user_skills us
    JOIN skills_master sm ON sm.id = us.skill_id
    WHERE us.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$skills = [];
while ($row = $result->fetch_assoc())
{
    $skills[] = $row;
}

echo json_encode($skills);
?>
