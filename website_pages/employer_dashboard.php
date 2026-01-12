<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    header("Location: login.php");
    exit();
}

$employer_id = (int)$_SESSION['user_id'];

/* ---------------------------------------------------------
   FETCH JOBS WITH COUNTRY + CITY NAMES
--------------------------------------------------------- */
$sql = "
    SELECT 
        jobs.id,
        jobs.title,
        jobs.job_type,
        jobs.description,
        jobs.skills_required,
        countries.name AS country_name,
        cities.name AS city_name,
        jobs.created_at
    FROM jobs
    LEFT JOIN countries ON jobs.country_id = countries.id
    LEFT JOIN cities ON jobs.city_id = cities.id
    WHERE jobs.employer_id = $employer_id
    ORDER BY jobs.created_at DESC
";

$result = $conn->query($sql);

/* ---------------------------------------------------------
   HANDLE CHATBOX AJAX (LOAD + SEND)
--------------------------------------------------------- */
if (isset($_POST['chat_action'])) {

    // SEND MESSAGE
    if ($_POST['chat_action'] === "send") {
        $uid = $_SESSION['user_id'];
        $msg = $conn->real_escape_string($_POST['message']);
        $conn->query("INSERT INTO public_chat (user_id, message) VALUES ($uid, '$msg')");
        exit;
    }

    // LOAD MESSAGES
    if ($_POST['chat_action'] === "load") {
        $messages = [];
        $q = $conn->query("
            SELECT public_chat.*, users.username 
            FROM public_chat 
            JOIN users ON users.id = public_chat.user_id
            ORDER BY posted_at DESC
            LIMIT 30
        ");

        while ($row = $q->fetch_assoc()) {
            $messages[] = $row;
        }

        echo json_encode($messages);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .link-button {
            background: none;
            border: none;
            color: #007BFF;
            padding: 0;
            font: inherit;
            cursor: pointer;
            text-decoration: none;
        }
        .link-button:hover { text-decoration: underline; }
        .inline-form { display: inline; }
        .job-item {
            background: #f9f9f9;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }

        /* CHATBOX */
        #chat-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            padding: 12px 18px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            z-index: 9999;
        }

        #chat-window {
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 300px;
            height: 380px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px #0003;
            display: none;
            flex-direction: column;
            z-index: 9999;
        }

        #chat-messages {
            flex: 1;
            padding: 10px;
            overflow-y: auto;
            font-size: 14px;
        }

        #chat-input {
            padding: 10px;
            border-top: 1px solid #ddd;
        }

        #chat-input textarea {
            width: 100%;
            height: 50px;
            resize: none;
        }

        #chat-input button {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>

    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>

<header>
    <div class="container">
        <h1>Employer Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <li><a href="add_job.php">Post Job</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">
        <h2>Your Job Listings</h2>

        <?php if (isset($_GET['deleted'])): ?>
            <p style="color: green;">Job deleted successfully!</p>
        <?php elseif (isset($_GET['error'])): ?>
            <p style="color: red;">Could not delete the job. Error code: <?php echo htmlspecialchars($_GET['error']); ?></p>
        <?php endif; ?>

        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($job = $result->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>

                    <p><strong>Location:</strong>
                        <?php 
                            if ($job['country_name'] || $job['city_name']) {
                                echo htmlspecialchars($job['country_name'] . ", " . $job['city_name']);
                            } else {
                                echo "Not specified";
                            }
                        ?>
                    </p>

                    <p><strong>Type:</strong> 
                        <?php echo htmlspecialchars($job['job_type']); ?>
                    </p>

                    <p><strong>Posted:</strong> 
                        <?php echo htmlspecialchars($job['created_at']); ?>
                    </p>

                    <a href="edit_job.php?id=<?php echo $job['id']; ?>">Edit</a>
                    |
                    <form action="delete_job.php" method="POST" class="inline-form" onsubmit="return confirm('Delete this job?');">
                        <input type="hidden" name="id" value="<?php echo (int)$job['id']; ?>">
                        <button type="submit" class="link-button">Delete</button>
                    </form>
                    |
                    <a href="view_applicants.php?job_id=<?php echo $job['id']; ?>">Applicants</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>You haven't posted any jobs yet.</p>
        <?php endif; ?>
    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

<!-- FLOATING CHATBOX -->
<div id="chat-toggle">Chat</div>

<div id="chat-window">
    <div id="chat-messages"></div>
    <div id="chat-input">
        <textarea id="chat-text" placeholder="Ask for advice..."></textarea>
        <button onclick="sendMessage()">Send</button>
    </div>
</div>

<script>
document.getElementById("chat-toggle").onclick = function() {
    const win = document.getElementById("chat-window");
    win.style.display = win.style.display === "flex" ? "none" : "flex";
};

function loadMessages() {
    fetch("", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "chat_action=load"
    })
    .then(res => res.json())
    .then(data => {
        let html = "";
        data.forEach(m => {
            html += `<p><strong>${m.username}:</strong> ${m.message}<br><small>${m.posted_at}</small></p>`;
        });
        document.getElementById("chat-messages").innerHTML = html;
    });
}

function sendMessage() {
    const msg = document.getElementById("chat-text").value;
    if (msg.trim() === "") return;

    fetch("", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "chat_action=send&message=" + encodeURIComponent(msg)
    }).then(() => {
        document.getElementById("chat-text").value = "";
        loadMessages();
    });
}

setInterval(loadMessages, 2000);
loadMessages();
</script>

</body>
</html>

