<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if (isset($_GET['id'])) {
    $answer_id = $_GET['id'];
    $question_id = $_GET['question_id'];

    // Fetch answer details
    $query = "SELECT * FROM answers WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $answer_id);
    $stmt->execute();
    $answer = $stmt->get_result()->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $content = $_POST['content'];
        $update_query = "UPDATE answers SET content = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('si', $content, $answer_id);

        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Cập nhật câu trả lời thành công!";
            header("Location: view_answer.php?question_id=" . $question_id);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Sửa Câu Trả Lời</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .form-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            min-height: 200px;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            font-size: 14px;
            line-height: 1.5;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
        }
        .btn-submit {
            background: #28a745;
            color: white;
            flex: 1;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
            flex: 1;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Sửa Câu Trả Lời</h2>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="content">Nội dung câu trả lời:</label>
                <textarea name="content" id="content" required><?php echo htmlspecialchars($answer['content']); ?></textarea>
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-submit">Cập nhật</button>
                <a href="view_answer.php?question_id=<?php echo $question_id; ?>" class="btn btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</body>
</html>
