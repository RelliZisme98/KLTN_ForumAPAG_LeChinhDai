<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$call_type = $_POST['call_type'];
$status = $_POST['status'];
$duration = isset($_POST['duration']) ? $_POST['duration'] : null;

// Create call message based on status
$message = '';
if ($status === 'missed') {
    $message = $call_type === 'video' ? '📹 Cuộc gọi video nhỡ' : '📞 Cuộc gọi thoại nhỡ';
} else if ($status === 'ended') {
    $message = ($call_type === 'video' ? '📹 Cuộc gọi video' : '📞 Cuộc gọi thoại') . 
               " - Thời gian: $duration";
}

// Insert message into database
$sql = "INSERT INTO messages (sender_id, receiver_id, content, message_type, created_at) 
        VALUES (?, ?, ?, 'call', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    // Format message for display
    $current_time = date('Y-m-d H:i:s');
    
    echo "<div class='message-container'>
            <div class='my-message'>
                <p>$message</p>
                <div class='message-info'>
                    <span class='message-time'>$current_time</span>
                    <span class='message-status'>✓</span>
                </div>
            </div>
          </div>";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
