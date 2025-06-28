<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set proper headers
header('Content-Type: application/json; charset=utf-8');

session_start();

// Konfigurasi upload
$upload_dir = 'uploads/';
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
$max_size = 5 * 1024 * 1024; // 5MB

// Buat folder uploads jika belum ada
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat folder uploads']);
        exit;
    }
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
    try {
        if (!isset($_FILES['photo'])) {
            return ['success' => false, 'message' => 'Tidak ada file yang dipilih'];
        }

        $file = $_FILES['photo'];
        
        // Debug info
        error_log("File upload attempt: " . print_r($file, true));
        
        // Cek jika ada error saat upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize)',
                UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE)',
                UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
            ];
            
            $error_message = isset($error_messages[$file['error']]) ? 
                           $error_messages[$file['error']] : 
                           'Error upload tidak dikenal: ' . $file['error'];
                           
            return ['success' => false, 'message' => $error_message];
        }
        
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        
        // Cek apakah file benar-benar ada
        if (!is_uploaded_file($file_tmp)) {
            return ['success' => false, 'message' => 'File tidak valid atau tidak aman'];
        }
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validasi ekstensi file
        if (!in_array($file_ext, $allowed_types)) {
            return ['success' => false, 'message' => 'Format file tidak diizinkan! Hanya: ' . implode(', ', $allowed_types)];
        }
        
        // Validasi ukuran file
        if ($file_size > $max_size) {
            return ['success' => false, 'message' => 'Ukuran file terlalu besar! Maksimal ' . ($max_size / 1024 / 1024) . 'MB'];
        }
        
        // Validasi apakah benar-benar file gambar
        $imageinfo = getimagesize($file_tmp);
        if ($imageinfo === false) {
            return ['success' => false, 'message' => 'File yang diupload bukan gambar yang valid'];
        }
        
        // Generate nama file unik
        $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $new_filename;
        
        // Cek apakah folder dapat ditulis
        if (!is_writable($upload_dir)) {
            return ['success' => false, 'message' => 'Folder uploads tidak dapat ditulis. Periksa permission folder.'];
        }
        
        if (move_uploaded_file($file_tmp, $destination)) {
            // Verifikasi file berhasil dipindahkan
            if (file_exists($destination)) {
                return ['success' => true, 'message' => 'Foto berhasil diupload: ' . $file_name];
            } else {
                return ['success' => false, 'message' => 'File tidak dapat disimpan'];
            }
        } else {
            return ['success' => false, 'message' => 'Gagal memindahkan file ke folder tujuan'];
        }
        
    } catch (Exception $e) {
        error_log("Upload error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()];
    }
}

// Jika request adalah AJAX untuk upload
if (isset($_POST['ajax_upload']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $result = handleUpload($upload_dir, $allowed_types, $max_size);
        echo json_encode($result);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
        exit;
    }
}

// Jika request adalah untuk mendapatkan daftar foto
if (isset($_GET['action']) && $_GET['action'] === 'get_photos') {
    try {
        $uploaded_photos = getUploadedPhotos($upload_dir, $allowed_types);
        echo json_encode(['success' => true, 'photos' => $uploaded_photos]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil daftar foto']);
    }
    exit;
}

// Jika bukan AJAX request dan method POST (fallback untuk form biasa)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_upload'])) {
    $result = handleUpload($upload_dir, $allowed_types, $max_size);
    
    // Simpan pesan dalam session untuk ditampilkan setelah redirect
    $_SESSION['upload_message'] = $result;
    
    // Redirect untuk mencegah resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
