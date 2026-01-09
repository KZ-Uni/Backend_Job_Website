<?php
include('timeout_check.php');
include('db.php');

if (!isset($_GET['country_id'])) {
    echo json_encode([]);
    exit();
}

$country_id = intval($_GET['country_id']);

$stmt = $conn->prepare("SELECT id, name FROM cities WHERE country_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $country_id);
$stmt->execute();
$result = $stmt->get_result();

$cities = [];
while ($row = $result->fetch_assoc()) {
    $cities[] = $row;
}

echo json_encode($cities);
?>
