<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$request_id = $_POST['request_id'] ?? null;
$action = $_POST['action'] ?? null;

if ($request_id && in_array($action, ['accept', 'delete'])) {
    if ($action === 'accept') {
        // Get sender_id before updating request
        $sql_get_sender = "SELECT sender_id FROM friend_requests WHERE id = ?";
        $stmt_sender = $conn->prepare($sql_get_sender);
        $stmt_sender->bind_param("i", $request_id);
        $stmt_sender->execute();
        $sender_result = $stmt_sender->get_result();
        $sender_id = $sender_result->fetch_assoc()['sender_id'];

        // Cập nhật trạng thái yêu cầu thành "accepted"
        $sql_update = "UPDATE friend_requests SET status = 'accepted' WHERE id = ? AND receiver_id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $request_id, $user_id);
        $stmt_update->execute();

        // Thêm mối quan hệ bạn bè vào bảng friends
        $sql_friend = "INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)";
        $stmt_friend = $conn->prepare($sql_friend);
        $stmt_friend->bind_param("iiii", $user_id, $sender_id, $sender_id, $user_id);
        $stmt_friend->execute();

        // Thêm thông báo cho người gửi yêu cầu
        $sql_notif = "INSERT INTO notifications (user_id, content, created_at, is_read) VALUES (?, ?, NOW(), 0)";
        $notif_content = $_SESSION['username'] . " đã chấp nhận lời mời kết bạn của bạn";
        $stmt_notif = $conn->prepare($sql_notif);
        $stmt_notif->bind_param("is", $sender_id, $notif_content);
        $stmt_notif->execute();

        // Return updated status with the response
        $response = [
            'success' => true,
            'message' => 'Đã chấp nhận lời mời kết bạn',
            'newStatus' => 'accepted'
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } elseif ($action === 'delete') {
        // Xóa yêu cầu kết bạn
        $sql_delete = "DELETE FROM friend_requests WHERE id = ? AND receiver_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("ii", $request_id, $user_id);
        $stmt_delete->execute();
    }
    header("Location: index.php"); // Quay lại trang chính sau khi xử lý
    exit;
}

$conn->close();
?>