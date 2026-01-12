<?php
session_start();
$conn = new mysqli("localhost", "root", "", "job_portal");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $uid = $_SESSION["user_id"];
    $msg = $conn->real_escape_string($_POST["message"]);
    $conn->query("INSERT INTO public_chat (user_id, message) VALUES ($uid, '$msg')");
    exit;
}

$result = $conn->query("
    SELECT public_chat.*, users.username 
    FROM public_chat 
    JOIN users ON users.id = public_chat.user_id
    ORDER BY posted_at DESC
    LIMIT 30
");

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode($messages);
?>
