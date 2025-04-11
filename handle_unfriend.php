<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Kết nối database
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối database']);
    exit;
}

$user_id = $_SESSION['user_id'];
$friend_id = isset($_POST['friend_id']) ? (int)$_POST['friend_id'] : 0;

if (!$friend_id) {
    echo json_encode(['success' => false, 'message' => 'ID bạn bè không hợp lệ']);
    exit;
}

// Xóa mối quan hệ bạn bè
$sql = "DELETE FROM friend WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Đã hủy kết bạn thành công']);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi hủy kết bạn']);
}

$conn->close();
