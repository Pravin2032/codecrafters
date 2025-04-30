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

    // Collect and sanitize form data
    $fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $dateOfBirth = mysqli_real_escape_string($conn, $_POST['dateOfBirth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirmPassword']);

    // Validate form
    $error = '';
    if (empty($fullName) || empty($email) || empty($phone) || empty($dateOfBirth) || empty($gender) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    }

    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();
    if ($result->num_rows > 0) {
        $error = 'Email address already registered';
    }
    $checkEmail->close();

    if (empty($error)) {
     
        
 // Directly store the plain text password (not hashed)
        // Prepare SQL query to insert user into the database
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, date_of_birth, gender, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $fullName, $email, $phone, $dateOfBirth, $gender, $password);
        
        // Execute the query
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Registration successful! You can now log in.';
            // Redirect to login page
            header('Location: login.php');
            exit();
        } else {
            $_SESSION['error'] = 'Registration failed. Please try again. Error: ' . $conn->error;
        }

        // Close the statement
        $stmt->close();
    } else {
        $_SESSION['error'] = $error;
    }

    // Close the database connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create an Account | CodeCrafters</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #6366f1;
      --primary-hover: #4f46e5;
      --background-color: #f9fafb;
      --form-bg: #ffffff;
      --text-color: #1f2937;
      --border-color: #d1d5db;
      --success-color: #10b981;
      --error-color: #ef4444;
      --box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    }

    body {
      background-color: var(--background-color);
      color: var(--text-color);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .container {
      width: 100%;
      max-width: 500px;
      background-color: var(--form-bg);
      border-radius: 1rem;
      box-shadow: var(--box-shadow);
      position: relative;
      overflow: hidden;
    }

    .header {
      padding: 1.5rem 2rem;
      position: relative;
      border-bottom: 1px solid var(--border-color);
      background-color: #f3f4f6;
    }

    .header h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--primary-color);
      text-align: center;
      margin-top: 0.75rem;
    }

    .header .logo {
      text-align: center;
      margin-bottom: 0.5rem;
      font-size: 1.75rem;
      color: var(--primary-color);
    }

    .close-btn {
      position: absolute;
      right: 1.25rem;
      top: 1.25rem;
      height: 2rem;
      width: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      background-color: #e5e7eb;
      color: #4b5563;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .close-btn:hover {
      background-color: #d1d5db;
      color: #1f2937;
    }

    .alert {
      padding: 0.75rem 1rem;
      border-radius: 0.375rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
    }

    .alert.error {
      background-color: #fee2e2;
      border-left: 4px solid var(--error-color);
      color: #b91c1c;
    }

    .alert.success {
      background-color: #d1fae5;
      border-left: 4px solid var(--success-color);
      color: #065f46;
    }

    .alert i {
      margin-right: 0.5rem;
      font-size: 1rem;
    }

    .form-body {
      padding: 1.5rem 2rem 2rem;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: #4b5563;
    }

    .form-control {
      display: block;
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      line-height: 1.5;
      color: #1f2937;
      background-color: #fff;
      background-clip: padding-box;
      border: 1px solid var(--border-color);
      border-radius: 0.5rem;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
      border-color: var(--primary-color);
      outline: 0;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
    }

    .form-select {
      display: block;
      width: 100%;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      font-weight: 400;
      line-height: 1.5;
      color: #1f2937;
      background-color: #fff;
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      background-size: 1.5em 1.5em;
      border: 1px solid var(--border-color);
      border-radius: 0.5rem;
      -webkit-appearance: none;
      -moz-appearance: none;
      appearance: none;
    }

    .form-select:focus {
      border-color: var(--primary-color);
      outline: 0;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
    }

    .btn {
      display: block;
      width: 100%;
      padding: 0.875rem 1.25rem;
      font-size: 1rem;
      font-weight: 500;
      line-height: 1.25;
      text-align: center;
      white-space: nowrap;
      vertical-align: middle;
      cursor: pointer;
      user-select: none;
      border: none;
      border-radius: 0.5rem;
      transition: all 0.15s ease-in-out;
    }

    .btn-primary {
      color: #fff;
      background-color: var(--primary-color);
      box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
    }

    .btn-primary:focus {
      outline: none;
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5);
    }

    .form-footer {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.875rem;
      color: #6b7280;
    }

    .form-footer a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
    }

    .form-footer a:hover {
      color: var(--primary-hover);
      text-decoration: underline;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin-right: -0.5rem;
      margin-left: -0.5rem;
    }

    .form-col {
      flex: 1 0 100%;
      padding-right: 0.5rem;
      padding-left: 0.5rem;
    }

    @media (min-width: 640px) {
      .form-col-6 {
        flex: 0 0 50%;
        max-width: 50%;
      }
    }

    /* Input with icons */
    .input-group {
      position: relative;
    }

    .input-icon {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      left: 1rem;
      color: #9ca3af;
    }

    .input-with-icon {
      padding-left: 2.75rem;
    }

    /* Password visibility toggle */
    .password-toggle {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      right: 1rem;
      color: #9ca3af;
      cursor: pointer;
    }

    /* Animation */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .container {
      animation: fadeIn 0.4s ease-out;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="close-btn" onclick="window.location.href='index.php'">
        <i class="fas fa-times"></i>
      </div>
      <div class="logo">
        <i class="fas fa-code"></i>
      </div>
      <h1>Join CodeCrafters</h1>
    </div>
    
    <form method="POST" class="form-body">
      <?php if (isset($_SESSION['error'])): ?>
      <div class="alert error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
      </div>
      <?php endif; ?>

      <?php if (isset($_SESSION['success'])): ?>
      <div class="alert success">
        <i class="fas fa-check-circle"></i>
        <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
      </div>
      <?php endif; ?>

      <div class="form-group">
        <label for="fullName" class="form-label">Full Name</label>
        <div class="input-group">
          <i class="fas fa-user input-icon"></i>
          <input id="fullName" name="fullName" type="text" value="<?php echo htmlspecialchars($fullName ?? '', ENT_QUOTES); ?>" class="form-control input-with-icon" placeholder="Enter your full name">
        </div>
      </div>

      <div class="form-row">
        <div class="form-col form-col-6">
          <div class="form-group">
            <label for="email" class="form-label">Email</label>
            <div class="input-group">
              <i class="fas fa-envelope input-icon"></i>
              <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES); ?>" class="form-control input-with-icon" placeholder="your@email.com">
            </div>
          </div>
        </div>
        <div class="form-col form-col-6">
          <div class="form-group">
            <label for="phone" class="form-label">Phone</label>
            <div class="input-group">
              <i class="fas fa-phone input-icon"></i>
              <input id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES); ?>" class="form-control input-with-icon" placeholder="Your phone number">
            </div>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-col form-col-6">
          <div class="form-group">
            <label for="dateOfBirth" class="form-label">Date of Birth</label>
            <div class="input-group">
              <i class="fas fa-calendar input-icon"></i>
              <input id="dateOfBirth" name="dateOfBirth" type="date" value="<?php echo htmlspecialchars($dateOfBirth ?? '', ENT_QUOTES); ?>" class="form-control input-with-icon">
            </div>
          </div>
        </div>
        <div class="form-col form-col-6">
          <div class="form-group">
            <label for="gender" class="form-label">Gender</label>
            <select id="gender" name="gender" class="form-select">
              <option value="">Select gender</option>
              <option value="male" <?php echo ($gender ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
              <option value="female" <?php echo ($gender ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
              <option value="other" <?php echo ($gender ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
            </select>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input id="password" name="password" type="password" class="form-control input-with-icon" placeholder="Create a strong password">
          <span class="password-toggle" onclick="togglePassword('password')">
            <i class="fas fa-eye"></i>
          </span>
        </div>
      </div>

      <div class="form-group">
        <label for="confirmPassword" class="form-label">Confirm Password</label>
        <div class="input-group">
          <i class="fas fa-lock input-icon"></i>
          <input id="confirmPassword" name="confirmPassword" type="password" class="form-control input-with-icon" placeholder="Confirm your password">
          <span class="password-toggle" onclick="togglePassword('confirmPassword')">
            <i class="fas fa-eye"></i>
          </span>
        </div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-user-plus"></i> Create Account
      </button>

      <div class="form-footer">
        Already have an account? <a href="login.php">Sign in here</a>
      </div>
    </form>
  </div>

  <script>
    function togglePassword(fieldId) {
      const passwordField = document.getElementById(fieldId);
      const icon = event.currentTarget.querySelector('i');
      
      if (passwordField.type === "password") {
        passwordField.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
      } else {
        passwordField.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
      }
    }
  </script>
</body>
</html>