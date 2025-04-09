<?php
// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra nếu có yêu cầu tạo chủ đề mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    $errors = [];
    if (empty($title)) {
        $errors[] = "Tiêu đề không được để trống";
    }
    if (empty($content)) {
        $errors[] = "Nội dung không được để trống";
    }

    if (empty($errors)) {
        $insertQuery = "INSERT INTO threads (title, content, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param('ss', $title, $content);

        if ($stmt->execute()) {
            header("Location: manage_threads.php?success=1");
            exit;
        } else {
            $errors[] = "Lỗi khi thêm chủ đề: " . $conn->error;
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Chủ đề Mới</title>
    <link rel="stylesheet" href="style.css">
</head>
<style>
    /* Đặt các thiết lập cơ bản cho body */
body {
    font-family: Arial, sans-serif;
    background-color: #f0f0f0;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Đặt kiểu cho phần chính */
.main-content {
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    width: 400px;
    text-align: center;
}

/* Tiêu đề */
.main-content h1 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #333;
}

/* Các nhãn (label) */
form label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    text-align: left;
    color: #555;
}

/* Ô nhập tiêu đề */
form input[type="text"], 
form textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
    font-size: 16px;
}

/* Khu vực nội dung */
form textarea {
    height: 150px;
    resize: none;
}

/* Nút submit */
form input[type="submit"] {
    background-color: #28a745;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
}

form input[type="submit"]:hover {
    background-color: #218838;
}

/* Đáp ứng với màn hình nhỏ */
@media (max-width: 500px) {
    .main-content {
        width: 100%;
        padding: 15px;
        box-sizing: border-box;
    }
}

.error-messages {
    background-color: #ffe6e6;
    border: 1px solid #ff8080;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.button-group {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    text-decoration: none;
}
</style>
<body>
<div class="main-content">
    <h1>Thêm Chủ đề Mới</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <label for="title">Tiêu đề:</label>
        <input type="text" name="title" required>

        <label for="content">Nội dung:</label>
        <textarea name="content" required></textarea>

        <div class="button-group">
            <a href="manage_threads.php" class="btn-secondary">Hủy</a>
            <input type="submit" value="Thêm Chủ đề">
        </div>
    </form>
</div>
</body>
</html>
