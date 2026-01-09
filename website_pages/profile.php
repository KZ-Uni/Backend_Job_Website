<?php
session_start();
include('timeout_check.php');
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Profile</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            width: 60%;
            margin: 30px auto;
            background: #fff;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .profile-container h2 {
            margin-bottom: 20px; color: #333;
        }
        .profile-container label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: #444;
        }
        .profile-container input {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .profile-container button {
            margin-top: 20px;
            padding: 10px 18px;
            background: #007BFF;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
        }
        .profile-container button:hover {
            background: #0056b3;
        }
        .success-message {
            color: green;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>

<header>
    <div class="container">
        <h1>Your Profile</h1>
        <nav>
            <ul>
                <li>
                    <?php if ($_SESSION['role'] === 'Jobseeker'): ?>
                        <a href="jobseeker_dashboard.php">Dashboard</a>
                    <?php elseif ($_SESSION['role'] === 'Employer'): ?>
                        <a href="employer_dashboard.php">Dashboard</a>
                    <?php elseif ($_SESSION['role'] === 'Admin'): ?>
                        <a href="admin_dashboard.php">Dashboard</a>
                    <?php endif; ?>
                </li>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>
<main>
    <div class="profile-container">
        <h2>Edit Profile</h2>
        <?php if (isset($_GET['updated'])): ?>
            <p class="success-message">Profile updated successfully!</p>
        <?php endif; ?>
        <form action="profile_update.php" method="POST">
            <label>Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            
            <label>Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            
            <label>New Password (optional)</label>
            <input type="password" name="password">
            
            <button type="submit">Save Changes</button>
        </form>
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

    <!-- Timeout Popup -->
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

</body>
</html>

