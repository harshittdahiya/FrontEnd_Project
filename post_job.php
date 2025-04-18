<?php
include 'db.php';
session_start();

if ($_SESSION['role'] !== 'recruiter') {
    die("Unauthorized.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $company = $_POST['company'];
    $location = $_POST['location'];
    $recruiter_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO jobs (recruiter_id, title, description, company, location) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $recruiter_id, $title, $description, $company, $location);
    
    if ($stmt->execute()) {
        $success = "Job posted successfully.";
    } else {
        $error = "Error posting job.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post a Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .job-form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-title {
            color: #2c3e50;
            margin-bottom: 25px;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0,123,255,0.3);
        }
        .btn-submit {
            padding: 10px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="job-form-container">
            <h2 class="form-title">Post a New Job</h2>
            
            <?php if (isset($success)): ?>
                <div class="message alert alert-success"><?php echo $success; ?></div>
            <?php elseif (isset($error)): ?>
                <div class="message alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="title" class="form-label">Job Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter job title" required>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Job Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter job description" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="company" class="form-label">Company Name</label>
                    <input type="text" class="form-control" id="company" name="company" placeholder="Enter company name" required>
                </div>

                <div class="mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" placeholder="Enter location" required>
                </div>

                <button type="submit" class="btn btn-primary btn-submit w-100">Post Job</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>