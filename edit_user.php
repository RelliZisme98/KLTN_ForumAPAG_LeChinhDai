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

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Lấy dữ liệu người dùng từ cơ sở dữ liệu
    $query = "SELECT username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cập nhật thông tin người dùng
    $newUsername = $_POST['username'];
    $newEmail = $_POST['email'];
    $updateQuery = "UPDATE users SET username = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('ssi', $newUsername, $newEmail, $userId);

    if ($stmt->execute()) {
        echo "Thông tin người dùng đã được cập nhật.";
    } else {
        echo "Lỗi khi cập nhật: " . $conn->error;
    }

    $stmt->close();
    // Điều hướng trở lại trang quản lý tài khoản sau khi cập nhật
    header("Location: manage_accounts.php");
    exit;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Tài Khoản</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
        }
        .main-content {
            width: 600px;
            margin: 50px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1a73e8;
            margin-bottom: 30px;
        }
        .btn-back {
            background-color: #1a73e8;
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #1557b0;
        }
        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #1a73e8;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #1557b0;
        }
    </style>
</head>
<body>
<div class="main-content">
    <h1>Sửa Tài Khoản</h1>
    <a href="manage_accounts.php" class="btn-back">Quay lại</a> <!-- Nút quay lại trang quản lý tài khoản -->
    <form method="POST">
        <label for="username">Tên người dùng:</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

        <label for="email">Email:</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

        <input type="submit" value="Cập nhật">
    </form>
</div>
</body>
</html>
