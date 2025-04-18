<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'recruiter') {
    header("Location: login.php");
    exit();
}

$job_id = $_GET['job_id'];
$recruiter_id = $_SESSION['user_id'];

// Get job details
$job_stmt = $conn->prepare("SELECT title, company, location FROM jobs WHERE id = ? AND recruiter_id = ?");
$job_stmt->bind_param("ii", $job_id, $recruiter_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
$job = $job_result->fetch_assoc();

if (!$job) {
    header("Location: recruiter_dashboard.php");
    exit();
}

// Get applicants
$applicants_stmt = $conn->prepare("SELECT a.id, a.status, a.cover_letter, a.created_at,
                                  u.name, u.email, u.resume_path
                                  FROM applications a
                                  JOIN users u ON a.user_id = u.id
                                  WHERE a.job_id = ?
                                  ORDER BY a.created_at DESC");
$applicants_stmt->bind_param("i", $job_id);
$applicants_stmt->execute();
$applicants = $applicants_stmt->get_result();

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['action'] == 'select' ? 'selected' : 'rejected';
    
    $update_stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ? AND job_id = ?");
    $update_stmt->bind_param("sii", $new_status, $application_id, $job_id);
    $update_stmt->execute();
    
    // Refresh the page to show updated status
    header("Location: view_applicants.php?job_id=" . $job_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicants - Job Portal</title>
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

        .job-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .job-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .job-company {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .job-location {
            color: #64748B;
            font-size: 0.9rem;
        }

        .applicant-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .applicant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .applicant-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .applicant-info {
            flex: 1;
        }

        .applicant-name {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .applicant-email {
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

        .cover-letter {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            color: #64748B;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-select {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
            border: none;
        }

        .btn-select:hover {
            background: rgba(16, 185, 129, 0.2);
            transform: translateY(-2px);
        }

        .btn-reject {
            background: rgba(239, 68, 68, 0.1);
            color: #EF4444;
            border: none;
        }

        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: translateY(-2px);
        }

        .btn-resume {
            background: rgba(74, 58, 255, 0.1);
            color: var(--primary-color);
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-resume:hover {
            background: rgba(74, 58, 255, 0.2);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .application-date {
            color: #64748B;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-briefcase me-2"></i>Job Portal
            </a>
            <a href="recruiter_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="job-header">
            <h1 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h1>
            <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
            <div class="job-location">
                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
            </div>
        </div>

        <h4 class="mb-4">Applicants</h4>

        <?php while ($applicant = $applicants->fetch_assoc()): ?>
            <div class="applicant-card">
                <div class="applicant-header">
                    <div class="applicant-info">
                        <h5 class="applicant-name"><?php echo htmlspecialchars($applicant['name']); ?></h5>
                        <p class="applicant-email">
                            <i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($applicant['email']); ?>
                        </p>
                        <p class="application-date">
                            <i class="fas fa-calendar me-2"></i>Applied on <?php echo date('M d, Y', strtotime($applicant['created_at'])); ?>
                        </p>
                        <?php if (!empty($applicant['resume_path'])): ?>
                            <a href="view_resume.php?application_id=<?php echo $applicant['id']; ?>" 
                               class="btn btn-resume" target="_blank">
                                <i class="fas fa-file-alt me-2"></i>View Resume
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="status-badge status-<?php echo $applicant['status']; ?>">
                        <?php echo ucfirst($applicant['status']); ?>
                    </div>
                </div>

                <?php if ($applicant['cover_letter']): ?>
                    <div class="cover-letter">
                        <?php echo nl2br(htmlspecialchars($applicant['cover_letter'])); ?>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center">
                    <?php if ($applicant['status'] == 'pending'): ?>
                        <div class="action-buttons">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="application_id" value="<?php echo $applicant['id']; ?>">
                                <input type="hidden" name="action" value="select">
                                <button type="submit" class="btn btn-action btn-select">
                                    <i class="fas fa-check me-2"></i>Select
                                </button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="application_id" value="<?php echo $applicant['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-action btn-reject">
                                    <i class="fas fa-times me-2"></i>Reject
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>