<?php
// Configure PHP to handle larger file uploads - place this at the top of your file
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300); // 5 minutes
ini_set('max_input_time', 300); // 5 minutes

// Add this at the beginning of your PHP file to help debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "codecrafters";

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Test the connection with a simple query
    $test_query = "SELECT 1";
    $test_result = $conn->query($test_query);
    if (!$test_result) {
        throw new Exception("Database test query failed: " . $conn->error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}

// Initialize variables
$message = "";
$course_materials = [];

// Define the video directories we'll scan
$video_directories = [
    'assets/videos/',
    'uploads/videos/',
    // Add more directories if needed
];

// Function to scan directories for video files
function scanDirectoryForVideos($directory) {
    $videos = [];
    
    // Check if directory exists
    if (!file_exists($directory)) {
        return $videos;
    }
    
    // Get all files in directory
    $files = scandir($directory);
    
    // Filter video files by common video extensions
    $video_extensions = ['mp4', 'webm', 'mov', 'avi', 'wmv'];
    
    foreach ($files as $file) {
        // Skip . and .. directory entries
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $video_extensions)) {
            $videos[] = [
                'name' => $file,
                'path' => $directory . $file,
                'size' => filesize($directory . $file),
                'type' => mime_content_type($directory . $file)
            ];
        }
    }
    
    return $videos;
}

// Replace your existing course insertion code with this
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    // Add debugging
    error_log("Add course form submitted");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Add new course
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $status = $_POST['status']; 
  
    // Process image upload
    $image = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Windows-compatible image path handling
        $target_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "images" . DIRECTORY_SEPARATOR;
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            // First check/create uploads directory
            $uploads_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "uploads";
            if (!file_exists($uploads_dir)) {
                if (!@mkdir($uploads_dir)) {
                    error_log("Failed to create uploads directory: " . error_get_last()['message']);
                }
            }
            
            // Then create images directory
            if (!@mkdir($target_dir)) {
                error_log("Failed to create images directory: " . error_get_last()['message']);
            }
        }
        
        // Create unique filename and set web path
        $new_filename = uniqid('img_') . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $new_filename;
        $web_path = "uploads/images/" . $new_filename; // Web path with forward slashes
        
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image = $web_path;
        } else {
            error_log("Failed to upload image: " . error_get_last()['message']);
        }
    }  
    
    try {
        // Insert course into database using prepared statement
        $sql = "INSERT INTO courses (title, description, price, image, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        error_log("About to execute SQL: " . $sql);
        error_log("Parameters: title=" . $title . ", desc=" . substr($description, 0, 50) . "..., price=" . $price . ", image=" . $image . ", status=" . $status);
        
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ssdss", $title, $description, $price, $image, $status);
        if ($stmt->execute() === false) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $course_id = $conn->insert_id;
        error_log("Course inserted with ID: " . $course_id);
        $stmt->close();
        
        // Process materials (PDFs and videos)
        if (processMaterials($conn, $course_id, true)) {
            error_log("Materials processed successfully");
            $message = "<div class='alert alert-success'>
                          <i class='fas fa-check-circle me-2'></i>New course added successfully!
                        </div>";       
            // Redirect to prevent form resubmission
            echo "<script>window.location.href = '?tab=courses&added=1';</script>";
            exit;
        } else {
            error_log("Materials processing failed");
            $message .= "<div class='alert alert-warning'>
                           <i class='fas fa-exclamation-triangle me-2'></i>Course added but there was an issue with some materials.
                         </div>";
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        $message = "<div class='alert alert-danger'>
                      <i class='fas fa-times-circle me-2'></i>Error: " . $e->getMessage() . "
                    </div>";
    }
}

// Process delete material request
if (isset($_POST['delete_material'])) {
    // Delete material
    $material_id = $_POST['material_id'];
    $file_path = $_POST['file_path'];    
    
    // Delete file from server if it exists and is not an external link
    $sql_check = "SELECT is_external FROM course_materials WHERE material_id = ?";
    $stmt = $conn->prepare($sql_check);
    $stmt->bind_param("i", $material_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $material_data = $result->fetch_assoc();
        if ($material_data['is_external'] == 0) {
            // Convert web path to system path for file operations
            $sys_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file_path);
            
            if (file_exists($sys_path)) {
                if (!@unlink($sys_path)) {
                    error_log("Failed to delete file: " . $sys_path . " - " . error_get_last()['message']);
                } else {
                    error_log("File deleted successfully: " . $sys_path);
                }
            } else {
                error_log("File not found for deletion: " . $sys_path);
            }
        }
    }
    $stmt->close();
    
    // Delete from database
    $sql = "DELETE FROM course_materials WHERE material_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $material_id);
    
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>
                      <i class='fas fa-check-circle me-2'></i>Material deleted successfully!
                    </div>";
    } else {
        $message = "<div class='alert alert-danger'>
                      <i class='fas fa-times-circle me-2'></i>Error deleting material: " . $stmt->error . "
                    </div>";
    } 
    $stmt->close();
}

