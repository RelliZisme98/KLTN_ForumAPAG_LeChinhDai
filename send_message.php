<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate input
if (empty($message) || $receiver_id <= 0) {
    die(json_encode(['error' => 'Invalid input']));
}

try {
    // Kiểm tra xem người nhận có tồn tại không
    $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $check_user->bind_param('i', $receiver_id);
    $check_user->execute();
    if ($check_user->get_result()->num_rows === 0) {
        throw new Exception('Receiver not found');
    }

    // Kiểm tra mối quan hệ bạn bè
    $check_friend = $conn->prepare(
        "SELECT status_add FROM friend WHERE 
        (user_id = ? AND friend_id = ?) OR 
        (user_id = ? AND friend_id = ?) AND 
        status_add = 'accepted'"
    );
    $check_friend->bind_param('iiii', $user_id, $receiver_id, $receiver_id, $user_id);
    $check_friend->execute();
    if ($check_friend->get_result()->num_rows === 0) {
        throw new Exception('Not friends');
    }

    // Insert tin nhắn mới
    $stmt = $conn->prepare(
        "INSERT INTO messages (sender_id, receiver_id, message, status, created_at) 
         VALUES (?, ?, ?, 'sent', NOW())"
    );
    $stmt->bind_param('iis', $user_id, $receiver_id, $message);

    if ($stmt->execute()) {
        $message_id = $stmt->insert_id;
        $time = date('H:i'); // Đổi định dạng thời gian ngắn gọn hơn
        
    // Trả về HTML trực tiếp thay vì JSON
        echo "<div class='my-message' data-message-id='$message_id'>
                <p>" . htmlspecialchars($message) . "</p>
                <div class='message-info'>
                    <span class='message-time'>$time</span>
                    <span class='message-status'>Đã gửi</span>
                </div>
              </div>";
    } else {
        echo "Lỗi: " . $stmt->error;
    }

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($check_user)) $check_user->close();
    if (isset($check_friend)) $check_friend->close();
    $conn->close();
}
?>