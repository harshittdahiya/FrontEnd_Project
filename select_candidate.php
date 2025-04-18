<?php
include 'db.php';
session_start();

if ($_SESSION['role'] !== 'recruiter') {
    die("Unauthorized.");
}

$app_id = $_GET['id'];
$status = $_GET['status'];

$stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $app_id);
$stmt->execute();

header("Location: view_applicants.php?job_id={$_SESSION['job_id']}");
?>