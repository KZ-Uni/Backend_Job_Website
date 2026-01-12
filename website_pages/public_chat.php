<?php
session_start();
$conn = new mysqli("localhost", "root", "", "job_portal");

// Handle new message
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $uid = $_SESSION["user_id"];
    $msg = $conn->real_escape_string($_POST["message"]);
    $conn->query("INSERT INTO public_chat (user_id, message) VALUES ($uid, '$msg')");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Community Chatbox</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .chat-box { width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; }
        .msg { padding: 10px; border-bottom: 1px solid #ddd; }
        .msg strong { color: #333; }
        .msg small { color: #888; }
        textarea { width: 100%; height: 60px; margin-top: 10px; }
        button { padding: 10px 20px; margin-top: 10px; }
    </style>
</head>
<body>

<div class="chat-box">
    <h2>Community Chatbox</h2>

    <?php
    $result = $conn->query("
        SELECT public_chat.*, users.username 
        FROM public_chat 
        JOIN users ON users.id = public_chat.user_id
        ORDER BY posted_at DESC
        LIMIT 50
    ");

    while ($row = $result->fetch_assoc()) {
        echo "<div class='msg'>
                <strong>{$row['username']}</strong>: {$row['message']}
                <br><small>{$row['posted_at']}</small>
              </div>";
    }
    ?>

    <form method="POST">
        <textarea name="message" placeholder="Ask for advice or chat with support..."></textarea>
        <button type="submit">Send</button>
    </form>
</div>

</body>
</html>
