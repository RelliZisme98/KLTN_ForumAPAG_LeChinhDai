<?php
session_start(); // Bắt đầu phiên làm việc
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra ID câu hỏi
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Lấy thông tin câu hỏi
    $query = "SELECT * FROM questions WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $question = $result->fetch_assoc();

    // Kiểm tra xem có kết quả không
    if (!$question) {
        echo "Không tìm thấy câu hỏi!";
        exit;
    }
} else {
    echo "ID không hợp lệ!";
    exit;
}

// Xử lý cập nhật thông tin câu hỏi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $thread_id = $_POST['thread_id'];
    $status = $_POST['status']; // Nếu bạn có trường trạng thái

    $update_query = "UPDATE questions SET title = ?, content = ?, thread_id = ?, status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ssisi', $title, $content, $thread_id, $status, $id);

    if ($update_stmt->execute()) {
        echo "Cập nhật câu hỏi thành công!";
        header('Location: manage_questions.php');
        exit;
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Câu Hỏi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .main-content {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }

        input[type="text"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        textarea {
            min-height: 200px;
            resize: vertical;
        }

        select {
            background-color: white;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button[type="submit"] {
            background-color: #28a745;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            flex: 1;
        }

        .btn-cancel {
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
            flex: 1;
        }

        button[type="submit"]:hover,
        .btn-cancel:hover {
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
    <div class="main-content">
        <h1>Sửa Câu Hỏi</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="title">Tiêu Đề:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($question['title']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="content">Nội Dung:</label>
                <textarea id="content" name="content" required><?php echo htmlspecialchars($question['content']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="thread_id">Chủ Đề:</label>
                <select id="thread_id" name="thread_id">
                    <?php
                    $threads_query = "SELECT id, name FROM threads";
                    $threads_result = $conn->query($threads_query);
                    while ($thread = $threads_result->fetch_assoc()):
                    ?>
                        <option value="<?php echo $thread['id']; ?>" <?php if ($thread['id'] == $question['thread_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($thread['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Trạng Thái:</label>
                <select id="status" name="status">
                    <option value="0" <?php if ($question['status'] == 0) echo 'selected'; ?>>Chưa duyệt</option>
                    <option value="1" <?php if ($question['status'] == 1) echo 'selected'; ?>>Đã duyệt</option>
                    <option value="2" <?php if ($question['status'] == 2) echo 'selected'; ?>>Từ chối</option>
                </select>
            </div>

            <div class="button-group">
                <button type="submit">Cập Nhật</button>
                <a href="manage_questions.php" class="btn-cancel">Hủy</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php include 'footer.php'; ?>
