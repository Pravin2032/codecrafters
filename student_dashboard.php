<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

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

// Get user details
$email = $_SESSION['email'];
$query = "SELECT u.* FROM users u WHERE u.email = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile picture upload
$uploadSuccess = false;
$uploadError = "";
$infoUpdateSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['updateProfile'])) {
    // Process profile picture upload
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/profile_pictures/";
        
        // Create directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('profile_') . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;
        
        // Check if file is an image
        $imageFileType = strtolower($fileExtension);
        if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
            // Move uploaded file to destination
            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $targetFile)) {
                // Update profile picture path in database
                $updateQuery = "UPDATE users SET profile_image = ? WHERE email = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("ss", $targetFile, $email);
                
                if ($updateStmt->execute()) {
                    $uploadSuccess = true;
                    
                    // Update user variable with new profile picture
                    $user['profile_image'] = $targetFile;
                } else {
                    $uploadError = "Failed to update profile picture in database.";
                }
            } else {
                $uploadError = "Failed to upload image. Please try again.";
            }
        } else {
            $uploadError = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    // Update user info if provided
    $fullName = isset($_POST['fullName']) ? $_POST['fullName'] : $user['full_name'];
    $phone = isset($_POST['phone']) ? $_POST['phone'] : $user['phone'];
    
    $updateInfoQuery = "UPDATE users SET full_name = ?, phone = ? WHERE email = ?";
    $updateInfoStmt = $conn->prepare($updateInfoQuery);
    $updateInfoStmt->bind_param("sss", $fullName, $phone, $email);
    
    if ($updateInfoStmt->execute()) {
        // Update the user array with new values
        $user['full_name'] = $fullName;
        $user['phone'] = $phone;
        $infoUpdateSuccess = true;
    }
}

// This section has been fixed - ensure variables are defined before use
// Also, move this into a conditional block so it only runs when needed
if (isset($_POST['startCourse']) && isset($_POST['course_id']) && isset($user['id'])) {
    $user_id = $user['id']; // Make sure to use the correct column name from your users table
    $course_id = $_POST['course_id'];
    
    // When a user starts a course, update the 'enrolled' status
    $updateEnrollmentQuery = "UPDATE purchases SET enrolled = 1 WHERE user_id = ? AND course_id = ?";
    $updateStmt = $conn->prepare($updateEnrollmentQuery);
    $updateStmt->bind_param("ii", $user_id, $course_id);
    $updateStmt->execute();
}

// Get enrolled courses for this user
$enrolledCourses = [];
$enrollmentCount = 0; // Initialize enrollment counter

