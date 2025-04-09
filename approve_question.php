<?php
session_start();
// Kết nối tới cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra xem có dữ liệu POST gửi đến hay không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($id <= 0) {
        die("ID không hợp lệ");
    }

    switch ($action) {
        case 'Duyệt':
            $status = 1;
            $message = "Đã duyệt câu hỏi";
            break;
        case 'Từ Chối':
            $status = 2;
            $message = "Đã từ chối câu hỏi";
            break;
        default:
            die("Hành động không hợp lệ");
    }

    $sql = "UPDATE questions SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $status, $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = $message;
    } else {
        $_SESSION['error'] = "Lỗi: " . $stmt->error;
    }

    header("Location: manage_questions.php");
    exit();
}
?>
