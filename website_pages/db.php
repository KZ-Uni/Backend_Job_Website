<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "job_portal";
$port = 3307;

/* ---------------------------------------------------------
   1. CONNECT TO MYSQL WITHOUT SELECTING A DATABASE
--------------------------------------------------------- */
$conn = new mysqli($servername, $username, $password, "", $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* ---------------------------------------------------------
   2. CREATE DATABASE IF NOT EXISTS
--------------------------------------------------------- */
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");

/* ---------------------------------------------------------
   3. SELECT THE DATABASE
--------------------------------------------------------- */
$conn->select_db($dbname);

/* ---------------------------------------------------------
   4. CREATE TABLES IF THEY DO NOT EXIST
--------------------------------------------------------- */

/* USERS TABLE */
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Employer','Jobseeker') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");

/* JOBS TABLE */
$conn->query("
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    job_type VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

/* APPLICATIONS TABLE */
$conn->query("
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Filtered','Interview','Accepted','Rejected') DEFAULT 'Pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

/* ---------------------------------------------------------
   5. CREATE ONE DEFAULT ADMIN IF NONE EXISTS
--------------------------------------------------------- */
$checkAdmin = $conn->query("SELECT id FROM users WHERE role='Admin' LIMIT 1");

if ($checkAdmin->num_rows == 0) {
    $hashed = password_hash("admin1", PASSWORD_BCRYPT);
    $conn->query("
        INSERT INTO users (username, email, password, role)
        VALUES ('admin1', 'admin1@example.com', '$hashed', 'Admin')
    ");
}

/* ---------------------------------------------------------
   6. OPTIONAL: CREATE SAMPLE JOBS ONLY ONCE
--------------------------------------------------------- */

$checkJobs = $conn->query("SELECT id FROM jobs LIMIT 1");

if ($checkJobs->num_rows == 0) {

    // Find any employer
    $emp = $conn->query("SELECT id FROM users WHERE role='Employer' LIMIT 1");

    if ($emp && $emp->num_rows > 0) {

        $employer_id = (int)$emp->fetch_assoc()['id'];

        // Insert sample jobs
        $conn->query("
            INSERT INTO jobs (employer_id, title, company, location, job_type, description)
            VALUES
            ($employer_id, 'Junior Web Developer', 'TechCorp', 'New York', 'Full-time',
            'We are looking for a junior web developer to join our growing team.'),

            ($employer_id, 'Graphic Designer', 'Creative Studio', 'Remote', 'Part-time',
            'Seeking a creative graphic designer for remote freelance work.'),

            ($employer_id, 'Marketing Assistant', 'MarketPro', 'San Francisco', 'Full-time',
            'Assist our marketing team with campaigns, social media, and analytics.')
        ");
    }
}
?>
