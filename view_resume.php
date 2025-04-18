<?php
session_start();
include 'db.php';

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header("Location: login.php");
    exit();
}

// Get application ID from URL
if (!isset($_GET['application_id'])) {
    header("Location: recruiter_dashboard.php");
    exit();
}

$application_id = $_GET['application_id'];
$recruiter_id = $_SESSION['user_id'];

// Get application details and verify the recruiter owns the job
$query = "SELECT a.*, j.recruiter_id, u.name as applicant_name, j.title as job_title
          FROM applications a
          JOIN jobs j ON a.job_id = j.id
          JOIN users u ON a.user_id = u.id
          WHERE a.id = ? AND j.recruiter_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $application_id, $recruiter_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: recruiter_dashboard.php");
    exit();
}

$application = $result->fetch_assoc();

// Check if resume exists
if (empty($application['resume_path']) || !file_exists($application['resume_path'])) {
    die("Resume file not found.");
}

// Get file information
$file_path = $application['resume_path'];
$file_name = basename($file_path);
$file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Set appropriate headers based on file type
switch ($file_extension) {
    case 'pdf':
        header('Content-Type: application/pdf');
        break;
    case 'doc':
        header('Content-Type: application/msword');
        break;
    case 'docx':
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        break;
    default:
        die("Unsupported file type.");
}

// Set download headers
header('Content-Disposition: inline; filename="' . $file_name . '"');
header('Content-Length: ' . filesize($file_path));

// Output the file
readfile($file_path);
exit; 