<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection parameters
    $servername = "localhost";
    $dbUsername = "root";  // Change if needed
    $dbPassword = "";      // Change if needed
    $dbname = "codecrafters";

    // Create database connection
    $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Database Connection Failed: " . $conn->connect_error);
    }

    // Handle login attempt
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if the fields are not empty
    if (!empty($email) && !empty($password)) {
        // Query the database to find the user
        $query = "SELECT * FROM users WHERE email = ? LIMIT 1";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                // Compare the plain text password
                if ($password === $user['password']) {
                    // Start a session for the user
                    $_SESSION['email'] = $user['email'];

                    // Redirect to student dashboard
                    header('Location: student_dashboard.php');
                    exit();
                } else {
                    $error_message = "Incorrect password.";
                }
            } else {
                $error_message = "No student found with that email.";
            }
        } else {
            $error_message = "Database query failed.";
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeCrafters - Student Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #7209b7;
            --accent-color: #f72585;
            --light-bg: #f8f9fa;
            --text-color: #333;
            --card-shadow: 0 10px 30px rgba(0,0,0,0.15);
            --input-shadow: 0 2px 10px rgba(0,0,0,0.05);
            --success-color: #4BB543;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color);
        }
        
        .page-container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            justify-content: center;
        }
        
        .login-container {
            width: 100%;
            max-width: 480px;
            background-color: #fff;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            position: relative;
            transform: translateY(0);
            transition: all 0.3s ease;
        }
        
        .login-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        .login-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            padding: 28px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            z-index: 1;
        }
        
        .login-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .login-form {
            padding: 35px;
        }
        
        .form-floating {
            margin-bottom: 22px;
        }
        
        .form-floating label {
            color: #666;
            font-weight: 500;
            padding-left: 15px;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            height: 58px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
            box-shadow: var(--input-shadow);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #777;
            cursor: pointer;
            z-index: 10;
            transition: color 0.2s ease;
            padding: 8px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .btn-login {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            width: 100%;
            padding: 14px;
            font-weight: 600;
            margin-top: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            font-size: 1.05rem;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            height: 54px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.6s ease;
        }
        
        .btn-login:hover {
            background: linear-gradient(45deg, var(--primary-dark), var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 5px;
            margin-bottom: 20px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            margin-right: 8px;
        }
        
        .forgot-password {
            text-align: right;
            font-size: 0.9rem;
        }
        
        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.2s;
            font-weight: 500;
        }
        
        .forgot-password a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .register-link {
            text-align: center;
            margin-top: 28px;
            padding-top: 25px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.95rem;
        }
        
        .register-link a {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
            margin-left: 5px;
        }
        
        .register-link a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 12px;
            font-size: 0.95rem;
            margin-bottom: 25px;
            padding: 15px;
            display: flex;
            align-items: center;
            border: none;
            background-color: #fee2e2;
            color: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .form-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        
        .brand-icon {
            font-size: 2.2rem;
            margin-right: 12px;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .social-login {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 25px 0;
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 10px;
            border: none;
            background-color: #f8f9fa;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            color: #555;
        }
        
        .social-btn i {
            font-size: 1.2rem;
        }
        
        .social-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .social-btn.google:hover {
            color: #DB4437;
        }
        
        .social-btn.facebook:hover {
            color: #4267B2;
        }
        
        .social-btn.github:hover {
            color: #333;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #888;
            font-size: 0.9rem;
        }
        
        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #eee;
        }
        
        .divider::before {
            margin-right: 15px;
        }
        
        .divider::after {
            margin-left: 15px;
        }
        
        .form-feedback {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: var(--success-color);
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(75, 181, 67, 0.3);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.5s ease;
            display: flex;
            align-items: center;
        }
        
        .form-feedback.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .form-feedback i {
            margin-right: 8px;
        }
        
        /* Animation classes */
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Loading indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(67, 97, 238, 0.3);
            border-radius: 50%;
            border-top: 4px solid var(--primary-color);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 576px) {
            .login-container {
                border-radius: 15px;
            }
            
            .login-header {
                padding: 22px;
            }
            
            .login-form {
                padding: 25px;
            }
            
            .form-floating {
                margin-bottom: 16px;
            }
            
            .form-control {
                height: 52px;
            }
            
            .btn-login {
                height: 52px;
                padding: 12px;
            }
            
            .brand-icon {
                width: 40px;
                height: 40px;
                font-size: 1.8rem;
            }
            
            .login-header h2 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <div class="page-container">
        <div class="login-container fade-in">
            <div class="login-header">
                <div class="form-brand">
                    <div class="brand-icon">
                        <i class="fas fa-code"></i>
                    </div>
                    <h2>CodeCrafters</h2>
                </div>
                <p>Student Login Portal</p>
            </div>
            
            <div class="login-form">
                <?php if (!empty($error_message)): ?>
                    <div class="alert" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Social Login Options -->
                <div class="social-login">
                    <button type="button" class="social-btn google">
                        <i class="fab fa-google"></i>
                    </button>
                    <button type="button" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i>
                    </button>
                    <button type="button" class="social-btn github">
                        <i class="fab fa-github"></i>
                    </button>
                </div>
                
                <div class="divider">or login with email</div>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                    </div>
                    
                    <div class="form-floating mb-2 password-field">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="rememberMe" class="form-check-input">
                            <label for="rememberMe" class="form-check-label">Remember me</label>
                        </div>
                        <div class="forgot-password">
                            <a href="forgot_password.php">Forgot Password?</a>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login" id="loginButton">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
                
                <div class="register-link">
                    New to CodeCrafters?<a href="register.php">Create an account</a>
                </div>
                
                <!-- Success Feedback (hidden by default) -->
                <div class="form-feedback" id="successFeedback">
                    <i class="fas fa-check-circle"></i> Login successful!
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Form validation enhancement
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Simple email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailInput.value)) {
                event.preventDefault();
                showValidationError('Please enter a valid email address.', emailInput);
                return false;
            }
            
            // Simple password validation
            if (passwordInput.value.length < 6) {
                event.preventDefault();
                showValidationError('Password must be at least 6 characters long.', passwordInput);
                return false;
            }
            
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('show');
            
            return true;
        });
        
        // Function to show validation error
        function showValidationError(message, inputElement) {
            // Create error alert if it doesn't exist
            let errorAlert = document.querySelector('.alert');
            if (!errorAlert) {
                errorAlert = document.createElement('div');
                errorAlert.className = 'alert';
                errorAlert.role = 'alert';
                
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-circle';
                errorAlert.appendChild(icon);
                
                const textNode = document.createTextNode(' ' + message);
                errorAlert.appendChild(textNode);
                
                const form = document.querySelector('.login-form');
                form.insertBefore(errorAlert, form.firstChild);
            } else {
                // Update existing alert
                errorAlert.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            }
            
            // Highlight the input
            inputElement.classList.add('is-invalid');
            
            // Add shake animation
            errorAlert.style.animation = 'none';
            setTimeout(function() {
                errorAlert.style.animation = 'shake 0.5s';
            }, 10);
            
            // Focus on the input
            inputElement.focus();
        }
        
        // Add shake animation
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                    20%, 40%, 60%, 80% { transform: translateX(5px); }
                }
            </style>
        `);
        
        // Social login buttons (non-functional in this example)
        document.querySelectorAll('.social-btn').forEach(button => {
            button.addEventListener('click', function() {
                alert('Social login feature is not implemented in this demo.');
            });
        });
        
        // Remember me functionality
        const rememberMe = document.getElementById('rememberMe');
        
        // Check if we have stored credentials
        if (localStorage.getItem('rememberedEmail')) {
            document.getElementById('email').value = localStorage.getItem('rememberedEmail');
            rememberMe.checked = true;
        }
        
        // Save credentials if remember me is checked
        rememberMe.addEventListener('change', function() {
            if (this.checked) {
                const email = document.getElementById('email').value;
                if (email) {
                    localStorage.setItem('rememberedEmail', email);
                }
            } else {
                localStorage.removeItem('rememberedEmail');
            }
        });
    </script>
</body>
</html>