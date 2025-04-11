<?php
session_start();

// Kiểm tra kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;

// Kiểm tra voice message
if (isset($_FILES['voice_message']) && $_FILES['voice_message']['error'] == 0) {
    // Đặt tên file âm thanh
    $file_name = uniqid() . '.ogg';
    $upload_dir = 'uploads/voice_messages/';
    $upload_path = $upload_dir . $file_name;

    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            die("Không thể tạo thư mục upload.");
        }
    }

    // Di chuyển file từ tạm thời đến thư mục lưu trữ
    if (move_uploaded_file($_FILES['voice_message']['tmp_name'], $upload_path)) {
        // Lưu tin nhắn voice vào database, không yêu cầu nội dung text
        $sql = "INSERT INTO messages (sender_id, receiver_id, status, created_at, file_audio) 
                VALUES (?, ?, 'sent', NOW(), ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iis', $user_id, $receiver_id, $upload_path);

        if ($stmt->execute()) {
            // Trả về HTML cho tin nhắn voice
            echo "<div class='my-message'>
                    <audio controls>
                        <source src='$upload_path' type='audio/ogg'>
                        Your browser does not support the audio element.
                    </audio>
                    <span class='message-time'>Vừa gửi</span>
                    <span class='message-status'>Đã gửi</span>
                </div>";
        } else {
            echo "Lỗi khi lưu tin nhắn: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Lỗi khi tải file lên.";
    }
} else {
    echo "Không nhận được file voice message.";
}

$conn->close();
?>
