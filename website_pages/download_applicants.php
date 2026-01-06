<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Employer') {
    exit("Unauthorized");
}

if (!isset($_GET['job_id'])) {
    exit("No job ID");
}

$job_id = (int)$_GET['job_id'];
$employer_id = $_SESSION['user_id'];

// Verify employer owns the job
$check = $conn->prepare("SELECT id FROM jobs WHERE id = ? AND employer_id = ?");
$check->bind_param("ii", $job_id, $employer_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows === 0) {
    exit("Unauthorized access");
}

// Fetch applicants
$stmt = $conn->prepare("
    SELECT users.username, users.email, applications.applied_at
    FROM applications
    JOIN users ON applications.user_id = users.id
    WHERE applications.job_id = ?
");
$stmt->bind_param("i", $job_id);
$stmt->execute();
$result = $stmt->get_result();

// Clean output buffer
ob_clean();

// CSV headers
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=applicants_job_$job_id.csv");

$output = fopen("php://output", "w");

// Use semicolon as delimiter
$delimiter = ";";

// Header row
fputcsv($output, ["Username", "Email", "Applied At"], $delimiter);

// Data rows
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['username'],
        $row['email'],
        $row['applied_at']
    ], $delimiter);
}

fclose($output);
exit();
?>
