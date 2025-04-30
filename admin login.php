<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Login</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --primary-light: rgba(67, 97, 238, 0.15);
            --secondary-color: #7209b7;
            --text-color: #333;
            --text-light: #777;
            --light-gray: #f8f9fa;
            --border-color: #e0e0e0;
            --success-color: #38b000;
            --error-color: #d90429;
            --bg-gradient-start: #f5f7fa;
            --bg-gradient-end: #c3cfe2;
            --border-radius: 12px;
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background-color: white;
            width: 420px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            position: relative;
            background-color: var(--primary-color);
            padding: 35px 30px;
            text-align: center;
            overflow: hidden;
        }
        
        .login-header::before, 
        .login-header::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: 0;
        }
        
        .login-header::before {
            top: -150px;
            left: -150px;
        }
        
        .login-header::after {
            bottom: -150px;
            right: -150px;
        }
        
        .login-header h1 {
            position: relative;
            color: white;
            font-size: 26px;
            margin: 0;
            letter-spacing: 0.5px;
            font-weight: 600;
            z-index: 1;
        }
        
        .login-logo {
            position: relative;
            font-size: 48px;
            color: white;
            margin-bottom: 15px;
            z-index: 1;
        }
        
        .login-body {
            padding: 35px;
        }
        
        .notification {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 22px;
            display: none;
            animation: fadeIn 0.3s;
            font-size: 14px;
            align-items: center;
        }
        
        .notification i {
            margin-right: 10px;
            font-size: 16px;
        }
        
        .alert {
            background-color: rgba(217, 4, 41, 0.1);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }
        
        .success {
            background-color: rgba(56, 176, 0, 0.1);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }
        
        .form-group {
            margin-bottom: 24px;
            position: relative;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 40px;
            color: var(--text-light);
            transition: var(--transition);
        }
        
        .form-group.focused i {
            color: var(--primary-color);
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 600;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 15px;
            transition: var(--transition);
            background-color: var(--light-gray);
        }
        
        input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px var(--primary-light);
            background-color: white;
        }
        
        .error {
            color: var(--error-color);
            font-size: 13px;
            margin-top: 6px;
            display: none;
            animation: shake 0.5s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-me input {
            width: 16px;
            height: 16px;
            margin-right: 8px;
            accent-color: var(--primary-color);
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }
        
        .forgot-password:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        button {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 6px rgba(67, 97, 238, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        button:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(67, 97, 238, 0.25);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button:disabled {
            opacity: 0.7;
            transform: none;
            cursor: not-allowed;
        }
        
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1>Admin Dashboard</h1>
        </div>
        
        <div class="login-body">
            <div id="alertMessage" class="notification alert"></div>
            <div id="successMessage" class="notification success"></div>
            
            <form id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter your email" autocomplete="email">
                    <div id="emailError" class="error"></div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                    <div id="passwordError" class="error"></div>
                </div>

                <div class="remember-forgot">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember" style="display:inline; margin:0; font-weight:normal;">Remember me</label>
                    </div>
                    <a href="index.php" class="forgot-password">Back To Home</a>
                </div>
                
                <button type="submit">
                    <span id="loading" class="loading"></span>
                    <span id="buttonText">Sign In</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');
            const alertMessage = document.getElementById('alertMessage');
            const successMessage = document.getElementById('successMessage');
            const loading = document.getElementById('loading');
            const buttonText = document.getElementById('buttonText');
            
            // Email validation regex pattern
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            // Focus email field on load
            emailInput.focus();
            
            // Add focused class for styling when inputs are focused
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('focused');
                });
            });
            
            loginForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Reset errors
                resetErrors();
                
                // Get form values
                const email = emailInput.value.trim();
                const password = passwordInput.value.trim();
                
                // Validate email
                if (!email) {
                    showError(emailError, 'Email address is required');
                    emailInput.focus();
                    return;
                } else if (!emailPattern.test(email)) {
                    showError(emailError, 'Please enter a valid email address');
                    emailInput.focus();
                    return;
                }
                
                // Validate password
                if (!password) {
                    showError(passwordError, 'Password is required');
                    passwordInput.focus();
                    return;
                }
                
                // If all validations pass, simulate login process
                simulateLogin(email, password);
            });
            
            function resetErrors() {
                emailError.style.display = 'none';
                passwordError.style.display = 'none';
                alertMessage.style.display = 'none';
                successMessage.style.display = 'none';
            }
            
            function showError(element, message) {
                element.textContent = message;
                element.style.display = 'block';
            }
            
            function simulateLogin(email, password) {
                // Show loading state
                loading.style.display = 'inline-block';
                buttonText.textContent = 'Signing in...';
                const loginButton = loginForm.querySelector('button');
                loginButton.disabled = true;
                
                // Simulate network delay
                setTimeout(function() {
                    // Using the credentials provided
                    if (email === 'codecrafters@gmail.com' && password === 'Admin') {
                        // Success case
                        successMessage.innerHTML = '<i class="fas fa-check-circle"></i> Login successful! Redirecting to dashboard...';
                        successMessage.style.display = 'flex';
                        
                        // Simulate redirect
                        setTimeout(function() {
                            window.location.href = "admin dashboard.php"; // In a real app, this would redirect to the actual dashboard
                            alert('Login successful! In a real application, you would be redirected to the admin dashboard.');
                            // Reset form after success
                            loginForm.reset();
                            loading.style.display = 'none';
                            buttonText.textContent = 'Sign In';
                            loginButton.disabled = false;
                        }, 1500);
                    } else {
                        // Failed login
                        alertMessage.innerHTML = '<i class="fas fa-exclamation-circle"></i> Invalid email or password. Please try again.';
                        alertMessage.style.display = 'flex';
                        loading.style.display = 'none';
                        buttonText.textContent = 'Sign In';
                        loginButton.disabled = false;
                    }
                }, 1500);
            }
        });
    </script>
</body>
</html>