// Function to process course materials (PDFs and videos) with Windows compatibility
function processMaterials($conn, $course_id, $is_new_course) {
    $success = true;       
    
    // Process PDF uploads
    if (isset($_FILES['pdf_files'])) {
        $pdf_count = count($_FILES['pdf_files']['name']); 
        for ($i = 0; $i < $pdf_count; $i++) {
            // Only process if there's a file uploaded and a title provided
            if ($_FILES['pdf_files']['error'][$i] == 0 && !empty($_POST['pdf_titles'][$i])) {
                $pdf_title = $_POST['pdf_titles'][$i];
                
                // Windows-compatible path
                $target_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "pdfs" . DIRECTORY_SEPARATOR;
                
                // Create directory structure recursively
                if (!file_exists($target_dir)) {
                    $uploads_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "uploads";
                    if (!file_exists($uploads_dir)) {
                        if (!@mkdir($uploads_dir)) {
                            error_log("Failed to create uploads directory: " . error_get_last()['message']);
                            continue;
                        }
                    }
                    
                    if (!@mkdir($target_dir)) {
                        error_log("Failed to create pdfs directory: " . error_get_last()['message']);
                        continue;
                    }
                    error_log("Created PDF directory: " . $target_dir);
                }  
                
                // Generate a unique filename to prevent overwriting
                $file_extension = pathinfo($_FILES['pdf_files']['name'][$i], PATHINFO_EXTENSION);
                $new_filename = uniqid('pdf_') . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;
                $web_path = "uploads/pdfs/" . $new_filename; // Store path for web access with forward slashes
                
                if (move_uploaded_file($_FILES['pdf_files']['tmp_name'][$i], $target_file)) {
                    // Insert into database - updated to match the actual table structure
                    $sql = "INSERT INTO course_materials (course_id, title, file_path, type, material_type, is_external, is_internal_video) 
                            VALUES (?, ?, ?, 'pdf', 'pdf', 0, 0)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iss", $course_id, $pdf_title, $web_path);
                    
                    if (!$stmt->execute()) {
                        error_log("Error inserting PDF: " . $stmt->error);
                        $success = false;
                    } 
                    $stmt->close();
                } else {
                    $upload_error = $_FILES['pdf_files']['error'][$i];
                    error_log("Failed to move uploaded PDF file. Error code: " . $upload_error);
                    error_log("PHP Error: " . error_get_last()['message']);
                    $success = false;
                }
            }
        }
    }
    
    // Process video uploads or video URLs
    if (isset($_POST['video_titles'])) {
        $video_count = count($_POST['video_titles']);
        
        for ($i = 0; $i < $video_count; $i++) {
            if (!empty($_POST['video_titles'][$i])) {
                $video_title = $_POST['video_titles'][$i];
                
                // Get video type for this index
                $video_type = isset($_POST['video_types'][$i]) ? $_POST['video_types'][$i] : 'url';
                
                $video_path = '';
                $is_external = 0;
                $is_internal_video = 0;
                
                // Debug output
                error_log("Processing video #{$i}: Title={$video_title}, Type={$video_type}");
                
                if ($video_type == 'url' && !empty($_POST['video_urls'][$i])) {
                    // Store video URL
                    $video_path = $_POST['video_urls'][$i];
                    $is_external = 1; // This should be 1 for external URLs
                    $is_internal_video = 0; // External URL is not an internal video
                    error_log("External video URL: $video_path");
                } elseif ($video_type == 'file') {
                    // Check if file exists and has no error
                    if (isset($_FILES['video_files']['name'][$i]) && 
                        isset($_FILES['video_files']['tmp_name'][$i]) && 
                        $_FILES['video_files']['error'][$i] == 0 && 
                        !empty($_FILES['video_files']['tmp_name'][$i])) {
                        
                        // Windows-compatible path with proper directory separators
                        $target_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR . "videos" . DIRECTORY_SEPARATOR;
                        
                        // Create directory structure recursively with better Windows compatibility
                        if (!file_exists($target_dir)) {
                            // First create assets directory if needed
                            $assets_dir = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . "assets";
                            if (!file_exists($assets_dir)) {
                                if (!@mkdir($assets_dir)) {
                                    error_log("Failed to create assets directory: " . error_get_last()['message']);
                                    continue;
                                }
                            }
                            
                            // Then create videos directory
                            if (!@mkdir($target_dir)) {
                                error_log("Failed to create video directory: " . error_get_last()['message']);
                                continue;
                            }
                            error_log("Created video directory: " . $target_dir);
                        }
                        
                        // Debug the upload
                        error_log("Processing video file: " . $_FILES['video_files']['name'][$i]);
                        error_log("Temp file exists: " . (file_exists($_FILES['video_files']['tmp_name'][$i]) ? 'Yes' : 'No'));
                        error_log("Temp file size: " . filesize($_FILES['video_files']['tmp_name'][$i]));
                        error_log("Upload directory writable: " . (is_writable($target_dir) ? 'Yes' : 'No'));
                        
                        // Make sure file extension is checked correctly
                        $file_extension = pathinfo($_FILES['video_files']['name'][$i], PATHINFO_EXTENSION);
                        $allowed_formats = ['mp4', 'webm', 'mov', 'avi', 'wmv'];
                        
                        if (!in_array(strtolower($file_extension), $allowed_formats)) {
                            error_log("Invalid video format: " . $file_extension);
                            continue;
                        }
                        
                        // Generate unique filename
                        $new_filename = uniqid('video_') . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;
                        $web_path = "assets/videos/" . $new_filename; // Store with forward slashes for web URLs
                        
                        // Try to move the file with better Windows error handling
                        $upload_result = move_uploaded_file($_FILES['video_files']['tmp_name'][$i], $target_file);
                        if ($upload_result) {
                            $video_path = $web_path;
                            $is_external = 0;
                            $is_internal_video = 1;
                            error_log("Video file moved successfully to: " . $target_file);
                        } else {
                            // Detailed error logging for troubleshooting
                            $upload_error = $_FILES['video_files']['error'][$i];
                            error_log("Failed to move video file. Error code: " . $upload_error);
                            error_log("PHP Error: " . error_get_last()['message']);
                            
                            // Try copy as a fallback method
                            if (copy($_FILES['video_files']['tmp_name'][$i], $target_file)) {
                                $video_path = $web_path;
                                $is_external = 0;
                                $is_internal_video = 1;
                                error_log("Video file copied successfully using copy() as fallback to: " . $target_file);
                            } else {
                                error_log("Copy fallback also failed. PHP error: " . error_get_last()['message']);
                                continue;
                            }
                        }
                    } else {
                        $error_code = isset($_FILES['video_files']['error'][$i]) ? $_FILES['video_files']['error'][$i] : 'not set';
                        error_log("Video file not properly uploaded for index " . $i);
                        error_log("Error code: " . $error_code);
                        // Translate error code to message
                        $error_messages = [
                            0 => "No error",
                            1 => "The uploaded file exceeds the upload_max_filesize directive",
                            2 => "The uploaded file exceeds the MAX_FILE_SIZE directive",
                            3 => "The uploaded file was only partially uploaded",
                            4 => "No file was uploaded",
                            6 => "Missing a temporary folder",
                            7 => "Failed to write file to disk",
                            8 => "A PHP extension stopped the file upload"
                        ];
                        if (isset($error_messages[$error_code])) {
                            error_log("Error meaning: " . $error_messages[$error_code]);
                        }
                        continue;
                    }
                } elseif ($video_type == 'manual' && !empty($_POST['video_manual_paths'][$i])) {
                    // Use the manually entered path
                    $video_path = $_POST['video_manual_paths'][$i];
                    $is_external = 0;
                    $is_internal_video = 1; // Treat as internal video
                    error_log("Using manually entered video path: " . $video_path);
                } elseif ($video_type == 'existing') {
                    // Use the selected existing video
                    if (!empty($_POST['existing_videos'][$i])) {
                        $video_path = $_POST['existing_videos'][$i];
                        $is_external = 0;
                        $is_internal_video = 1;
                        error_log("Using existing video: " . $video_path);
                    }
                }
                
                // Only insert if we have a valid path
                if (!empty($video_path)) {
                    // Sanitize data
                    $video_title = htmlspecialchars($video_title);
                    $video_path = htmlspecialchars($video_path);
                    
                    // Log the values we're about to insert
                    error_log("Inserting video with: title=$video_title, path=$video_path, is_external=$is_external, is_internal_video=$is_internal_video");
                    
                    // Updated SQL to match course_materials table structure
                    $sql = "INSERT INTO course_materials (course_id, title, file_path, type, material_type, is_external, is_internal_video) 
                            VALUES (?, ?, ?, 'video', 'video', ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issii", $course_id, $video_title, $video_path, $is_external, $is_internal_video);
                    
                    if (!$stmt->execute()) {
                        error_log("Error inserting video: " . $stmt->error);
                        $success = false;
                    } else {
                        error_log("Video inserted successfully into database: $video_title");
                    }
                    $stmt->close();
                }
            }
        }
    }
    
    return $success;
}

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = $_GET['delete']; 
    // First, delete associated materials
    // Get materials to delete files from server
    $materials_sql = "SELECT * FROM course_materials WHERE course_id = ?";
    $stmt = $conn->prepare($materials_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $materials_result = $stmt->get_result();
    while ($material = $materials_result->fetch_assoc()) {
        // Only delete file if it's not an external URL
        if ($material['is_external'] == 0) {
            // Convert web path to system path
            $sys_path = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $material['file_path']);
            
            if (file_exists($sys_path)) {
                if (!@unlink($sys_path)) {
                    error_log("Failed to delete file during course deletion: " . $sys_path . " - " . error_get_last()['message']);
                }
            }
        }
    }
    // Delete materials from database
    $delete_materials_sql = "DELETE FROM course_materials WHERE course_id = ?";
    $stmt = $conn->prepare($delete_materials_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    // Now delete the course
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);  
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>
                      <i class='fas fa-check-circle me-2'></i>Course and all associated materials deleted successfully!
                    </div>";
    } else {
        $message = "<div class='alert alert-danger'>
                      <i class='fas fa-times-circle me-2'></i>Error deleting course: " . $stmt->error . "
                    </div>";
    }
    
    $stmt->close();
}

