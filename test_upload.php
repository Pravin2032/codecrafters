<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration settings
ini_set('upload_max_filesize', '100M');
ini_set('post_max_size', '100M');
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Check server configuration
echo "<h2>Server Configuration</h2>";
echo "<pre>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "Current working directory: " . getcwd() . "\n";
echo "</pre>";

// Check directory permissions
$upload_dir = "uploads/videos/";
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color:green'>Successfully created directory: {$upload_dir}</p>";
        chmod($upload_dir, 0777);
    } else {
        echo "<p style='color:red'>Failed to create directory: {$upload_dir}</p>";
    }
} else {
    echo "<p>Directory already exists: {$upload_dir}</p>";
    echo "<p>Is writable: " . (is_writable($upload_dir) ? "Yes" : "No") . "</p>";
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "<h2>Upload Result</h2>";
    
    if (isset($_FILES["video_file"]) && $_FILES["video_file"]["error"] == 0) {
        echo "<pre>";
        echo "File details:\n";
        print_r($_FILES["video_file"]);
        echo "</pre>";
        
        $target_file = $upload_dir . basename($_FILES["video_file"]["name"]);
        
        // Try to upload the file
        if (move_uploaded_file($_FILES["video_file"]["tmp_name"], $target_file)) {
            chmod($target_file, 0644);
            echo "<p style='color:green'>File uploaded successfully to: {$target_file}</p>";
            echo "<p>File size: " . filesize($target_file) . " bytes</p>";
            echo "<a href='{$target_file}' target='_blank'>View File</a>";
        } else {
            echo "<p style='color:red'>Failed to upload file!</p>";
            echo "<p>Error: " . $_FILES["video_file"]["error"] . "</p>";
            
            // Try copy as a fallback
            if (copy($_FILES["video_file"]["tmp_name"], $target_file)) {
                chmod($target_file, 0644);
                echo "<p style='color:green'>File copied successfully using copy() as fallback to: {$target_file}</p>";
            } else {
                echo "<p style='color:red'>Copy fallback also failed.</p>";
                echo "<p>PHP error: " . error_get_last()['message'] . "</p>";
            }
        }
    } else {
        echo "<p style='color:red'>Error: " . $_FILES["video_file"]["error"] . "</p>";
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
        if (isset($error_messages[$_FILES["video_file"]["error"]])) {
            echo "<p>Error meaning: " . $error_messages[$_FILES["video_file"]["error"]] . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Video Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        button { padding: 8px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Test Video Upload</h1>
    
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="video_file">Select Video File:</label>
            <input type="file" id="video_file" name="video_file" accept="video/*" required>
        </div>
        
        <div class="form-group">
            <button type="submit">Upload Video</button>
        </div>
    </form>
    
    <h2>Directory Contents</h2>
    <?php
    if (file_exists($upload_dir)) {
        $files = scandir($upload_dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>{$file} (" . filesize($upload_dir . $file) . " bytes)</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>Directory does not exist</p>";
    }
    ?>
</body>
</html>