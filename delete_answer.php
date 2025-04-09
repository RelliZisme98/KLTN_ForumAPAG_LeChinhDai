<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

if (isset($_GET['answer_id']) || isset($_GET['id'])) {
    $id = isset($_GET['answer_id']) ? $_GET['answer_id'] : $_GET['id'];
    $type = isset($_GET['answer_id']) ? 'answer' : 'question';
    
    if ($type === 'answer') {
        $query = "DELETE FROM answers WHERE id = ?";
        $redirect = "view_answer.php?question_id=" . $_GET['question_id'];
        $success_message = "Câu trả lời đã được xóa thành công";
    } else {
        $query = "DELETE FROM questions WHERE id = ?";
        $redirect = "manage_questions.php";
        $success_message = "Câu hỏi đã được xóa thành công";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['message'] = $success_message;
    } else {
        $_SESSION['error'] = "Lỗi khi xóa: " . $conn->error;
    }

    header('Location: ' . $redirect);
    exit;
} else {
    $_SESSION['error'] = "Dữ liệu không hợp lệ";
    header('Location: manage_questions.php');
    exit;
}
?>