// Function to safely get courses with material counts
function getCourses($conn) {
    $courses = [];
    $courses_sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id AND material_type = 'pdf') as pdf_count,
                    (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id AND material_type = 'video') as video_count,
                    (SELECT COUNT(*) FROM purchases WHERE course_id = c.id) as purchase_count 
                    FROM courses c ORDER BY c.created_at DESC";
    
    $result = $conn->query($courses_sql);
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $courses[] = $row;
        }
    }  
    return $courses;
}

// Function to get videos directly from the file system
function getExistingVideos() {
    global $video_directories;
    $all_videos = [];
    
    foreach ($video_directories as $directory) {
        $videos = scanDirectoryForVideos($directory);
        $all_videos = array_merge($all_videos, $videos);
    }
    
    return $all_videos;
}

// Function to safely get purchases with user details
function getPurchases($conn) {
    $purchases = [];
    $purchases_sql = "SELECT p.id, p.purchase_date, c.title as course_title, c.id as course_id, 
                      u.full_name, u.email, p.user_id, c.price
                      FROM purchases p
                      JOIN courses c ON p.course_id = c.id
                      JOIN users u ON p.user_id = u.id
                      ORDER BY p.purchase_date DESC";
    $result = $conn->query($purchases_sql);
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $purchases[] = $row;
        }
    }
    return $purchases;
}

// Function to get course materials by user
function getUserCourseMaterials($conn, $user_id) {
    $materials = [];
    $materials_sql = "SELECT cm.*, cm.material_id, cm.material_type, c.title as course_title, c.id as course_id
                      FROM course_materials cm
                      JOIN courses c ON cm.course_id = c.id
                      JOIN purchases p ON p.course_id = c.id
                      WHERE p.user_id = ?
                      ORDER BY cm.course_id, cm.material_type, cm.created_at";
    $stmt = $conn->prepare($materials_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
    }
    $stmt->close();
    return $materials;
}

