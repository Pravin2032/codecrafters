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

// Handle feedback actions
if (isset($_POST['action'])) {
    $feedback_id = filter_input(INPUT_POST, 'feedback_id', FILTER_SANITIZE_NUMBER_INT);
    
    if ($_POST['action'] == 'delete' && !empty($feedback_id)) {
        try {
            $stmt = $conn->prepare("DELETE FROM student_feedback WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $success_message = "Feedback entry deleted successfully.";
        } catch(PDOException $e) {
            $error_message = "Error deleting feedback: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'archive' && !empty($feedback_id)) {
        try {
            $stmt = $conn->prepare("UPDATE student_feedback SET status = 'archived' WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $success_message = "Feedback entry archived successfully.";
        } catch(PDOException $e) {
            $error_message = "Error archiving feedback: " . $e->getMessage();
        }
    }
    
    if ($_POST['action'] == 'restore' && !empty($feedback_id)) {
        try {
            $stmt = $conn->prepare("UPDATE student_feedback SET status = 'active' WHERE feedback_id = ?");
            $stmt->execute([$feedback_id]);
            $success_message = "Feedback entry restored successfully.";
        } catch(PDOException $e) {
            $error_message = "Error restoring feedback: " . $e->getMessage();
        }
    }
}

// Handle export functionality
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    try {
        $stmt = $conn->prepare("
            SELECT 
                sf.feedback_id,
                c.title AS course_title,
                u.full_name AS student_name,
                u.email AS student_email,
                sf.instructor_rating,
                sf.content_rating,
                sf.materials_rating,
                sf.overall_rating,
                sf.positive_comments,
                sf.improvement_comments,
                sf.submission_date,
                sf.status
            FROM 
                student_feedback sf
            JOIN 
                users u ON sf.user_id = u.id
            JOIN 
                courses c ON sf.course_id = c.id
            ORDER BY 
                sf.submission_date DESC
        ");
        
        $stmt->execute();
        $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="feedback_export_' . date('Y-m-d') . '.csv"');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array_keys($feedbacks[0]));
        
        // Add data rows
        foreach ($feedbacks as $feedback) {
            fputcsv($output, $feedback);
        }
        
        fclose($output);
        exit;
    } catch(PDOException $e) {
        $error_message = "Error exporting data: " . $e->getMessage();
    }
}

// Get filter parameters
$filter_course = filter_input(INPUT_GET, 'course', FILTER_SANITIZE_NUMBER_INT);
$filter_status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
$filter_rating = filter_input(INPUT_GET, 'rating', FILTER_SANITIZE_NUMBER_INT);

// Initialize filter parts for the SQL query
$filter_parts = [];
$filter_params = [];

if (!empty($filter_course)) {
    $filter_parts[] = "sf.course_id = ?";
    $filter_params[] = $filter_course;
}

if (!empty($filter_status)) {
    $filter_parts[] = "sf.status = ?";
    $filter_params[] = $filter_status;
}

if (!empty($filter_rating)) {
    $filter_parts[] = "sf.overall_rating >= ?";
    $filter_params[] = $filter_rating;
}

// Construct WHERE clause
$where_clause = !empty($filter_parts) ? "WHERE " . implode(" AND ", $filter_parts) : "";

// Get feedback data with pagination
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT) ?: 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Count total records for pagination
    $count_sql = "
        SELECT COUNT(*) as total FROM student_feedback sf
        $where_clause
    ";
    
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($filter_params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get paginated feedback records
    $stmt = $conn->prepare("
        SELECT 
            sf.feedback_id,
            c.title AS course_title,
            u.full_name AS student_name,
            u.email AS student_email,
            sf.instructor_rating,
            sf.content_rating,
            sf.materials_rating,
            sf.overall_rating,
            ROUND((sf.instructor_rating + sf.content_rating + sf.materials_rating + sf.overall_rating) / 4, 1) AS avg_rating,
            sf.positive_comments,
            sf.improvement_comments,
            sf.submission_date,
            sf.status
        FROM 
            student_feedback sf
        JOIN 
            users u ON sf.user_id = u.id
        JOIN 
            courses c ON sf.course_id = c.id
        $where_clause
        ORDER BY 
            sf.submission_date DESC
        LIMIT $offset, $per_page
    ");
    
    $stmt->execute($filter_params);
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching feedback data: " . $e->getMessage());
    $error_message = "Error loading feedback data: " . $e->getMessage();
    $feedbacks = [];
    $total_pages = 0;
}

// Get all courses for filter dropdown
try {
    $stmt = $conn->prepare("
        SELECT id, title FROM courses WHERE status = 'active' ORDER BY title ASC
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching courses: " . $e->getMessage());
    $courses = [];
}

// Get summary statistics
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_feedback,
            ROUND(AVG(instructor_rating), 1) as avg_instructor,
            ROUND(AVG(content_rating), 1) as avg_content,
            ROUND(AVG(materials_rating), 1) as avg_materials,
            ROUND(AVG(overall_rating), 1) as avg_overall
        FROM 
            student_feedback
        WHERE 
            status = 'active'
    ");
    
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Error fetching statistics: " . $e->getMessage());
    $stats = [
        'total_feedback' => 0,
        'avg_instructor' => 0,
        'avg_content' => 0,
        'avg_materials' => 0,
        'avg_overall' => 0
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Feedback Management - CodeCrafters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #3a86ff;
            --secondary-color: #4361ee;
            --accent-color: #7209b7;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --success-color: #38b000;
            --warning-color: #ffaa00;
            --danger-color: #d90429;
            --border-radius: 10px;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .admin-container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px 25px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border-radius: var(--border-radius);
            color: white;
            box-shadow: var(--card-shadow);
        }
        
        .admin-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-dashboard {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-dashboard:hover {
            background-color: #5a189a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(114, 9, 183, 0.3);
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            padding: 20px;
            border-radius: var(--border-radius);
            background: white;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-top: 4px solid var(--primary-color);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .stat-title {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--dark-bg);
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .card-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 25px;
            margin-bottom: 30px;
            transition: var(--transition);
        }
        
        .card-container:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .filter-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .export-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 6px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .export-btn:hover {
            background-color: #2e8c0d;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 176, 0, 0.2);
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 25px;
            margin-bottom: 25px;
            overflow: hidden;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table > :not(caption) > * > * {
            padding: 15px 12px;
        }
        
        .table thead th {
            background-color: #f3f4f6;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table-actions {
            white-space: nowrap;
            display: flex;
            gap: 5px;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-view {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-view:hover {
            background-color: #2b6ef3;
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }
        
        .btn-archive {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn-archive:hover {
            background-color: #e69900;
            box-shadow: 0 4px 12px rgba(255, 170, 0, 0.3);
        }
        
        .btn-restore {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-restore:hover {
            background-color: #2e8c0d;
            box-shadow: 0 4px 12px rgba(56, 176, 0, 0.3);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #bf0026;
            box-shadow: 0 4px 12px rgba(217, 4, 41, 0.3);
        }
        
        .pagination-container {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .page-link {
            color: var(--primary-color);
            border-radius: 5px;
            margin: 0 3px;
            transition: var(--transition);
        }
        
        .page-link:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .feedback-text {
            max-height: 100px;
            overflow-y: auto;
            padding: 8px;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        
        .archived {
            opacity: 0.7;
            background-color: #f8f9fa;
        }
        
        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 20px;
        }
        
        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8rem;
        }
        
        .badge.bg-success {
            background-color: var(--success-color) !important;
        }
        
        .modal-content {
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .modal-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-top-left-radius: calc(var(--border-radius) - 1px);
            border-top-right-radius: calc(var(--border-radius) - 1px);
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .form-select, .form-control {
            border-radius: 6px;
            padding: 10px 15px;
            box-shadow: none;
            transition: var(--transition);
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(58, 134, 255, 0.25);
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .btn-filter {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .btn-filter:hover {
            background-color: #2b6ef3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(58, 134, 255, 0.3);
        }
        
        .btn-reset {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: var(--transition);
            font-weight: 500;
        }
        
        .btn-reset:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }
        .highlight {
    background-color: rgba(0, 123, 255, 0.05);
    transition: background-color 0.3s ease;
}
    </style>
</head>
<body>
    <!-- Admin Dashboard -->
    <div class="admin-container">
        <div class="admin-header">
            <h2 class="admin-title">Feedback Management</h2>
            <div class="admin-actions">
                <a href="admin dashboard.php" class="btn-dashboard">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-title">Total Feedback</div>
                <div class="stat-value"><?php echo $stats['total_feedback']; ?></div>
                <small class="text-muted">Active entries</small>
            </div>
            <div class="stat-card">
                <div class="stat-title">Avg. Instructor Rating</div>
                <div class="stat-value"><?php echo $stats['avg_instructor']; ?> <span class="star-rating">★</span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats['avg_instructor']/5)*100; ?>%"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Avg. Content Rating</div>
                <div class="stat-value"><?php echo $stats['avg_content']; ?> <span class="star-rating">★</span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats['avg_content']/5)*100; ?>%"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Avg. Materials Rating</div>
                <div class="stat-value"><?php echo $stats['avg_materials']; ?> <span class="star-rating">★</span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats['avg_materials']/5)*100; ?>%"></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Avg. Overall Rating</div>
                <div class="stat-value"><?php echo $stats['avg_overall']; ?> <span class="star-rating">★</span></div>
                <div class="progress mt-2" style="height: 6px;">
                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo ($stats['avg_overall']/5)*100; ?>%"></div>
                </div>
            </div>
        </div>
        
            <!-- Filter Form -->
            <div class="filter-container">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="row g-3">
                    <div class="col-md-3">
                        <label for="course" class="form-label">Filter by Course</label>
                        <select class="form-select" id="course" name="course">
                            <option value="">All Courses</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php if($filter_course == $course['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php if($filter_status == 'active') echo 'selected'; ?>>Active</option>
                            <option value="archived" <?php if($filter_status == 'archived') echo 'selected'; ?>>Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="rating" class="form-label">Minimum Rating</label>
                        <select class="form-select" id="rating" name="rating">
                            <option value="">Any Rating</option>
                            <option value="5" <?php if($filter_rating == 5) echo 'selected'; ?>>5 Stars</option>
                            <option value="4" <?php if($filter_rating == 4) echo 'selected'; ?>>4+ Stars</option>
                            <option value="3" <?php if($filter_rating == 3) echo 'selected'; ?>>3+ Stars</option>
                            <option value="2" <?php if($filter_rating == 2) echo 'selected'; ?>>2+ Stars</option>
                            <option value="1" <?php if($filter_rating == 1) echo 'selected'; ?>>1+ Stars</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn-filter me-2">
                            <i class="bi bi-filter me-1"></i> Apply Filters
                        </button>
                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn-reset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
        
        <!-- Action Buttons -->
        <div class="action-bar">
            <a href="?export=csv<?php echo !empty($filter_course) ? '&course=' . $filter_course : ''; echo !empty($filter_status) ? '&status=' . $filter_status : ''; echo !empty($filter_rating) ? '&rating=' . $filter_rating : ''; ?>" class="export-btn">
                <i class="bi bi-download"></i> Export to CSV
            </a>
            <span class="badge bg-secondary">Total: <?php echo $total_records; ?> feedback entries</span>
        </div>
        
        <!-- Feedback Table -->
        <div class="table-container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course</th>
                        <th>Student</th>
                        <th>Ratings</th>
                        <th>Comments</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbacks)): ?>
                        <tr>
                            <td colspan="8" class="text-center">No feedback data found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbacks as $feedback): ?>
                            <tr class="<?php echo ($feedback['status'] == 'archived') ? 'archived' : ''; ?>">
                                <td><?php echo $feedback['feedback_id']; ?></td>
                                <td><?php echo htmlspecialchars($feedback['course_title']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($feedback['student_name']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($feedback['student_email']); ?></small>
                                </td>
                                <td>
                                    <div title="Instructor: <?php echo $feedback['instructor_rating']; ?>★">
                                        I: <span class="star-rating"><?php echo str_repeat('★', $feedback['instructor_rating']); ?></span>
                                    </div>
                                    <div title="Content: <?php echo $feedback['content_rating']; ?>★">
                                        C: <span class="star-rating"><?php echo str_repeat('★', $feedback['content_rating']); ?></span>
                                    </div>
                                    <div title="Materials: <?php echo $feedback['materials_rating']; ?>★">
                                        M: <span class="star-rating"><?php echo str_repeat('★', $feedback['materials_rating']); ?></span>
                                    </div>
                                    <div title="Overall: <?php echo $feedback['overall_rating']; ?>★">
                                        O: <span class="star-rating"><?php echo str_repeat('★', $feedback['overall_rating']); ?></span>
                                    </div>
                                    <div class="mt-1">
                                        <strong>Avg: <?php echo $feedback['avg_rating']; ?>★</strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($feedback['positive_comments'])): ?>
                                        <div class="mb-2">
                                            <strong>Liked:</strong> 
                                            <div class="feedback-text"><?php echo nl2br(htmlspecialchars($feedback['positive_comments'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($feedback['improvement_comments'])): ?>
                                        <div>
                                            <strong>Improve:</strong> 
                                            <div class="feedback-text"><?php echo nl2br(htmlspecialchars($feedback['improvement_comments'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($feedback['submission_date'])); ?></td>
                                <td>
                                    <span class="badge <?php echo ($feedback['status'] == 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ucfirst($feedback['status']); ?>
                                    </span>
                                </td>
                                <td class="table-actions">
                                <button type="button" class="action-btn btn-view" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $feedback['feedback_id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    
                                    <?php if ($feedback['status'] == 'active'): ?>
                                        <form method="post" action="" style="display:inline;">
                                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                            <input type="hidden" name="action" value="archive">
                                            <button type="submit" class="action-btn btn-archive" onclick="return confirm('Are you sure you want to archive this feedback?');">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="" style="display:inline;">
                                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                            <input type="hidden" name="action" value="restore">
                                            <button type="submit" class="action-btn btn-restore" onclick="return confirm('Are you sure you want to restore this feedback?');">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to permanently delete this feedback? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- View Modal for each feedback entry -->
                            <div class="modal fade" id="viewModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewModalLabel<?php echo $feedback['feedback_id']; ?>">
                                                Feedback Details - ID #<?php echo $feedback['feedback_id']; ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold">Course Information</h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($feedback['course_title']); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold">Student Information</h6>
                                                    <p class="mb-1"><?php echo htmlspecialchars($feedback['student_name']); ?></p>
                                                    <p class="text-muted mb-1"><?php echo htmlspecialchars($feedback['student_email']); ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-12">
                                                    <h6 class="fw-bold">Ratings</h6>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1">Instructor: <span class="star-rating"><?php echo str_repeat('★', $feedback['instructor_rating']); ?></span> (<?php echo $feedback['instructor_rating']; ?>/5)</p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1">Content: <span class="star-rating"><?php echo str_repeat('★', $feedback['content_rating']); ?></span> (<?php echo $feedback['content_rating']; ?>/5)</p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1">Materials: <span class="star-rating"><?php echo str_repeat('★', $feedback['materials_rating']); ?></span> (<?php echo $feedback['materials_rating']; ?>/5)</p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1">Overall: <span class="star-rating"><?php echo str_repeat('★', $feedback['overall_rating']); ?></span> (<?php echo $feedback['overall_rating']; ?>/5)</p>
                                                </div>
                                                <div class="col-12 mt-2">
                                                    <p class="fw-bold mb-1">Average Rating: <?php echo $feedback['avg_rating']; ?>/5</p>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold">What Students Liked</h6>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php if (!empty($feedback['positive_comments'])): ?>
                                                            <?php echo nl2br(htmlspecialchars($feedback['positive_comments'])); ?>
                                                        <?php else: ?>
                                                            <em class="text-muted">No comments provided</em>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold">Suggested Improvements</h6>
                                                    <div class="p-3 bg-light rounded">
                                                        <?php if (!empty($feedback['improvement_comments'])): ?>
                                                            <?php echo nl2br(htmlspecialchars($feedback['improvement_comments'])); ?>
                                                        <?php else: ?>
                                                            <em class="text-muted">No comments provided</em>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold">Submission Information</h6>
                                                    <p class="mb-1">Date: <?php echo date('F d, Y - h:i A', strtotime($feedback['submission_date'])); ?></p>
                                                    <p class="mb-1">Status: 
                                                        <span class="badge <?php echo ($feedback['status'] == 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($feedback['status']); ?>
                                                        </span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <?php if ($feedback['status'] == 'active'): ?>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                                    <input type="hidden" name="action" value="archive">
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="bi bi-archive me-1"></i> Archive Feedback
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                                    <input type="hidden" name="action" value="restore">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore Feedback
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <form method="post" action="" style="display:inline;">
                                                <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to permanently delete this feedback? This action cannot be undone.');">
                                                    <i class="bi bi-trash me-1"></i> Delete Permanently
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); echo !empty($filter_course) ? '&course=' . $filter_course : ''; echo !empty($filter_status) ? '&status=' . $filter_status : ''; echo !empty($filter_rating) ? '&rating=' . $filter_rating : ''; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; echo !empty($filter_course) ? '&course=' . $filter_course : ''; echo !empty($filter_status) ? '&status=' . $filter_status : ''; echo !empty($filter_rating) ? '&rating=' . $filter_rating : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); echo !empty($filter_course) ? '&course=' . $filter_course : ''; echo !empty($filter_status) ? '&status=' . $filter_status : ''; echo !empty($filter_rating) ? '&rating=' . $filter_rating : ''; ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
        
        // Add confirmation for delete actions
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to permanently delete this feedback? This action cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
        // Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get the filter form and its elements
    const filterForm = document.querySelector('.filter-container form');
    const selectElements = filterForm.querySelectorAll('select');
    const resetButton = filterForm.querySelector('.btn-reset');
    
    // Add change event listeners to all select elements for automatic submission
    selectElements.forEach(select => {
        select.addEventListener('change', function() {
            filterForm.submit();
        });
    });
    
    // Add click event listener to reset button
    resetButton.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Reset all select elements to their default value
        selectElements.forEach(select => {
            select.value = '';
        });
        
        // Submit the form with reset values
        filterForm.submit();
    });
    
    // Optional: Add visual feedback when hovering over filters
    selectElements.forEach(select => {
        select.addEventListener('mouseover', function() {
            this.parentElement.classList.add('highlight');
        });
        
        select.addEventListener('mouseout', function() {
            this.parentElement.classList.remove('highlight');
        });
    });
});
    </script>
</body>
</html>