<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Database Connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "codecrafters";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all skills for use in the form
$skills = [];
$skillsResult = $conn->query("SELECT * FROM skills ORDER BY skill_name ASC");
while ($row = $skillsResult->fetch_assoc()) {
    $skills[] = $row;
}

// Handle Form Submission for Adding New Instructor
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_instructor'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $experience = $_POST['experience'];
    $bio = $_POST['bio'];
    $selected_skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    
    // Validate email
    if (strpos($email, '@') === false) {
        echo "<script>alert('Invalid email address. The email must contain an \"@\" symbol.');</script>";
        return;
    }

    // Validate phone number (should be exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $phone)) {
        echo "<script>alert('Invalid phone number. Phone number must be exactly 10 digits.');</script>";
        return;
    }
    
    // Handle profile picture upload
    $profile_pic = null;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $upload_dir = "uploads/instructors/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $temp_name = $_FILES['profile_pic']['tmp_name'];
        $file_name = time() . '_' . $_FILES['profile_pic']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($temp_name, $file_path)) {
            $profile_pic = $file_path;
        } else {
            echo "<script>alert('Failed to upload profile picture.');</script>";
        }
    }

    // Insert instructor data
    $sql = "INSERT INTO instructors (name, email, phone, experience, bio, profile_pic) 
            VALUES ('$name', '$email', '$phone', '$experience', '$bio', " . ($profile_pic ? "'$profile_pic'" : "NULL") . ")";

    if ($conn->query($sql) === TRUE) {
        $instructor_id = $conn->insert_id;
        
        // Insert instructor skills
        if (!empty($selected_skills)) {
            foreach ($selected_skills as $skill_id) {
                $conn->query("INSERT INTO instructor_skills (instructor_id, skill_id) VALUES ('$instructor_id', '$skill_id')");
            }
        }
        
        echo "<script>alert('Instructor added successfully!'); window.location='admin_instructor.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Remove Instructor by ID
if (isset($_GET['remove_id'])) {
    $remove_id = $_GET['remove_id'];
    
    // Delete instructor skills first (foreign key constraint)
    $conn->query("DELETE FROM instructor_skills WHERE instructor_id = '$remove_id'");
    
    // Delete instructor
    $sql = "DELETE FROM instructors WHERE instructor_id = '$remove_id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Instructor removed successfully!'); window.location='admin_instructor.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle Edit Instructor Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_instructor'])) {
    $instructor_id = $_POST['instructor_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $experience = $_POST['experience'];
    $bio = $_POST['bio'];
    $selected_skills = isset($_POST['skills']) ? $_POST['skills'] : [];
    
    // Validate email
    if (strpos($email, '@') === false) {
        echo "<script>alert('Invalid email address. The email must contain an \"@\" symbol.');</script>";
        return;
    }

    // Validate phone number (should be exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $phone)) {
        echo "<script>alert('Invalid phone number. Phone number must be exactly 10 digits.');</script>";
        return;
    }
    
    // Handle profile picture upload for edit
    $profile_pic_update = "";
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $upload_dir = "uploads/instructors/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $temp_name = $_FILES['profile_pic']['tmp_name'];
        $file_name = time() . '_' . $_FILES['profile_pic']['name'];
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($temp_name, $file_path)) {
            $profile_pic_update = ", profile_pic = '$file_path'";
        } else {
            echo "<script>alert('Failed to upload profile picture.');</script>";
        }
    }

    // Update instructor data
    $sql = "UPDATE instructors SET 
            name = '$name',
            email = '$email', 
            phone = '$phone', 
            experience = '$experience',
            bio = '$bio'
            $profile_pic_update
            WHERE instructor_id = '$instructor_id'";

    if ($conn->query($sql) === TRUE) {
        // First delete existing skills
        $conn->query("DELETE FROM instructor_skills WHERE instructor_id = '$instructor_id'");
        
        // Insert updated skills
        if (!empty($selected_skills)) {
            foreach ($selected_skills as $skill_id) {
                $conn->query("INSERT INTO instructor_skills (instructor_id, skill_id) VALUES ('$instructor_id', '$skill_id')");
            }
        }
        
        echo "<script>alert('Instructor details updated successfully!'); window.location='admin_instructor.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch Instructor Records with their skills
$instructors = [];
$result = $conn->query("SELECT * FROM instructors ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    // Get instructor skills
    $instructor_id = $row['instructor_id'];
    $skillsQuery = $conn->query("
        SELECT s.skill_name 
        FROM instructor_skills is_rel
        JOIN skills s ON is_rel.skill_id = s.skill_id
        WHERE is_rel.instructor_id = $instructor_id
    ");
    
    $instructorSkills = [];
    while ($skillRow = $skillsQuery->fetch_assoc()) {
        $instructorSkills[] = $skillRow['skill_name'];
    }
    
    $row['skills'] = $instructorSkills;
    $instructors[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Management - CodeCrafters Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Custom Animations */
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }
        
        .animate-fadeIn {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        /* Customize scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #6366f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #4f46e5;
        }
        
        /* Form focus effects */
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
            transition: all 0.2s ease;
        }
        
        /* Card hover effects */
        .instructor-card {
            transition: all 0.3s ease;
        }
        
        .instructor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        /* Modal animation */
        .modal {
            transition: opacity 0.3s ease;
        }
        
        /* Button hover effects */
        .btn {
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-1px);
        }
        
        /* Custom checkboxes */
        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 4px 8px;
            background-color: #f3f4f6;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .checkbox-item:hover {
            background-color: #e5e7eb;
        }
        
        .checkbox-item.checked {
            background-color: #e0e7ff;
            color: #4f46e5;
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .responsive-form {
                padding: 1rem;
            }
            
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Top Navigation Bar -->
    <nav class="bg-indigo-600 text-white p-4 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <div class="flex items-center">
                <i class="fas fa-laptop-code text-2xl mr-3"></i>
                <h1 class="text-xl font-semibold">CodeCrafters Administration</h1>
            </div>
            <div class="flex items-center space-x-4">
                <a href="admin dashboard.php" class="flex items-center hover:text-indigo-200 transition">
                    <i class="fas fa-tachometer-alt mr-2"></i>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>
                <button id="directoryBtn" class="flex items-center hover:text-indigo-200 transition">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>
                    <span class="hidden sm:inline">Instructor Directory</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 md:p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">
                <i class="fas fa-chalkboard-teacher text-indigo-600 mr-2"></i>Instructor Management
            </h2>
        </div>

        <!-- Instructor Form Card -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8 animate-fadeIn">
            <!-- Form Header with Tabs -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-4 text-white">
                <h3 class="text-xl font-semibold">New Instructor Registration</h3>
                <p class="text-indigo-100 text-sm">Complete the form below to register a new instructor</p>
            </div>
            
            <!-- Instructor Registration Form -->
            <form action="" method="POST" enctype="multipart/form-data" class="responsive-form p-6">
                <!-- Personal Information -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-id-card text-indigo-500 mr-2"></i>Personal Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" name="name" required 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                            <select name="experience" required
                                    class="form-select w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                <option value="">Select Experience</option>
                                <option value="0-1">Less than 1 year</option>
                                <option value="1-3">1-3 years</option>
                                <option value="3-5">3-5 years</option>
                                <option value="5-10">5-10 years</option>
                                <option value="10+">10+ years</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Profile Picture</label>
                            <input type="file" name="profile_pic" accept="image/*"
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                            <p class="text-xs text-gray-500 mt-1">Upload a professional headshot (JPG, PNG, max 2MB)</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-address-book text-indigo-500 mr-2"></i>Contact Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" required 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" required placeholder="10 digits" 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                    </div>
                </div>

                <!-- Skills & Expertise -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-code text-indigo-500 mr-2"></i>Skills & Expertise
                    </h4>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Skills</label>
                    <div class="checkbox-container mb-4">
                        <?php foreach ($skills as $skill): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="skills[]" value="<?= $skill['skill_id'] ?>" class="mr-2">
                                <?= $skill['skill_name'] ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Biography -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-user-circle text-indigo-500 mr-2"></i>Instructor Biography
                    </h4>
                    <textarea name="bio" rows="4" required
                              class="form-textarea w-full p-2 border border-gray-300 rounded-lg focus:outline-none" 
                              placeholder="Enter professional background, teaching philosophy, and areas of expertise..."></textarea>
                </div>

                <!-- Form Submission -->
                <div class="flex justify-end">
                    <button type="submit" name="add_instructor" 
                            class="btn px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm">
                        <i class="fas fa-user-plus mr-2"></i>Register Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Instructor Directory Modal -->
    <div id="directoryModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-3/4 flex flex-col animate-fadeIn">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-4 text-white rounded-t-lg flex justify-between items-center">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Instructor Directory
                </h2>
                <div class="flex items-center">
                    <div class="relative mr-4">
                        <input type="text" id="searchInput" placeholder="Search instructors..." 
                               class="pl-8 pr-4 py-1 border border-indigo-300 rounded-md bg-indigo-50 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <i class="fas fa-search absolute left-2 top-2 text-indigo-500"></i>
                    </div>
                    <button id="closeModalBtn" class="text-white hover:text-indigo-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Modal Content -->
            <div class="flex-1 overflow-auto p-4">
                <!-- Instructor List -->
                <div id="instructorList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($instructors as $instructor): ?>
                        <div class="instructor-item instructor-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:bg-indigo-50">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex items-center">
                                    <?php if ($instructor['profile_pic']): ?>
                                        <img src="<?= $instructor['profile_pic'] ?>" alt="<?= $instructor['name'] ?>" class="w-12 h-12 rounded-full mr-3 object-cover border-2 border-indigo-200">
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-full mr-3 bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl border-2 border-indigo-200">
                                            <?= strtoupper(substr($instructor['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <h3 class="font-bold text-lg text-indigo-700">
                                        <?= $instructor['name'] ?>
                                    </h3>
                                </div>
                                <div>
                                    <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                                        <?= $instructor['experience'] ?> exp
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 gap-2 mb-3 text-sm">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-envelope mr-2 text-indigo-500"></i>
                                    <span class="truncate"><?= $instructor['email'] ?></span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-phone mr-2 text-indigo-500"></i>
                                    <span><?= $instructor['phone'] ?></span>
                                </div>
                                <div class="flex flex-wrap items-center gap-1 mt-2">
                                    <i class="fas fa-code text-indigo-500 mr-2"></i>
                                    <?php foreach ($instructor['skills'] as $skill): ?>
                                        <span class="inline-block bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded">
                                            <?= $skill ?>
                                        </span>
                                    <?php endforeach; ?>
                                    <?php if (empty($instructor['skills'])): ?>
                                        <span class="text-gray-500 text-xs">No skills specified</span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2">
                                    <p class="text-gray-600 text-sm line-clamp-2"><?= substr($instructor['bio'], 0, 100) . (strlen($instructor['bio']) > 100 ? '...' : '') ?></p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-2 mt-3">
                                <button onclick="openEditForm('<?= $instructor['instructor_id'] ?>')" 
                                        class="btn text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded">
                                    <i class="fas fa-edit mr-1"></i><span class="text-sm">Edit</span>
                                </button>
                                <button onclick="removeInstructor('<?= $instructor['instructor_id'] ?>')" 
                                        class="btn text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1 rounded">
                                    <i class="fas fa-trash-alt mr-1"></i><span class="text-sm">Remove</span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Edit Form (Initially hidden) -->
                <div id="editForm" class="hidden bg-white rounded-lg border border-indigo-200 shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-bold text-indigo-700">Edit Instructor Information</h3>
                        <button id="backToListBtn" class="text-indigo-600 hover:text-indigo-800 flex items-center">
                            <i class="fas fa-arrow-left mr-1"></i> Back to Directory
                        </button>
                    </div>
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="space-y-6" onsubmit="return validateEditForm()">
                        <input type="hidden" name="instructor_id" id="edit_instructor_id">
                        
                        <!-- Personal Information -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                    <input type="text" name="name" id="edit_name" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                                    <select name="experience" id="edit_experience" required
                                            class="form-select w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                        <option value="">Select Experience</option>
                                        <option value="0-1">Less than 1 year</option>
                                        <option value="1-3">1-3 years</option>
                                        <option value="3-5">3-5 years</option>
                                        <option value="5-10">5-10 years</option>
                                        <option value="10+">10+ years</option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Update Profile Picture</label>
                                    <input type="file" name="profile_pic" accept="image/*"
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty to keep current profile picture</p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Contact Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" id="edit_email" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                    <input type="tel" name="phone" id="edit_phone" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Skills -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Skills & Expertise</h4>
                            <div id="edit_skills_container" class="checkbox-container">
                                <?php foreach ($skills as $skill): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="skills[]" value="<?= $skill['skill_id'] ?>" class="skill-checkbox mr-2">
                                        <?= $skill['skill_name'] ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Biography -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Instructor Biography</h4>
                            <textarea name="bio" id="edit_bio" rows="4" required
                                      class="form-textarea w-full p-2 border border-gray-300 rounded-lg focus:outline-none"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="edit_instructor" 
                                    class="btn px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification (Hidden by default) -->
    <div id="toastNotification" class="hidden fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="toastMessage">Operation completed successfully!</span>
    </div>

    <script>
        // Show/Hide Instructor Directory Modal
        document.getElementById('directoryBtn').addEventListener('click', function() {
            document.getElementById('directoryModal').classList.remove('hidden');
            document.getElementById('searchInput').style.display = 'block';
        });
        
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            closeModal();
        });
        function closeModal() {
    document.getElementById('directoryModal').classList.add('hidden');
    resetEditForm();
    document.getElementById('instructorList').style.display = 'grid';
    document.getElementById('editForm').style.display = 'none';
}

// Close modal when clicking outside the content
document.getElementById('directoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchText = this.value.toLowerCase();
    const instructorItems = document.querySelectorAll('.instructor-item');
    
    instructorItems.forEach(item => {
        const instructorName = item.querySelector('h3').textContent.toLowerCase();
        const instructorEmail = item.querySelector('.fa-envelope').nextElementSibling.textContent.toLowerCase();
        const skillElements = item.querySelectorAll('.bg-indigo-50.text-indigo-700');
        let skillsMatch = false;
        
        skillElements.forEach(skillEl => {
            if (skillEl.textContent.toLowerCase().includes(searchText)) {
                skillsMatch = true;
            }
        });
        
        if (instructorName.includes(searchText) || instructorEmail.includes(searchText) || skillsMatch) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Back to list button
document.getElementById('backToListBtn').addEventListener('click', function() {
    document.getElementById('instructorList').style.display = 'grid';
    document.getElementById('editForm').style.display = 'none';
});

// Function to open edit form and populate with instructor data
function openEditForm(instructorId) {
    // Hide the instructor list and show the edit form
    document.getElementById('instructorList').style.display = 'none';
    document.getElementById('editForm').style.display = 'block';
    
    // Fetch instructor data and populate the form
    const instructorItems = document.querySelectorAll('.instructor-item');
    let selectedInstructor = null;
    
    instructorItems.forEach(item => {
        const editButton = item.querySelector('button[onclick^="openEditForm"]');
        if (editButton && editButton.getAttribute('onclick').includes(instructorId)) {
            selectedInstructor = item;
        }
    });
    
    if (selectedInstructor) {
        // Set instructor ID
        document.getElementById('edit_instructor_id').value = instructorId;
        
        // Get instructor details
        const name = selectedInstructor.querySelector('h3').textContent.trim();
        const email = selectedInstructor.querySelector('.fa-envelope').nextElementSibling.textContent.trim();
        const phone = selectedInstructor.querySelector('.fa-phone').nextElementSibling.textContent.trim();
        const experience = selectedInstructor.querySelector('.bg-indigo-100.text-indigo-800').textContent.trim().replace(' exp', '');
        const bio = selectedInstructor.querySelector('.text-gray-600.text-sm').textContent.trim();
        
        // Set form values
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_phone').value = phone;
        document.getElementById('edit_experience').value = experience;
        document.getElementById('edit_bio').value = bio;
        
        // Reset and set selected skills
        const skillCheckboxes = document.querySelectorAll('.skill-checkbox');
        skillCheckboxes.forEach(checkbox => checkbox.checked = false);
        
        const instructorSkills = selectedInstructor.querySelectorAll('.bg-indigo-50.text-indigo-700');
        instructorSkills.forEach(skillElement => {
            const skillName = skillElement.textContent.trim();
            
            // Find and check the corresponding checkbox
            skillCheckboxes.forEach(checkbox => {
                const checkboxLabel = checkbox.parentElement.textContent.trim();
                if (checkboxLabel === skillName) {
                    checkbox.checked = true;
                }
            });
        });
    }
}

// Function to reset edit form
function resetEditForm() {
    document.getElementById('edit_name').value = '';
    document.getElementById('edit_email').value = '';
    document.getElementById('edit_phone').value = '';
    document.getElementById('edit_experience').value = '';
    document.getElementById('edit_bio').value = '';
    
    const skillCheckboxes = document.querySelectorAll('.skill-checkbox');
    skillCheckboxes.forEach(checkbox => checkbox.checked = false);
}

// Function to remove instructor (with confirmation)
function removeInstructor(instructorId) {
    if (confirm('Are you sure you want to remove this instructor? This action cannot be undone.')) {
        window.location.href = `admin_instructor.php?remove_id=${instructorId}`;
    }
}

// Form validation function
function validateEditForm() {
    const email = document.getElementById('edit_email').value;
    const phone = document.getElementById('edit_phone').value;
    
    // Validate email
    if (!email.includes('@')) {
        alert('Invalid email address. The email must contain an "@" symbol.');
        return false;
    }
    
    // Validate phone (must be exactly 10 digits)
    if (!/^\d{10}$/.test(phone)) {
        alert('Invalid phone number. Phone number must be exactly 10 digits.');
        return false;
    }
    
    return true;
}

// Add event listeners to checkbox items for toggle effect
document.querySelectorAll('.checkbox-item').forEach(item => {
    item.addEventListener('click', function(e) {
        if (e.target !== this.querySelector('input[type="checkbox"]')) {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            updateCheckboxStyle(this, checkbox.checked);
        }
    });
    
    const checkbox = item.querySelector('input[type="checkbox"]');
    checkbox.addEventListener('change', function() {
        updateCheckboxStyle(item, this.checked);
    });
});

// Function to update checkbox style based on checked state
function updateCheckboxStyle(item, isChecked) {
    if (isChecked) {
        item.classList.add('checked');
    } else {
        item.classList.remove('checked');
    }
}

// Show toast notification
function showToast(message) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    
    toastMessage.textContent = message;
    toast.classList.remove('hidden');
    
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}

// Initialize the page
document.addEventListener('DOMContentLoaded', function() {
    // Check URL parameters for success messages
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showToast(urlParams.get('success'));
    }
});
</script>
</body>
</html>
    