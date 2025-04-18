<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$company = isset($_GET['company']) ? $_GET['company'] : '';

// Build query
$query = "SELECT j.*, u.name as recruiter_name 
          FROM jobs j 
          JOIN users u ON j.recruiter_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $location_param = "%$location%";
    $params[] = $location_param;
    $types .= "s";
}

if (!empty($company)) {
    $query .= " AND j.company LIKE ?";
    $company_param = "%$company%";
    $params[] = $company_param;
    $types .= "s";
}

$query .= " ORDER BY j.id DESC";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// Get unique locations and companies for filters
$locations_stmt = $conn->prepare("SELECT DISTINCT location FROM jobs WHERE location IS NOT NULL");
$locations_stmt->execute();
$locations = $locations_stmt->get_result();

$companies_stmt = $conn->prepare("SELECT DISTINCT company FROM jobs WHERE company IS NOT NULL");
$companies_stmt->execute();
$companies = $companies_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Jobs - Job Portal</title>
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

        .search-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .search-input {
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }

        .search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(74, 58, 255, 0.1);
            background: white;
        }

        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .filter-title {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .filter-item {
            padding: 0.5rem 0;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #64748B;
        }

        .filter-item:hover {
            color: var(--primary-color);
            transform: translateX(5px);
        }

        .filter-item.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        .job-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .job-title {
            font-size: 1.25rem;
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
            margin-bottom: 1rem;
        }

        .job-description {
            color: #64748B;
            font-size: 0.9rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-apply {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-apply:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .no-jobs {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .no-jobs-icon {
            font-size: 3rem;
            color: #CBD5E1;
            margin-bottom: 1rem;
        }

        .no-jobs-text {
            color: #64748B;
            font-size: 1.1rem;
        }

        .search-icon {
            color: #64748B;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper input {
            padding-left: 2.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-briefcase me-2"></i>Job Portal
            </a>
            <a href="candidate_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="search-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control search-input" name="search" 
                               placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select search-input" name="location">
                        <option value="">All Locations</option>
                        <?php while ($loc = $locations->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($loc['location']); ?>"
                                    <?php echo $location == $loc['location'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc['location']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select search-input" name="company">
                        <option value="">All Companies</option>
                        <?php while ($comp = $companies->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($comp['company']); ?>"
                                    <?php echo $company == $comp['company'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($comp['company']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-apply w-100">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="filter-section">
                    <h5 class="filter-title">Quick Filters</h5>
                    <div class="filter-item active">
                        <i class="fas fa-briefcase me-2"></i>All Jobs
                    </div>
                    <div class="filter-item">
                        <i class="fas fa-clock me-2"></i>Recent Jobs
                    </div>
                    <div class="filter-item">
                        <i class="fas fa-star me-2"></i>Featured Jobs
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($job = $result->fetch_assoc()): ?>
                        <div class="job-card">
                            <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                            <div class="job-company">
                                <i class="fas fa-building me-2"></i><?php echo htmlspecialchars($job['company']); ?>
                            </div>
                            <div class="job-location">
                                <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                            </div>
                            <p class="job-description"><?php echo htmlspecialchars($job['description']); ?></p>
                            <a href="apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-apply">
                                <i class="fas fa-paper-plane me-2"></i>Apply Now
                            </a>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-jobs">
                        <div class="no-jobs-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="no-jobs-text">No jobs found matching your criteria</h4>
                        <p class="text-muted">Try adjusting your search filters</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add active class to clicked filter
        document.querySelectorAll('.filter-item').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.filter-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>