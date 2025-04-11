<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
$user_id = $_SESSION['user_id'];

// Lấy stories của người dùng hiện tại
$sql = "SELECT s.*, u.username, u.profile_picture 
        FROM stories s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.user_id = ? 
        AND s.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY s.created_at DESC";

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
        'background_color' => $row['background_color'] ?? '#3b5998',
        'font_style' => $row['font_style'] ?? 'Arial',
        'username' => $row['username'],
        'profile_picture' => $row['profile_picture'] ?? 'default-avatar.jpg',
        'created_at' => $row['created_at']
    ];
}

echo json_encode(['stories' => $stories]);
$stmt->close();
$conn->close();