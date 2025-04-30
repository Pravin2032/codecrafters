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

// Initialize variables
$message = "";
$courses = [];
$selected_course = null;

// Get available courses
$courses_sql = "SELECT id, title, description, price, image, status FROM courses WHERE status = 'active' ORDER BY created_at DESC";
$result = $conn->query($courses_sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Handle course selection
if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = $_GET['course_id'];
    $stmt = $conn->prepare("SELECT id, title, description, price, image FROM courses WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selected_course = $result->fetch_assoc();
    }
    
    $stmt->close();
}

// Process purchase form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['purchase_course'])) {
    $course_id = $_POST['course_id'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $payment_method = $_POST['payment_method'];
    
    // Check if user exists
    $user_id = null;
    $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $user_check->bind_param("s", $email);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows > 0) {
        // User exists
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['id'];
        
        // Update user information
        $update_user = $conn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
        $update_user->bind_param("ssi", $full_name, $phone, $user_id);
        $update_user->execute();
        $update_user->close();
    } else {
        // Create new user
        $create_user = $conn->prepare("INSERT INTO users (full_name, email, phone, created_at) VALUES (?, ?, ?, NOW())");
        $create_user->bind_param("sss", $full_name, $email, $phone);
        $create_user->execute();
        $user_id = $conn->insert_id;
        $create_user->close();
    }
    
    $user_check->close();
    
    // Check if this user already purchased this course
    $purchase_check = $conn->prepare("SELECT id FROM purchases WHERE user_id = ? AND course_id = ?");
    $purchase_check->bind_param("ii", $user_id, $course_id);
    $purchase_check->execute();
    $purchase_result = $purchase_check->get_result();
    
    if ($purchase_result->num_rows > 0) {
        $message = "<div class='alert alert-custom alert-warning'>
            <div class='alert-icon'><i class='fas fa-exclamation-triangle'></i></div>
            <div class='alert-content'>You have already purchased this course. Please check your email for access details.</div>
        </div>";
    } else {
        // Get course price
        $course_price = 0;
        $get_price = $conn->prepare("SELECT price FROM courses WHERE id = ?");
        $get_price->bind_param("i", $course_id);
        $get_price->execute();
        $price_result = $get_price->get_result();
        if ($price_result->num_rows > 0) {
            $price_row = $price_result->fetch_assoc();
            $course_price = $price_row['price'];
        }
        $get_price->close();
        
        // Generate transaction ID
        $transaction_id = 'TXN' . time() . rand(1000, 9999);
        
        // Create new purchase
        $create_purchase = $conn->prepare("INSERT INTO purchases (user_id, course_id, purchase_date) VALUES (?, ?, NOW())");
        $create_purchase->bind_param("ii", $user_id, $course_id);
        
        if ($create_purchase->execute()) {
            $purchase_id = $conn->insert_id;
            
            // Generate access code (simple implementation)
            $access_code = strtoupper(substr(md5($user_id . $course_id . time()), 0, 8));
            
            // Update purchase with access code
            $update_purchase = $conn->prepare("UPDATE purchases SET access_code = ? WHERE id = ?");
            $update_purchase->bind_param("si", $access_code, $purchase_id);
            $update_purchase->execute();
            $update_purchase->close();
            
            // Create order record
            $create_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, payment_method, transaction_id, order_date, status) VALUES (?, ?, ?, ?, NOW(), 'completed')");
            $create_order->bind_param("idss", $user_id, $course_price, $payment_method, $transaction_id);
            $create_order->execute();
            $create_order->close();
            
            $message = "<div class='alert alert-custom alert-success'>
                <div class='alert-icon'><i class='fas fa-check-circle'></i></div>
                <div class='alert-content'>
                    <h4 class='alert-heading'>Purchase Successful!</h4>
                    <p>Thank you for your purchase. Your access code is: <strong>$access_code</strong></p>
                    <p>Your transaction ID is: <strong>$transaction_id</strong></p>
                    <p>We've sent an email with course access details to $email</p>
                    <hr>
                    <p class='mb-0'>You will be redirected to the course materials shortly.</p>
                </div>
            </div>";
            
            // In a real implementation, send email with access details here
        } else {
            $message = "<div class='alert alert-custom alert-danger'>
                <div class='alert-icon'><i class='fas fa-times-circle'></i></div>
                <div class='alert-content'>Error processing your purchase. Please try again later.</div>
            </div>";
        }
        
        $create_purchase->close();
    }
    
    $purchase_check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CodeCrafters - Course Purchase</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #a5b4fc;
            --secondary-color: #14b8a6;
            --accent-color: #8b5cf6;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --gray-light: #e2e8f0;
            --gray-medium: #94a3b8;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --success-color: #10b981;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f1f5f9;
            color: var(--dark-color);
        }
        
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            padding: 1rem 0;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white;
            display: flex;
            align-items: center;
        }
        
        .navbar-brand i {
            font-size: 1.8rem;
            margin-right: 0.5rem;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .navbar-nav .nav-link:hover {
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-dashboard {
            background-color: white;
            color: var(--primary-color);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-dashboard:hover {
            background-color: var(--light-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .hero-section {
            background: url('https://images.unsplash.com/photo-1517694712202-14dd9538aa97?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') no-repeat center center;
            background-size: cover;
            position: relative;
            padding: 120px 0;
            margin-bottom: 60px;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.9), rgba(139, 92, 246, 0.8));
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .btn-hero {
            background-color: white;
            color: var(--primary-color);
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .btn-hero:hover {
            background-color: var(--light-color);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .course-container {
            padding: 2rem;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .course-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
        }
        
        .course-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
        }
        
        .course-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.4s ease;
        }
        
        .course-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .course-card:hover::after {
            transform: scaleX(1);
        }
        
        .course-image {
            height: 200px;
            object-fit: cover;
        }
        
        .course-body {
            padding: 1.5rem;
        }
        
        .course-title {
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .course-description {
            color: var(--gray-medium);
            margin-bottom: 1rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }
        
        .course-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .course-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .btn-purchase {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-purchase:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        
        .checkout-section {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .checkout-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 2rem;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .breadcrumb-item a:hover {
            color: var(--accent-color);
        }
        
        .breadcrumb-item.active {
            color: var(--gray-medium);
        }
        
        .course-details-img {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            height: auto;
            object-fit: cover;
        }
        
        .order-summary {
            background-color: #f8fafc;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-header {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed var(--gray-light);
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            color: var(--gray-medium);
        }
        
        .summary-value {
            font-weight: 500;
        }
        
        .summary-total {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 0.75rem 1.25rem;
            border: 1px solid var(--gray-light);
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
        
        .payment-method {
            border: 2px solid var(--gray-light);
            border-radius: 15px;
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        
        .payment-method:hover {
            border-color: var(--primary-light);
            background-color: rgba(99, 102, 241, 0.05);
        }
        
        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: rgba(99, 102, 241, 0.1);
        }
        
        .payment-radio {
            margin-right: 1rem;
        }
        
        .payment-label {
            font-weight: 500;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .btn-pay {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-pay:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--accent-color));
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
        
        .benefits-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 1.5rem;
        }
        
        .benefits-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .benefits-body {
            padding: 1.5rem;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        
        .benefit-item:last-child {
            margin-bottom: 0;
        }
        
        .benefit-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(to bottom right, var(--primary-light), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .benefit-content h6 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .benefit-content p {
            color: var(--gray-medium);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .help-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }
        
        .help-header {
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            color: white;
            padding: 1.25rem 1.5rem;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .help-body {
            padding: 1.5rem;
        }
        
        .btn-support {
            background-color: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-support:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.2);
        }
        
        .alert-custom {
            display: flex;
            padding: 1.25rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .alert-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
        }
        
        .alert-content {
            flex-grow: 1;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            border-left: 5px solid var(--success-color);
        }
        
        .alert-success .alert-icon {
            color: var(--success-color);
        }
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.1);
            border-left: 5px solid var(--warning-color);
        }
        
        .alert-warning .alert-icon {
            color: var(--warning-color);
        }
        
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border-left: 5px solid var(--danger-color);
        }
        
        .alert-danger .alert-icon {
            color: var(--danger-color);
        }
        
        .empty-courses {
            text-align: center;
            padding: 4rem 0;
        }
        
        .empty-icon {
            font-size: 4rem;
            color: var(--gray-medium);
            margin-bottom: 1.5rem;
        }
        
        .empty-title {
            font-size: 1.5rem;
            color: var(--dark-color);
            margin-bottom: 0.75rem;
        }
        
        .empty-text {
            color: var(--gray-medium);
            font-size: 1.1rem;
        }
        
        .modal-content {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            border-top: none;
            padding: 1rem 1.5rem 1.5rem;
        }
        
        .btn-modal {
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 1.75rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-modal:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--accent-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-code-branch"></i>
                CodeCrafters
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="btn btn-dashboard" href="student_dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <?php if ($selected_course): ?>
        <!-- Course Detail and Checkout -->
        <div class="container my-5">
            <div class="row">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                            <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($selected_course['title']); ?></li>
                        </ol>
                    </nav>
                    
                    <?php if (!empty($message)): ?>
                        <?php echo $message; ?>
                    <?php else: ?>
                        <div class="checkout-section mb-4">
                            <h2 class="mb-4 course-header">Course Enrollment</h2>
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="position-relative mb-4">
                                        <img src="<?php echo $selected_course['image'] ?: 'uploads/images/default-course.jpg'; ?>" 
                                             alt="<?php echo htmlspecialchars($selected_course['title']); ?>" 
                                             class="course-details-img">
                                        <div class="position-absolute top-0 end-0 p-2">
                                            <span class="badge bg-primary rounded-pill px-3 py-2">
                                                <i class="fas fa-star me-1"></i> Premium
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <h3 class="mb-2"><?php echo htmlspecialchars($selected_course['title']); ?></h3>
                                    <div class="d-flex align-items-center mb-3">
                                        <span class="badge bg-success me-2">Bestseller</span>
                                        <div class="text-warning">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                            <span class="text-muted ms-2">(342 reviews)</span>
                                        </div>
                                    </div>
                                    <p class="course-price mb-3">₹<?php echo number_format($selected_course['price'], 2); ?></p>
                                    <div class="mb-4 course-description">
                                        <?php echo nl2br(htmlspecialchars($selected_course['description'])); ?>
                                    </div>
                                    <div class="d-flex flex-wrap mb-3">
                                        <div class="me-4 mb-3">
                                            <i class="fas fa-users me-2 text-primary"></i>
                                            <span>7,245 enrolled</span>
                                        </div>
                                        <div class="me-4 mb-3">
                                            <i class="fas fa-clock me-2 text-primary"></i>
                                            <span>24 hours total</span>
                                        </div>
                                        <div class="mb-3">
                                            <i class="fas fa-signal me-2 text-primary"></i>
                                            <span>All levels</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-summary my-4">
                                <h4 class="summary-header">Order Summary</h4>
                                <div class="summary-item">
                                    <span class="summary-label">Course Price</span>
                                    <span class="summary-value">₹<?php echo number_format($selected_course['price'], 2); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Discount</span>
                                    <span class="summary-value">-₹0.00</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Tax (18% GST)</span>
                                    <span class="summary-value">₹<?php echo number_format($selected_course['price'] * 0.18, 2); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label">Total</span>
                                    <span class="summary-value summary-total">₹<?php echo number_format($selected_course['price'] * 1.18, 2); ?></span>
                                </div>
                            </div>
                            
                            <form method="post" action="">
                                <input type="hidden" name="course_id" value="<?php echo $selected_course['id']; ?>">
                                
                                <div class="mb-4">
                                    <h4 class="mb-3">Personal Information</h4>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="full_name" class="form-label">Full Name</label>
                                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <h4 class="mb-3">Payment Method</h4>
                                    <div class="payment-method">
                                        <input type="radio" name="payment_method" id="credit_card" value="credit_card" class="payment-radio" checked>
                                        <label for="credit_card" class="payment-label">
                                            <i class="fas fa-credit-card payment-icon"></i>
                                            Credit / Debit Card
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" name="payment_method" id="paypal" value="paypal" class="payment-radio">
                                        <label for="paypal" class="payment-label">
                                            <i class="fab fa-paypal payment-icon"></i>
                                            PayPal
                                        </label>
                                    </div>
                                    <div class="payment-method">
                                        <input type="radio" name="payment_method" id="upi" value="upi" class="payment-radio">
                                        <label for="upi" class="payment-label">
                                            <i class="fas fa-mobile-alt payment-icon"></i>
                                            UPI Payment
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="text-center">
                                    <button type="submit" name="purchase_course" class="btn btn-pay">
                                        <i class="fas fa-lock me-2"></i>Complete Purchase
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <div class="benefits-card">
                        <div class="benefits-header">
                            <i class="fas fa-gift me-2"></i> What You'll Get
                        </div>
                        <div class="benefits-body">
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-laptop-code"></i>
                                </div>
                                <div class="benefit-content">
                                    <h6>Lifetime Access</h6>
                                    <p>Learn at your own pace with unlimited access to course materials.</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-certificate"></i>
                                </div>
                                <div class="benefit-content">
                                    <h6>Completion Certificate</h6>
                                    <p>Receive a certificate upon successful completion of the course.</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <div class="benefit-content">
                                    <h6>Expert Support</h6>
                                    <p>Get help from experienced instructors whenever you need it.</p>
                                </div>
                            </div>
                            <div class="benefit-item">
                                <div class="benefit-icon">
                                    <i class="fas fa-file-code"></i>
                                </div>
                                <div class="benefit-content">
                                    <h6>Practical Projects</h6>
                                    <p>Build a portfolio with real-world coding projects.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="help-card mt-4">
                        <div class="help-header">
                            <i class="fas fa-headset me-2"></i> Need Help?
                        </div>
                        <div class="help-body">
                            <p>If you have any questions about this course or the enrollment process, our support team is here to help.</p>
                            <div class="d-grid gap-2">
                                <a href="support.php" class="btn btn-support">
                                    <i class="fas fa-comment-dots me-2"></i>Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Course Listing -->
        <div class="hero-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-8 hero-content">
                        <h1>Elevate Your Coding Skills</h1>
                        <p>Join thousands of successful developers who have mastered programming with our industry-leading courses.</p>
                        <a href="#courses" class="btn btn-hero">
                            <i class="fas fa-chevron-down me-2"></i>Explore Courses
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container mb-5" id="courses">
            <div class="course-container">
                <h2 class="mb-4">Available Courses</h2>
                
                <?php if (!empty($message)): ?>
                    <?php echo $message; ?>
                <?php endif; ?>
                
                <?php if (!empty($courses)): ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($courses as $course): ?>
                            <div class="col">
                                <div class="course-card">
                                    <img src="<?php echo $course['image'] ?: 'uploads/images/default-course.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($course['title']); ?>" 
                                         class="course-image">
                                    <div class="course-body">
                                        <h5 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                        <p class="course-description">
                                            <?php echo (strlen($course['description']) > 100) ? htmlspecialchars(substr($course['description'], 0, 100)) . '...' : htmlspecialchars($course['description']); ?>
                                        </p>
                                    </div>
                                    <div class="course-footer">
                                        <div class="course-price">₹<?php echo number_format($course['price'], 2); ?></div>
                                        <a href="?course_id=<?php echo $course['id']; ?>" class="btn btn-purchase">
                                            Enroll Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-courses">
                        <div class="empty-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="empty-title">No Courses Available</h3>
                        <p class="empty-text">Check back later for new course offerings.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-4">CodeCrafters</h5>
                    <p>Providing high-quality programming education to help you succeed in the world of technology.</p>
                    <div class="d-flex mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    <h6 class="mb-3">Courses</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Web Development</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Mobile Apps</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Data Science</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Game Development</a></li>
                        <li><a href="#" class="text-white text-decoration-none">AI & Machine Learning</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                    
                </div>
                <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                   
                </div>
                <div class="col-lg-2 col-md-4">

                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">© 2025 CodeCrafters. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <select class="form-select-sm bg-dark text-white border-secondary">
                        <option selected>English</option>
                        <option>Hindi</option>
                        <option>Spanish</option>
                        <option>French</option>
                    </select>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                // Add selected class to clicked method
                this.classList.add('selected');
                // Check the radio button
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
            });
        });
    </script>
</body>
</html>