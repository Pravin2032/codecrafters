<?php
session_start();
require_once 'db_connection.php'; // Ensure this file exists with your database connection


// Initialize variables
$report_type = '';
$start_date = '';
$end_date = '';
$instructor = '';
$status = '';
$category = '';
$reportData = [];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $report_type = $_POST['report_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $instructor = $_POST['instructor'] ?? '';
    $status = $_POST['status'] ?? '';
    $category = $_POST['category'] ?? '';
    
    // Generate report based on type
    switch ($report_type) {
        case 'students':
            generateStudentsReport($conn, $start_date, $end_date);
            break;
        case 'courses':
            generateCoursesReport($conn, $category);
            break;
        case 'instructors':
            generateInstructorsReport($conn);
            break;
        case 'projects':
            generateProjectsReport($conn, $status);
            break;
        case 'feedback':
            generateFeedbackReport($conn, $start_date, $end_date);
            break;
        default:
            $message = "Please select a report type.";
    }
}

// Function to generate students report
function generateStudentsReport($conn, $start_date, $end_date) {
    global $reportData, $message;
    
    $sql = "SELECT * FROM students WHERE 1=1";
    
    if (!empty($start_date)) {
        $sql .= " AND registration_date >= '$start_date'";
    }
    
    if (!empty($end_date)) {
        $sql .= " AND registration_date <= '$end_date'";
    }
    
    $sql .= " ORDER BY last_name, first_name";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $reportData = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $message = "No students found matching the criteria.";
        }
    } else {
        $message = "Error executing query: " . mysqli_error($conn);
    }
}

// Function to generate courses report
function generateCoursesReport($conn, $category) {
    global $reportData, $message;

    $sql = "SELECT c.*, i.name AS instructor_name
            FROM courses c 
            LEFT JOIN instructors i ON c.instructor_id = i.instructor_id 
            WHERE 1=1";

    if (!empty($category)) {
        $category = mysqli_real_escape_string($conn, $category); // Optional but recommended
        $sql .= " AND c.category = '$category'";
    }

    $sql .= " ORDER BY c.title";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $reportData = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $message = "No courses found matching the criteria.";
        }
    } else {
        $message = "Error executing query: " . mysqli_error($conn);
    }
}


// Function to generate instructors report
function generateInstructorsReport($conn) {
    global $reportData, $message;
    
    $sql = "SELECT i.*, COUNT(c.id) AS course_count 
    FROM instructors i
    LEFT JOIN courses c ON i.instructor_id = c.instructor_id
    GROUP BY i.instructor_id
    ORDER BY i.name";

    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $reportData = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $message = "No instructors found in the database.";
        }
    } else {
        $message = "Error executing query: " . mysqli_error($conn);
    }
}


// Function to generate projects report
function generateProjectsReport($conn, $status) {
    global $reportData, $message;
    
    $sql = "SELECT p.*, c.title AS course_title, 
            COUNT(DISTINCT sp.student_id) AS student_count 
            FROM projects p
            LEFT JOIN courses c ON p.course_id = c.id
            LEFT JOIN student_projects sp ON p.id = sp.project_id
            WHERE 1=1";
    
    if (!empty($status)) {
        $sql .= " AND p.status = '$status'";
    }
    
    $sql .= " GROUP BY p.id ORDER BY p.deadline";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $reportData = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $message = "No projects found matching the criteria.";
        }
    } else {
        $message = "Error executing query: " . mysqli_error($conn);
    }
}

function generateFeedbackReport($conn, $start_date, $end_date) {
    global $reportData, $message;

    $sql = "SELECT f.*, c.title AS course_title, 
            u.full_name AS user_full_name
            FROM student_feedback f
            LEFT JOIN courses c ON f.course_id = c.id
            LEFT JOIN users u ON f.user_id = u.id
            WHERE 1=1";

    if (!empty($start_date)) {
        $sql .= " AND f.submission_date >= '$start_date'";
    }

    if (!empty($end_date)) {
        $sql .= " AND f.submission_date <= '$end_date'";
    }

    $sql .= " ORDER BY f.submission_date DESC";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            $reportData = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $message = "No feedback found matching the criteria.";
        }
    } else {
        $message = "Error executing query: " . mysqli_error($conn);
    }
}
// Function to fetch all instructors for dropdown
function getInstructors($conn) {
    $instructors = [];
    $sql = "SELECT name FROM instructors ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $instructors[] = $row['name'];
        }
    }
    
    return $instructors;
}

