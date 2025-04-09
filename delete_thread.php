<?php
// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra nếu có ID của chủ đề
if (isset($_GET['id'])) {
    $threadId = $_GET['id'];

    // Kiểm tra xem chủ đề có tồn tại không
    $checkQuery = "SELECT id FROM threads WHERE id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param('i', $threadId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: manage_threads.php?error=not_found");
        exit;
    }

    // Xóa chủ đề
    $deleteQuery = "DELETE FROM threads WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param('i', $threadId);

    if ($stmt->execute()) {
        header("Location: manage_threads.php?deleted=1");
    } else {
        header("Location: manage_threads.php?error=delete_failed");
    }

    $stmt->close();
    exit;
} else {
    header("Location: manage_threads.php");
    exit;
}

$conn->close();
?>
