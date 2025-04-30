<?php
// Database configuration
$db_host = 'localhost';      // Database host (usually localhost)
$db_user = 'root';           // Database username (default: root)
$db_password = '';           // Database password (default: empty for XAMPP/WAMP)
$db_name = 'codecrafters';     // Database name

// Create connection
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set
mysqli_set_charset($conn, "utf8");

// Optional: set timezone for consistent datetime handling
date_default_timezone_set('Asia/Kolkata'); // Change to your timezone

// Function to sanitize input data
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to handle SQL errors and log them
function handle_sql_error($conn, $query) {
    $error_message = "MySQL Error: " . mysqli_error($conn) . "\n";
    $error_message .= "Query: " . $query . "\n";
    
    // Log the error to a file
    error_log($error_message, 3, "sql_errors.log");
    
    // Return a user-friendly message
    return "A database error occurred. Please try again later or contact the administrator.";
}