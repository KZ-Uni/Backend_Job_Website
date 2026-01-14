<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Jobseeker')
{
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// FETCH APPLICATIONS
$applications = $conn->prepare("
    SELECT jobs.*, applications.status, applications.applied_at
    FROM applications
    JOIN jobs ON applications.job_id = jobs.id
    WHERE applications.user_id = ?
    ORDER BY applications.applied_at DESC
");
$applications->bind_param("i", $user_id);
$applications->execute();
$appResults = $applications->get_result();

//  FETCH ALL JOBS
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");

// CHATBOX AJAX HANDLER (LOAD + SEND)
if (isset($_POST['chat_action']))
{
    // SEND MESSAGE
    if ($_POST['chat_action'] === "send")
    {
        $uid = $_SESSION['user_id'];
        $msg = $conn->real_escape_string($_POST['message']);
        $conn->query("INSERT INTO public_chat (user_id, message) VALUES ($uid, '$msg')");
        exit;
    }

    // LOAD MESSAGES
    if ($_POST['chat_action'] === "load")
    {
        $messages = [];
        $q = $conn->query("
            SELECT public_chat.*, users.username 
            FROM public_chat 
            JOIN users ON users.id = public_chat.user_id
            ORDER BY posted_at DESC
            LIMIT 30
        ");

        while ($row = $q->fetch_assoc())
        {
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
    <title>Jobseeker Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">

    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>
    <header>
        <div class="container">
            <h1>Jobseeker Dashboard</h1>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li>Hello, <?php echo $_SESSION['username']; ?></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container" style="width:80%; margin:30px auto;">

            <h2>Jobs You Applied To</h2>

            <?php if ($appResults->num_rows > 0): ?>
                <?php while($job = $appResults->fetch_assoc()): ?>
                    <div class="job-item">
                        <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
                        <p><strong>Applied on:</strong> <?php echo htmlspecialchars($job['applied_at']); ?></p>

                        <p>
                            <strong>Status:</strong>
                            <span class="status-badge <?php echo $job['status']; ?>">
                                <?php echo $job['status']; ?>
                            </span>
                        </p>

                        <a href="job_details.php?id=<?php echo $job['id']; ?>">View Job</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You haven't applied to any jobs yet.</p>
            <?php endif; ?>

            <h2 style="margin-top:40px;">Browse All Jobs</h2>

            <?php while($job = $jobs->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo htmlspecialchars($job['title']); ?></h4>
                    <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
                    <a href="job_details.php?id=<?php echo $job['id']; ?>">View Job</a>
                </div>
            <?php endwhile; ?>
        <div id="timeout-overlay" style="
            display:none;
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:100%;
            background:rgba(0,0,0,0.5);
            z-index:9998;
        "></div>

        <div id="timeout-popup" style="
            display:none;
            position:fixed;
            top:50%;
            left:50%;
            transform:translate(-50%, -50%);
            background:white;
            padding:25px 30px;
            width:320px;
            border-radius:12px;
            box-shadow:0 8px 25px rgba(0,0,0,0.25);
            z-index:9999;
            text-align:center;
            opacity:0;
            transition:opacity 0.3s ease;
        ">
            <h3 style="margin-top:0; font-size:20px; color:#333;">Session Timeout</h3>
            <p style="font-size:14px; color:#555; margin-bottom:20px;">
                You’ve been inactive for a while.  
                You will be logged out soon.
            </p>

            <button onclick="stayLoggedIn()" style="
                padding:10px 18px;
                background:#007BFF;
                color:white;
                border:none;
                border-radius:6px;
                font-size:14px;
                cursor:pointer;
                width:100%;
            ">Stay Logged In</button>
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
        document.getElementById("chat-toggle").onclick = function()
        {
            const win = document.getElementById("chat-window");
            win.style.display = win.style.display === "flex" ? "none" : "flex";
        };

        function loadMessages()
        {
            fetch("",
            {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "chat_action=load"
            }).then(res => res.json()).then(data =>
            {
                let html = "";
                data.forEach(m =>
                {
                    html += `<p><strong>${m.username}:</strong> ${m.message}<br><small>${m.posted_at}</small></p>`;
                });
                document.getElementById("chat-messages").innerHTML = html;
            });
        }

        function sendMessage()
        {
            const msg = document.getElementById("chat-text").value;
            if (msg.trim() === "") return;

            fetch("",
            {
                method: "POST",
                headers: {"Content-Type": "application/x-www-form-urlencoded"},
                body: "chat_action=send&message=" + encodeURIComponent(msg)
            }).then(() =>
            {
                document.getElementById("chat-text").value = "";
                loadMessages();
            });
        }

        setInterval(loadMessages, 2000);
        loadMessages();
    </script>
</body>
</html>
