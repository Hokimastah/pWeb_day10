<?php
echo "<h2>Debug Information - Upload Configuration</h2>";

// Check PHP configuration
echo "<h3>PHP Upload Configuration:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";

// Check folder permissions
echo "<h3>Folder Permissions:</h3>";
$upload_dir = 'uploads/';

if (!file_exists($upload_dir)) {
    echo "❌ Folder 'uploads/' tidak ada<br>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "✅ Folder 'uploads/' berhasil dibuat<br>";
    } else {
        echo "❌ Gagal membuat folder 'uploads/'<br>";
    }
} else {
    echo "✅ Folder 'uploads/' ada<br>";
}

if (is_writable($upload_dir)) {
    echo "✅ Folder 'uploads/' dapat ditulis<br>";
} else {
    echo "❌ Folder 'uploads/' tidak dapat ditulis<br>";
    echo "Coba jalankan: chmod 755 uploads/<br>";
}

// Check file permissions
$current_dir = dirname(__FILE__);
echo "Current directory: " . $current_dir . "<br>";
echo "Current directory writable: " . (is_writable($current_dir) ? "✅ Yes" : "❌ No") . "<br>";

// Test file upload
echo "<h3>Test Upload Form:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo'])) {
    echo "<h4>Upload Test Result:</h4>";
    $file = $_FILES['test_photo'];
    
    echo "File info:<br>";
    echo "Name: " . $file['name'] . "<br>";
    echo "Type: " . $file['type'] . "<br>";
    echo "Size: " . $file['size'] . " bytes<br>";
    echo "Error: " . $file['error'] . "<br>";
    echo "Temp file: " . $file['tmp_name'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $destination = $upload_dir . 'test_' . $file['name'];
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "✅ File berhasil diupload ke: " . $destination . "<br>";
        } else {
            echo "❌ Gagal memindahkan file<br>";
        }
    } else {
        echo "❌ Upload error code: " . $file['error'] . "<br>";
    }
}
?>

<form method="POST" enctype="multipart/form-data" style="margin-top: 20px; padding: 20px; border: 1px solid #ccc;">
    <h4>Test Upload:</h4>
    <input type="file" name="test_photo" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>

<h3>Server Information:</h3>
<?php
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "<br>";

// Check required functions
echo "<h3>Required Functions:</h3>";
$functions = ['move_uploaded_file', 'getimagesize', 'pathinfo', 'uniqid'];
foreach ($functions as $func) {
    echo $func . ": " . (function_exists($func) ? "✅ Available" : "❌ Not available") . "<br>";
}

// Check GD extension
echo "<h3>Image Extensions:</h3>";
echo "GD Extension: " . (extension_loaded('gd') ? "✅ Loaded" : "❌ Not loaded") . "<br>";
if (extension_loaded('gd')) {
    $gd_info = gd_info();
    echo "JPEG Support: " . ($gd_info['JPEG Support'] ? "✅ Yes" : "❌ No") . "<br>";
    echo "PNG Support: " . ($gd_info['PNG Support'] ? "✅ Yes" : "❌ No") . "<br>";
    echo "GIF Support: " . ($gd_info['GIF Read Support'] ? "✅ Yes" : "❌ No") . "<br>";
}
?>
