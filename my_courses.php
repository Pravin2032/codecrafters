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

// Initialize variables
$errors = [];
$success_message = '';
$course_id = 0;
$edit_mode = false;
$lesson_to_edit = null;
$selectedCourse = null; // Initialize selectedCourse
$enrolledCourses = []; // Initialize enrolledCourses array
$completedLessons = []; // Initialize completedLessons array
$courseMaterials = []; // Initialize courseMaterials array

// Get user details
$email = $_SESSION['email'];
$userQuery = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc(); // Now we have the user data

// Check if we're viewing a specific course
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $courseId = $_GET['id'];
    
    // Get course details
$courseQuery = "SELECT * FROM courses WHERE id = ?";
    $stmt = $conn->prepare($courseQuery);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedCourse = $result->fetch_assoc();
        
        // Get lessons for this course
        $lessonQuery = "SELECT * FROM lessons WHERE course_id = ? ORDER BY lesson_order ASC";
        $stmt = $conn->prepare($lessonQuery);
        $stmt->bind_param("i", $courseId);
        $stmt->execute();
        $lessonResult = $stmt->get_result();
        
        $lessonsList = [];
        while ($lesson = $lessonResult->fetch_assoc()) {
            $lessonsList[] = $lesson;
        }
        
        // Get completed lessons for this user
        $completedQuery = "SELECT last_lesson_id FROM user_progress WHERE user_id = ? AND course_id = ? AND completed = 1";
        $stmt = $conn->prepare($completedQuery);
        $stmt->bind_param("ii", $user['id'], $courseId);
        $stmt->execute();
        $completedResult = $stmt->get_result();
        
        $completedLessons = [];
        while ($completed = $completedResult->fetch_assoc()) {
            $completedLessons[] = $completed['lesson_id'];
        }
        
        // Get course materials (PDFs and videos) from course_materials table
      // Get course materials (PDFs and videos) from course_materials table
$materialsQuery = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY material_type, created_at";
$stmt = $conn->prepare($materialsQuery);
$stmt->bind_param("i", $courseId);
$stmt->execute();
$materialsResult = $stmt->get_result();

$courseMaterials = [];
while ($material = $materialsResult->fetch_assoc()) {
    $courseMaterials[] = $material;
}
    }
}

// Get user's enrolled courses
$enrolledQuery = "SELECT c.* FROM courses c 
                 JOIN purchases p ON c.id = p.course_id 
                 WHERE p.user_id = ?";
$stmt = $conn->prepare($enrolledQuery);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$enrolledResult = $stmt->get_result();

while ($course = $enrolledResult->fetch_assoc()) {
    // Calculate progress for each course
    // Get completed lessons for this user
// Get completed lessons for this user
$completedQuery = "SELECT lesson_id FROM user_progress WHERE user_id = ? AND course_id = ? AND completed = 1";
$stmt = $conn->prepare($completedQuery);
$stmt->bind_param("ii", $user['id'], $courseId);
$stmt->execute();
$completedResult = $stmt->get_result();

$completedLessons = [];
while ($completed = $completedResult->fetch_assoc()) {
    $completedLessons[] = $completed['lesson_id'];
}
    
    // Get total lessons for this course
    $totalLessonsQuery = "SELECT COUNT(*) as total FROM lessons WHERE course_id = ?";
    $stmt = $conn->prepare($totalLessonsQuery);
    $stmt->bind_param("i", $course['id']);
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalData = $totalResult->fetch_assoc();
    
    $progress = 0;
    if ($totalData['total'] > 0) {
        $progress = ($progressData['completed'] / $totalData['total']) * 100;
    }
    
    $course['progress'] = $progress;
    $enrolledCourses[] = $course;
}

