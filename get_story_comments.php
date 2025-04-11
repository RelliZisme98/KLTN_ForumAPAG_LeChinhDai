<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$story_id = $_GET['story_id'] ?? null;

if (!$story_id) {
    echo json_encode(['success' => false, 'message' => 'Story ID required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Get main comments
$sql = "SELECT c.*, u.username 
        FROM story_comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.story_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $story_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
while ($comment = $result->fetch_assoc()) {
    // Get replies for each comment
    $reply_sql = "SELECT c.*, u.username 
                  FROM story_comments c
                  JOIN users u ON c.user_id = u.id
                  WHERE c.parent_id = ?
                  ORDER BY c.created_at ASC";
    
    $reply_stmt = $conn->prepare($reply_sql);
    $reply_stmt->bind_param('i', $comment['id']);
    $reply_stmt->execute();
    
    $comment['replies'] = $reply_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $comments[] = $comment;
}

echo json_encode(['success' => true, 'comments' => $comments]);

$stmt->close();
$conn->close();
