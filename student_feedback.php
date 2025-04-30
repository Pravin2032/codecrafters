<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Database connection parameters
$host = "localhost";
$username = "root";
$password = "";
$dbname = "codecrafters";

// Create database connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle logout
if (isset($_GET['logout'])) {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to the same page to refresh the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Check if user is logged in via session
if (isset($_SESSION['user_email'])) {
    // Get the user ID from the database based on email
    try {
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
        $stmt->execute([$_SESSION['user_email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user_id = $user['id'];
            $_SESSION['user_id'] = $user_id; // Store user_id in session
            $_SESSION['user_name'] = $user['full_name']; // Store user_name in session
        } else {
            // Email exists in session but no matching user found
            $error_message = 'User not found. Please log in again.';
            // Clear invalid session data
            unset($_SESSION['user_email']);
            unset($_SESSION['user_id']);
            unset($_SESSION['user_name']);
        }
    } catch(PDOException $e) {
        error_log("Error fetching user: " . $e->getMessage());
        $error_message = "Database error: " . $e->getMessage();
    }
} else if (isset($_POST['user_login'])) {
    // Process login attempt
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (!empty($email) && !empty($password)) {
        try {
            // In a real application, you should verify the password hash
            $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND password = ?");
            $stmt->execute([$email, $password]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Login successful
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $user['full_name'];
                $user_id = $user['id'];
                $success_message = "Login successful!";
            } else {
                // Login failed
                $error_message = "Invalid email or password.";
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error_message = "Database error during login.";
        }
    } else {
        $error_message = "Email and password are required.";
    }
} else {
    // No user is logged in
    $user_id = null;
}

// Process form submission for feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    // Verify user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        $error_message = 'You must be logged in to submit feedback.';
    } else {
        // Make sure user_id is set from session
        $user_id = $_SESSION['user_id'];
        
        // Collect and sanitize input data
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
        $instructor_rating = filter_input(INPUT_POST, 'instructor_rating', FILTER_SANITIZE_NUMBER_INT);
        $content_rating = filter_input(INPUT_POST, 'content_rating', FILTER_SANITIZE_NUMBER_INT);
        $materials_rating = filter_input(INPUT_POST, 'materials_rating', FILTER_SANITIZE_NUMBER_INT);
        $overall_rating = filter_input(INPUT_POST, 'overall_rating', FILTER_SANITIZE_NUMBER_INT);
        $positive_comments = htmlspecialchars($_POST['positive_comments'] ?? '');
        $improvement_comments = htmlspecialchars($_POST['improvement_comments'] ?? '');
        
        // Validate required fields
        if (empty($course_id) || empty($instructor_rating) || empty($content_rating) || 
            empty($materials_rating) || empty($overall_rating)) {
            $error_message = 'All rating fields are required';
        } else {
            try {
                // Check if user has already submitted feedback for this course
                $checkFeedback = $conn->prepare("SELECT feedback_id FROM student_feedback WHERE user_id = ? AND course_id = ?");
                $checkFeedback->execute([$user_id, $course_id]);
                
                if ($checkFeedback->rowCount() > 0) {
                    // Update existing feedback
                    $stmt = $conn->prepare("UPDATE student_feedback SET 
                        instructor_rating = ?, 
                        content_rating = ?, 
                        materials_rating = ?, 
                        overall_rating = ?,
                        positive_comments = ?,
                        improvement_comments = ?,
                        submission_date = CURRENT_TIMESTAMP
                        WHERE user_id = ? AND course_id = ?");
                        
                    $result = $stmt->execute([
                        $instructor_rating,
                        $content_rating,
                        $materials_rating,
                        $overall_rating,
                        $positive_comments,
                        $improvement_comments,
                        $user_id,
                        $course_id
                    ]);
                    
                    if ($result) {
                        $success_message = "Your feedback has been updated successfully!";
                        
                        // Redirect to logout after successful feedback submission
                        header("Location: " . $_SERVER['PHP_SELF'] . "?logout=1");
                        exit;
                    } else {
                        $error_message = "Failed to update feedback.";
                    }
                } else {
                    // Insert new feedback
                    $stmt = $conn->prepare("INSERT INTO student_feedback 
                        (user_id, course_id, instructor_rating, content_rating, materials_rating, overall_rating, positive_comments, improvement_comments) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        
                    $result = $stmt->execute([
                        $user_id,
                        $course_id,
                        $instructor_rating,
                        $content_rating,
                        $materials_rating,
                        $overall_rating,
                        $positive_comments,
                        $improvement_comments
                    ]);
                    
                    if ($result) {
                        $success_message = "Thank you for your feedback!";
                        
                        // Redirect to logout after successful feedback submission
                        header("Location: " . $_SERVER['PHP_SELF'] . "?logout=1");
                        exit;
                    } else {
                        $error_message = "Failed to save feedback.";
                    }
                }
            } catch(PDOException $e) {
                // Log error and show message
                error_log("Error: " . $e->getMessage());
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Get all courses for selection
try {
    $stmt = $conn->prepare("
        SELECT c.id as course_id, c.title 
        FROM courses c
        WHERE c.status = 'active'
        ORDER BY c.title ASC
    ");
    
    $stmt->execute();
    $enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $error_message = "Error loading courses: " . $e->getMessage();
    $enrolled_courses = [];
}

// For diagnostics: Get current user information
$current_user = null;
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Ensure user_id is set from session
    try {
        $stmt = $conn->prepare("SELECT id, full_name as name, email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        error_log("Error fetching user info: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Feedback Form - CodeCrafters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --border-radius: 10px;
            --box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .feedback-form {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .form-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .rating-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .rating-label {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .btn-submit {
            background-color: var(--primary-color);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 50px;
        }
        
        .btn-submit:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .btn-dashboard {
            background-color: var(--success-color);
            border: none;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 50px;
            margin-right: 10px;
        }
        
        .btn-dashboard:hover {
            background-color: #3da5d9;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 201, 240, 0.3);
        }
        
        .form-select, .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }
        
        .success-alert {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
            border-radius: var(--border-radius);
            padding: 15px;
        }
        
        .error-alert {
            background-color: #f8d7da;
            color: #842029;
            border-color: #f5c2c7;
            border-radius: var(--border-radius);
            padding: 15px;
        }
        
        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 28px;
            color: #e0e0e0;
            cursor: pointer;
            margin: 0 3px;
            transition: all 0.2s ease;
        }
        
        .star-rating input:checked ~ label {
            color: #ffc107;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffdb70;
        }
        
        .user-info {
            background-color: #e7f0fd;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-left: 4px solid var(--primary-color);
        }
        
        .user-data {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            background-color: var(--primary-color);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        
        .login-section {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: var(--border-radius);
            margin-bottom: 25px;
            border: 1px solid #e9ecef;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        .logout-link {
            color: #6c757d;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logout-link i {
            margin-right: 5px;
        }
        
        .logout-link:hover {
            color: #343a40;
            text-decoration: underline;
        }
        
        .badge {
            background-color: var(--primary-color);
            font-weight: 500;
            font-size: 12px;
            margin-left: 8px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .feedback-form {
                padding: 20px;
                margin: 20px 10px;
            }
            
            .user-info {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-controls {
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="feedback-form">
            <h2 class="form-title">Course Feedback Form</h2>
            <p class="text-muted text-center mb-4">Please share your honest feedback to help us improve our courses</p>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert success-alert" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert error-alert" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])): ?>
                <!-- Login Form -->
                <div class="login-section">
                    <h4 class="section-title"><i class="fas fa-sign-in-alt me-2"></i>Please Login to Submit Feedback</h4>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" name="user_login" class="btn btn-primary btn-submit">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                        <a href="student_dashboard.php" class="btn btn-dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </form>
                </div>
            <?php else: ?>
                <!-- User is logged in, show user info and feedback form -->
                <?php if ($current_user): ?>
                <div class="user-info">
                    <div class="user-data">
                        <div class="user-avatar">
                            <?php echo substr($current_user['name'] ?? 'U', 0, 1); ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($current_user['name'] ?? 'Unknown User'); ?></strong>
                            <div class="text-muted"><?php echo htmlspecialchars($current_user['email'] ?? 'unknown@example.com'); ?></div>
                            <span class="badge bg-primary">ID: <?php echo $_SESSION['user_id']; ?></span>
                        </div>
                    </div>
                    <div class="user-controls">
                        <a href="student_dashboard.php" class="btn btn-dashboard">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?logout=1" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <form id="feedbackForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <!-- Course Selection -->
                    <div class="mb-4">
                        <label for="courseId" class="form-label"><i class="fas fa-book me-2"></i>Select Course</label>
                        <select class="form-select" id="courseId" name="course_id" required>
                            <option value="" selected disabled>Select a course</option>
                            <?php foreach ($enrolled_courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Instructor Rating -->
                    <div class="mb-4">
                        <label class="form-label rating-label"><i class="fas fa-chalkboard-teacher me-2"></i>Instructor Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="instructor5" name="instructor_rating" value="5" required/>
                            <label for="instructor5">★</label>
                            <input type="radio" id="instructor4" name="instructor_rating" value="4" />
                            <label for="instructor4">★</label>
                            <input type="radio" id="instructor3" name="instructor_rating" value="3" />
                            <label for="instructor3">★</label>
                            <input type="radio" id="instructor2" name="instructor_rating" value="2" />
                            <label for="instructor2">★</label>
                            <input type="radio" id="instructor1" name="instructor_rating" value="1" />
                            <label for="instructor1">★</label>
                        </div>
                    </div>
                    
                    <!-- Course Content Rating -->
                    <div class="mb-4">
                        <label class="form-label rating-label"><i class="fas fa-file-alt me-2"></i>Course Content Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="content5" name="content_rating" value="5" required/>
                            <label for="content5">★</label>
                            <input type="radio" id="content4" name="content_rating" value="4" />
                            <label for="content4">★</label>
                            <input type="radio" id="content3" name="content_rating" value="3" />
                            <label for="content3">★</label>
                            <input type="radio" id="content2" name="content_rating" value="2" />
                            <label for="content2">★</label>
                            <input type="radio" id="content1" name="content_rating" value="1" />
                            <label for="content1">★</label>
                        </div>
                    </div>
                    
                    <!-- Materials Rating -->
                    <div class="mb-4">
                        <label class="form-label rating-label"><i class="fas fa-book-open me-2"></i>Learning Materials Rating</label>
                        <div class="star-rating">
                            <input type="radio" id="materials5" name="materials_rating" value="5" required/>
                            <label for="materials5">★</label>
                            <input type="radio" id="materials4" name="materials_rating" value="4" />
                            <label for="materials4">★</label>
                            <input type="radio" id="materials3" name="materials_rating" value="3" />
                            <label for="materials3">★</label>
                            <input type="radio" id="materials2" name="materials_rating" value="2" />
                            <label for="materials2">★</label>
                            <input type="radio" id="materials1" name="materials_rating" value="1" />
                            <label for="materials1">★</label>
                        </div>
                    </div>
                    
                    <!-- Overall Experience Rating -->
                    <div class="mb-4">
                        <label class="form-label rating-label"><i class="fas fa-star me-2"></i>Overall Experience</label>
                        <div class="star-rating">
                            <input type="radio" id="overall5" name="overall_rating" value="5" required/>
                            <label for="overall5">★</label>
                            <input type="radio" id="overall4" name="overall_rating" value="4" />
                            <label for="overall4">★</label>
                            <input type="radio" id="overall3" name="overall_rating" value="3" />
                            <label for="overall3">★</label>
                            <input type="radio" id="overall2" name="overall_rating" value="2" />
                            <label for="overall2">★</label>
                            <input type="radio" id="overall1" name="overall_rating" value="1" />
                            <label for="overall1">★</label>
                        </div>
                    </div>
                    
                    <!-- Comments -->
                    <div class="mb-4">
                        <label for="positive_comments" class="form-label"><i class="fas fa-thumbs-up me-2"></i>What did you like about this course?</label>
                        <textarea class="form-control" id="positive_comments" name="positive_comments" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="improvement_comments" class="form-label"><i class="fas fa-lightbulb me-2"></i>What could be improved?</label>
                        <textarea class="form-control" id="improvement_comments" name="improvement_comments" rows="3"></textarea>
                    </div>
                    
                    <div class="text-center action-buttons">
                        <button type="submit" name="submit_feedback" class="btn btn-submit">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>