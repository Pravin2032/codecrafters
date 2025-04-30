<?php
session_start();
require_once 'db_connection.php'; // Ensure this file exists with your database connection



// Get parameters from URL
$report_type = $_GET['report_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$instructor = $_GET['instructor'] ?? '';
$status = $_GET['status'] ?? '';
$category = $_GET['category'] ?? '';
$reportData = [];
$message = '';

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
        $message = "Invalid report type.";
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
        $category = mysqli_real_escape_string($conn, $category);
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

// Function to render report table
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
                // Truncate long text fields for readability
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 97) . '...';
                }
            }
            
            $output .= "<td>$value</td>";
        }
        $output .= "</tr>";
    }
    
    $output .= "</tbody></table></div>";
    
    return $output;
}

// Get report title
function getReportTitle($report_type, $start_date, $end_date, $category, $status) {
    $title = ucfirst($report_type) . " Report";
    
    // Add date range if provided
    if (!empty($start_date) && !empty($end_date)) {
        $title .= " (" . $start_date . " to " . $end_date . ")";
    }
    
    // Add category if provided
    if (!empty($category)) {
        $title .= " - Category: " . $category;
    }
    
    // Add status if provided
    if (!empty($status)) {
        $title .= " - Status: " . ucfirst($status);
    }
    
    return $title;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printable Report - <?php echo ucfirst($report_type); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 20px;
            background-color: #f8f9fa;
        }
        
        .report-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .report-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .report-metadata {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
        }
        
        .report-actions {
            margin-bottom: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
            padding: 12px;
            border: 1px solid #ddd;
        }
        
        .table td {
            padding: 10px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        /* Print styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
                background: white;
            }
            
            .report-container {
                box-shadow: none;
                padding: 15px;
                margin: 0;
                max-width: 100%;
            }
            
            .report-actions, .no-print {
                display: none !important;
            }
            
            .table th {
                background-color: #f5f5f5 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
            
            .table-striped tbody tr:nth-of-type(odd) {
                background-color: rgba(0, 0, 0, 0.02) !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .report-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .report-title {
                margin-bottom: 10px;
            }
            
            .table th, .table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="report-header">
            <div>
                <h1 class="report-title">
                    <i class="fas fa-file-alt"></i> 
                    <?php echo getReportTitle($report_type, $start_date, $end_date, $category, $status); ?>
                </h1>
                <div class="report-metadata">
                    <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
                    <?php if (!empty($start_date) && !empty($end_date)): ?>
                        <p>Date Range: <?php echo $start_date; ?> to <?php echo $end_date; ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="report-actions no-print">
                <button onclick="window.print();" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <a href="report.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($reportData)): ?>
            <div id="report_content">
                <?php echo renderReportTable($reportData, $report_type); ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No data available for this report.
            </div>
        <?php endif; ?>
        
        <footer class="mt-4 text-center text-muted">
            <p><small>Education Portal &copy; <?php echo date('Y'); ?></small></p>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-print when the page loads (optional - uncomment to enable)
        /*
        window.onload = function() {
            window.print();
        }
        */
    </script>
</body>
</html>