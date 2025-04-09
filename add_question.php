<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $thread_id = $_POST['thread_id'];
    $user_id = $_POST['user_id'];
    
    $query = "INSERT INTO questions (title, content, thread_id, user_id, created_at, status) 
              VALUES (?, ?, ?, ?, NOW(), 0)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssii', $title, $content, $thread_id, $user_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Thêm câu hỏi thành công!";
        header('Location: manage_questions.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Thêm Câu Hỏi Mới</title>
    <style>
        .form-container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        textarea { width: 100%; min-height: 150px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Thêm Câu Hỏi Mới</h2>
        <form method="POST">
            <div class="form-group">
                <label>Tiêu đề:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Nội dung:</label>
                <textarea name="content" required></textarea>
            </div>
            <div class="form-group">
                <label>Chủ đề:</label>
                <select name="thread_id" required>
                    <?php
                    $threads = $conn->query("SELECT * FROM threads");
                    while ($thread = $threads->fetch_assoc()) {
                        echo "<option value='{$thread['id']}'>{$thread['name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Người dùng:</label>
                <select name="user_id" required>
                    <?php
                    $users = $conn->query("SELECT * FROM users");
                    while ($user = $users->fetch_assoc()) {
                        echo "<option value='{$user['id']}'>{$user['username']}</option>";
                    }
                    ?>
                </select>
            </div>
            <button type="submit">Thêm câu hỏi</button>
        </form>
    </div>
</body>
</html>