// Handle Mark as Complete functionality
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['markComplete'])) {
    $lesson_id = $_POST['lesson_id'];
    $course_id = $_POST['course_id'];
    
    // Check if progress record exists
    $checkQuery = "SELECT * FROM user_progress WHERE user_id = ? AND course_id = ? AND last_lesson_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iii", $user['id'], $course_id, $lesson_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $updateQuery = "UPDATE user_progress SET completed = 1, completion_date = NOW() 
                       WHERE user_id = ? AND course_id = ? AND last_lesson_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("iii", $user['id'], $course_id, $lesson_id);
        $stmt->execute();
    } else {
        // Insert new record
        $insertQuery = "INSERT INTO user_progress (user_id, course_id, lesson_id, completed, completion_date) 
                       VALUES (?, ?, ?, 1, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iii", $user['id'], $course_id, $lesson_id);
        $stmt->execute();
    }
    
    // Redirect to refresh the page
    header("Location: my_courses.php?id=" . $course_id);
    exit();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Code Crafters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="mycourses.css">
        <style>
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
                    <?php if(isset($user) && !empty($user['profile_pic']) && file_exists($user['profile_pic'])): ?>
                        <img src="<?php echo $user['profile_pic']; ?>" alt="Profile Picture">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <span><?php echo isset($user) && isset($user['full_name']) ? $user['full_name'] : (isset($user) ? $user['email'] : 'User'); ?></span>
            </div>
        </div>
        
        <div class="content-area">
            <div class="sidebar">
                
                <a href="my_courses.php" class="nav-item active">
                    <i class="fas fa-book-open"></i> My Courses
                </a>
                <a href="courses.php" class="nav-item">
                    <i class="fas fa-laptop-code"></i> Courses
                </a>
                <a href="student_dashboard.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i> Dashboard
                </a>
            </div>
            
            <div class="main-content">
                <?php if($selectedCourse === null): ?>
                    <!-- Courses List View -->
                    <h1 class="section-title">My Courses</h1>
                    
                    <?php if(empty($enrolledCourses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-book"></i>
                        <h3>No Enrolled Courses</h3>
                        <p>You haven't enrolled in any courses yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="courses-grid">
                    <?php foreach($enrolledCourses as $course): ?>
                        <div class="course-card">
                            <img src="<?php echo !empty($course['image']) ? $course['image'] : 'images/default-course.jpg'; ?>" alt="<?php echo $course['title']; ?>" class="course-image">
                            <div class="course-content">
                                <h3 class="course-title"><?php echo $course['title']; ?></h3>
                                <p class="course-description"><?php echo substr($course['description'], 0, 100); ?>...</p>
                                
                                
                                <div class="course-footer">
                                    <div class="course-instructor">
                                        <div class="instructor-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <span><?php echo !empty($course['instructor']) ? $course['instructor'] : 'Staff Instructor'; ?></span>
                                    </div>
                                    <a href="my_courses.php?id=<?php echo $course['id']; ?>" class="course-action">Continue</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                
                <?php else: ?>
                    <!-- Course Detail View -->
                    <a href="my_courses.php" class="back-button"><i class="fas fa-arrow-left"></i> Back to My Courses</a>
                    
                    <div class="course-detail">
                        <div class="course-header" style="background: url('<?php echo !empty($selectedCourse['image']) ? $selectedCourse['image'] : 'images/default-course.jpg'; ?>') center/cover no-repeat;">
                            <div class="course-header-content">
                                <h1 class="course-detail-title"><?php echo $selectedCourse['title']; ?></h1>
                                
                                <div class="course-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo !empty($selectedCourse['instructor']) ? $selectedCourse['instructor'] : 'Staff Instructor'; ?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Last Updated: <?php 
    $updated_date = !empty($selectedCourse['updated_at']) ? 
                   date('F j, Y', strtotime($selectedCourse['updated_at'])) : 
                   date('F j, Y'); // Use current date as fallback
    echo $updated_date;
?></span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo !empty($selectedCourse['duration']) ? $selectedCourse['duration'] : '8 weeks'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="course-progress-large">
                                <?php
                                    // Calculate progress for this specific course
                                    $progress = 0;
                                    if (isset($lessonsList) && count($lessonsList) > 0) {
                                        $completedCount = count($completedLessons);
                                        $totalCount = count($lessonsList);
                                        $progress = ($completedCount / $totalCount) * 100;
                                    }
                                    ?>
                                   
                                    <div class="progress-text">
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="course-body">
                            <div class="course-tabs">
                                <div class="course-tab active" data-tab="overview">Overview</div>
                                <div class="course-tab" data-tab="materials">Course Materials</div>
                            </div>
                            
                            <div id="overview" class="tab-content active">
                                <div class="course-description-full">
                                    <h2>About This Course</h2>
                                    <p><?php echo $selectedCourse['description']; ?></p>
                                </div>
                            </div>
                            
                            <div id="lessons" class="tab-content">
                                <div class="lessons-container">
                                    <h2>Course Lessons</h2>
                                    
                                    <?php if(isset($lessonsList) && !empty($lessonsList)): ?>
                                    <?php foreach($lessonsList as $lesson): ?>
                                        <div class="lesson-item">
                                            <div class="lesson-status">
                                                <?php if(in_array($lesson['id'], $completedLessons)): ?>
                                                <i class="fas fa-check-circle status-complete"></i>
                                                <?php else: ?>
                                                <i class="far fa-circle status-incomplete"></i>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="lesson-info">
                                                <h3 class="lesson-title"><?php echo $lesson['title']; ?></h3>
                                                <div class="lesson-meta">
                                                    <span><i class="fas fa-clock"></i> <?php echo !empty($lesson['duration']) ? $lesson['duration'] : '30 min'; ?></span>
                                                </div>
                                            </div>
                                            
                                            <div class="lesson-actions">
                                                <a href="lesson.php?id=<?php echo $lesson['id']; ?>" class="btn-view-lesson">
                                                    <i class="fas fa-play"></i> Start Lesson
                                                </a>
                                                
                                                <?php if(!in_array($lesson['id'], $completedLessons)): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="lesson_id" value="<?php echo $lesson['id']; ?>">
                                                    <input type="hidden" name="course_id" value="<?php echo $selectedCourse['id']; ?>">
                                                    <button type="submit" name="markComplete" class="btn-mark-complete">
                                                        <i class="fas fa-check"></i> Mark Complete
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                        <p>No lessons available for this course yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div id="materials" class="tab-content">
                                <div class="materials-container">
                                    <h2>Course Materials</h2>
                                    
                                    <!-- In the materials tab section, replace the existing PHP loop with this code -->
                                    <?php if(!empty($courseMaterials)): ?>
<div class="materials-grid">
    <?php foreach($courseMaterials as $material): ?>
        <div class="material-card">
            <?php if($material['material_type'] == 'pdf' || $material['type'] == 'pdf'): ?>
                <div class="material-icon pdf">
                    <i class="fas fa-file-pdf"></i>
                </div>
                <h3 class="material-title"><?php echo $material['title']; ?></h3>
                <a href="<?php echo $material['file_path']; ?>" target="_blank" class="material-action pdf">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            <?php elseif($material['material_type'] == 'video' || $material['type'] == 'video'): ?>
                <div class="material-icon video">
                    <i class="fas fa-video"></i>
                </div>
                <h3 class="material-title"><?php echo $material['title']; ?></h3>
                <?php if(isset($material['is_internal_video']) && $material['is_internal_video'] == 1): ?>
                    <!-- For internal videos - open in a video player -->
                    <a href="javascript:void(0);" onclick="openVideoPlayer('<?php echo $material['file_path']; ?>', '<?php echo $material['title']; ?>')" class="material-action video">
                        <i class="fas fa-play"></i> Watch Video
                    </a>
                <?php else: ?>
                    <!-- For external videos - open in new tab -->
                    <a href="<?php echo $material['file_path']; ?>" target="_blank" class="material-action video">
                        <i class="fas fa-play"></i> Watch Video (External)
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
    <p>No additional materials available for this course yet.</p>
<?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Video Player Modal -->
<div id="videoPlayerModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2 id="videoTitle"></h2>
        <div class="video-container">
            <video id="videoPlayer" controls>
                Your browser does not support the video tag.
            </video>
        </div>
    </div>
</div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.course-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');
        });
    });
    
    // Video Player Modal
    const modal = document.getElementById('videoPlayerModal');
    const closeModal = document.querySelector('.close-modal');
    const videoPlayer = document.getElementById('videoPlayer');
    const videoTitle = document.getElementById('videoTitle');
    
    // Close modal when clicking the X
    if (closeModal) {
        closeModal.addEventListener('click', function() {
            modal.style.display = 'none';
            videoPlayer.pause();
            videoPlayer.src = '';
        });
    }
    
    // Close modal when clicking outside the modal content
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
            videoPlayer.pause();
            videoPlayer.src = '';
        }
    });
});

// Function to open video player
function openVideoPlayer(videoPath, title) {
    const modal = document.getElementById('videoPlayerModal');
    const videoPlayer = document.getElementById('videoPlayer');
    const videoTitle = document.getElementById('videoTitle');
    
    // Set the video source and title
    videoPlayer.src = videoPath;
    videoTitle.textContent = title;
    
    // Display the modal
    modal.style.display = 'block';
    
    // Load and play the video
    videoPlayer.load();
    videoPlayer.play();
}
    </script>
</body>
</html>