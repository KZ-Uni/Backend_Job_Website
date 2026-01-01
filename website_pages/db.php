<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "job_portal";
$port = 3307;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
}

/* ---------------------------------------------------------
   1. CONNECT TO MYSQL WITHOUT SELECTING A DATABASE
--------------------------------------------------------- */
$conn = new mysqli($servername, $username, $password, "", $port);
if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
}
/* ---------------------------------------------------------
   2. CREATE DATABASE IF IT DOES NOT EXIST
--------------------------------------------------------- */
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");

/* ---------------------------------------------------------
   3. SELECT THE DATABASE
--------------------------------------------------------- */

$conn->select_db($dbname);


/* ---------------------------------------------------------
   4. AUTO‑CREATE TABLES IF THEY DO NOT EXIST
--------------------------------------------------------- */

// USERS TABLE
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

// JOBS TABLE
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

// APPLICATIONS TABLE
$conn->query("
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

/* ---------------------------------------------------------
   5. AUTO‑CREATE DEFAULT USERS IF THEY DO NOT EXIST
--------------------------------------------------------- */

function createDefaultUser($conn, $username, $email, $password, $role) {
    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows == 0) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $conn->query("
            INSERT INTO users (username, email, password, role)
            VALUES ('$username', '$email', '$hashed', '$role')
        ");
    }
}

createDefaultUser($conn, "admin1", "admin1@example.com", "admin1", "Admin");
createDefaultUser($conn, "employer1", "employer1@example.com", "employer1", "Employer");
createDefaultUser($conn, "jobseeker1", "jobseeker1@example.com", "jobseeker1", "Jobseeker");


/* ---------------------------------------------------------
   6. CREATE DEFAULT JOBS IF NONE EXIST
--------------------------------------------------------- */

$checkJobs = $conn->query("SELECT id FROM jobs LIMIT 1");

if ($checkJobs->num_rows == 0) {

    // Get employer ID
    $emp = $conn->query("SELECT id FROM users WHERE role='Employer' LIMIT 1");

    if ($emp && $emp->num_rows > 0) {

        $empRow = $emp->fetch_assoc();
        $employer_id = (int)$empRow['id'];

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

    } else {
        error_log("No employer found. Default jobs not created.");
    }
}?>

