<?php
include('timeout_check.php');
include('db.php');

$q = "%" . $_GET['q'] . "%";

$stmt = $conn->prepare("SELECT id, name FROM skills_master WHERE name LIKE ? LIMIT 10");
$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

$skills = [];
while ($row = $result->fetch_assoc())
{
    $skills[] = $row;
}

echo json_encode($skills);
?>
