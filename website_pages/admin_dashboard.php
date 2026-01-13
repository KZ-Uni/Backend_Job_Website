<?php
session_start();
include('timeout_check.php');
include('db.php');

// Only Admin can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

/* ---------------------------------------------------------
   FETCH USERS
--------------------------------------------------------- */
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");

/* ---------------------------------------------------------
   FETCH JOBS WITH COUNTRY + CITY
--------------------------------------------------------- */
$jobs = $conn->query("
    SELECT 
        j.*,
        c.name AS country_name,
        ci.name AS city_name
    FROM jobs j
    LEFT JOIN countries c ON j.country_id = c.id
    LEFT JOIN cities ci ON j.city_id = ci.id
    ORDER BY j.created_at DESC
");

/* ---------------------------------------------------------
   FETCH APPLICATIONS
--------------------------------------------------------- */
$applications = $conn->query("
    SELECT 
        a.id,
        a.applied_at,
        u.username AS applicant_name,
        u.email AS applicant_email,
        j.title AS job_title,
        j.company AS job_company
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    ORDER BY a.applied_at DESC
");

/* ---------------------------------------------------------
   FETCH JOB REPORTS
--------------------------------------------------------- */
$reports = $conn->query("
    SELECT 
        job_reports.id AS report_id,
        job_reports.report_date,

        jobs.id AS job_id,
        jobs.title AS job_title,
        jobs.company AS job_company,

        users.username AS reporter_name,
        users.email AS reporter_email

    FROM job_reports
    LEFT JOIN jobs ON job_reports.job_id = jobs.id
    LEFT JOIN users ON job_reports.user_id = users.id
    ORDER BY job_reports.report_date DESC
");

/* ---------------------------------------------------------
   CHATBOX AJAX HANDLER (LOAD + SEND)
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
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>

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
</head>
<body>

<header>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">

        <!-- USERS SECTION -->
        <h2>All Users</h2>
        <?php while($u = $users->fetch_assoc()): ?>
            <div class="job-item">
                <p><strong><?php echo htmlspecialchars($u['username']); ?></strong> (<?php echo htmlspecialchars($u['role']); ?>)</p>

                <a href="edit_user_role.php?id=<?php echo $u['id']; ?>">Edit Role</a> | 
                <a href="delete_user.php?id=<?php echo $u['id']; ?>">Delete User</a>
            </div>
        <?php endwhile; ?>


        <!-- JOBS SECTION -->
        <h2 style="margin-top:40px;">All Jobs</h2>
        <?php while($j = $jobs->fetch_assoc()): ?>
            <div class="job-item">
                <h4><?php echo htmlspecialchars($j['title']); ?></h4>

                <p><strong>Company:</strong> <?php echo htmlspecialchars($j['company']); ?></p>

                <p><strong>Location:</strong>
                    <?php 
                        if ($j['country_name'] || $j['city_name']) {
                            echo htmlspecialchars($j['country_name'] . ", " . $j['city_name']);
                        } else {
                            echo "Not specified";
                        }
                    ?>
                </p>

                <p><strong>Type:</strong> <?php echo htmlspecialchars($j['job_type']); ?></p>

                <p><strong>Posted:</strong> <?php echo htmlspecialchars($j['created_at']); ?></p>

                <a href="edit_job.php?id=<?php echo $j['id']; ?>">Edit Job</a> | 

                <form action="delete_job.php" method="POST" class="inline-form" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo (int)$j['id']; ?>">
                    <button type="submit" class="link-button" onclick="return confirm('Delete this job?');">
                        Delete Job
                    </button>
                </form>

            </div>
        <?php endwhile; ?>


        <!-- APPLICATIONS SECTION -->
        <h2 style="margin-top:40px;">All Applications</h2>

        <?php if ($applications && $applications->num_rows > 0): ?>
            <?php while($a = $applications->fetch_assoc()): ?>
                <div class="job-item">
                    <p>
                        <strong>Applicant:</strong>
                        <?php echo htmlspecialchars($a['applicant_name']); ?>
                        (<?php echo htmlspecialchars($a['applicant_email']); ?>)
                    </p>
                    <p>
                        <strong>Job:</strong>
                        <?php echo htmlspecialchars($a['job_title']); ?>
                        at <?php echo htmlspecialchars($a['job_company']); ?>
                    </p>
                    <p>
                        <strong>Applied at:</strong> <?php echo htmlspecialchars($a['applied_at']); ?>
                    </p>

                    <form action="delete_application.php" method="POST" class="inline-form">
                        <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                        <button type="submit" class="link-button" onclick="return confirm('Delete this application?');">
                            Delete Application
                        </button>
                    </form>

                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No applications found.</p>
        <?php endif; ?>

        <!-- REPORTS SECTION -->
        <h2 style="margin-top:40px;">Job Reports</h2>

        <?php if ($reports && $reports->num_rows > 0): ?>
            <?php while($r = $reports->fetch_assoc()): ?>
                <div class="job-item">
                    <p>
                        <strong>Job:</strong>
                        <a href="job_details.php?id=<?php echo (int)$r['job_id']; ?>" 
                        style="color:#007BFF; text-decoration:none;">
                            <?php echo htmlspecialchars($r['job_title']); ?>
                        </a>
                        (<?php echo htmlspecialchars($r['job_company']); ?>)
                    </p>

                    <p>
                        <strong>Reported by:</strong>
                        <?php echo htmlspecialchars($r['reporter_name']); ?>
                        (<?php echo htmlspecialchars($r['reporter_email']); ?>)
                    </p>

                    <p>
                        <strong>Date:</strong>
                        <?php echo htmlspecialchars($r['report_date']); ?>
                    </p>

                    <form action="delete_report.php" method="POST" class="inline-form">
                        <input type="hidden" name="id" value="<?php echo (int)$r['report_id']; ?>">
                        <button type="submit" class="link-button" onclick="return confirm('Delete this report?');">
                            Delete Report
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No reports found.</p>
        <?php endif; ?>


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
