<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Vui lòng đăng nhập']));
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die(json_encode(['error' => 'Lỗi kết nối database']));
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

if (!$receiver_id) {
    die(json_encode(['error' => 'Thiếu thông tin người nhận']));
}

if (!isset($_FILES['image'])) {
    die(json_encode(['error' => 'Không tìm thấy file ảnh']));
}

$file = $_FILES['image'];
if ($file['error'] !== 0) {
    die(json_encode(['error' => 'Lỗi upload: ' . $file['error']]));
}

// Kiểm tra mime type thực tế của file
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($mime_type, $allowed_types)) {
    die(json_encode(['error' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)']));
}

// Kiểm tra kích thước file (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    die(json_encode(['error' => 'File không được vượt quá 5MB']));
}

// Tạo tên file mới an toàn
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$new_name = uniqid() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$upload_dir = "uploads/images/";
$file_path = $upload_dir . $new_name;

// Tạo thư mục nếu chưa tồn tại
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        die(json_encode(['error' => 'Không thể tạo thư mục upload']));
    }
}

// Di chuyển file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    die(json_encode(['error' => 'Không thể lưu file']));
}

try {
    // Lưu vào database
    $sql = "INSERT INTO messages (sender_id, receiver_id, message, status, created_at, file_url) 
            VALUES (?, ?, '', 'sent', NOW(), ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iis', $user_id, $receiver_id, $file_path);
    
    if (!$stmt->execute()) {
        unlink($file_path); // Xóa file nếu không lưu được DB
        throw new Exception('Lỗi khi lưu vào database');
    }
    
    $message_id = $stmt->insert_id;
    $time = date('H:i');
    
    // Trả về HTML
    echo "<div class='my-message' data-message-id='$message_id'>
            <img src='$file_path' 
                 class='message-image' 
                 onclick=\"openImageModal('$file_path')\" 
                 alt='Sent image'
                 loading='lazy'>
            <div class='message-info'>
                <span class='message-time'>$time</span>
                <span class='message-status'>Đã gửi</span>
            </div>
          </div>";
    
} catch (Exception $e) {
    unlink($file_path); // Xóa file nếu có lỗi
    die(json_encode(['error' => $e->getMessage()]));
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>
