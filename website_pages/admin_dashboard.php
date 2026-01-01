<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
$jobs = $conn->query("SELECT * FROM jobs ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<header>
    <div class="container">
        <h1>Admin Dashboard</h1>
        <nav>
            <ul>
                <li>Hello, <?php echo $_SESSION['username']; ?></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <div class="container" style="width:80%; margin:30px auto;">

        <h2>All Users</h2>
        <?php while($u = $users->fetch_assoc()): ?>
            <div class="job-item">
                <p><strong><?php echo $u['username']; ?></strong> (<?php echo $u['role']; ?>)</p>
                <a href="delete_user.php?id=<?php echo $u['id']; ?>">Delete User</a>
            </div>
        <?php endwhile; ?>

        <h2 style="margin-top:40px;">All Jobs</h2>
        <?php while($j = $jobs->fetch_assoc()): ?>
            <div class="job-item">
                <h4><?php echo $j['title']; ?></h4>
                <p><strong>Company:</strong> <?php echo $j['company']; ?></p>
                <a href="delete_job.php?id=<?php echo $j['id']; ?>">Delete Job</a>
            </div>
        <?php endwhile; ?>

    </div>
</main>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>
