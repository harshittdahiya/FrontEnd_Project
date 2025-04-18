<?php
include 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'recruiter') {
            header("Location: recruiter_dashboard.php");
        } else if ($user['role'] == 'seeker') {
            header("Location: candidate_dashboard.php");
        } else {
            $error = "Invalid role detected!";
        }
        exit();
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Job Portal</title>
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

        .login-container {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
        }

        .row {
            margin: 0;
        }

        .login-image {
            background: linear-gradient(45deg, rgba(74, 58, 255, 0.05) 0%, rgba(108, 99, 255, 0.05) 100%);
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            height: 100%;
        }

        .login-image img {
            width: 100%;
            max-width: 480px;
            height: auto;
            object-fit: contain;
            border-radius: 20px;
        }

        .login-form {
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

        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: var(--secondary-color);
        }

        .btn-login {
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

        .btn-login:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(74, 58, 255, 0.2);
        }

        .social-login {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
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
            text-decoration: none;

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
    transform: translateY(-50%);
    width: calc(50% - 60px); /* adjusted for more space around text */
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
            padding: 0 1rem;
            color: #64748B;
            font-size: 0.875rem;
        }

        .signup-link {
            text-align: center;
            margin-top: 2rem;
            color: #64748B;
        }

        .signup-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            color: var(--secondary-color);
        }

        @media (max-width: 991.98px) {
            .login-image {
                display: none;
            }
        }

        .back-to-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--primary-color);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
    </style>
</head>
<body>
    <div class="container position-relative">
        <a href="index.php" class="back-to-home">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>

        <div class="login-container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="login-image">
                        <img src="https://img.freepik.com/free-vector/job-interview-conversation_74855-7566.jpg" alt="Login illustration">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="login-form">
                        <div class="brand">
                            <div class="brand-logo">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="brand-name">Job Portal</div>
                        </div>
                        
                        <h2>Welcome back</h2>
                        <p class="subtitle">Please enter your details to sign in</p>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form action="login.php" method="POST">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Remember me</label>
                                </div>
                                <a href="#" class="forgot-password">Forgot password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-login">Sign in</button>
                            
                            <div class="divider">
                                <span>or continue with</span>
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
                            
                            <div class="signup-link">
                                Don't have an account? <a href="signup.php">Sign up for free</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>