<?php
// Start session if needed
session_start();

// Database connection
$servername = "localhost";
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password
$dbname = "Codecrafters";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Helper function to get activity item CSS class
function getActivityClass($icon) {
    switch ($icon) {
        case 'user':
            return 'new-registration';
        case 'book':
            return 'new-enrollment';
        case 'star':
            return 'new-feedback';
        default:
            return '';
    }
}

// Helper function to get icon CSS class
function getIconClass($icon) {
    switch ($icon) {
        case 'user':
            return 'register';
        case 'book':
            return 'enroll';
        case 'star':
            return 'feedback';
        default:
            return '';
    }
}

// Function to get total number of students
function getTotalStudents($conn) {
    $sql = "SELECT COUNT(*) as total FROM students";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["total"];
    }
    return 0;
}

// Function to get active courses
function getActiveCourses($conn) {
    $sql = "SELECT COUNT(*) as total FROM courses WHERE status = 'active'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["total"];
    }
    return 0;
}

// Function to get available instructors
function getAvailableInstructors($conn) {
    $sql = "SELECT COUNT(*) as total FROM instructors";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["total"];
    }
    return 0;
}

// Function to get recent student feedback
function getRecentFeedback($conn) {
    $sql = "SELECT COUNT(*) as total FROM student_feedback";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["total"];
    }
    return 0;
}

// Function to get recent activities
function getRecentActivities($conn) {
    $activities = array();
    
    // Get latest student registration
    $sql = "SELECT full_name, created_at 
        FROM users
        ORDER BY created_at DESC 
        LIMIT 3";
$result = $conn->query($sql);
$activities = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $time = date("h:i A", strtotime($row["created_at"]));
        $activities[] = array(
            "time" => $time,
            "description" => "New student registration: " . $row["full_name"],
            "icon" => "user"
        );
    }
}

    
    // Get latest course enrollment
 $sql = "SELECT u.full_name, c.title AS course_name, p.purchase_date 
        FROM purchases p 
        JOIN users u ON p.user_id = u.id 
        JOIN courses c ON p.course_id = c.id 
        ORDER BY p.purchase_date DESC 
        LIMIT 3";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $time = date("h:i A", strtotime($row["purchase_date"]));
        $activities[] = array(
            "time" => $time,
            "description" => "New purchase: " . $row["full_name"] . " in " . $row["course_name"],
            "icon" => "shopping-cart"
        );
    }
}
    
   // Get latest feedback submissions
// Get latest feedback submissions
$sql = "SELECT u.full_name, f.course_id, f.overall_rating as rating, f.submission_date as submitted_at
        FROM student_feedback f 
        JOIN users u ON f.user_id = u.id 
        ORDER BY f.submission_date DESC 
        LIMIT 3";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $time = date("h:i A", strtotime($row["submitted_at"]));
        $activities[] = array(
            "time" => $time,
           "description" => "New feedback: " . $row["full_name"] . " rated course #" . $row["course_id"] . " " . $row["rating"] . "/5",
            "icon" => "star"
        );
    }
}

    
    // Sort activities by time (most recent first)
    usort($activities, function($a, $b) {
        return strtotime($b["time"]) - strtotime($a["time"]);
    });
    
    // Return the 5 most recent activities
    return array_slice($activities, 0, 5);
}

// Function to get course enrollment statistics
function getCourseEnrollmentStats($conn) {
    $stats = array();
    
    $sql = "SELECT c.title as course_name, COUNT(p.id) as enrollment_count
            FROM courses c
            LEFT JOIN purchases p ON c.id = p.course_id
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY enrollment_count DESC
            LIMIT 5";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $stats[] = array(
                "course" => $row["course_name"],
                "count" => $row["enrollment_count"]
            );
        }
    }
    
    return $stats;
}

function getCourseRatings($conn) {
    $ratings = array();
    
    $sql = "SELECT course_id, AVG(overall_rating) as overall_rating, COUNT(*) as feedback_count
            FROM student_feedback
            GROUP BY course_id
            ORDER BY overall_rating DESC
            LIMIT 5";
    
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Get the course name from courses table
            $course_query = "SELECT title FROM courses WHERE id = " . $row["course_id"];
            $course_result = $conn->query($course_query);
            $course_name = "Unknown Course";
            if ($course_result && $course_result->num_rows > 0) {
                $course_row = $course_result->fetch_assoc();
                $course_name = $course_row["title"];
            }
            
            $ratings[] = array(
                "course" => $course_name,
                "overall_rating" => round($row["overall_rating"], 1),
                "count" => $row["feedback_count"]
            );
        }
    }
    
    return $ratings;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get dashboard data
$totalStudents = getTotalStudents($conn);
$activeCourses = getActiveCourses($conn);
$availableInstructors = getAvailableInstructors($conn);
$recentFeedback = getRecentFeedback($conn);
$recentActivities = getRecentActivities($conn);
$courseEnrollmentStats = getCourseEnrollmentStats($conn);
$courseRatings = getCourseRatings($conn);


// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CodeCrafters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a7bd5;
            --primary-light: #6faae2;
            --primary-dark: #2c5282;
            --secondary-color: #f0f4f8;
            --text-dark: #2d3748;
            --text-light: #718096;
            --text-white: #ffffff;
            --danger-color: #e53e3e;
            --success-color: #38a169;
            --warning-color: #f6ad55;
            --border-color: #e2e8f0;
            --sidebar-width: 260px;
            --header-height: 70px;
            --transition-speed: 0.3s;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-bg: #f7fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f8fafc;
            color: var(--text-dark);
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: var(--text-white);
            position: fixed;
            height: 100%;
            overflow-y: auto;
            transition: all var(--transition-speed);
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .logo {
            font-weight: bold;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: white;
            display: flex;
            align-items: center;
        }

        .logo i {
            margin-right: 0.75rem;
        }

        .admin-badge {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .nav-links {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-links li {
            padding: 0;
            transition: background-color 0.2s;
        }

        .nav-links li a {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-links li a i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

        .nav-links li:hover a {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .nav-links li.active a {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: 600;
            position: relative;
        }

        .nav-links li.active a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background-color: white;
        }

        .logout {
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
        }

        .logout a {
            color: rgba(255, 255, 255, 0.7);
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
            transition: margin-left var(--transition-speed);
        }

        /* Header Styles */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .notification {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--text-light);
        }

        .notification::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 8px;
            background-color: var(--danger-color);
            border-radius: 50%;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        /* Dashboard Grid Styles */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .card h3 i {
            margin-right: 0.5rem;
            color: var(--primary-color);
        }

        .stat {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .trend {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .trend.positive {
            color: var(--success-color);
        }

        .trend.negative {
            color: var(--danger-color);
        }

        .action-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.2s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-btn i {
            margin-right: 0.5rem;
        }

        .action-btn:hover {
            background-color: var(--primary-dark);
        }

        /* Recent Activity Styles - Positioned below dashboard grid */
        .recent-activity {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-top: 2rem;
            width: 100%;
        }

        .recent-activity h2 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .refresh-btn {
            background-color: transparent;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .refresh-btn:hover {
            color: var(--primary-dark);
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            border-radius: 8px;
            background-color: var(--secondary-color);
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .activity-item:hover {
            background-color: #e9eef5;
            transform: translateX(5px);
        }

        .activity-item.new-registration {
            border-left-color: var(--success-color);
        }

        .activity-item.new-enrollment {
            border-left-color: var(--primary-color);
        }

        .activity-item.new-feedback {
            border-left-color: var(--warning-color);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .activity-icon.register {
            background-color: var(--success-color);
        }

        .activity-icon.enroll {
            background-color: var(--primary-color);
        }

        .activity-icon.feedback {
            background-color: var(--warning-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .activity-content .description {
            color: var(--text-dark);
            font-size: 0.95rem;
        }

        .time {
            color: var(--text-light);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .time i {
            margin-right: 0.4rem;
            font-size: 0.8rem;
        }

        /* Responsive Design */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-dark);
            cursor: pointer;
        }

        @media (max-width: 991px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Dark mode toggle */
        .theme-toggle {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .theme-toggle:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-code"></i>
                    <span>CodeCrafters</span>
                </div>
                <span class="admin-badge">Administrator</span>
            </div>
            <ul class="nav-links">
                <li class="active"><a href="#overview"><i class="fas fa-chart-pie"></i> Overview</a></li>
                <li><a href="admin_students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="admin_courses.php"><i class="fas fa-book"></i> Courses</a></li>
                <li><a href="admin_instructor.php"><i class="fas fa-chalkboard-teacher"></i> Instructors</a></li>
                <li><a href="admin_feedback.php"><i class="fas fa-file-alt"></i> Feedback</a></li>
                <li><a href="admin.php"><i class="fas fa-tasks"></i> Admin</a></li>
                <li><a href="report.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li class="logout"><a href="admin login.php" onclick="logout()"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            
        </nav>

        <main class="main-content">
            <header>
                <div class="header-left">
                    <button class="menu-toggle" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="user-info">
                    <span class="notification"><i class="fas fa-bell"></i></span>
                    <div class="user-profile">
                        <div class="user-avatar">A</div>
                        <span>Admin</span>
                    </div>
                </div>
            </header>

            <div class="dashboard-grid">
            <div class="card">
    <h3><i class="fas fa-user-graduate"></i> Total Students</h3>
    <p class="stat"><?php echo $totalStudents; ?></p>
    <?php 
    // Initialize the variable with a default value
    $growthPercentage = $growthPercentage ?? 0; 
    ?>
    <p class="trend <?php echo ($growthPercentage > 0) ? 'positive' : 'negative'; ?>">
        <i class="fas <?php echo ($growthPercentage > 0) ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> 
        <?php echo abs($growthPercentage); ?>% this month
    </p>
    <button class="action-btn"><i class="fas fa-users"></i> View Students</button>
</div>

                <div class="card">
                    <h3><i class="fas fa-book"></i> Active Courses</h3>
                    <p class="stat"><?php echo $activeCourses; ?></p>
                    <p class="trend positive">
                        <i class="fas fa-check-circle"></i> All courses running
                    </p>
                    <button class="action-btn"><i class="fas fa-eye"></i> View Courses</button>
                </div>

                <div class="card">
                    <h3><i class="fas fa-chalkboard-teacher"></i> Available Instructors</h3>
                    <p class="stat"><?php echo $availableInstructors; ?></p>
                    <p class="trend positive">
                        <i class="fas fa-user-check"></i> Ready to teach
                    </p>
                    <button class="action-btn"><i class="fas fa-cog"></i> Manage Instructors</button>
                </div>

                <div class="card">
                    <h3><i class="fas fa-comment-alt"></i> Recent Feedback</h3>
                    <p class="stat"><?php echo $recentFeedback; ?></p>
                    <p class="trend <?php echo ($recentFeedback > 5) ? 'positive' : 'neutral'; ?>">
                        <i class="fas <?php echo ($recentFeedback > 5) ? 'fa-comments' : 'fa-comment'; ?>"></i> 
                        <?php echo ($recentFeedback > 5) ? 'Active discussions' : 'Needs more feedback'; ?>
                    </p>
                    <button class="action-btn"><i class="fas fa-eye"></i> View Feedback</button>
                </div>
            </div>

            <div class="recent-activity">
                <h2>
                    Recent Activity
                    <button class="refresh-btn" onclick="refreshDashboardData()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </h2>
                <div class="activity-list">
                    <?php if (empty($recentActivities)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No recent activities to display</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item <?php echo getActivityClass($activity['icon']); ?>">
                            <div class="activity-icon <?php echo getIconClass($activity['icon']); ?>">
                                <i class="fas fa-<?php echo $activity["icon"]; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p class="description"><?php echo $activity["description"]; ?></p>
                                <span class="time">
                                    <i class="far fa-clock"></i> <?php echo $activity["time"]; ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // dashboard.js - JavaScript for CodeCrafters Admin Dashboard

        // Document ready function
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize dashboard components
            initializeDashboard();
            setupNavigation();
            setupResponsiveness();
        });

        // Initialize dashboard elements
        function initializeDashboard() {
            // Setup notification click handler
            const notificationBtn = document.querySelector('.notification');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', function() {
                    alert('Notifications feature coming soon!');
                });
            }
            
            // Setup action buttons
            const actionButtons = document.querySelectorAll('.action-btn');
            actionButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const buttonText = this.textContent.trim();
                    
                    if (buttonText.includes('View Students')) {
                        window.location.href = 'admin_students.php';
                    } else if (buttonText.includes('View Courses')) {
                        window.location.href = 'admin_courses.php';
                    } else if (buttonText.includes('Manage Instructors')) {
                        window.location.href = 'admin_instructor.php';
                    }  else if (buttonText.includes('View Feedback')) {
                        window.location.href = 'admin_feedback.php';
                    }
                });
            });

        }

        // Handle navigation and active states
        function setupNavigation() {
            const navLinks = document.querySelectorAll('.nav-links li a');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Don't prevent logout link default behavior
                    if (!this.textContent.includes('Logout')) {
                        // Remove active class from all links
                        document.querySelectorAll('.nav-links li').forEach(item => {
                            item.classList.remove('active');
                        });
                        
                        // Add active class to parent of clicked link
                        this.parentElement.classList.add('active');
                    }
                });
            });
            
            // Set active navigation based on current page
            const currentPage = window.location.pathname.split('/').pop();
            
            navLinks.forEach(link => {
                const linkHref = link.getAttribute('href');
                if (linkHref === currentPage || (currentPage === 'admin-dashboard.php' && linkHref === '#overview')) {
                    // Remove active from all
                    document.querySelectorAll('.nav-links li').forEach(item => {
                        item.classList.remove('active');
                    });
                    // Add active to current
                    link.parentElement.classList.add('active');
                }
            });
        }

        // Setup responsive behavior
        function setupResponsiveness() {
            const menuToggle = document.getElementById('menu-toggle');
            const sidebar = document.getElementById('sidebar');
            
            if (menuToggle && sidebar) {
                menuToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(e) {
                    const isClickInsideSidebar = sidebar.contains(e.target);
                    const isClickOnMenuToggle = menuToggle.contains(e.target);
                    
                    if (!isClickInsideSidebar && !isClickOnMenuToggle && window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                        sidebar.classList.remove('active');
                    }
                });
            }
        }

        // Logout function
        function logout() {
            // Perform any logout tasks here (clear session storage, etc)
            console.log('Logging out user...');
            localStorage.removeItem('adminLoggedIn');
            sessionStorage.clear();
            
            // No need to redirect as the href in the HTML will handle that
            // Returning true allows the default link behavior to continue
            return true;
        }

        // Function to handle refresh data
        function refreshDashboardData() {
            // Show loading animation
            const refreshBtn = document.querySelector('.refresh-btn i');
            refreshBtn.classList.add('fa-spin');
            
            // Simulating network request
            setTimeout(function() {
                // Stop loading animation
                refreshBtn.classList.remove('fa-spin');
                // Refresh the page to get new data
                location.reload();
            }, 1000);
        }
    </script>
</body>
</html>