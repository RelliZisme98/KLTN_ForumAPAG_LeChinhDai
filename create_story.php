<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/stories/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    if ($_POST['type'] === 'text') {
        $content = $_POST['content_text'];
        $background = $_POST['background_color'] ?? '#3b5998';
        $font = $_POST['font_style'] ?? 'Arial';
        
        if (empty($content)) {
            throw new Exception('Story text cannot be empty');
        }
        
        $sql = "INSERT INTO stories (user_id, content_text, background_color, font_style, created_at, expires_at) 
                VALUES (?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $_SESSION['user_id'], $content, $background, $font);
        
    } else if ($_POST['type'] === 'image') {
        if (!isset($_FILES['image'])) {
            throw new Exception('No image uploaded');
        }
        
        $image = $_FILES['image'];
        $image_text = $_POST['image_text'] ?? '';
        
        if ($image['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Image upload failed with error code: ' . $image['error']);
        }
        
        $image_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", $image['name']);
        $upload_path = $upload_dir . $image_name;
        
        if (!move_uploaded_file($image['tmp_name'], $upload_path)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        $sql = "INSERT INTO stories (user_id, image_url, image_text, created_at, expires_at) 
                VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iss', $_SESSION['user_id'], $image_name, $image_text);
    }

    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    echo json_encode(['success' => true, 'message' => 'Story created successfully']);
    
} catch (Exception $e) {
    error_log('Story creation error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
