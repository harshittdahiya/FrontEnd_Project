<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seeker') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

// Handle resume upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["resume"])) {
    $target_dir = "uploads/resumes/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES["resume"]["name"], PATHINFO_EXTENSION));
    $new_filename = $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["resume"]["tmp_name"], $target_file)) {
        // Update user's resume path in database
        $stmt = $conn->prepare("UPDATE users SET resume_path = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $user_id);
        $stmt->execute();
        $success = "Resume uploaded successfully!";
    } else {
        $error = "Sorry, there was an error uploading your resume.";
    }
}

// Get user's resume path
$resume_stmt = $conn->prepare("SELECT resume_path FROM users WHERE id = ?");
$resume_stmt->bind_param("i", $user_id);
$resume_stmt->execute();
$resume_result = $resume_stmt->get_result();
$resume_path = $resume_result->fetch_assoc()['resume_path'];

// Get job applications with status
$stmt = $conn->prepare("SELECT jobs.title, jobs.company, applications.status, applications.created_at 
                        FROM applications 
                        JOIN jobs ON applications.job_id = jobs.id 
                        WHERE applications.user_id = ? 
                        ORDER BY applications.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard - Job Portal</title>
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

        .welcome-text {
            color: var(--text-color);
            font-weight: 500;
        }

        .btn-logout {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .resume-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
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

        .resume-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .resume-text {
            color: #64748B;
            margin-bottom: 1rem;
        }

        .resume-file {
            color: var(--primary-color);
            font-weight: 500;
            word-break: break-all;
        }

        .applications-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .application-item {
            padding: 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .application-item:hover {
            background: var(--light-bg);
        }

        .job-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .job-company {
            color: #64748B;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(100, 116, 139, 0.1);
            color: #64748B;
        }

        .status-selected {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
        }

        .btn-apply {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-apply:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-briefcase me-2"></i>Job Portal
            </a>
            <div class="d-flex align-items-center">
                <span class="welcome-text me-3">Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="resume-card">
            <h4 class="mb-4">Your Resume</h4>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="candidate_dashboard.php" method="POST" enctype="multipart/form-data">
                <div class="resume-upload" onclick="document.getElementById('resume').click()">
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" style="display: none;" onchange="this.form.submit()">
                    <div class="resume-icon">
                        <i class="fas fa-file-upload"></i>
                    </div>
                    <div class="resume-text">
                        <?php if ($resume_path): ?>
                            <div class="resume-file">
                                <i class="fas fa-file-alt me-2"></i>
                                <?php echo basename($resume_path); ?>
                            </div>
                            <small>Click to upload a new resume</small>
                        <?php else: ?>
                            Drag and drop your resume here or click to browse
                            <br>
                            <small>Supported formats: PDF, DOC, DOCX</small>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="applications-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Your Applications</h4>
                <a href="jobs.php" class="btn btn-apply">
                    <i class="fas fa-search me-2"></i>Find Jobs
                </a>
            </div>

            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="application-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="job-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <div class="job-company"><?php echo htmlspecialchars($row['company']); ?></div>
                            <small class="text-muted">Applied on <?php echo date('M d, Y', strtotime($row['created_at'])); ?></small>
                        </div>
                        <div class="status-badge status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
