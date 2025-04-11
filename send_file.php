<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập");
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

// Kiểm tra file và receiver_id
if (!$receiver_id || !isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    die("Thiếu thông tin cần thiết");
}

$file = $_FILES['file'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];
$file_size = $file['size'];

// Kiểm tra kích thước
if ($file_size > 10 * 1024 * 1024) {
    die("File không được vượt quá 10MB");
}

// Tạo tên file an toàn
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
$new_name = uniqid() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $file_name);
$upload_dir = "uploads/files/";
$file_path = $upload_dir . $new_name;

// Tạo thư mục nếu chưa có
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Upload file
if (!move_uploaded_file($file_tmp, $file_path)) {
    die("Không thể upload file");
}

// Lưu vào database
$sql = "INSERT INTO messages (sender_id, receiver_id, message, status, created_at, file_url) 
        VALUES (?, ?, '', 'sent', NOW(), ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $user_id, $receiver_id, $file_path);

if (!$stmt->execute()) {
    unlink($file_path); // Xóa file nếu không lưu được DB
    die("Lỗi khi lưu tin nhắn");
}

// Trả về HTML
$size_mb = number_format($file_size / 1024 / 1024, 2);
$icon = getFileIcon($file_ext);
$time = date('H:i');

echo "<div class='my-message'>
        <div class='file-attachment'>
            <i class='fa {$icon}'></i>
            <div class='file-info'>
                <a href='{$file_path}' target='_blank' download>".htmlspecialchars($file_name)."</a>
                <small>{$size_mb} MB</small>
            </div>
        </div>
        <div class='message-info'>
            <span class='message-time'>{$time}</span>
            <span class='message-status'>Đã gửi</span>
        </div>
    </div>";

function getFileIcon($ext) {
    $icons = [
        'pdf' => 'fa-file-pdf-o',
        'doc' => 'fa-file-word-o', 
        'docx' => 'fa-file-word-o',
        'xls' => 'fa-file-excel-o',
        'xlsx' => 'fa-file-excel-o',
        'txt' => 'fa-file-text-o',
        'zip' => 'fa-file-archive-o',
        'rar' => 'fa-file-archive-o'
    ];
    return isset($icons[$ext]) ? $icons[$ext] : 'fa-file-o';
}

$stmt->close();
$conn->close();
?>
