<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
$user_id = $_SESSION['user_id'];

// Lấy thông tin user hiện tại
$sql_current = "SELECT id, username, profile_picture FROM users WHERE id = ?";
$stmt_current = $conn->prepare($sql_current);
$stmt_current->bind_param('i', $user_id);
$stmt_current->execute();
$current_user = $stmt_current->get_result()->fetch_assoc();

// Lấy tất cả stories từ 24h qua, ưu tiên stories của mình lên đầu
$sql = "SELECT s.*, u.username, u.profile_picture 
        FROM stories s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY 
            CASE WHEN s.user_id = ? THEN 0 ELSE 1 END,
            s.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$stories = [];
while ($row = $result->fetch_assoc()) {
    $stories[] = [
        'story_id' => $row['story_id'],
        'user_id' => $row['user_id'],
        'content_text' => $row['content_text'],
        'image_url' => $row['image_url'],
        'image_text' => $row['image_text'] ?? '',
        'background_color' => $row['background_color'] ?? '#3b5998',
        'font_style' => $row['font_style'] ?? 'Arial',
        'username' => $row['username'],
        'profile_picture' => !empty($row['profile_picture']) ? $row['profile_picture'] : 'default-avatar.jpg'
    ];
}

echo json_encode([
    'success' => true,
    'stories' => $stories,
    'current_user' => [
        'id' => $current_user['id'],
        'username' => $current_user['username'],
        'profile_picture' => !empty($current_user['profile_picture']) ? $current_user['profile_picture'] : 'default-avatar.jpg'
    ]
]);

$stmt->close();
$conn->close();