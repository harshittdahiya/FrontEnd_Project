<?php
session_start();
include 'db.php'; // Ensure this connects to the database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role']; // This will be either 'seeker' or 'recruiter'

    // Validate role
    if ($role !== 'seeker' && $role !== 'recruiter') {
        $error = "Invalid role selected!";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered!";
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $role);

            if ($stmt->execute()) {
                // Get the newly created user's ID
                $user_id = $stmt->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;
                
                // Redirect to appropriate dashboard
                if ($role == 'recruiter') {
                    header("Location: recruiter_dashboard.php");
                } else {
                    header("Location: candidate_dashboard.php");
                }
                exit();
            } else {
                $error = "Signup failed: " . $conn->error;
            }
        }
    }
}

// Get role from URL parameter if present
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Job Portal</title>
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
            display: flex;
            align-items: center;
            padding: 2rem 0;
            position: relative;
        }

        .back-to-home {
            position: fixed;
            top: 2rem;
            left: 2rem;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-to-home:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .back-to-home i {
            font-size: 0.9rem;
        }

        .back-to-home span {
            font-size: 0.95rem;
            background: linear-gradient(to right, var(--text-color), var(--primary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            transition: all 0.3s ease;
        }

        .back-to-home:hover span {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @media (max-width: 768px) {
            .back-to-home-wrapper {
                padding: 1.5rem;
            }
            
            .back-to-home {
                padding: 0.75rem 1.25rem;
            }
        }

        .signup-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
        }

        .row {
            margin: 0;
        }

        .signup-image {
            background: linear-gradient(45deg, rgba(74, 58, 255, 0.05) 0%, rgba(108, 99, 255, 0.05) 100%);
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .signup-image img {
            width: 100%;
            max-width: 480px;
            height: auto;
            object-fit: contain;
            border-radius: 20px;
        }

        .signup-form {
            padding: 3rem;
        }

        .brand {
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-logo {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        .brand-name {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        h2 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #64748B;
            margin-bottom: 2rem;
        }

        .role-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-option {
            flex: 1;
            padding: 1.5rem;
            border: 2px solid #E2E8F0;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }

        .role-option:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(74, 58, 255, 0.1);
        }

        .role-option.selected {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 5px 15px rgba(74, 58, 255, 0.1);
        }

        .role-option i {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .role-option h5 {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .role-option p {
            font-size: 0.875rem;
            color: #64748B;
            margin: 0;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(74, 58, 255, 0.1);
            background: white;
        }

        .form-check {
            padding-left: 1.8rem;
        }

        .form-check-input {
            width: 1.2rem;
            height: 1.2rem;
            margin-left: -1.8rem;
            border: 2px solid #CBD5E1;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-signup {
            background: var(--primary-color);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            color: white;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }

        .btn-signup:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(74, 58, 255, 0.2);
        }

        .divider {
            text-align: center;
            position: relative;
            margin: 2rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 70px);
            height: 1px;
            background: #E2E8F0;
        }

        .divider::before {
            left: 0;
        }

        .divider::after {
            right: 0;
        }

        .divider span {
            background: white;
            padding: 0 1.5rem;
            color: #64748B;
            font-size: 0.875rem;
            position: relative;
            z-index: 1;
        }

        .social-login {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: 2px solid #E2E8F0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748B;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .social-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: #64748B;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 991.98px) {
            .signup-image {
                display: none;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-to-home">
        <i class="fas fa-arrow-left"></i>
        <span>Back to Home</span>
    </a>

    <div class="container">
        <div class="signup-container">
            <div class="row">
                <!-- Swapped columns: Image on left, form on right -->
                <div class="col-lg-6">
                    <div class="signup-image">
                        <img src="https://img.freepik.com/free-vector/recruitment-process-concept-illustration_114360-5146.jpg" alt="Sign up illustration">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="signup-form">
                        <div class="brand">
                            <div class="brand-logo">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="brand-name">Job Portal</div>
                        </div>
                        
                        <h2>Create your account</h2>
                        <p class="subtitle">Join us and find your dream job</p>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="signup.php" method="POST">
                            <div class="role-selector">
                                <div class="role-option <?php echo $selected_role == 'seeker' ? 'selected' : ''; ?>" onclick="selectRole('seeker')">
                                    <i class="fas fa-user-graduate"></i>
                                    <h5>Job Seeker</h5>
                                    <p>Looking for opportunities</p>
                                    <input type="radio" name="role" value="seeker" <?php echo $selected_role == 'seeker' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                                <div class="role-option <?php echo $selected_role == 'recruiter' ? 'selected' : ''; ?>" onclick="selectRole('recruiter')">
                                    <i class="fas fa-building"></i>
                                    <h5>Recruiter</h5>
                                    <p>Post jobs and hire</p>
                                    <input type="radio" name="role" value="recruiter" <?php echo $selected_role == 'recruiter' ? 'checked' : ''; ?> style="display: none;">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create a password" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" style="color: var(--primary-color);">Terms of Service</a> and <a href="#" style="color: var(--primary-color);">Privacy Policy</a>
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-signup">Create Account</button>
                        </form>
                        
                        <div class="divider">
                            <span>or sign up with</span>
                        </div>
                        
                        <div class="social-login">
                            <a href="#" class="social-btn">
                                <i class="fab fa-google"></i>
                            </a>
                            <a href="#" class="social-btn">
                                <i class="fab fa-apple"></i>
                            </a>
                            <a href="#" class="social-btn">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        </div>
                        
                        <div class="login-link">
                            Already have an account? <a href="login.php">Sign in</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectRole(role) {
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            document.querySelector(`input[value="${role}"]`).checked = true;
        }

        // Select role based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const role = new URLSearchParams(window.location.search).get('role');
            if (role) {
                selectRole(role);
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>