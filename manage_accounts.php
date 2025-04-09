<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra xem có yêu cầu tìm kiếm không
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchTerm = "%$searchTerm%";
    
    // Truy vấn tìm kiếm người dùng
    $query = "SELECT id, username, email FROM users WHERE username LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $searchTerm, $searchTerm);
} else {
    // Truy vấn tất cả người dùng nếu không có yêu cầu tìm kiếm
    $query = "SELECT id, username, email FROM users";
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
    <title>Quản lý Tài khoản</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .main-content {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a73e8;
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: white;
        }
        th {
            background-color: #1a73e8;
            color: white;
            padding: 15px;
            text-align: left;
        }
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .btn-back, .btn-edit, .btn-delete {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
            transition: background-color 0.3s;
        }
        .btn-back {
            background-color: #1a73e8;
            color: white;
        }
        .btn-edit {
            background-color: #34a853;
            color: white;
        }
        .btn-delete {
            background-color: #ea4335;
            color: white;
        }
        input[type="text"] {
            padding: 12px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-right: 10px;
        }
        input[type="submit"] {
            background-color: #1a73e8;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        footer {
            background-color: #1a73e8;
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>
<body>
<div class="main-content">
    <h1>Quản Lý Tài Khoản</h1>
    <a href="index.php" class="btn-back">Quay lại</a> <!-- Dẫn đến trang index.php hoặc trang bạn muốn -->  
    <!-- Form tìm kiếm -->
    <form method="GET" action="manage_accounts.php">
        <input type="text" name="search" placeholder="Tìm kiếm theo tên hoặc email" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <input type="submit" value="Tìm kiếm">
    </form>

    <!-- Hiển thị danh sách người dùng -->
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Tên người dùng</th>
            <th>Email</th>
            <th>Hành động</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo $row['id']; ?>" class="btn-edit">Sửa</a>
                    <a href="delete_user.php?id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?');">Xóa</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php
    // Thông báo nếu không tìm thấy kết quả nào
    if ($result->num_rows == 0) {
        echo "<p>Không tìm thấy người dùng nào.</p>";
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
