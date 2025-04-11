<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$story_id = $data['story_id'] ?? null;
$comment = $data['comment'] ?? null;
$parent_id = $data['parent_id'] ?? null;

if (!$story_id || !$comment) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO story_comments (story_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iisi', $story_id, $user_id, $comment, $parent_id);

$success = $stmt->execute();
echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
