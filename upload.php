<?php
session_start();

// Konfigurasi upload
$upload_dir = 'uploads/';
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Buat folder uploads jika belum ada
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Fungsi untuk mendapatkan daftar foto
function getUploadedPhotos($upload_dir, $allowed_types) {
    $uploaded_photos = [];
    if (is_dir($upload_dir)) {
        $files = scandir($upload_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $allowed_types)) {
                $uploaded_photos[] = $file;
            }
        }
    }
    return $uploaded_photos;
}

// Fungsi untuk menghandle upload
function handleUpload($upload_dir, $allowed_types, $max_size) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['photo'])) {
        $file = $_FILES['photo'];
        
        // Cek jika ada error saat upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error saat upload file!'];
        }
        
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi file
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => 'Format file tidak diizinkan! Hanya: ' . implode(', ', $allowed_types)];
        }
        
        // Validasi ukuran file
        if ($file_size > $max_size) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar! Maksimal 5MB'];
        }
        
        // Generate nama file unik
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file_tmp, $destination)) {
            return ['success' => true, 'message' => 'Foto berhasil diupload: ' . $file_name];
        } else {
            return ['success' => false, 'message' => 'Gagal mengupload file!'];
        }
    }
    
    return ['success' => false, 'message' => 'Tidak ada file yang diupload'];
}

// Proses upload
$result = ['success' => false, 'message' => ''];
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = handleUpload($upload_dir, $allowed_types, $max_size);
    
    // Simpan pesan dalam session untuk ditampilkan setelah redirect
    $_SESSION['upload_message'] = $result;
    
    // Redirect untuk mencegah resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Ambil pesan dari session jika ada
if (isset($_SESSION['upload_message'])) {
    $result = $_SESSION['upload_message'];
    unset($_SESSION['upload_message']);
}

// Ambil daftar foto yang sudah diupload
$uploaded_photos = getUploadedPhotos($upload_dir, $allowed_types);

// Jika request adalah AJAX untuk mendapatkan daftar foto
if (isset($_GET['action']) && $_GET['action'] == 'get_photos') {
    header('Content-Type: application/json');
    echo json_encode(['photos' => $uploaded_photos]);
    exit;
}

// Jika request adalah AJAX untuk upload
if (isset($_POST['ajax_upload'])) {
    $result = handleUpload($upload_dir, $allowed_types, $max_size);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>
