<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer')
{
    header("Location: login.php");
    exit();
}

$employer_id = (int)$_SESSION['user_id'];

//FETCH JOBS WITH COUNTRY + CITY NAMES
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

//HANDLE CHATBOX AJAX (LOAD + SEND)
if (isset($_POST['chat_action'])) {

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
    <title>Employer Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
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
                                if ($job['country_name'] || $job['city_name'])
                                {
                                    echo htmlspecialchars($job['country_name'] . ", " . $job['city_name']);
                                }
                                else
                                {
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
