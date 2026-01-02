<?php
session_start();
include('db.php');

// Fetch jobs from the database
$sql = "SELECT * FROM jobs ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal</title>
    <link rel="stylesheet" href="../css/style.css">
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
        <!-- Left Side Filters -->
        <aside class="filters">
            <h3>Filters</h3>
            <form action="index.php" method="GET">
                <label for="location">Location</label>
                <select id="location" name="location">
                    <option value="">Any</option>
                    <option value="New York">New York</option>
                    <option value="San Francisco">San Francisco</option>
                    <option value="Remote">Remote</option>
                </select>

                <label for="job-type">Job Type</label>
                <select id="job-type" name="job-type">
                    <option value="">Any</option>
                    <option value="Full-time">Full-time</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Internship">Internship</option>
                </select>

                <label for="category">Category</label>
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

        <!-- Main Job Listings Area -->
        <main class="job-listings">
            <h2>Job Listings</h2>

            <?php if ($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="job-item">
                        <h4><?php echo htmlspecialchars($row['title']); ?></h4>
                        <p><strong>Company:</strong> <?php echo htmlspecialchars($row['company']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                        <p><strong>Type:</strong> <?php echo htmlspecialchars($row['job_type']); ?></p>
                        <p><strong>Description:</strong> <?php echo substr(htmlspecialchars($row['description']), 0, 100); ?>...</p>
                        <a href="job-details.php?id=<?php echo $row['id']; ?>">View Details</a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No job listings available.</p>
            <?php endif; ?>

            <!-- Pagination (Optional) -->
            <div class="pagination">
                <a href="#">Previous</a>
                <a href="#">1</a>
                <a href="#">2</a>
                <a href="#">3</a>
                <a href="#">Next</a>
            </div>
        </main>
    </div>

    <footer>
        <p>&copy; 2025 Job Portal. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>

