<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
$story_id = $_GET['id'];
$viewer_id = $_SESSION['user_id'];

// Kiểm tra xem đã xem chưa
$check_sql = "SELECT id FROM story_views WHERE story_id = ? AND viewer_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('ii', $story_id, $viewer_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $sql = "INSERT INTO story_views (story_id, viewer_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $story_id, $viewer_id);
    $success = $stmt->execute();
    echo json_encode(['success' => $success]);
}