// Function to get course materials and details by course ID
function getCourseMaterials($conn, $course_id) {
    // Get course details
    $course_sql = "SELECT * FROM courses WHERE id = ?";
    $stmt = $conn->prepare($course_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = $result->fetch_assoc();
    $stmt->close();
    
    // Get course materials
    $materials = [];
    $materials_sql = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY material_type, created_at";
    $stmt = $conn->prepare($materials_sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
    $stmt->close();
    
    return ['course' => $course, 'materials' => $materials];
}

// Video player functionality
if (isset($_GET['play_video']) && !empty($_GET['play_video'])) {
    $active_tab = "video_player";
    $video_path = $_GET['play_video'];
    $video_title = isset($_GET['title']) ? $_GET['title'] : "Video Player";
    $course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;
}

// Get data
$courses = getCourses($conn);
$purchases = getPurchases($conn);
$existing_videos = getExistingVideos();

// Scan directory functionality - for manual browsing of videos
if (isset($_GET['scan_directory'])) {
    $active_tab = "directory_browser";
    $directory_to_scan = $_GET['scan_directory'];
    $scanned_videos = scanDirectoryForVideos($directory_to_scan);
}

// Determine active tab based on URL parameters or POST data
if (!isset($active_tab)) {
    $active_tab = "courses";
    if (isset($_GET['tab'])) {
        $active_tab = $_GET['tab'];
    } elseif (isset($_GET['user_materials']) && is_numeric($_GET['user_materials'])) {
        $active_tab = "user_materials";
        $user_id = $_GET['user_materials'];
        $user_materials = getUserCourseMaterials($conn, $user_id);
        // Get user info
        $user_sql = "SELECT full_name, email FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user_info = $result->fetch_assoc();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeCrafters - Course Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Video.js for better video player -->
    <link href="https://vjs.zencdn.net/7.20.3/video-js.css" rel="stylesheet" />
    
    <style>
        /* Custom Styles */
        .navbar.bg-gradient-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        }
        
        .sidebar {
            background-color: #f8f9fc;
            border-right: 1px solid #e3e6f0;
        }
        
        .sidebar .nav-link {
            color: #5a5c69;
            padding: 1rem;
            margin: 0.2rem 0;
            border-radius: 0.35rem;
        }
        
        .sidebar .nav-link:hover {
            background-color: #eaecf4;
        }
        
        .sidebar .nav-link.active {
            color: #4e73df;
            font-weight: 600;
            background-color: #eaecf4;
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            color: #4e73df;
        }
        
        .content-area {
            padding-top: 1.5rem;
        }
        .course-card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.25rem 2.25rem 0 rgba(58, 59, 69, 0.25);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-danger {
            background-color: #e74a3b;
            border-color: #e74a3b;
        }
        
        .btn-danger:hover {
            background-color: #be2617;
            border-color: #be2617;
        }
        
        .material-icon {
            font-size: 2rem;
            margin-right: 0.5rem;
        }
        
        .video-js {
            width: 100%;
            height: 500px;
        }
        
        .material-card {
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }
        
        .material-card:hover {
            background-color: #f8f9fc;
        }
        
        .material-actions {
            visibility: hidden;
            opacity: 0;
            transition: visibility 0s, opacity 0.2s linear;
        }
        
        .material-card:hover .material-actions {
            visibility: visible;
            opacity: 1;
        }
        
        .stats-card {
            border-left: 0.25rem solid #4e73df;
            border-radius: 0.35rem;
        }
        
        .stats-card.primary {
            border-left-color: #4e73df;
        }
        
        .stats-card.success {
            border-left-color: #1cc88a;
        }
        
        .stats-card.info {
            border-left-color: #36b9cc;
        }
        
        .stats-card.warning {
            border-left-color: #f6c23e;
        }
        
        .video-preview {
            position: relative;
            height: 150px;
            background-color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
            border-radius: 0.35rem;
        }
        
        .video-preview img {
            max-width: 100%;
            max-height: 100%;
        }
        
        .video-preview .play-icon {
            position: absolute;
            font-size: 3rem;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .video-preview:hover .play-icon {
            transform: scale(1.2);
            opacity: 1;
        }
        
        .directory-tree {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .directory-item {
            padding: 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .directory-item:hover {
            background-color: #f0f0f0;
        }
        
        .directory-item.video {
            color: #4e73df;
        }
        
        .directory-item.folder {
            color: #f6c23e;
        }
        
        #video_container video {
            max-width: 100%;
            height: auto;
        }
        .custom-dashboard-btn {
  background-color: #6c757d;
  border-color: #6c757d;
  color: #fff;
  transition: background-color 0.2s ease;
}

.custom-dashboard-btn:hover {
  background-color: #5a6268;
  color: #fff;
}
/* Main layout and spacing */
body {
    font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
    background-color: #f8f9fa;
    color: #333;
}

.container {
    padding: 2rem 1rem;
}

/* Card styling */
.card {
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
    border: none;
    transition: transform 0.2s, box-shadow 0.2s;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
    background-color: #ffffff;
    padding: 1rem 1.25rem;
    border-top-left-radius: 8px;
    border-top-right-radius: 8px;
}

.card-header h5 {
    font-weight: 600;
    color: #333;
}

.card-body {
    padding: 1.25rem;
}

/* Stat cards */
.stat-card {
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.stat-icon {
    margin-bottom: 0.75rem;
    background-color: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Card background colors */
.bg-primary {
    background-color: #4e73df !important;
}

.bg-success {
    background-color: #1cc88a !important;
}

.bg-info {
    background-color: #36b9cc !important;
}

.bg-warning {
    background-color: #f6c23e !important;
}

/* Table styling */
.table {
    margin-bottom: 0;
}

.table thead th {
    border-top: 0;
    background-color: #f8f9fa;
    color: #555;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.table td, .table th {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-color: #e3e6f0;
}

/* Badge styling */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    border-radius: 4px;
}

/* Progress bars */
.progress {
    height: 8px;
    border-radius: 4px;
    background-color: #eaecf4;
    margin-bottom: 1rem;
}

.progress-bar {
    border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-3, .col-md-4, .col-md-8 {
        margin-bottom: 1rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
    }
    
    .table-responsive {
        border: 0;
    }
}

/* Additional utilities */
.text-white {
    color: #fff !important;
}

.small {
    font-size: 85%;
}

.text-muted {
    color: #858796 !important;
}

.mb-0 {
    margin-bottom: 0 !important;
}

.mt-3 {
    margin-top: 1rem !important;
}

.mb-1 {
    margin-bottom: 0.25rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.mb-4 {
    margin-bottom: 1.5rem !important;
}

.me-2 {
    margin-right: 0.5rem !important;
}

.d-flex {
    display: flex !important;
}

.justify-content-between {
    justify-content: space-between !important;
}

.align-items-center {
    align-items: center !important;
}

.text-center {
    text-align: center !important;
}

    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 col-lg-2 px-0 sidebar vh-100 position-fixed">
                <div class="navbar bg-gradient-primary p-3 d-flex flex-column align-items-center mb-4">
                    <h4 class="text-white fw-bold mb-0 text-center">
                        <i class="fas fa-code me-2"></i>
                        CodeCrafters
                    </h4>
                </div>
                <div class="nav flex-column px-3">
                    <a href="?tab=courses" class="nav-link <?php echo $active_tab == 'courses' ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Courses
                    </a>
                    <a href="?tab=purchases" class="nav-link <?php echo $active_tab == 'purchases' ? 'active' : ''; ?>">
                        <i class="fas fa-shopping-cart"></i> Purchases
                    </a>
                    
                    <a href="?tab=add_course" class="nav-link <?php echo $active_tab == 'add_course' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i> Add New Course
                    </a>
                   
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 col-lg-10 ms-auto content-area">
                <div class="container-fluid">
                    <!-- Show message if any -->
                    <?php if(!empty($message)): ?>
                        <?php echo $message; ?>
                    <?php endif; ?>
                    
                    <!-- Added notification -->
                    <?php if(isset($_GET['added']) && $_GET['added'] == 1): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i>Course added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Content based on active tab -->
                    <?php if($active_tab == 'courses'): ?>
                        <!-- Courses Tab -->
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Course Management</h1>
    <div class="d-flex">
        <a href="admin dashboard.php" class="btn btn-secondary me-2 custom-dashboard-btn">
            <i class="fas fa-tachometer-alt me-1"></i>Go to Dashboard
        </a>
        <a href="?tab=add_course" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i>Add New Course
        </a>
    </div>
</div>

                        
                        <!-- Course Cards -->
                        <div class="row">
                            <?php foreach($courses as $course): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card course-card h-100">
                                        <?php if($course['image']): ?>
                                            <img src="<?php echo htmlspecialchars($course['image']); ?>" 
                                                 class="card-img-top" alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                                 style="height: 200px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white text-center py-5">
                                                <i class="fas fa-image fa-3x mb-2"></i>
                                                <p>No Image</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <span class="badge bg-<?php echo $course['status'] == 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($course['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="card-body">
                                            <p class="card-text">
                                                <?php echo substr(htmlspecialchars($course['description']), 0, 100); ?>...
                                            </p>
                                            
                                            <div class="d-flex justify-content-between mb-3">
                                                <div>
                                                    <i class="fas fa-file-pdf text-danger"></i> 
                                                    <?php echo $course['pdf_count']; ?> PDFs
                                                </div>
                                                <div>
                                                    <i class="fas fa-video text-primary"></i> 
                                                    <?php echo $course['video_count']; ?> Videos
                                                </div>
                                                <div>
                                                    <i class="fas fa-shopping-cart text-success"></i> 
                                                    <?php echo $course['purchase_count']; ?> Sales
                                                </div>
                                            </div>
                                            
                                            <p class="mb-0">
                                                <strong>Price:</strong> 
                                                ₹<?php echo number_format($course['price'], 2); ?>
                                            </p>
                                        </div>
                                        
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between">
                                               
                                                
                                                <a href="?delete=<?php echo $course['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Are you sure you want to delete this course? This will also delete all associated materials.')">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if(empty($courses)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>No courses found. 
                                        <a href="?tab=add_course">Add your first course</a>.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        
                    <?php elseif($active_tab == 'purchases'): ?>
                        <!-- Dashboard Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-book fa-lg"></i>
                                        </div>
                                        <div class="stat-value"><?php echo count($courses); ?></div>
                                        <div class="stat-label">Total Courses</div>
                                    </div></div> </div>
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-users fa-lg"></i>
                                        </div>
                                        <div class="stat-value"><?php echo count($purchases); ?></div>
                                        <div class="stat-label">Total Enrollments</div>
                                    </div> </div> </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-rupee-sign fa-lg"></i>
                                        </div>
                                        <div class="stat-value">
                                            <?php 
                                                $total_revenue = 0;
                                                foreach($purchases as $purchase) {
                                                    $total_revenue += $purchase['price'];
                                                }
                                                echo '₹' . number_format($total_revenue, 2);
                                            ?>
                                        </div>
                                        <div class="stat-label">Total Revenue</div>
                                    </div></div> </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-white">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="fas fa-file-alt fa-lg"></i>
                                        </div>
                                        <?php 
                                            $total_materials = 0;
                                            foreach($courses as $course) {
                                                $total_materials += $course['pdf_count'] + $course['video_count'];
                                            }
                                        ?>
                                        <div class="stat-value"><?php echo $total_materials; ?></div>
                                        <div class="stat-label">Total Materials</div>
                                    </div></div></div> </div> 
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Course Performance</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Course</th>
                                                        <th>Status</th>
                                                        <th>Enrollments</th>
                                                        <th>Revenue</th>
                                                        <th>Materials</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($courses as $course): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($course['title']); ?></td>
                                                            <td>
                                                                <span class="badge <?php echo $course['status'] == 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                                    <?php echo ucfirst($course['status']); ?>
                                                                </span>
                                                            </td>
                                                            <td><?php echo $course['purchase_count']; ?></td>
                                                            <td>₹<?php echo number_format($course['purchase_count'] * $course['price'], 2); ?></td>
                                                            <td><?php echo $course['pdf_count'] + $course['video_count']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>  </div></div></div>  </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Material Distribution</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                            $pdf_count = 0;
                                            $video_count = 0;
                                            foreach($courses as $course) {
                                                $pdf_count += $course['pdf_count'];
                                                $video_count += $course['video_count'];
                                            }
                                            $total = $pdf_count + $video_count;
                                            $pdf_percentage = $total > 0 ? round(($pdf_count / $total) * 100) : 0;
                                            $video_percentage = $total > 0 ? round(($video_count / $total) * 100) : 0;
                                        ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span>PDF Materials</span>
                                                <span><?php echo $pdf_percentage; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $pdf_percentage; ?>%" 
                                                     aria-valuenow="<?php echo $pdf_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>  </div>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span>Video Materials</span>
                                                <span><?php echo $video_percentage; ?>%</span>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $video_percentage; ?>%" 
                                                     aria-valuenow="<?php echo $video_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div> </div>
                                        <div class="text-center mt-3">
                                            <div class="small text-muted">Total Materials: <?php echo $total; ?></div>
                                            <div>
                                                <span class="badge bg-danger me-2">PDF: <?php echo $pdf_count; ?></span>
                                                <span class="badge bg-primary">Videos: <?php echo $video_count; ?></span>
                                            </div>
                                        </div></div></div></div></div>
                        <!-- Purchases Tab -->
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">Purchase Management</h1>
                        </div>
                        
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Purchases</h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover" id="purchases-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>User</th>
                                                <th>Course</th>
                                                <th>Price</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($purchases as $purchase): ?>
                                                <tr>
                                                    <td><?php echo $purchase['id']; ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($purchase['full_name']); ?><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($purchase['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <a href="?view_course=<?php echo $purchase['course_id']; ?>">
                                                            <?php echo htmlspecialchars($purchase['course_title']); ?>
                                                        </a>
                                                    </td>
                                                    <td>₹<?php echo number_format($purchase['price'], 2); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($purchase['purchase_date'])); ?></td>
                                                    <td>
                                                       
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            
                                            <?php if(empty($purchases)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center">No purchases found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        
                    <?php elseif($active_tab == 'add_course'): ?>
                        <!-- Add Course Tab -->
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">Add New Course</h1>
                        </div>
                        
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Course Information</h6>
                            </div>
                            <div class="card-body">
                                <form action="" method="post" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="title" class="form-label">Course Title</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="price" class="form-label">Price (₹)</label>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status" class="form-label">Status</label>
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="draft">Draft</option>
                                                <option value="active">Active
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="image" class="form-label">Course Image</label>
                                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                        <small class="text-muted">Recommended size: 1200x600 pixels</small>
                                    </div>
                                    
                                    <!-- Course Materials -->
                                    <h5 class="mt-4 mb-3">Course Materials</h5>
                                    
                                    <!-- PDF Materials -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">PDF Materials</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="pdf_materials">
                                                <div class="pdf-material mb-3">
                                                    <div class="row">
                                                        <div class="col-md-5">
                                                            <input type="text" class="form-control" name="pdf_titles[]" placeholder="PDF Title">
                                                        </div>
                                                        <div class="col-md-7">
                                                            <input type="file" class="form-control" name="pdf_files[]" accept=".pdf">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="add_pdf">
                                                <i class="fas fa-plus me-1"></i> Add More PDFs
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Video Materials -->
                                    <div class="card mb-4">
                                        <div class="card-header">
                                            <h6 class="mb-0">Video Materials</h6>
                                        </div>
                                        <div class="card-body">
                                            <div id="video_materials">
                                                <div class="video-material mb-4 pb-3 border-bottom">
                                                    <div class="row mb-2">
                                                        <div class="col-md-12">
                                                            <input type="text" class="form-control" name="video_titles[]" placeholder="Video Title" required>
                                                        </div>
                                                    </div>
                                                    <div class="row align-items-center">
                                                        <div class="col-md-3">
                                                            <select class="form-select video-type-select" name="video_types[]">
                                                                <option value="url">Video URL (YouTube, Vimeo, etc)</option>
                                                                
                                                                <option value="manual">Enter Video Path Manually</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-9">
                                                            <!-- URL option (default) -->
                                                            <div class="video-url-option">
                                                                <input type="url" class="form-control" name="video_urls[]" placeholder="Enter video URL (YouTube, Vimeo, etc)">
                                                            </div>
                                                            
                                                            <!-- File upload option (hidden by default) -->
                                                            <div class="video-file-option d-none">
                                                                <input type="file" class="form-control" name="video_files[]" accept="video/*">
                                                                <small class="text-muted">Max file size: 100MB. Supported formats: MP4, WebM, MOV</small>
                                                            </div>
                                                            
                                                            <!-- Existing video option (hidden by default) -->
                                                            <div class="video-existing-option d-none">
                                                                <select class="form-select" name="existing_videos[]">
                                                                    <option value="">-- Select Existing Video --</option>
                                                                    <?php foreach($existing_videos as $video): ?>
                                                                        <option value="<?php echo htmlspecialchars($video['path']); ?>">
                                                                            <?php echo htmlspecialchars($video['name']); ?> 
                                                                            (<?php echo round($video['size'] / 1048576, 2); ?> MB)
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <!-- Manual path option (hidden by default) -->
                                                            <div class="video-manual-option d-none">
                                                                <input type="text" class="form-control" name="video_manual_paths[]" placeholder="Enter video path manually (e.g., assets/videos/file.mp4)">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-secondary" id="add_video">
                                                <i class="fas fa-plus me-1"></i> Add More Videos
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="?tab=courses" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-1"></i> Cancel
                                        </a>
                                        <button type="submit" name="add_course" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Course
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php elseif($active_tab == 'course_viewer' && isset($course_data)): ?>
                        <!-- Course Viewer Tab -->
                        <div class="d-sm-flex align-items-center justify-content-between mb-4">
                            <h1 class="h3 mb-0 text-gray-800">
                                Course: <?php echo htmlspecialchars($course_data['course']['title']); ?>
                            </h1>
                            <div>
                                <a href="?tab=courses" class="btn btn-secondary ms-2">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Courses
                                </a>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="card shadow h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Course Information</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <?php if($course_data['course']['image']): ?>
                                                    <img src="<?php echo htmlspecialchars($course_data['course']['image']); ?>" 
                                                         class="img-fluid rounded mb-3" 
                                                         alt="<?php echo htmlspecialchars($course_data['course']['title']); ?>">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white text-center py-5 rounded mb-3">
                                                        <i class="fas fa-image fa-3x mb-2"></i>
                                                        <p>No Image</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-8">
                                                <h4><?php echo htmlspecialchars($course_data['course']['title']); ?></h4>
                                                <p class="text-muted">
                                                    Created: <?php echo date('F j, Y', strtotime($course_data['course']['created_at'])); ?>
                                                </p>
                                                <div class="mb-3">
                                                    <span class="badge bg-<?php echo $course_data['course']['status'] == 'published' ? 'success' : 'warning'; ?> mb-2">
                                                        <?php echo ucfirst($course_data['course']['status']); ?>
                                                    </span>
                                                    <span class="badge bg-primary ms-2">
                                                        $<?php echo number_format($course_data['course']['price'], 2); ?>
                                                    </span>
                                                </div>
                                                <p><?php echo nl2br(htmlspecialchars($course_data['course']['description'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card shadow h-100">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">Course Statistics</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="stats-card primary p-3 mb-3">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        PDF Materials
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php 
                                                            $pdf_count = 0;
                                                            foreach($course_data['materials'] as $material) {
                                                                if($material['material_type'] == 'pdf') $pdf_count++;
                                                            }
                                                            echo $pdf_count;
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-file-pdf fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="stats-card success p-3 mb-3">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                        Video Materials
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php 
                                                            $video_count = 0;
                                                            foreach($course_data['materials'] as $material) {
                                                                if($material['material_type'] == 'video') $video_count++;
                                                            }
                                                            echo $video_count;
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-video fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="stats-card info p-3">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                        Total Purchases
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        <?php 
                                                            $sql = "SELECT COUNT(*) as count FROM purchases WHERE course_id = ?";
                                                            $stmt = $conn->prepare($sql);
                                                            $stmt->bind_param("i", $course_data['course']['id']);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            $row = $result->fetch_assoc();
                                                            echo $row['count'];
                                                            $stmt->close();
                                                        ?>
                                                    </div>
                                                </div>
                                                <div class="col-auto">
                                                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Course Materials -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Course Materials</h5>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs mb-4" id="materials-tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="pdf-tab" data-bs-toggle="tab" 
                                                data-bs-target="#pdf-materials" type="button" role="tab">
                                            <i class="fas fa-file-pdf me-1 text-danger"></i> PDF Materials
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="video-tab" data-bs-toggle="tab" 
                                                data-bs-target="#video-materials" type="button" role="tab">
                                            <i class="fas fa-video me-1 text-primary"></i> Video Materials
                                        </button>
                                    </li>
                                </ul>
                                
                                <div class="tab-content" id="materials-content">
                                    <!-- PDF Materials Tab -->
                                    <div class="tab-pane fade show active" id="pdf-materials" role="tabpanel">
                                        <div class="row">
                                            <?php 
                                                $has_pdfs = false;
                                                foreach($course_data['materials'] as $material):
                                                    if($material['material_type'] == 'pdf'):
                                                        $has_pdfs = true;
                                            ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
    <div class="card material-card">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <i class="fas fa-file-pdf material-icon text-danger"></i>
                <div>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($material['title']); ?></h5>
                    <p class="card-text text-muted mb-0">
                        <?php 
                            if(file_exists($material['file_path'])) {
                                $filesize = filesize($material['file_path']);
                                echo 'Size: ' . round($filesize / 1024, 2) . ' KB';
                            } else {
                                echo 'File not found';
                            }
                        ?>
                    </p>
                </div>
            </div>
            <div class="material-actions mt-3 text-end">
                <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                   class="btn btn-sm btn-primary" target="_blank">
                    <i class="fas fa-eye me-1"></i> View
                </a>
                <a href="?download=<?php echo $material['id']; ?>" 
                   class="btn btn-sm btn-success">
                    <i class="fas fa-download me-1"></i> Download
                </a>
                <a href="?delete_material=<?php echo $material['id']; ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Are you sure you want to delete this material?')">
                    <i class="fas fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
</div>
<?php 
    endif;
    endforeach;
    
    if(!$has_pdfs):
?>
<div class="col-12">
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>No PDF materials found for this course.
        <a href="?tab=edit_course&id=<?php echo $course_data['course']['id']; ?>">Add PDFs</a>
    </div>
</div>
<?php endif; ?>
</div>
</div>

<!-- Video Materials Tab -->
<div class="tab-pane fade" id="video-materials" role="tabpanel">
    <div class="row">
        <?php 
            $has_videos = false;
            foreach($course_data['materials'] as $material):
                if($material['material_type'] == 'video'):
                    $has_videos = true;
                    // Determine video type (file, URL, etc.)
                    $video_type = '';
                    if(strpos($material['file_path'], 'youtube.com') !== false || 
                       strpos($material['file_path'], 'vimeo.com') !== false) {
                        $video_type = 'url';
                    } else {
                        $video_type = 'file';
                    }
        ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card material-card">
                <div class="video-preview">
                    <?php if($video_type == 'url'): ?>
                        <!-- YouTube/Vimeo thumbnail would be shown here -->
                        <img src="assets/images/video-placeholder.jpg" alt="Video Thumbnail">
                    <?php else: ?>
                        <!-- Local video thumbnail -->
                        <i class="fas fa-play-circle play-icon"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-video material-icon text-primary"></i>
                        <div>
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($material['title']); ?></h5>
                            <p class="card-text text-muted mb-0">
                                <?php 
                                    if($video_type == 'file' && file_exists($material['file_path'])) {
                                        $filesize = filesize($material['file_path']);
                                        echo 'Size: ' . round($filesize / 1048576, 2) . ' MB';
                                    } else if($video_type == 'url') {
                                        echo 'External Video';
                                    } else {
                                        echo 'File not found';
                                    }
                                ?>
                            </p>
                        </div>
                    </div>
                    <div class="material-actions mt-3 text-end">
                        <a href="?play_video=<?php echo $material['id']; ?>" 
                           class="btn btn-sm btn-primary">
                            <i class="fas fa-play me-1"></i> Play
                        </a>
                        <?php if($video_type == 'file'): ?>
                        <a href="?download=<?php echo $material['id']; ?>" 
                           class="btn btn-sm btn-success">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                        <?php endif; ?>
                        <a href="?delete_material=<?php echo $material['id']; ?>" 
                           class="btn btn-sm btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this material?')">
                            <i class="fas fa-trash me-1"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php 
                endif;
            endforeach;
            
            if(!$has_videos):
        ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>No video materials found for this course.
                <a href="?tab=edit_course&id=<?php echo $course_data['course']['id']; ?>">Add Videos</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

</div>
</div>
</div>

<?php elseif($active_tab == 'edit_course' && isset($edit_course)): ?>


<!-- Course Materials -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-primary">Course Materials</h6>
        <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" 
                data-bs-target="#addMaterialsCollapse">
            <i class="fas fa-plus me-1"></i> Add New Materials
        </button>
    </div>
    <div class="collapse" id="addMaterialsCollapse">
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                
                <ul class="nav nav-tabs mb-3" id="add-materials-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="add-pdf-tab" data-bs-toggle="tab" 
                                data-bs-target="#add-pdf-materials" type="button" role="tab">
                            <i class="fas fa-file-pdf me-1"></i> Add PDF
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-video-tab" data-bs-toggle="tab" 
                                data-bs-target="#add-video-materials" type="button" role="tab">
                            <i class="fas fa-video me-1"></i> Add Video
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="add-materials-content">
                    <!-- Add PDF Tab -->
                    <div class="tab-pane fade show active" id="add-pdf-materials" role="tabpanel">
                        <div class="mb-3">
                            <label for="pdf_title" class="form-label">PDF Title</label>
                            <input type="text" class="form-control" id="pdf_title" name="pdf_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="pdf_file" class="form-label">PDF File</label>
                            <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf" required>
                        </div>
                        <button type="submit" name="add_pdf_material" class="btn btn-primary">
                            <i class="fas fa-upload me-1"></i> Upload PDF
                        </button>
                    </div>
                    
                    <!-- Add Video Tab -->
                    <div class="tab-pane fade" id="add-video-materials" role="tabpanel">
                        <div class="mb-3">
                            <label for="video_title" class="form-label">Video Title</label>
                            <input type="text" class="form-control" id="video_title" name="video_title" required>
                        </div>
                        <div class="mb-3">
                            <label for="video_type" class="form-label">Video Type</label>
                            <select class="form-select" id="video_type" name="video_type" required>
                                <option value="url">Video URL (YouTube, Vimeo, etc)</option>
                                <option value="file">Upload Video File</option>
                                <option value="existing">Select Existing Video</option>
                                <option value="manual">Enter Video Path Manually</option>
                            </select>
                        </div>
                        
                        <!-- URL option (default) -->
                        <div id="video_url_option" class="mb-3">
                            <label for="video_url" class="form-label">Video URL</label>
                            <input type="url" class="form-control" id="video_url" name="video_url" 
                                   placeholder="Enter video URL (YouTube, Vimeo, etc)">
                        </div>
                        
                        <!-- File upload option (hidden by default) -->
                        <div id="video_file_option" class="mb-3 d-none">
                            <label for="video_file" class="form-label">Video File</label>
                            <input type="file" class="form-control" id="video_file" name="video_file" accept="video/*">
                            <small class="text-muted">Max file size: 100MB. Supported formats: MP4, WebM, MOV</small>
                        </div>
                        
                        <!-- Existing video option (hidden by default) -->
                        <div id="video_existing_option" class="mb-3 d-none">
                            <label for="existing_video" class="form-label">Existing Video</label>
                            <select class="form-select" id="existing_video" name="existing_video">
                                <option value="">-- Select Existing Video --</option>
                                <?php foreach($existing_videos as $video): ?>
                                    <option value="<?php echo htmlspecialchars($video['path']); ?>">
                                        <?php echo htmlspecialchars($video['name']); ?> 
                                        (<?php echo round($video['size'] / 1048576, 2); ?> MB)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Manual path option (hidden by default) -->
                        <div id="video_manual_option" class="mb-3 d-none">
                            <label for="video_manual_path" class="form-label">Video Path</label>
                            <input type="text" class="form-control" id="video_manual_path" name="video_manual_path" 
                                   placeholder="Enter video path manually (e.g., assets/videos/file.mp4)">
                        </div>
                        
                        <button type="submit" name="add_video_material" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Add Video
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Existing Materials List -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Existing Materials</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title</th>
                        <th>Path</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($edit_course_materials as $material): ?>
                    <tr>
                        <td>
                            <?php if($material['material_type'] == 'pdf'): ?>
                                <i class="fas fa-file-pdf text-danger"></i> PDF
                            <?php else: ?>
                                <i class="fas fa-video text-primary"></i> Video
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($material['title']); ?></td>
                        <td>
                            <small class="text-muted">
                                <?php echo htmlspecialchars($material['file_path']); ?>
                            </small>
                        </td>
                        <td>
                            <?php if($material['material_type'] == 'pdf'): ?>
                                <a href="<?php echo htmlspecialchars($material['file_path']); ?>" 
                                   class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php else: ?>
                                <a href="?play_video=<?php echo $material['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-play"></i>
                                </a>
                            <?php endif; ?>
                            <a href="?delete_material=<?php echo $material['id']; ?>&course_id=<?php echo $edit_course['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this material?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($edit_course_materials)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No materials found for this course</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif($active_tab == 'directory_browser'): ?>


<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and other JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    
    <script>
        // Material type selector for video
        document.querySelectorAll('.video-type-select').forEach(function(select) {
            select.addEventListener('change', function() {
                const parent = this.closest('.video-material');
                const type = this.value;
                
                // Hide all options
                parent.querySelector('.video-url-option').classList.add('d-none');
                parent.querySelector('.video-file-option').classList.add('d-none');
                parent.querySelector('.video-existing-option').classList.add('d-none');
                parent.querySelector('.video-manual-option').classList.add('d-none');
                
                // Show selected option
                parent.querySelector('.video-' + type + '-option').classList.remove('d-none');
            });
        });
        
        // For edit course page
        if(document.getElementById('video_type')) {
            document.getElementById('video_type').addEventListener('change', function() {
                const type = this.value;
                
                // Hide all options
                document.getElementById('video_url_option').classList.add('d-none');
                document.getElementById('video_file_option').classList.add('d-none');
                document.getElementById('video_existing_option').classList.add('d-none');
                document.getElementById('video_manual_option').classList.add('d-none');
                
                // Show selected option
                document.getElementById('video_' + type + '_option').classList.remove('d-none');
            });
        }
        
        // Add more PDF materials
        document.getElementById('add_pdf')?.addEventListener('click', function() {
            const container = document.getElementById('pdf_materials');
            const newMaterial = document.querySelector('.pdf-material').cloneNode(true);
            
            // Clear input values
            newMaterial.querySelectorAll('input').forEach(input => input.value = '');
            
            container.appendChild(newMaterial);
        });
        
        // Add more video materials
        document.getElementById('add_video')?.addEventListener('click', function() {
            const container = document.getElementById('video_materials');
            const newMaterial = document.querySelector('.video-material').cloneNode(true);
            
            // Clear input values
            newMaterial.querySelectorAll('input').forEach(input => input.value = '');
            newMaterial.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            
            // Reset to default video type (URL)
            newMaterial.querySelector('.video-url-option').classList.remove('d-none');
            newMaterial.querySelector('.video-file-option').classList.add('d-none');
            newMaterial.querySelector('.video-existing-option').classList.add('d-none');
            newMaterial.querySelector('.video-manual-option').classList.add('d-none');
            
            // Add event listener to the new select
            newMaterial.querySelector('.video-type-select').addEventListener('change', function() {
                const parent = this.closest('.video-material');
                const type = this.value;
                
                // Hide all options
                parent.querySelector('.video-url-option').classList.add('d-none');
                parent.querySelector('.video-file-option').classList.add('d-none');
                parent.querySelector('.video-existing-option').classList.add('d-none');
                parent.querySelector('.video-manual-option').classList.add('d-none');
                
                // Show selected option
                parent.querySelector('.video-' + type + '-option').classList.remove('d-none');
            });
            
            container.appendChild(newMaterial);
        });
    </script>
</body>
</html>