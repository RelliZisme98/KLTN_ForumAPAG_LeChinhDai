<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$follow_id = $data['user_id'] ?? 0;

// Kiểm tra xem đã follow chưa
$sql = "SELECT * FROM follower WHERE user_id = ? AND follower_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $follow_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Nếu đã follow thì unfollow
    $sql = "DELETE FROM follower WHERE user_id = ? AND follower_id = ?";
} else {
    // Nếu chưa follow thì follow
    $sql = "INSERT INTO follower (user_id, follower_id) VALUES (?, ?)";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $user_id, $follow_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
