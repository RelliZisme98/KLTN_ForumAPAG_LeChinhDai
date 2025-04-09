<?php
session_start(); // Bắt đầu phiên làm việc
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$query = "SELECT questions.id, questions.title, questions.user_id, questions.created_at, questions.views, questions.status, users.username 
          FROM questions 
          JOIN users ON questions.user_id = users.id 
          ORDER BY questions.created_at DESC";

$result = $conn->query($query);
if (!$result) {
    echo "Lỗi truy vấn: " . $conn->error;
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Diễn Đàn</title>
    <link rel="stylesheet" href="style.css">
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    h1 {
        text-align: center;
        color: #333;
        margin-top: 20px;
    }

    .main-content {
        width: 80%;
        margin: 20px auto;
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 12px;
        text-align: center;
        color: #333;
    }

    th {
        background-color: #007bff;
        color: white;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    .btn-approve, .btn-reject {
        padding: 8px 12px;
        border-radius: 4px;
        text-decoration: none;
        color: white;
        font-size: 14px;
    }

    .btn-approve {
        background-color: green;
    }

    .btn-reject {
        background-color: red;
    }

    .btn-approve:hover, .btn-reject:hover {
        opacity: 0.8;
    }

    /* Footer */
    footer {
        text-align: center;
        padding: 20px;
        background-color: #007bff;
        color: white;
        position: relative;
        bottom: 0;
        width: 100%;
        margin-top: 40px;
        border-top: 1px solid #ddd;
    }

    footer p {
        margin: 0;
        font-size: 14px;
    }
    .btn-back {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .btn-back:hover {
        background-color: #0056b3;
    }
    .btn-view {
        padding: 8px 12px;
        border-radius: 4px;
        text-decoration: none;
        background-color: #007bff;
        color: white;
        font-size: 14px;
        display: inline-block;
        margin-top: 8px;
    }

    .btn-view:hover {
        background-color: #0056b3;
    }

    .filters {
        margin: 20px 0;
        padding: 15px;
        background: #fff;
        border-radius: 5px;
    }
    
    .status-filter {
        padding: 8px 15px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .message {
        padding: 10px;
        margin: 10px 0;
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

    .btn-action {
        padding: 5px 10px;
        margin: 2px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    .btn-edit {
        background: #ffc107;
        color: #000;
    }

    .btn-delete {
        background: #dc3545;
        color: #fff;
    }

    .btn-add {
        background: #28a745;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        border-radius: 4px;
        display: inline-block;
        margin-bottom: 20px;
    }
    .actions {
        margin: 20px 0;
        display: flex;
        gap: 10px;
    }
    </style>
</head>
<body>
<div class="main-content">
    <h1>Quản Lý Câu Hỏi</h1>
    
    <div class="actions">
        <a href="add_question.php" class="btn-action btn-add">Thêm câu hỏi mới</a>
        <a href="view_answer.php" class="btn-action btn-add">Quản lý câu trả lời</a>
        <a href="index.php" class="btn-back">Quay lại</a>
    </div>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="message success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="message error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="filters">
        <form method="GET">
            <select name="status" class="status-filter" onchange="this.form.submit()">
                <option value="">Tất cả trạng thái</option>
                <option value="0" <?php echo isset($_GET['status']) && $_GET['status'] == '0' ? 'selected' : ''; ?>>Chưa duyệt</option>
                <option value="1" <?php echo isset($_GET['status']) && $_GET['status'] == '1' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="2" <?php echo isset($_GET['status']) && $_GET['status'] == '2' ? 'selected' : ''; ?>>Từ chối</option>
            </select>
        </form>
    </div>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Tiêu Đề</th>
            <th>Người Dùng</th>
            <th>Tên Người Dùng</th>
            <th>Ngày Tạo</th>
            <th>Lượt Xem</th>
            <th>Trạng Thái</th>
            <th>Hành Động</th>
        </tr>
        </thead>
        <tbody>
        <?php 
        $status_filter = isset($_GET['status']) ? " WHERE questions.status = " . intval($_GET['status']) : "";
        $query = "SELECT questions.*, users.username 
                 FROM questions 
                 JOIN users ON questions.user_id = users.id 
                 $status_filter 
                 ORDER BY questions.created_at DESC";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()): 
        ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></td>
                <td><?php echo $row['views']; ?></td>
                <td>
                    <?php
                    if ($row['status'] == 0) {
                        echo "Chưa duyệt";
                    } elseif ($row['status'] == 1) {
                        echo "Đã duyệt";
                    } else {
                        echo "Từ chối";
                    }
                    ?>
                </td>
                <td>
                    <form method="POST" action="approve_question.php" style="display: inline;">
                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="action" value="Duyệt" class="btn-action btn-approve">Duyệt</button>
                        <button type="submit" name="action" value="Từ Chối" class="btn-action btn-reject">Từ Chối</button>
                    </form>
                    <a href="edit_question.php?id=<?php echo $row['id']; ?>" class="btn-action btn-edit">Sửa</a>
                    <a href="delete_answer.php?id=<?php echo $row['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                    <a href="view_answer.php?question_id=<?php echo $row['id']; ?>" class="btn-view">Xem câu trả lời</a> <!-- Nút Xem câu trả lời -->
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
<footer>
    <p>&copy; 2024 Lê Chính Đại Diễn Đàn Câu hỏi và Trả lời cho Sinh viên Học viện Hành chính Quốc gia. Tất cả quyền được bảo lưu.</p>
</footer>
</body>
</html>