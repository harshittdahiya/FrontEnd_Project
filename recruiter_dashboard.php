<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'recruiter') {
    header("Location: login.php");
    exit();
}

$recruiter_id = $_SESSION['user_id'];
$recruiter_name = $_SESSION['name'];

// Get posted jobs count
$jobs_stmt = $conn->prepare("SELECT COUNT(*) as total_jobs FROM jobs WHERE recruiter_id = ?");
$jobs_stmt->bind_param("i", $recruiter_id);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();
$total_jobs = $jobs_result->fetch_assoc()['total_jobs'];

// Get total applications
$apps_stmt = $conn->prepare("SELECT COUNT(*) as total_apps FROM applications a 
                            JOIN jobs j ON a.job_id = j.id 
                            WHERE j.recruiter_id = ?");
$apps_stmt->bind_param("i", $recruiter_id);
$apps_stmt->execute();
$apps_result = $apps_stmt->get_result();
$total_apps = $apps_result->fetch_assoc()['total_apps'];

// Get recent jobs
$recent_jobs_stmt = $conn->prepare("SELECT id, title, company, location, 
                                   (SELECT COUNT(*) FROM applications WHERE job_id = jobs.id) as applicants 
                                   FROM jobs WHERE recruiter_id = ? ORDER BY id DESC LIMIT 5");
$recent_jobs_stmt->bind_param("i", $recruiter_id);
$recent_jobs_stmt->execute();
$recent_jobs = $recent_jobs_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruiter Dashboard - Job Portal</title>
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

        .stats-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .stats-icon.jobs {
            background: rgba(74, 58, 255, 0.1);
            color: var(--primary-color);
        }

        .stats-icon.apps {
            background: rgba(255, 211, 61, 0.1);
            color: var(--accent-color);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            color: #64748B;
            font-size: 0.9rem;
        }

        .recent-jobs {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .job-item {
            padding: 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .job-item:hover {
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

        .job-applicants {
            background: rgba(74, 58, 255, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .btn-post-job {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-post-job:hover {
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
                <span class="welcome-text me-3">Welcome, <?php echo htmlspecialchars($recruiter_name); ?></span>
                <a href="logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon jobs">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stats-number"><?php echo $total_jobs; ?></div>
                    <div class="stats-label">Posted Jobs</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stats-card">
                    <div class="stats-icon apps">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?php echo $total_apps; ?></div>
                    <div class="stats-label">Total Applications</div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">Recent Jobs</h4>
            <a href="post_job.php" class="btn btn-post-job">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>

        <div class="recent-jobs">
            <?php while ($job = $recent_jobs->fetch_assoc()): ?>
                <a href="view_applicants.php?job_id=<?php echo $job['id']; ?>" class="text-decoration-none">
                    <div class="job-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
                            </div>
                            <div class="job-applicants">
                                <?php echo $job['applicants']; ?> applicants
                            </div>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>