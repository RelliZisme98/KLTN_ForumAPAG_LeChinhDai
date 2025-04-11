<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Kết nối thất bại: ' . $conn->connect_error]));
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id']; // Người nhận (người từ chối)
$sender_id = $_POST['sender_id'] ?? null;
$notification_id = $_POST['notification_id'] ?? null;

if ($sender_id && $notification_id) {
    // Xóa yêu cầu kết bạn
    $sql = "DELETE FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $sender_id, $user_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        // Đánh dấu thông báo là đã đọc hoặc xóa nó
        $sql_notif = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("ii", $notification_id, $user_id);
        $stmt_notif->execute();
        
        echo json_encode(['success' => true]);
        $stmt_notif->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu để từ chối']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$conn->close();
?>