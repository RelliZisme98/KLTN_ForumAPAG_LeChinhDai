<?php
session_start();

// Kiểm tra xác thực người dùng
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);  // Trả về mã lỗi unauthorized
    exit('Unauthorized');
}

// Thiết lập kết nối database
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    http_response_code(500); // Internal server error
    exit("Database connection failed");
}

try {
    // Lấy ID người dùng từ session và ID bạn bè từ tham số GET
    $user_id = (int)$_SESSION['user_id']; 
    $friend_id = isset($_GET['friend_id']) ? (int)$_GET['friend_id'] : 0;

    // Kiểm tra tính hợp lệ của friend_id
    if ($friend_id <= 0) {
        throw new Exception('Invalid friend ID');
    }

    // Xác minh mối quan hệ bạn bè
    $check_friend = "SELECT status_add FROM friend WHERE 
                    (user_id = ? AND friend_id = ?) OR 
                    (user_id = ? AND friend_id = ?) AND 
                    status_add = 'accepted'";
    $stmt_check = $conn->prepare($check_friend);
    $stmt_check->bind_param('iiii', $user_id, $friend_id, $friend_id, $user_id);
    $stmt_check->execute();
    
    // Kiểm tra kết quả truy vấn bạn bè
    if ($stmt_check->get_result()->num_rows === 0) {
        throw new Exception('Not friends');
    }

    // Cập nhật trạng thái tin nhắn thành 'delivered' khi người nhận xem
    $update_status_sql = "UPDATE messages SET status = 'delivered' 
                         WHERE receiver_id = ? AND sender_id = ? AND status = 'sent'";
    $update_stmt = $conn->prepare($update_status_sql);
    $update_stmt->bind_param('ii', $user_id, $friend_id);
    $update_stmt->execute();

    // Truy vấn lấy tin nhắn giữa 2 người dùng
    $sql = "SELECT sender_id, message, file_url, file_audio, created_at, status 
            FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?)
            OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiii', $user_id, $friend_id, $friend_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Xử lý và hiển thị tin nhắn
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        // Làm sạch dữ liệu trước khi hiển thị
        $message = [
            'sender_id' => (int)$row['sender_id'],
            'message' => htmlspecialchars($row['message']),
            'file_url' => $row['file_url'] ? htmlspecialchars($row['file_url']) : null,
            'file_audio' => $row['file_audio'] ? htmlspecialchars($row['file_audio']) : null,
            'created_at' => htmlspecialchars($row['created_at']),
            'status' => htmlspecialchars($row['status'])
        ];
        
        // Hiển thị tin nhắn với định dạng HTML phù hợp
        echo formatMessageHTML($message, $user_id);
    }

    // Đóng tất cả các prepared statements
    $stmt_check->close();
    $update_stmt->close();
    $stmt->close();

} catch (Exception $e) {
    // Xử lý lỗi và trả về response code phù hợp
    http_response_code(400);
    echo "Error: " . $e->getMessage();
} finally {
    // Đảm bảo đóng kết nối database
    $conn->close();
}

/**
 * Hàm định dạng tin nhắn thành HTML
 * @param array $message Mảng chứa thông tin tin nhắn
 * @param int $user_id ID người dùng hiện tại
 * @return string HTML đã được định dạng
 */
function formatMessageHTML($message, $user_id) {
    $isMyMessage = $message['sender_id'] == $user_id;
    $messageClass = $isMyMessage ? 'my-message' : 'friend-message';
    $time = date('H:i', strtotime($message['created_at'])); // Định dạng thời gian ngắn gọn
    
    $html = "<div class='$messageClass'>";
    
    // Phần nội dung tin nhắn
    if ($message['file_audio']) {
        $html .= "<audio controls><source src='{$message['file_audio']}' type='audio/ogg'></audio>";
    } elseif ($message['file_url']) {
        $fileType = getFileType($message['file_url']);
        
        if ($fileType === 'image') {
            $html .= "<img src='{$message['file_url']}' 
                          class='message-image' 
                          onclick='openImageModal(\"{$message['file_url']}\")' 
                          alt='Sent image'
                          loading='lazy'>";
        } else {
            $fileName = basename($message['file_url']);
            $html .= "<div class='file-attachment'>
                        <i class='fa fa-file'></i>
                        <a href='{$message['file_url']}' target='_blank'>" . 
                        htmlspecialchars($fileName) . "</a>
                     </div>";
        }
    } elseif (!empty($message['message'])) {
        $html .= "<p>{$message['message']}</p>";
    }
    
    // Thêm phần thông tin tin nhắn (thời gian và trạng thái)
    $html .= "<div class='message-info'>";
    $html .= "<span class='message-time'>$time</span>";
    
    if ($isMyMessage) {
        $status_label = ($message['status'] == 'seen') ? 'Đã xem' : 
                       (($message['status'] == 'delivered') ? 'Đã nhận' : 'Đã gửi');
        $html .= "<span class='message-status'>$status_label</span>";
    }
    
    $html .= "</div></div>";
    return $html;
}

// Thêm hàm kiểm tra loại file
function getFileType($file_url) {
    $ext = strtolower(pathinfo($file_url, PATHINFO_EXTENSION));
    $image_types = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($ext, $image_types)) {
        return 'image';
    }
    
    return 'file';
}
?>
