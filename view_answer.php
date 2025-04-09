<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$question = null;
$answers = null;

if (isset($_GET['question_id'])) {
    $question_id = intval($_GET['question_id']);
    
    // Fetch question details
    $question_query = "SELECT q.*, u.username 
                      FROM questions q 
                      JOIN users u ON q.user_id = u.id 
                      WHERE q.id = ?";
    $stmt = $conn->prepare($question_query);
    $stmt->bind_param('i', $question_id);
    $stmt->execute();
    $question = $stmt->get_result()->fetch_assoc();

    if ($question) {
        // Fetch answers if question exists
        $answers_query = "SELECT a.*, u.username 
                         FROM answers a 
                         JOIN users u ON a.user_id = u.id 
                         WHERE a.question_id = ?
                         ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($answers_query);
        $stmt->bind_param('i', $question_id);
        $stmt->execute();
        $answers = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Câu Trả Lời</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .main-content {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .question-section {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .answer-container {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 5px;
        }
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            margin: 5px;
            display: inline-block;
            cursor: pointer;
        }
        .btn-edit { background: #ffc107; color: black; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-back { background: #007bff; color: white; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="main-content">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <a href="manage_questions.php" class="btn btn-back">Quay lại</a>

        <?php if ($question): ?>
            <div class="question-section">
                <h2><?php echo htmlspecialchars($question['title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($question['content'])); ?></p>
                <small>Đăng bởi: <?php echo htmlspecialchars($question['username']); ?></small>
            </div>

            <h3>Câu Trả Lời (<?php echo $answers ? $answers->num_rows : 0; ?>)</h3>

            <?php if ($answers && $answers->num_rows > 0): ?>
                <?php while ($answer = $answers->fetch_assoc()): ?>
                    <div class="answer-container">
                        <div style="margin-bottom: 10px;">
                            <strong>Trả lời bởi:</strong> <?php echo htmlspecialchars($answer['username']); ?>
                            <span style="float: right;"><?php echo date('d/m/Y H:i', strtotime($answer['created_at'])); ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($answer['content'])); ?></p>
                        <div style="text-align: right;">
                            <a href="edit_answer.php?id=<?php echo $answer['id']; ?>&question_id=<?php echo $question_id; ?>" 
                               class="btn btn-edit">Sửa</a>
                            <a href="delete_answer.php?answer_id=<?php echo $answer['id']; ?>&question_id=<?php echo $question_id; ?>" 
                               class="btn btn-delete" 
                               onclick="return confirm('Bạn có chắc chắn muốn xóa câu trả lời này?')">Xóa</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Chưa có câu trả lời nào.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="message error">Không tìm thấy câu hỏi</div>
        <?php endif; ?>
    </div>
</body>
</html>
