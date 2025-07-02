<?php
session_start();
include './config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matriculation_number = $_POST['matriculation_number'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM students WHERE matriculation_number = ?");
    $stmt->bind_param("s", $matriculation_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['id'];
            header("Location: index.php");
            exit();
        } else {
            echo "Invalid password";
        }
    } else {
        echo "No student found with that matriculation number";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - UB Student</title>
    <style>
        /* Reset & Box-Sizing */
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .left-panel, .right-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .left-panel {
            background: url('./assets/imgs/ub_login.png') no-repeat center/cover;
            color: #ffffff;
            padding: 40px;
            justify-content: flex-end;
        }
        .left-panel .welcome-content h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .left-panel .welcome-content p {
            font-size: 1rem;
            opacity: 0.9;
        }
        .right-panel {
            padding: 40px;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 380px;
            margin: 0 auto;
        }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 32px;
            gap: 12px;
        }
        .logo-icon img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1a1a1a;
        }
        .form-header h2 {
            font-size: 1.75rem;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
            text-align: center;
        }
        .form-header p {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 32px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }
        .input-wrapper {
            position: relative;
        }
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            opacity: 0.7;
        }
        .form-input {
            width: 100%;
            padding: 14px 16px 14px 45px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 1rem;
            background: #ffffff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .form-input:focus {
            outline: none;
            border-color: #4f6ffa;
            box-shadow: 0 0 0 3px rgba(79, 111, 250, 0.1);
        }
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .forgot-link {
            color: #4f6ffa;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .forgot-link:hover {
            text-decoration: underline;
        }
        .signin-btn {
            width: 100%;
            padding: 14px;
            background: #4f6ffa;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s ease;
            margin-top: 16px;
        }
        .signin-btn:hover {
            background: #3b5cfa;
        }
        .support-section, .footer-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 24px;
        }
        .support-section a {
            color: #4f6ffa;
            text-decoration: none;
            font-weight: 500;
        }
        .support-section a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE BREAKPOINTS */
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            .left-panel {
                height: 200px;
                padding: 20px;
                justify-content: center;
                text-align: center;
            }
            .left-panel .welcome-content h1 {
                font-size: 2rem;
            }
            .right-panel {
                padding: 20px;
            }
        }
        @media (max-width: 576px) {
            .left-panel {
                height: 150px;
                padding: 16px;
            }
            .left-panel .welcome-content h1 {
                font-size: 1.5rem;
            }
            .logo-section {
                flex-direction: column;
                gap: 8px;
            }
            .form-header h2 {
                font-size: 1.5rem;
            }
            .form-header p {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="welcome-content">
                <h1>Welcome to the University of Buea</h1>
                <p>A place to be!</p>
            </div>
        </div>
        <div class="right-panel">
            <div class="login-container">
                <div class="logo-section">
                    <div class="logo-icon">
                        <img src="./assets/imgs/ub.jpg" alt="UB Logo">
                    </div>
                    <div class="logo-text">UB Student</div>
                </div>
                <div class="form-header">
                    <h2>Student Login</h2>
                    <p>Please enter your credentials to continue</p>
                </div>
                <form method="post" action="">
                    <div class="form-group">
                        <div class="form-options">
                            <label for="matriculation_number">Matriculation Number</label>
                            <a href="#" class="forgot-link">Forgot password?</a>
                        </div>
                        <div class="input-wrapper">
                            <img src="https://img.icons8.com/fluency-systems-regular/20/user.png" class="input-icon" alt="user">
                            <input type="text" id="matriculation_number" name="matriculation_number" class="form-input" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <img src="https://img.icons8.com/fluency-systems-regular/20/lock.png" class="input-icon" alt="lock">
                            <input type="password" id="password" name="password" class="form-input" required>
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <img src="https://img.icons8.com/fluency-systems-regular/20/visible.png" id="eyeIcon" alt="show">
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="signin-btn">Login</button>
                </form>
                <div class="support-section">
                    Having trouble logging in? <a href="#">Contact support</a>
                </div>
                <div class="footer-text">
                    © 2025 UB Student. All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            const eye = document.getElementById('eyeIcon');
            if (pw.type === 'password') {
                pw.type = 'text';
                eye.src = 'https://img.icons8.com/fluency-systems-regular/20/invisible.png';
                eye.alt = 'hide';
            } else {
                pw.type = 'password';
                eye.src = 'https://img.icons8.com/fluency-systems-regular/20/visible.png';
                eye.alt = 'show';
            }
        }
    </script>
</body>
</html>
