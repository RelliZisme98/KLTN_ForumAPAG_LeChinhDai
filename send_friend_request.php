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

$user_id = $_SESSION['user_id']; // Người gửi yêu cầu
$receiver_id = $_POST['receiver_id'] ?? null;

if ($receiver_id && $receiver_id != $user_id && isset($_POST['send_request'])) {
    // Kiểm tra xem đã gửi yêu cầu chưa
    $sql_check = "SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $user_id, $receiver_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows == 0) {
        // Thêm yêu cầu kết bạn
        $sql = "INSERT INTO friend_requests (sender_id, receiver_id, status, created_at) VALUES (?, ?, 'pending', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $receiver_id);

        if ($stmt->execute()) {
            // Thêm thông báo cho người nhận
            $sql_notif = "INSERT INTO notifications (user_id, content, created_at, is_read) 
                          VALUES (?, ?, NOW(), 0)";
            $content = "Bạn nhận được yêu cầu kết bạn từ " . $_SESSION['username'];
            $stmt_notif = $conn->prepare($sql_notif);
            $stmt_notif->bind_param("is", $receiver_id, $content);
            $stmt_notif->execute();

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể gửi yêu cầu']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu đã được gửi trước đó']);
    }
    $stmt_check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
}

$conn->close();
?>