function getCourseCategories($conn) {
    $categories = [];
    $sql = "SELECT DISTINCT type FROM courses WHERE type IS NOT NULL ORDER BY type";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row['type'];
        }
    }
    
    return $categories;
}

// Get instructors for dropdown
$instructors = getInstructors($conn);

// Get course categories for dropdown
$categories = getCourseCategories($conn);

// Function to render report output
function renderReportTable($reportData, $report_type) {
    if (empty($reportData)) {
        return "<p>No data to display.</p>";
    }
    
    $output = "<div class='table-responsive'><table class='table table-striped table-bordered'><thead><tr>";
    
    // Get headers from the first row
    $headers = array_keys($reportData[0]);
    foreach ($headers as $header) {
        // Format header for display
        $display_header = ucwords(str_replace('_', ' ', $header));
        $output .= "<th>$display_header</th>";
    }
    
    $output .= "</tr></thead><tbody>";
    
    // Add rows
    foreach ($reportData as $row) {
        $output .= "<tr>";
        foreach ($row as $key => $value) {
            // Format specific columns
            if ($key == 'registration_date' || $key == 'enrollment_date' || $key == 'deadline' || $key == 'submission_date' || $key == 'dob') {
                $value = !empty($value) ? date('Y-m-d', strtotime($value)) : '';
            } elseif ($key == 'status') {
                $value = ucfirst($value);
            } elseif ($key == 'description' || $key == 'comments' || $key == 'feedback_text' || $key == 'address' || $key == 'qualifications') {
                $value = nl2br(htmlspecialchars($value));
            }
            
            $output .= "<td>$value</td>";
        }
        $output .= "</tr>";
    }
    
    $output .= "</tbody></table></div>";
    
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Education Portal - Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #f8f9fa;
            padding: 20px;
        }

        /* Main Content Styles */
        .main-content {
            background: #f8f9fa;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border: none;
        }

        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            background: white;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background: #17a2b8;
            border: none;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #138496;
        }

        .btn-success {
            background: #28a745;
            border: none;
            transition: background 0.3s ease;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-info {
            background: #17a2b8;
            border: none;
            color: white;
            transition: background 0.3s ease;
        }

        .btn-info:hover {
            background: #138496;
            color: white;
        }

        .alert-info {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            color: #0d47a1;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
        }

        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #333;
        }

        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem;
            font-weight: 600;
        }

        .table td {
            padding: 0.75rem;
            border-bottom: 1px solid #dee2e6;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }

        /* Print styles */
        @media print {
            .btn, form {
                display: none;
            }
            
            .main-content {
                padding: 0;
            }
            
            .card {
                box-shadow: none;
                border: none;
            }
        }
    </style>
</head>
<body>
    <!-- Main content -->
    <main class="main-content">
        <header>
            <h1><i class="fas fa-chart-bar"></i> Generate Reports</h1>
            <div>
                <a href="admin dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="admin login.php" onclick="logout()" class="btn btn-outline-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </header>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-filter"></i> Report Parameters</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select name="report_type" id="report_type" class="form-select" required>
                                <option value="" <?php echo empty($report_type) ? 'selected' : ''; ?>>Select Report Type</option>
                                <option value="students" <?php echo $report_type == 'students' ? 'selected' : ''; ?>>Students</option>
                                <option value="courses" <?php echo $report_type == 'courses' ? 'selected' : ''; ?>>Courses</option>
                                <option value="instructors" <?php echo $report_type == 'instructors' ? 'selected' : ''; ?>>Instructors</option>
                                <option value="projects" <?php echo $report_type == 'projects' ? 'selected' : ''; ?>>Projects</option>
                                <option value="feedback" <?php echo $report_type == 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                            </select>
                        </div>

                        <!-- Date filters - show for students, enrollments, and feedback reports -->
                        <div class="col-md-4 date-filter <?php echo in_array($report_type, ['students', 'enrollments', 'feedback']) ? '' : 'd-none'; ?>">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="col-md-4 date-filter <?php echo in_array($report_type, ['students', 'enrollments', 'feedback']) ? '' : 'd-none'; ?>">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                    </div>
                    
                    <!-- Instructor filter - show for enrollments report only -->
                    <div class="row mb-3 instructor-filter <?php echo $report_type == 'enrollments' ? '' : 'd-none'; ?>">
                        <div class="col-md-4">
                            <label for="instructor" class="form-label">Instructor</label>
                            <select name="instructor" id="instructor" class="form-select">
                                <option value="">All Instructors</option>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?php echo htmlspecialchars($inst); ?>" <?php echo $instructor == $inst ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($inst); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="dropped" <?php echo $status == 'dropped' ? 'selected' : ''; ?>>Dropped</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Project status filter - show for projects report only -->
                    <div class="row mb-3 project-filter <?php echo $report_type == 'projects' ? '' : 'd-none'; ?>">
                        <div class="col-md-4">
                            <label for="status" class="form-label">Project Status</label>
                            <select name="status" id="project_status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="in_progress" <?php echo $status == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Category filter - show for courses report only -->
                    <div class="row mb-3 category-filter <?php echo $report_type == 'courses' ? '' : 'd-none'; ?>">
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-chart-bar"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($reportData)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>
                        <i class="fas fa-table"></i>
                        <?php 
                        $title = ucfirst($report_type) . " Report";
                        if (!empty($start_date) && !empty($end_date)) {
                            $title .= " (" . $start_date . " to " . $end_date . ")";
                        }
                        echo $title;
                        ?>
                    </h5>
                    <div>
                        <button class="btn btn-success" onclick="exportToExcel('report_table')">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                        <a href="report_pdf.php?report_type=<?php echo $report_type; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&instructor=<?php echo urlencode($instructor); ?>&status=<?php echo $status; ?>&category=<?php echo urlencode($category); ?>" target="_blank" class="btn btn-info">
                            <i class="fas fa-file-pdf"></i> View Printable Report
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div id="report_table">
                        <?php echo renderReportTable($reportData, $report_type); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide filters based on report type
        document.getElementById('report_type').addEventListener('change', function() {
            const reportType = this.value;
            
            // Date filter visibility
            const dateFilters = document.querySelectorAll('.date-filter');
            dateFilters.forEach(filter => {
                filter.classList.toggle('d-none', !['students', 'enrollments', 'feedback'].includes(reportType));
            });
            
            // Instructor filter visibility
            const instructorFilters = document.querySelectorAll('.instructor-filter');
            instructorFilters.forEach(filter => {
                filter.classList.toggle('d-none', reportType !== 'enrollments');
            });
            
            // Project status filter visibility
            const projectFilters = document.querySelectorAll('.project-filter');
            projectFilters.forEach(filter => {
                filter.classList.toggle('d-none', reportType !== 'projects');
            });
            
            // Category filter visibility
            const categoryFilters = document.querySelectorAll('.category-filter');
            categoryFilters.forEach(filter => {
                filter.classList.toggle('d-none', reportType !== 'courses');
            });
        });
        
        // Export to Excel function
        function exportToExcel(tableID) {
            let tableHTML = document.getElementById(tableID).outerHTML;
            let filename = 'education_portal_report_' + new Date().toISOString().slice(0, 10) + '.xls';
            
            let downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            
            // Specify file format using MIME types
            let dataType = 'application/vnd.ms-excel';
            
            // Add BOM for proper UTF-8 encoding
            tableHTML = '\ufeff' + tableHTML;
            
            // Create a download link
            downloadLink.href = 'data:' + dataType + ', ' + encodeURIComponent(tableHTML);
            downloadLink.download = filename;
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
        
        // Logout function
        function logout() {
            console.log('Logging out admin...');
            localStorage.removeItem('adminLoggedIn');
            sessionStorage.clear();
            return true;
        }
    </script>
</body>
</html>