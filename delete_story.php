<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$story_id = $data['story_id'] ?? null;

if (!$story_id) {
    echo json_encode(['success' => false, 'message' => 'Story ID is required']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Verify that the story belongs to the current user
$user_id = $_SESSION['user_id'];
$sql = "DELETE FROM stories WHERE story_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $story_id, $user_id);

if ($stmt->execute()) {
    // Also delete the story image if it exists
    $image_sql = "SELECT image_url FROM stories WHERE story_id = ?";
    $image_stmt = $conn->prepare($image_sql);
    $image_stmt->bind_param('i', $story_id);
    $image_stmt->execute();
    $result = $image_stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['image_url'])) {
            $image_path = 'uploads/stories/' . $row['image_url'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete story']);
}

$stmt->close();
$conn->close();
