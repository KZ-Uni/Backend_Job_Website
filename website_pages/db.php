<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "job_portal";
$port = 3306; // CHANGE THIS TO WHATEVER PORT IS ON XAMPP

// 1. CONNECT TO MYSQL (NO DB SELECTED YET)

$conn = new mysqli($servername, $username, $password, "", $port);

if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
}

// 2. CREATE DATABASE IF NOT EXISTS
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");

// 3. SELECT THE DATABASE
$conn->select_db($dbname);

// 4. CREATE TABLES IN CORRECT ORDER

// COUNTRIES TABLE
$conn->query("
CREATE TABLE IF NOT EXISTS countries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
) ENGINE=InnoDB;
");

// CITIES TABLE
$conn->query("
CREATE TABLE IF NOT EXISTS cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    country_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

// SKILLS MASTER TABLE (predefined skills)
$conn->query("
CREATE TABLE IF NOT EXISTS skills_master (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
) ENGINE=InnoDB;
");

// USERS TABLE
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    country_id INT NULL,
    city_id INT NULL,
    skills TEXT NULL, -- legacy/free-text if you ever need it
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin','Employer','Jobseeker') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
) ENGINE=InnoDB;
");

$conn->query("
CREATE TABLE IF NOT EXISTS user_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    skill_id INT NOT NULL,
    UNIQUE KEY user_skill_unique (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills_master(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

// JOBS TABLE
$conn->query("
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,

    country_id INT NULL,
    city_id INT NULL,

    job_type VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    skills_required TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (employer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (country_id) REFERENCES countries(id) ON DELETE SET NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL
) ENGINE=InnoDB;
");

// APPLICATIONS TABLE
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


/* MESSAGES TABLE (NO FOREIGN KEYS)
   In‑App Messaging System
   Employers ↔ Jobseekers */
$conn->query("
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,      -- user sending the message
    receiver_id INT NOT NULL,    -- user receiving the message
    job_id INT NOT NULL,         -- job the conversation is about
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0
) ENGINE=InnoDB;
");


/* PUBLIC CHATBOX (GLOBAL SUPPORT CHAT)
   Everyone can post messages visible to all users. */
$conn->query("
CREATE TABLE IF NOT EXISTS public_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,      -- who posted
    message TEXT NOT NULL,
    posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
");



/* JOB REPORTS TABLE */
$conn->query("
CREATE TABLE IF NOT EXISTS job_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NULL,
    report_date DATETIME NOT NULL,
    UNIQUE KEY unique_report (job_id, user_id)
)
");

// 5. INSERT EUROPEAN COUNTRIES IF EMPTY

$checkCountries = $conn->query("SELECT id FROM countries LIMIT 1");

if ($checkCountries->num_rows == 0)
{
    $conn->query("
        INSERT INTO countries (name) VALUES
        ('Austria'), ('Belgium'), ('Bulgaria'), ('Croatia'), ('Cyprus'),
        ('Czech Republic'), ('Denmark'), ('Estonia'), ('Finland'), ('France'),
        ('Germany'), ('Greece'), ('Hungary'), ('Iceland'), ('Ireland'),
        ('Italy'), ('Latvia'), ('Lithuania'), ('Luxembourg'), ('Malta'),
        ('Netherlands'), ('Norway'), ('Poland'), ('Portugal'), ('Romania'),
        ('Slovakia'), ('Slovenia'), ('Spain'), ('Sweden'), ('Switzerland'),
        ('United Kingdom')
    ");
}

// 6. INSERT ALL MAJOR EUROPEAN CITIES IF EMPTY

$checkCities = $conn->query("SELECT id FROM cities LIMIT 1");

if ($checkCities->num_rows == 0)
{
    // Austria
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Austria'), 'Vienna'),
        ((SELECT id FROM countries WHERE name='Austria'), 'Graz'),
        ((SELECT id FROM countries WHERE name='Austria'), 'Linz'),
        ((SELECT id FROM countries WHERE name='Austria'), 'Salzburg')
    ");

    // Belgium
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Belgium'), 'Brussels'),
        ((SELECT id FROM countries WHERE name='Belgium'), 'Antwerp'),
        ((SELECT id FROM countries WHERE name='Belgium'), 'Ghent')
    ");

    // Bulgaria
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Bulgaria'), 'Sofia'),
        ((SELECT id FROM countries WHERE name='Bulgaria'), 'Plovdiv'),
        ((SELECT id FROM countries WHERE name='Bulgaria'), 'Varna')
    ");

    // Croatia
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Croatia'), 'Zagreb'),
        ((SELECT id FROM countries WHERE name='Croatia'), 'Split'),
        ((SELECT id FROM countries WHERE name='Croatia'), 'Rijeka')
    ");

    // Cyprus
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Cyprus'), 'Nicosia'),
        ((SELECT id FROM countries WHERE name='Cyprus'), 'Limassol')
    ");

    // Czech Republic
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Czech Republic'), 'Prague'),
        ((SELECT id FROM countries WHERE name='Czech Republic'), 'Brno'),
        ((SELECT id FROM countries WHERE name='Czech Republic'), 'Ostrava')
    ");

    // Denmark
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Denmark'), 'Copenhagen'),
        ((SELECT id FROM countries WHERE name='Denmark'), 'Aarhus'),
        ((SELECT id FROM countries WHERE name='Denmark'), 'Odense')
    ");

    // Estonia
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Estonia'), 'Tallinn'),
        ((SELECT id FROM countries WHERE name='Estonia'), 'Tartu')
    ");

    // Finland
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Finland'), 'Helsinki'),
        ((SELECT id FROM countries WHERE name='Finland'), 'Espoo'),
        ((SELECT id FROM countries WHERE name='Finland'), 'Tampere')
    ");

    // France
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='France'), 'Paris'),
        ((SELECT id FROM countries WHERE name='France'), 'Lyon'),
        ((SELECT id FROM countries WHERE name='France'), 'Marseille'),
        ((SELECT id FROM countries WHERE name='France'), 'Nice')
    ");

    // Germany
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Germany'), 'Berlin'),
        ((SELECT id FROM countries WHERE name='Germany'), 'Hamburg'),
        ((SELECT id FROM countries WHERE name='Germany'), 'Munich'),
        ((SELECT id FROM countries WHERE name='Germany'), 'Cologne'),
        ((SELECT id FROM countries WHERE name='Germany'), 'Frankfurt')
    ");

    // Greece
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Greece'), 'Athens'),
        ((SELECT id FROM countries WHERE name='Greece'), 'Thessaloniki')
    ");

    // Hungary
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Hungary'), 'Budapest'),
        ((SELECT id FROM countries WHERE name='Hungary'), 'Debrecen')
    ");

    // Iceland
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Iceland'), 'Reykjavik')
    ");

    // Ireland
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Ireland'), 'Dublin'),
        ((SELECT id FROM countries WHERE name='Ireland'), 'Cork'),
        ((SELECT id FROM countries WHERE name='Ireland'), 'Galway')
    ");

    // Italy
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Italy'), 'Rome'),
        ((SELECT id FROM countries WHERE name='Italy'), 'Milan'),
        ((SELECT id FROM countries WHERE name='Italy'), 'Naples'),
        ((SELECT id FROM countries WHERE name='Italy'), 'Turin'),
        ((SELECT id FROM countries WHERE name='Italy'), 'Florence')
    ");

    // Latvia
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Latvia'), 'Riga')
    ");

    // Lithuania
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Lithuania'), 'Vilnius'),
        ((SELECT id FROM countries WHERE name='Lithuania'), 'Kaunas')
    ");

    // Luxembourg
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Luxembourg'), 'Luxembourg City')
    ");

    // Malta
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Malta'), 'Valletta'),
        ((SELECT id FROM countries WHERE name='Malta'), 'Sliema')
    ");

    // Netherlands
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Netherlands'), 'Amsterdam'),
        ((SELECT id FROM countries WHERE name='Netherlands'), 'Rotterdam'),
        ((SELECT id FROM countries WHERE name='Netherlands'), 'The Hague'),
        ((SELECT id FROM countries WHERE name='Netherlands'), 'Utrecht')
    ");

    // Norway
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Norway'), 'Oslo'),
        ((SELECT id FROM countries WHERE name='Norway'), 'Bergen'),
        ((SELECT id FROM countries WHERE name='Norway'), 'Trondheim')
    ");

    // Poland
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Poland'), 'Warsaw'),
        ((SELECT id FROM countries WHERE name='Poland'), 'Krakow'),
        ((SELECT id FROM countries WHERE name='Poland'), 'Gdansk'),
        ((SELECT id FROM countries WHERE name='Poland'), 'Wroclaw')
    ");

    // Portugal
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Portugal'), 'Lisbon'),
        ((SELECT id FROM countries WHERE name='Portugal'), 'Porto'),
        ((SELECT id FROM countries WHERE name='Portugal'), 'Coimbra')
    ");

    // Romania
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Romania'), 'Bucharest'),
        ((SELECT id FROM countries WHERE name='Romania'), 'Cluj-Napoca'),
        ((SELECT id FROM countries WHERE name='Romania'), 'Timisoara')
    ");

    // Slovakia
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Slovakia'), 'Bratislava'),
        ((SELECT id FROM countries WHERE name='Slovakia'), 'Kosice')
    ");

    // Slovenia
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Slovenia'), 'Ljubljana'),
        ((SELECT id FROM countries WHERE name='Slovenia'), 'Maribor')
    ");

    // Spain
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Spain'), 'Madrid'),
        ((SELECT id FROM countries WHERE name='Spain'), 'Barcelona'),
        ((SELECT id FROM countries WHERE name='Spain'), 'Valencia'),
        ((SELECT id FROM countries WHERE name='Spain'), 'Seville')
    ");

    // Sweden
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Sweden'), 'Stockholm'),
        ((SELECT id FROM countries WHERE name='Sweden'), 'Gothenburg'),
        ((SELECT id FROM countries WHERE name='Sweden'), 'Malmö')
    ");

    // Switzerland
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='Switzerland'), 'Zurich'),
        ((SELECT id FROM countries WHERE name='Switzerland'), 'Geneva'),
        ((SELECT id FROM countries WHERE name='Switzerland'), 'Basel')
    ");

    // United Kingdom
    $conn->query("INSERT INTO cities (country_id, name) VALUES
        ((SELECT id FROM countries WHERE name='United Kingdom'), 'London'),
        ((SELECT id FROM countries WHERE name='United Kingdom'), 'Manchester'),
        ((SELECT id FROM countries WHERE name='United Kingdom'), 'Birmingham'),
        ((SELECT id FROM countries WHERE name='United Kingdom'), 'Glasgow'),
        ((SELECT id FROM countries WHERE name='United Kingdom'), 'Edinburgh')
    ");
}

// 7. INSERT PREDEFINED SKILLS IF EMPTY

$checkSkills = $conn->query("SELECT id FROM skills_master LIMIT 1");

if ($checkSkills->num_rows == 0)
{
    $conn->query("
        INSERT INTO skills_master (name) VALUES
        ('PHP'),
        ('JavaScript'),
        ('HTML'),
        ('CSS'),
        ('MySQL'),
        ('Python'),
        ('Java'),
        ('C#'),
        ('C++'),
        ('React'),
        ('Node.js'),
        ('Laravel'),
        ('Symfony'),
        ('Django'),
        ('Flask'),
        ('Git'),
        ('REST APIs'),
        ('SQL'),
        ('NoSQL'),
        ('Linux'),
        ('Docker'),
        ('Kubernetes'),
        ('Azure'),
        ('AWS'),
        ('Agile'),
        ('Scrum'),
        ('Project Management'),
        ('UI Design'),
        ('UX Design'),
        ('Figma'),
        ('Adobe Photoshop'),
        ('Adobe Illustrator'),
        ('Data Analysis'),
        ('Excel'),
        ('Power BI'),
        ('Machine Learning'),
        ('Communication'),
        ('Teamwork'),
        ('Problem Solving'),
        ('Time Management'),
        ('Leadership'),
        ('Critical Thinking'),
        ('Public Speaking'),
        ('Customer Service'),
        ('Sales'),
        ('Marketing'),
        ('Copywriting'),
        ('SEO'),
        ('Content Creation')
    ");
}

// 8. CREATE DEFAULT USERS IF THEY DO NOT EXIST
function createDefaultUser($conn, $username, $email, $password, $role)
{
    $check = $conn->query("SELECT id FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows == 0) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $conn->query("
            INSERT INTO users (username, email, password, role)
            VALUES ('$username', '$email', '$hashed', '$role')
        ");
    }
}

// Count users
$checkUsers = $conn->query("SELECT COUNT(*) AS total FROM users");
$row = $checkUsers->fetch_assoc();

if ($row['total'] == 0)
{
    createDefaultUser($conn, "admin1", "admin1@example.com", "admin1", "Admin");
    createDefaultUser($conn, "employer1", "employer1@example.com", "employer1", "Employer");
    createDefaultUser($conn, "jobseeker1", "jobseeker1@example.com", "jobseeker1", "Jobseeker");
}

// 9. CREATE DEFAULT JOBS IF NONE EXIST

$checkJobs = $conn->query("SELECT id FROM jobs LIMIT 1");

if ($checkJobs->num_rows == 0)
{
    $emp = $conn->query("SELECT id FROM users WHERE role='Employer' LIMIT 1");

    if ($emp && $emp->num_rows > 0)
    {
        $employer_id = (int)$emp->fetch_assoc()['id'];

        $conn->query("
            INSERT INTO jobs (employer_id, title, company, country_id, city_id, job_type, description, skills_required)
            VALUES
            ($employer_id, 'Junior Web Developer', 'TechCorp', 1, 1, 'Full-time',
            'We are looking for a junior web developer to join our growing team.', ''),

            ($employer_id, 'Graphic Designer', 'Creative Studio', 1, 1, 'Part-time',
            'Seeking a creative graphic designer for remote freelance work.', ''),

            ($employer_id, 'Marketing Assistant', 'MarketPro', 1, 1, 'Full-time',
            'Assist our marketing team with campaigns, social media, and analytics.', '')
        ");
    }
}
?>
