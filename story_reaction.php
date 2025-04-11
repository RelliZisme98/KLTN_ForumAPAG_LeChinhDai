<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$story_id = $data['story_id'] ?? null;
$reaction_type = $data['reaction_type'] ?? null;

if (!$story_id || !$reaction_type) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
$user_id = $_SESSION['user_id'];

// Insert or update reaction
$sql = "INSERT INTO story_reactions (story_id, user_id, reaction_type) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE reaction_type = VALUES(reaction_type)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iis', $story_id, $user_id, $reaction_type);

$success = $stmt->execute();
echo json_encode(['success' => $success]);

$stmt->close();
$conn->close();
