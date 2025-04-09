<?php
// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Kiểm tra xem có yêu cầu tìm kiếm không
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchTerm = "%$searchTerm%";

    // Truy vấn tìm kiếm chủ đề
    $query = "SELECT id, title, content, created_at FROM threads WHERE title LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $searchTerm);
} else {
    // Truy vấn tất cả chủ đề nếu không có yêu cầu tìm kiếm
    $query = "SELECT id, title, content, created_at FROM threads ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chủ đề</title>
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

        footer {
            text-align: center;
            padding: 20px;
            background-color: #007bff;
            color: white;
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

        input[type="text"] {
            width: 70%;
            padding: 10px;
            margin: 20px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
        }

        .search-form {
            display: flex;
            gap: 10px;
        }

        .btn-edit, .btn-delete {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            margin: 0 5px;
        }

        .btn-edit {
            background-color: #ffc107;
        }

        .btn-delete {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
<div class="main-content">
    <h1>Quản Lý Chủ Đề</h1>

    <div class="actions-bar">
        <a href="add_thread.php" class="btn-primary">+ Thêm Chủ Đề Mới</a>
        <!-- Form tìm kiếm -->
        <form method="GET" action="manage_threads.php" class="search-form">
            <input type="text" name="search" placeholder="Tìm kiếm theo tên chủ đề" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <input type="submit" value="Tìm kiếm">
        </form>
    </div>

    <!-- Hiển thị danh sách chủ đề -->
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th width="25%">Tiêu đề</th>
            <th width="40%">Nội dung</th>
            <th>Ngày tạo</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['title']); ?></td>
                <td><?php echo htmlspecialchars(substr($row['content'], 0, 50)) . '...'; ?></td>
                <td><?php echo $row['created_at']; ?></td>
                <td>
                    <!-- <a href="add_thread.php?id=<?php echo $row['id']; ?>" class="btn-add">Thêm</a> -->
                    <a href="edit_thread.php?id=<?php echo $row['id']; ?>" class="btn-edit">Sửa</a>
                    <a href="delete_thread.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa chủ đề này không?');">Xóa</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php
    // Thông báo nếu không tìm thấy kết quả nào
    if ($result->num_rows == 0) {
        echo "<p>Không tìm thấy chủ đề nào.</p>";
    }
    ?>
</div>

<footer>
    <p>&copy; 2024 Lê Chính Đại Diễn Đàn Câu hỏi và Trả lời cho Sinh viên Học viện Hành chính Quốc gia. Tất cả quyền được bảo lưu.</p>
</footer>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
