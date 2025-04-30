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

// Handle Form Submission for Adding New Student
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $enrolled_courses = $_POST['enrolled_courses']; // New field

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

    $sql = "INSERT INTO students (first_name, last_name, email, phone, dob, enrolled_courses) 
            VALUES ('$first_name', '$last_name', '$email', '$phone', '$dob', '$enrolled_courses')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Student added successfully!'); window.location='admin_students.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Remove Student by ID
if (isset($_GET['remove_id'])) {
    $remove_id = $_GET['remove_id'];
    $sql = "DELETE FROM students WHERE student_id = '$remove_id'";
    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Student removed successfully!'); window.location='admin_students.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Handle Edit Student Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_student'])) {
    $student_id = $_POST['student_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $dob = $_POST['dob'];
    $enrolled_courses = $_POST['enrolled_courses']; // New field

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

    $sql = "UPDATE students SET 
            first_name = '$first_name',
            last_name = '$last_name', 
            email = '$email', 
            phone = '$phone', 
            dob = '$dob',
            enrolled_courses = '$enrolled_courses'
            WHERE student_id = '$student_id'";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Student details updated successfully!'); window.location='admin_students.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch Student Records
$students = [];
$result = $conn->query("SELECT * FROM students ORDER BY registration_date DESC");
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - CodeCrafters Admin</title>
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
        .student-card {
            transition: all 0.3s ease;
        }
        
        .student-card:hover {
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
                <button id="historyBtn" class="flex items-center hover:text-indigo-200 transition">
                    <i class="fas fa-users mr-2"></i>
                    <span class="hidden sm:inline">Student Records</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mx-auto p-4 md:p-6">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 md:mb-0">
                <i class="fas fa-user-plus text-indigo-600 mr-2"></i>Student Registration
            </h2>
        </div>

        <!-- Student Form Card -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden mb-8 animate-fadeIn">
            <!-- Form Header with Tabs -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-4 text-white">
                <h3 class="text-xl font-semibold">New Student Registration</h3>
                <p class="text-indigo-100 text-sm">Complete the form below to register a new student</p>
            </div>
            
            <!-- Student Registration Form -->
            <form action="" method="POST" class="responsive-form p-6">
                <!-- Personal Information -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-id-card text-indigo-500 mr-2"></i>Personal Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                            <input type="text" name="first_name" required 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                            <input type="text" name="last_name" required 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" name="dob" required 
                                   class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
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

                <!-- Course Information -->
                <div class="mb-6">
                    <h4 class="text-lg font-semibold mb-4 flex items-center text-gray-700">
                        <i class="fas fa-book text-indigo-500 mr-2"></i>Course Information
                    </h4>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Enrolled Courses</label>
                        <input type="text" name="enrolled_courses" placeholder="e.g. Web Development, Python, Data Science" 
                               class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                        <p class="text-xs text-gray-500 mt-1">Separate multiple courses with commas</p>
                    </div>
                </div>

                <!-- Form Submission -->
                <div class="flex justify-end">
                    <button type="submit" name="add_student" 
                            class="btn px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-sm">
                        <i class="fas fa-user-plus mr-2"></i>Register Student
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Student Records Modal -->
    <div id="historyModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-3/4 flex flex-col animate-fadeIn">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 p-4 text-white rounded-t-lg flex justify-between items-center">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-users mr-2"></i>Student Records
                </h2>
                <div class="flex items-center">
                    <div class="relative mr-4">
                        <input type="text" id="searchInput" placeholder="Search students..." 
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
                <!-- Student List -->
                <div id="studentList" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($students as $student): ?>
                        <div class="student-item student-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 hover:bg-indigo-50">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-lg text-indigo-700">
                                    <?= $student['first_name'] . ' ' . $student['last_name'] ?>
                                </h3>
                                <div>
                                    <span class="text-xs px-2 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                                        ID: <?= $student['student_id'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-2 mb-3 text-sm">
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                                    <span><?= $student['dob'] ?></span>
                                </div>
                                <div class="flex items-center text-gray-600">
                                    <i class="fas fa-phone mr-2 text-indigo-500"></i>
                                    <span><?= $student['phone'] ?></span>
                                </div>
                                <div class="flex items-center text-gray-600 col-span-2">
                                    <i class="fas fa-envelope mr-2 text-indigo-500"></i>
                                    <span class="truncate"><?= $student['email'] ?></span>
                                </div>
                                <div class="flex items-center text-gray-600 col-span-2">
                                    <i class="fas fa-book mr-2 text-indigo-500"></i>
                                    <span class="truncate"><?= isset($student['enrolled_courses']) ? $student['enrolled_courses'] : 'None' ?></span>
                                </div>
                                <div class="flex items-center text-gray-600 col-span-2">
                                    <i class="fas fa-clock mr-2 text-indigo-500"></i>
                                    <span>Registered: <?= date('M d, Y', strtotime($student['registration_date'])) ?></span>
                                </div>
                            </div>
                            
                            <div class="flex justify-end gap-2 mt-3">
                                <button onclick="openEditForm('<?= $student['student_id'] ?>', '<?= $student['first_name'] ?>', '<?= $student['last_name'] ?>', '<?= $student['email'] ?>', '<?= $student['phone'] ?>', '<?= $student['dob'] ?>', '<?= isset($student['enrolled_courses']) ? $student['enrolled_courses'] : '' ?>')" 
                                        class="btn text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded">
                                    <i class="fas fa-edit mr-1"></i><span class="text-sm">Edit</span>
                                </button>
                                <button onclick="removeStudent('<?= $student['student_id'] ?>')" 
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
                        <h3 class="text-lg font-bold text-indigo-700">Edit Student Information</h3>
                        <button id="backToListBtn" class="text-indigo-600 hover:text-indigo-800 flex items-center">
                            <i class="fas fa-arrow-left mr-1"></i> Back to List
                        </button>
                    </div>
                    
                    <form action="" method="POST" class="space-y-6" onsubmit="return validateEditForm()">
                        <input type="hidden" name="student_id" id="edit_student_id">
                        
                        <!-- Personal Information -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Personal Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                    <input type="text" name="first_name" id="edit_first_name" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                    <input type="text" name="last_name" id="edit_last_name" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="dob" id="edit_dob" required 
                                           class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
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

                        <!-- Course Information -->
                        <div class="bg-indigo-50 p-4 rounded-lg">
                            <h4 class="text-md font-semibold mb-3 text-indigo-700">Course Information</h4>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Enrolled Courses</label>
                                <input type="text" name="enrolled_courses" id="edit_enrolled_courses" 
                                       class="form-input w-full p-2 border border-gray-300 rounded-lg focus:outline-none">
                                <p class="text-xs text-gray-500 mt-1">Separate multiple courses with commas</p>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="edit_student" 
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
        // Show/Hide Student Records Modal
        document.getElementById('historyBtn').addEventListener('click', function() {
            document.getElementById('historyModal').classList.remove('hidden');
            document.getElementById('searchInput').style.display = 'block';
        });
        
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            closeModal();
        });
        
        // Function to close the modal
        function closeModal() {
            document.getElementById('historyModal').classList.add('hidden');
            document.getElementById('editForm').classList.add('hidden');
            document.getElementById('studentList').classList.remove('hidden');
        }
        
        // Function to open the edit form
        function openEditForm(student_id, first_name, last_name, email, phone, dob, enrolled_courses) {
            document.getElementById('studentList').classList.add('hidden');
            document.getElementById('editForm').classList.remove('hidden');
            
            // Fill the edit form with student details
            document.getElementById('edit_student_id').value = student_id;
            document.getElementById('edit_first_name').value = first_name;
            document.getElementById('edit_last_name').value = last_name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone;
            document.getElementById('edit_dob').value = dob;
            document.getElementById('edit_enrolled_courses').value = enrolled_courses;
        }
        
        // Function to remove student with confirmation
        function removeStudent(student_id) {
            if (confirm("Are you sure you want to remove this student? This action cannot be undone.")) {
                window.location.href = "admin_students.php?remove_id=" + student_id;
            }
        }
        
        // Back to list button
        document.getElementById('backToListBtn').addEventListener('click', function() {
            document.getElementById('editForm').classList.add('hidden');
            document.getElementById('studentList').classList.remove('hidden');
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            let searchValue = this.value.toLowerCase();
            let studentItems = document.querySelectorAll('.student-item');
            
            studentItems.forEach(function(item) {
                let studentText = item.textContent.toLowerCase();
                if (studentText.includes(searchValue)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        
        // Edit Form Validation
        function validateEditForm() {
            var email = document.getElementById('edit_email').value;
            var phone = document.getElementById('edit_phone').value;
            
            // Validate email
            if (email.indexOf('@') === -1) {
                showToast('Invalid email address. The email must contain an "@" symbol.', 'error');
                return false;
            }
            
            // Validate phone number (should be exactly 10 digits)
            if (!/^\d{10}$/.test(phone)) {
                showToast('Invalid phone number. Phone number must be exactly 10 digits.', 'error');
                return false;
            }
            
            return true;
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toastNotification');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.textContent = message;
            
            // Set color based on type
            if (type === 'error') {
                toast.classList.remove('bg-green-500');
                toast.classList.add('bg-red-500');
            } else {
                toast.classList.remove('bg-red-500');
                toast.classList.add('bg-green-500');
            }
            
            // Show toast
            toast.classList.remove('hidden');
            
            // Hide after 3 seconds
            setTimeout(function() {
                toast.classList.add('hidden');
            }, 3000);
        }
        
        // Add event listeners for form inputs to apply custom styling
        const formInputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
        formInputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.classList.add('ring-2', 'ring-indigo-200', 'border-indigo-300');
            });
            
            input.addEventListener('blur', () => {
                input.classList.remove('ring-2', 'ring-indigo-200', 'border-indigo-300');
            });
        });
    </script>
</body>
</html>