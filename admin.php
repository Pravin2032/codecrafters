<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "codecrafters";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete operations
if(isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $sql = "DELETE FROM users WHERE id = $user_id";
    if ($conn->query($sql) === TRUE) {
        $message = "User deleted successfully";
    } else {
        $error = "Error deleting user: " . $conn->error;
    }
}

if(isset($_POST['delete_purchase'])) {
    $purchase_id = $_POST['purchase_id'];
    $sql = "DELETE FROM purchases WHERE id = $purchase_id";
    if ($conn->query($sql) === TRUE) {
        $message = "Purchase deleted successfully";
    } else {
        $error = "Error deleting purchase: " . $conn->error;
    }
}

if(isset($_POST['delete_certificate'])) {
    $certificate_id = $_POST['certificate_id'];
    $sql = "DELETE FROM certificates WHERE id = $certificate_id";
    if ($conn->query($sql) === TRUE) {
        $message = "Certificate deleted successfully";
    } else {
        $error = "Error deleting certificate: " . $conn->error;
    }
}

// Get counts for dashboard
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$purchase_count = $conn->query("SELECT COUNT(*) as count FROM purchases")->fetch_assoc()['count'];
$certificate_count = $conn->query("SELECT COUNT(*) as count FROM certificates")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CodeCrafters</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      /* Main Dashboard Styles */
body {
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.container-fluid {
    padding: 0 25px;
}

/* Header Styles */
h1 {
    font-weight: 600;
    margin-bottom: 5px;
}

/* Dashboard Cards */
.dashboard-card {
    border-radius: 8px;
    border: none;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s;
}

.dashboard-card:hover {
    transform: translateY(-5px);
}

.dashboard-card .card-body {
    padding: 1.5rem;
}

.dashboard-card .text-xs {
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
}

.dashboard-card .h5 {
    font-size: 1.8rem;
}

/* Tabs Styling */
.nav-tabs {
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 10px 20px;
    margin-right: 5px;
}

.nav-tabs .nav-link:hover {
    border: none;
    color: #495057;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border: none;
    border-bottom: 3px solid #007bff;
}

/* Table Styling */
.table-container {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 20px;
    margin-bottom: 30px;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background-color: #f8f9fa;
    border-bottom: none;
    font-weight: 600;
    color: #495057;
    padding: 12px;
}

.table tbody td {
    vertical-align: middle;
    padding: 12px;
}

/* Action Buttons */
.action-btn {
    border-radius: 4px;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
}

/* Badge Styling */
.badge {
    font-weight: 500;
    padding: 0.5em 0.85em;
    border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .dashboard-card {
        margin-bottom: 15px;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
}

/* Dashboard Button in Corner */
.dashboard-btn {
    position: fixed;
    top: 20px;
    right: 25px;
    z-index: 1000;
    padding: 8px 16px;
    font-weight: 500;
    border-radius: 5px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    transition: all 0.3s;
}

.dashboard-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Alert styling */
.alert {
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

/* Profile circle placeholder */
.profile-circle {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
} 
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="text-primary">Admin Panel</h1>
                <p class="text-muted">Manage users, purchases, courses, and certificates</p>
            </div>
        </div>
        <a href="admin dashboard.php" class="btn btn-primary dashboard-btn">
    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
</a>

        <?php if(isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Dashboard Stats -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card border-left-primary h-100 py-2 bg-primary text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                    Total Users</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $user_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-white-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card border-left-success h-100 py-2 bg-success text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                    Total Purchases</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $purchase_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-white-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card border-left-info h-100 py-2 bg-info text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                    Certificates Issued</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $certificate_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-certificate fa-2x text-white-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card border-left-warning h-100 py-2 bg-warning text-white">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-white text-uppercase mb-1">
                                    Total Courses</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $course_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-book fa-2x text-white-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">Users</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases" type="button" role="tab" aria-controls="purchases" aria-selected="false">Purchases</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="courses-tab" data-bs-toggle="tab" data-bs-target="#courses" type="button" role="tab" aria-controls="courses" aria-selected="false">My Courses</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="certificates-tab" data-bs-toggle="tab" data-bs-target="#certificates" type="button" role="tab" aria-controls="certificates" aria-selected="false">Certificates</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="myTabContent">
                    <!-- Users Tab -->
                    <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div class="table-container">
                            <h3 class="mb-4">User Management</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Profile</th>
                                            <th>Full Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>DOB</th>
                                            <th>Gender</th>
                                            <th>Created At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM users ORDER BY id DESC";
                                        $result = $conn->query($sql);
                                        
                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row["id"] . "</td>";
                                                echo "<td>";
                                                if($row["profile_image"]) {
                                                    echo "<img src='" . $row["profile_image"] . "' alt='Profile' width='40' height='40' class='rounded-circle'>";
                                                } else {
                                                    echo "<div class='rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center' style='width:40px;height:40px;'>" . substr($row["full_name"], 0, 1) . "</div>";
                                                }
                                                echo "</td>";
                                                echo "<td>" . $row["full_name"] . "</td>";
                                                echo "<td>" . $row["email"] . "</td>";
                                                echo "<td>" . $row["phone"] . "</td>";
                                                echo "<td>" . $row["date_of_birth"] . "</td>";
                                                echo "<td>" . ucfirst($row["gender"]) . "</td>";
                                                echo "<td>" . date("d M Y", strtotime($row["created_at"])) . "</td>";
                                                echo "<td>
                                                    <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this user?\");'>
                                                        <input type='hidden' name='user_id' value='" . $row["id"] . "'>
                                                        <button type='submit' name='delete_user' class='btn btn-danger btn-sm action-btn'><i class='fas fa-trash-alt'></i> Delete</button>
                                                    </form>
                                                </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='9' class='text-center'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Purchases Tab -->
                    <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                        <div class="table-container">
                            <h3 class="mb-4">Purchase Management</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Course</th>
                                            <th>Purchase Date</th>
                                            <th>Access Code</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, u.full_name, c.title 
                                                FROM purchases p
                                                JOIN users u ON p.user_id = u.id
                                                JOIN courses c ON p.course_id = c.id
                                                ORDER BY p.purchase_date DESC";
                                        $result = $conn->query($sql);
                                        
                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row["id"] . "</td>";
                                                echo "<td>" . $row["full_name"] . "</td>";
                                                echo "<td>" . $row["title"] . "</td>";
                                                echo "<td>" . date("d M Y H:i", strtotime($row["purchase_date"])) . "</td>";
                                                echo "<td><span class='badge bg-secondary'>" . $row["access_code"] . "</span></td>";
                                                echo "<td>
                                                    <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this purchase?\");'>
                                                        <input type='hidden' name='purchase_id' value='" . $row["id"] . "'>
                                                        <button type='submit' name='delete_purchase' class='btn btn-danger btn-sm action-btn'><i class='fas fa-trash-alt'></i> Delete</button>
                                                    </form>
                                                </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='7' class='text-center'>No purchases found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- My Courses Tab -->
                    <div class="tab-pane fade" id="courses" role="tabpanel" aria-labelledby="courses-tab">
                        <div class="table-container">
                            <h3 class="mb-4">User Courses</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Course</th>



                                            <th>Purchase Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT p.*, u.full_name, c.title
                                                FROM purchases p
                                                JOIN users u ON p.user_id = u.id
                                                JOIN courses c ON p.course_id = c.id
                                                LEFT JOIN user_progress up ON p.user_id = up.user_id AND p.course_id = up.course_id
                                                ORDER BY p.purchase_date DESC";
                                        $result = $conn->query($sql);
                                        
                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row["full_name"] . "</td>";
                                                echo "<td>" . $row["title"] . "</td>";
                                                echo "<td>" . date("d M Y", strtotime($row["purchase_date"])) . "</td>";
                                                
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center'>No course enrollments found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Certificates Tab -->
                    <div class="tab-pane fade" id="certificates" role="tabpanel" aria-labelledby="certificates-tab">
                        <div class="table-container">
                            <h3 class="mb-4">Certificate Management</h3>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>User Name</th>
                                            <th>Email</th>
                                            <th>Language</th>
                                            <th>Score</th>
                                            <th>Date Issued</th>
                                            <th>Certificate ID</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql = "SELECT * FROM certificates ORDER BY date_issued DESC";
                                        $result = $conn->query($sql);
                                        
                                        if ($result->num_rows > 0) {
                                            while($row = $result->fetch_assoc()) {
                                                echo "<tr>";
                                                echo "<td>" . $row["id"] . "</td>";
                                                echo "<td>" . $row["user_name"] . "</td>";
                                                echo "<td>" . $row["email"] . "</td>";
                                                echo "<td>" . ucfirst($row["language"]) . "</td>";
                                                echo "<td>";
                                                $score_class = $row["score"] >= 70 ? "success" : ($row["score"] >= 50 ? "warning" : "danger");
                                                echo "<span class='badge bg-" . $score_class . "'>" . $row["score"] . "%</span>";
                                                echo "</td>";
                                                echo "<td>" . date("d M Y", strtotime($row["date_issued"])) . "</td>";
                                                echo "<td><span class='badge bg-info'>" . $row["certificate_id"] . "</span></td>";
                                                echo "<td>
                                                    <form method='post' onsubmit='return confirm(\"Are you sure you want to delete this certificate?\");'>
                                                        <input type='hidden' name='certificate_id' value='" . $row["id"] . "'>
                                                        <button type='submit' name='delete_certificate' class='btn btn-danger btn-sm action-btn'><i class='fas fa-trash-alt'></i> Delete</button>
                                                    </form>
                                                </td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='8' class='text-center'>No certificates found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>