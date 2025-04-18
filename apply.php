<?php
include 'db.php';
session_start();

// Check if user is logged in and is a job seeker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    header("Location: login.php");
    exit();
}

// Get job details
if (!isset($_GET['job_id'])) {
    header("Location: jobs.php");
    exit();
}

$job_id = $_GET['job_id'];
$user_id = $_SESSION['user_id'];

// Get job details
$job_query = "SELECT j.*, u.name as recruiter_name 
              FROM jobs j 
              JOIN users u ON j.recruiter_id = u.id 
              WHERE j.id = ?";
$job_stmt = $conn->prepare($job_query);
$job_stmt->bind_param("i", $job_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();

if ($job_result->num_rows === 0) {
    header("Location: jobs.php");
    exit();
}

$job = $job_result->fetch_assoc();

// Handle form submission
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if already applied
    $check_query = "SELECT id FROM applications WHERE user_id = ? AND job_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $user_id, $job_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $error = "You have already applied for this job.";
    } else {
        // Check if resume was uploaded
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = "Please upload your resume to apply for this job.";
        } else {
            // Debug file upload
            error_log("File upload attempted. File info: " . print_r($_FILES['resume'], true));
            
            // Handle resume upload
            $resume_path = null;
            if ($_FILES['resume']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $file_type = $_FILES['resume']['type'];
                
                error_log("File type: " . $file_type);
                
                if (!in_array($file_type, $allowed_types)) {
                    $error = "Only PDF and Word documents are allowed.";
                    error_log("Invalid file type: " . $file_type);
                } else {
                    $upload_dir = 'uploads/resumes/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0777, true)) {
                            $error = "Failed to create upload directory.";
                            error_log("Failed to create directory: " . $upload_dir);
                        }
                    }
                    
                    if (empty($error)) {
                        $file_extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
                        $resume_filename = uniqid() . '_' . $_SESSION['user_id'] . '.' . $file_extension;
                        $resume_path = $upload_dir . $resume_filename;
                        
                        error_log("Attempting to move file to: " . $resume_path);
                        
                        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
                            $error = "Failed to upload resume. Please try again.";
                            error_log("Failed to move uploaded file. Error: " . error_get_last()['message']);
                        } else {
                            error_log("File uploaded successfully to: " . $resume_path);
                        }
                    }
                }
            } else {
                $error = "Error uploading file. Error code: " . $_FILES['resume']['error'];
                error_log("File upload error: " . $_FILES['resume']['error']);
            }

            if (empty($error)) {
                // Insert application
                $cover_letter = $_POST['cover_letter'] ?? '';
                $status = 'pending';
                
                error_log("Attempting to insert application with resume_path: " . $resume_path);
                
                $insert_query = "INSERT INTO applications (user_id, job_id, resume_path, cover_letter, status) 
                                VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iisss", $user_id, $job_id, $resume_path, $cover_letter, $status);
                
                if ($insert_stmt->execute()) {
                    $success = "Your application has been submitted successfully!";
                    error_log("Application submitted successfully");
                } else {
                    $error = "Failed to submit application. Please try again.";
                    error_log("Failed to execute insert query: " . $conn->error);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Job - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4A3AFF;
            --secondary-color: #6C63FF;
            --accent-color: #FFD93D;
            --text-color: #2D3748;
            --light-bg: #F8FAFC;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f8f9fe;
            min-height: 100vh;
        }

        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        .back-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            color: var(--secondary-color);
        }

        .application-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin: 2rem 0;
        }

        .job-header {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .job-company {
            color: var(--primary-color);
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .job-location {
            color: #64748B;
            font-size: 0.9rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(74, 58, 255, 0.1);
        }

        .resume-upload {
            border: 2px dashed #E2E8F0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .resume-upload:hover {
            border-color: var(--primary-color);
            background: rgba(74, 58, 255, 0.02);
        }

        .resume-upload i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .btn-submit {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .alert {
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-briefcase me-2"></i>Job Portal
            </a>
            <a href="jobs.php" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i>Back to Jobs
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="application-container">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="job-header">
                <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
                <div class="job-company">
                    <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company']); ?>
                </div>
                <div class="job-location">
                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="form-label">Resume</label>
                    <div class="resume-upload" onclick="document.getElementById('resume').click()">
                        <input type="file" id="resume" name="resume" class="d-none" accept=".pdf,.doc,.docx">
                        <i class="fas fa-file-upload"></i>
                        <p class="mb-0">Click to upload your resume</p>
                        <small class="text-muted">PDF or Word documents only</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Cover Letter</label>
                    <textarea class="form-control" name="cover_letter" rows="5" 
                              placeholder="Write a cover letter explaining why you're a good fit for this position..."></textarea>
                </div>

                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show selected file name
        document.getElementById('resume').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.resume-upload p').textContent = fileName;
            }
        });
    </script>
</body>
</html>