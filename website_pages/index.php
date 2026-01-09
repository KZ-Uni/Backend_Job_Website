<?php
session_start();
include('timeout_check.php');
include('db.php');

/* ---------------------------------------------------------
   FILTERS
--------------------------------------------------------- */
$where = [];
$params = [];
$types = "";

// Location filter (country + city)
if (!empty($_GET['location'])) {
    $where[] = "(c.name LIKE ? OR ci.name LIKE ?)";
    $params[] = "%" . $_GET['location'] . "%";
    $params[] = "%" . $_GET['location'] . "%";
    $types .= "ss";
}

// Job type filter
if (!empty($_GET['job-type'])) {
    $where[] = "j.job_type = ?";
    $params[] = $_GET['job-type'];
    $types .= "s";
}

// Category filter (skills)
if (!empty($_GET['category'])) {
    $where[] = "j.skills_required LIKE ?";
    $params[] = "%" . $_GET['category'] . "%";
    $types .= "s";
}

$sql = "
    SELECT 
        j.*,
        c.name AS country_name,
        ci.name AS city_name
    FROM jobs j
    LEFT JOIN countries c ON j.country_id = c.id
    LEFT JOIN cities ci ON j.city_id = ci.id
";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY j.created_at DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <?php if (isset($_SESSION['user_id'])): ?>
        <script src="../javascript/timeout.js"></script>
    <?php endif; ?>
</head>
<body>

<!-- Header -->
<header>
    <div class="container">
        <h1>Job Portal</h1>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <li>Hello, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</li>

                    <?php if ($_SESSION['role'] === 'Admin'): ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'Employer'): ?>
                        <li><a href="employer_dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'Jobseeker'): ?>
                        <li><a href="jobseeker_dashboard.php">Dashboard</a></li>
                    <?php endif; ?>

                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="signup.php">Sign Up</a></li>
                    <li><a href="login.php">Sign In</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<div class="main-content">

    <!-- Filters -->
    <aside class="filters">
        <h3>Filters</h3>
        <form action="index.php" method="GET">

            <label for="location">Location</label>
            <select id="location" name="location">
                <option value="">Any</option>
                <option value="Remote">Remote</option>
                <option value="USA">USA</option>
                <option value="UK">UK</option>
                <option value="Canada">Canada</option>
            </select>

            <label for="job-type">Job Type</label>
            <select id="job-type" name="job-type">
                <option value="">Any</option>
                <option value="Full-time">Full-time</option>
                <option value="Part-time">Part-time</option>
                <option value="Internship">Internship</option>
                <option value="Remote">Remote</option>
                <option value="Contract">Contract</option>
            </select>

            <label for="category">Skill Category</label>
            <select id="category" name="category">
                <option value="">All</option>
                <option value="Engineering">Engineering</option>
                <option value="Design">Design</option>
                <option value="Marketing">Marketing</option>
                <option value="Sales">Sales</option>
            </select>

            <button type="submit">Apply Filters</button>
        </form>
    </aside>

    <!-- Job Listings -->
    <main class="job-listings">
        <h2>Job Listings</h2>

        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="job-item">
                    <h4><?php echo htmlspecialchars($row['title']); ?></h4>

                    <p><strong>Company:</strong> <?php echo htmlspecialchars($row['company']); ?></p>

                    <p><strong>Location:</strong>
                        <?php 
                            if ($row['country_name'] || $row['city_name']) {
                                echo htmlspecialchars($row['country_name'] . ", " . $row['city_name']);
                            } else {
                                echo "Not specified";
                            }
                        ?>
                    </p>

                    <p><strong>Type:</strong> <?php echo htmlspecialchars($row['job_type']); ?></p>

                    <p><strong>Description:</strong>
                        <?php echo substr(htmlspecialchars($row['description']), 0, 100); ?>...
                    </p>

                    <a href="job_details.php?id=<?php echo $row['id']; ?>">View Details</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No job listings available.</p>
        <?php endif; ?>

        <div class="pagination">
            <a href="#">Previous</a>
            <a href="#">1</a>
            <a href="#">2</a>
            <a href="#">Next</a>
        </div>

        <!-- Timeout Overlay -->
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
</div>

<footer>
    <p>&copy; 2025 Job Portal. All rights reserved.</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