if (isset($user['id'])) {
    // Modified query to check for enrollment status
    $courseQuery = "SELECT c.*, p.enrolled FROM courses c 
JOIN purchases p ON c.id = p.course_id 
WHERE p.user_id = ?";
    $courseStmt = $conn->prepare($courseQuery);
    $courseStmt->bind_param("i", $user['id']); // Make sure to use the correct column name
    $courseStmt->execute();
    $courseResult = $courseStmt->get_result();
    
    while ($course = $courseResult->fetch_assoc()) {
        $enrolledCourses[] = $course;
        // Count enrolled courses separately (if the enrolled field exists and is set to 1)
        if (isset($course['enrolled']) && $course['enrolled'] == 1) {
            $enrollmentCount++;
        }
    }
}
// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Code Crafters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
       /* General Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: #f5f7fa;
    color: #333;
    line-height: 1.6;
}

/* Dashboard Layout */
.dashboard-container {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Header Styles */
.header {
    background-color: #3498db;
    color: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    font-weight: bold;
}

.logo i {
    font-size: 1.8rem;
}

.user-nav {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.profile-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #2980b9;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.profile-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-img i {
    font-size: 1.5rem;
    color: white;
}

.logout-btn {
    background-color: #2980b9;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: #1c638d;
}

/* Content Area */
.content-area {
    display: flex;
    flex: 1;
}

/* Sidebar */
.sidebar {
    width: 250px;
    background-color: #2c3e50;
    padding: 2rem 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    padding: 1rem 2rem;
    color: #ecf0f1;
    text-decoration: none;
    transition: background-color 0.3s;
}

.nav-item:hover {
    background-color: #34495e;
}

.nav-item.active {
    background-color: #3498db;
    border-left: 4px solid #ecf0f1;
}

/* Main Content Area */
.main-content {
    flex: 1;
    padding: 2rem;
    overflow-y: auto;
}

.section-title {
    margin-bottom: 1.5rem;
    font-size: 1.8rem;
    color: #2c3e50;
    border-bottom: 2px solid #3498db;
    padding-bottom: 0.5rem;
}

/* Alert Messages */
.alert {
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Profile Container */
.profile-container {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-bottom: 2rem;
}

.profile-picture {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #ecf0f1;
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.profile-picture img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-icon {
    position: absolute;
    bottom: 0;
    right: 0;
    background-color: #3498db;
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    cursor: pointer;
    transition: background-color 0.3s;
}

.edit-icon:hover {
    background-color: #2980b9;
}

.user-info {
    flex: 1;
    min-width: 250px;
}

.user-info h2 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.user-info p {
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    color: #555;
}

/* Stats Grid */
.stats-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    width: 100%;
    margin-top: 1.5rem;
}

.stat-card {
    flex: 1;
    min-width: 200px;
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    font-size: 2rem;
    color: #3498db;
    margin-bottom: 0.8rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #7f8c8d;
}

/* Form Styles */
.profile-form {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    padding: 2rem;
    margin-bottom: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    font-weight: 500;
}

.form-group input {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
    border-color: #3498db;
    outline: none;
}

.form-group input[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
}

.submit-btn {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background-color: #2980b9;
}

/* Courses Grid */
.courses-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.course-card {
    background-color: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s, box-shadow 0.3s;
}

.course-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.course-image {
    height: 160px;
    width: 100%;
    object-fit: cover;
}

.course-content {
    padding: 1.5rem;
}

.course-title {
    margin-bottom: 0.8rem;
    color: #2c3e50;
}

.course-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #ecf0f1;
}

.course-price {
    font-weight: bold;
    color: #2c3e50;
}

.course-action {
    background-color: #3498db;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    text-decoration: none;
    transition: background-color 0.3s;
}

.course-action:hover {
    background-color: #2980b9;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 4rem;
    color: #bdc3c7;
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.empty-state p {
    color: #7f8c8d;
    margin-bottom: 1.5rem;
}

.btn {
    background-color: #3498db;
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background-color 0.3s;
}

.btn:hover {
    background-color: #2980b9;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 2rem;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    position: relative;
}

.close-modal {
    position: absolute;
    top: 1rem;
    right: 1rem;
    color: #aaa;
    font-size: 1.5rem;
    cursor: pointer;
    transition: color 0.3s;
}

.close-modal:hover {
    color: #333;
}

.upload-area {
    border: 2px dashed #bdc3c7;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    margin: 1.5rem 0;
    transition: border-color 0.3s;
}

.upload-area:hover {
    border-color: #3498db;
}

.upload-area i {
    font-size: 3rem;
    color: #bdc3c7;
    margin-bottom: 1rem;
}

.upload-area input[type="file"] {
    display: none;
}

.preview-container {
    display: none;
    margin: 1.5rem 0;
    text-align: center;
}

.image-preview {
    max-width: 100%;
    max-height: 300px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Responsive Styles */
@media screen and (max-width: 1024px) {
    .profile-container {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .user-info p {
        justify-content: center;
    }
}

@media screen and (max-width: 768px) {
    .content-area {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        padding: 1rem 0;
    }
    
    .main-content {
        padding: 1.5rem;
    }
    
    .stats-grid {
        flex-direction: column;
    }
}

@media screen and (max-width: 480px) {
    .header {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem;
    }
    
    .user-nav {
        width: 100%;
        justify-content: space-between;
    }
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
        padding: 1.5rem;
    }
    
    .courses-grid {
        grid-template-columns: 1fr;
    }
}

/* Add JavaScript to handle modal functionality */
/* Include this in a separate JS file */
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-code"></i>
                <h1>Code Crafters</h1>
            </div>
            <div class="user-nav">
                <div class="profile-img">
                    <?php if(!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                        <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo isset($user['full_name']) ? $user['full_name'] : $user['email']; ?></span>
                <a href="login.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <div class="content-area">
            <div class="sidebar">
                <a href="my_courses.php" class="nav-item">
                    <i class="fas fa-book-open"></i> My Courses
                </a>
                <a href="courses.php" class="nav-item">
                    <i class="fas fa-laptop-code"></i> Courses
                </a>
                <a href="practice_test.php" class="nav-item">
                    <i class="fas fa-vial"></i> Practice Test
                </a>
                <a href="tutorials.php" class="nav-item">
                    <i class="fas fa-chalkboard-teacher"></i> Tutorials
                </a>
                <a href="student_feedback.php" class="nav-item">
    <i class="fas fa-comment-dots"></i> Feedback
</a>
                <a href="login.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="main-content">
                <?php if($uploadSuccess || $infoUpdateSuccess): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php if($uploadSuccess && $infoUpdateSuccess): ?>
                        Profile information and picture updated successfully!
                    <?php elseif($uploadSuccess): ?>
                        Profile picture updated successfully!
                    <?php else: ?>
                        Profile information updated successfully!
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($uploadError)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $uploadError; ?>
                </div>
                <?php endif; ?>
                
                <h1 class="section-title">My Profile</h1>
                
                <div class="profile-container">
                    <div class="profile-picture">
                        <?php if(!empty($user['profile_image']) && file_exists($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <i class="fas fa-user" style="font-size: 75px; color: #ccc; display: flex; align-items: center; justify-content: center; height: 100%;"></i>
                        <?php endif; ?>
                        <div class="edit-icon" onclick="openUploadModal()">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                    
                    <div class="user-info">
                        <h2><?php echo isset($user['full_name']) && !empty($user['full_name']) ? $user['full_name'] : 'Complete Your Profile'; ?></h2>
                        <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
                        <?php if(isset($user['phone']) && !empty($user['phone'])): ?>
                        <p><i class="fas fa-phone"></i> <?php echo $user['phone']; ?></p>
                        <?php endif; ?>
                        <?php if(isset($user['date_of_birth']) && !empty($user['date_of_birth'])): ?>
                        <p><i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($user['date_of_birth'])); ?></p>
                        <?php endif; ?>
                        <?php if(isset($user['gender']) && !empty($user['gender'])): ?>
                        <p><i class="fas fa-venus-mars"></i> <?php echo ucfirst($user['gender']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <div class="stat-value"><?php echo count($enrolledCourses); ?></div>
                        <div class="stat-label">Enrolled Courses</div>
                    </div>
                </div>
            
                
                <h2 class="section-title">Personal Information</h2>
                
                <div class="profile-form">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" value="<?php echo isset($user['full_name']) ? $user['full_name'] : ''; ?>" placeholder="Enter your full name">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" value="<?php echo $user['email']; ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo isset($user['phone']) ? $user['phone'] : ''; ?>" placeholder="Enter your phone number">
                        </div>
                        
                        <!-- Hidden profile image input -->
                        <input type="hidden" name="currentProfileImage" value="<?php echo isset($user['profile_image']) ? $user['profile_image'] : ''; ?>">
                        
                        <button type="submit" name="updateProfile" class="submit-btn">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                </div>
                
                <h2 class="section-title" style="margin-top: 2rem;">Recent Courses</h2>
                
                <?php if(empty($enrolledCourses)): ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <h3>No Courses Yet</h3>
                    <p>You haven't enrolled in any courses yet. Explore our catalog and start learning today!</p>
                    <a href="courses.php" class="btn"><i class="fas fa-search"></i> Explore Courses</a>
                </div>
                <?php else: ?>
                <div class="courses-grid">
                    <?php foreach($enrolledCourses as $course): ?>
                    <div class="course-card">
                        <?php if(!empty($course['image']) && file_exists($course['image'])): ?>
                            <img src="<?php echo $course['image']; ?>" alt="<?php echo $course['title']; ?>" class="course-image">
                        <?php else: ?>
                            <div class="course-image" style="background-color: #3498db; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-laptop-code" style="font-size: 48px; color: white;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="course-content">
                        <h3 class="course-title"><?php echo $course['title']; ?></h3>
                            
                            <p><?php echo substr($course['description'], 0, 100) . '...'; ?></p>
                            <div class="course-footer">
                                <span class="course-price">
                                    <?php if($course['price'] > 0): ?>
                                        $<?php echo number_format($course['price'], 2); ?>
                                    <?php else: ?>
                                        Free
                                    <?php endif; ?>
                                </span>
                                <a href="my_courses.php?id=<?php echo $course['id']; ?>" class="course-action">Continue</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Profile Image Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeUploadModal()">&times;</span>
            <h2>Upload Profile Picture</h2>
            
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="dropArea">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop an image or click to select</p>
                    <input type="file" name="profileImage" id="profileImage" accept="image/*" onchange="previewImage(this)">
                </div>
                
                <div class="preview-container" id="previewContainer">
                    <img src="" alt="Preview" class="image-preview" id="imagePreview">
                </div>
                
                <button type="submit" name="updateProfile" class="submit-btn" style="width: 100%; margin-top: 1.5rem; justify-content: center;">
                    <i class="fas fa-save"></i> Save Profile Picture
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Profile Image Upload Modal
        function openUploadModal() {
            document.getElementById('uploadModal').style.display = 'block';
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').style.display = 'none';
            // Reset preview
            document.getElementById('previewContainer').style.display = 'none';
            document.getElementById('uploadForm').reset();
        }
        
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('previewContainer').style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Drag and drop functionality
        const dropArea = document.getElementById('dropArea');
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.classList.add('highlight');
        }
        
        function unhighlight() {
            dropArea.classList.remove('highlight');
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            document.getElementById('profileImage').files = files;
            previewImage(document.getElementById('profileImage'));
        }
        
        // Add click functionality to the drop area
        dropArea.addEventListener('click', function() {
            document.getElementById('profileImage').click();
        });
        
        // Add highlight class style
        const style = document.createElement('style');
        style.innerHTML = `
            .highlight {
                border-color: #3498db !important;
                background-color: rgba(52, 152, 219, 0.1);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>