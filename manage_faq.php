<?php
session_start(); // Bắt đầu phiên làm việc

// Kiểm tra xem admin đã đăng nhập hay chưa
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối đến cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ledai_forum";
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thêm biến cho thông báo
$message = '';
$message_type = '';

// Xử lý thêm mới FAQ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_faq'])) {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    if (strlen($question) < 10 || strlen($answer) < 10) {
        $message = "Câu hỏi và câu trả lời phải có ít nhất 10 ký tự!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO faqs (question, answer, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ss", $question, $answer);
        if ($stmt->execute()) {
            $message = "Thêm FAQ thành công!";
            $message_type = "success";
        } else {
            $message = "Có lỗi xảy ra: " . $conn->error;
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Xử lý xóa FAQ
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Xóa FAQ thành công!";
        $message_type = "success";
    } else {
        $message = "Có lỗi xảy ra khi xóa!";
        $message_type = "error";
    }
    $stmt->close();
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
if ($search) {
    $search = "%$search%";
    $where = "WHERE question LIKE ? OR answer LIKE ?";
}

// Lấy danh sách FAQ với điều kiện tìm kiếm
$sql = "SELECT * FROM faqs $where ORDER BY created_at DESC";
if ($search) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý FAQ</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #fff;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .search-box {
            margin: 20px 0;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .add-faq-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
        }
        td {
            padding: 15px;
            border-top: 1px solid #dee2e6;
            max-width: 300px; /* Giới hạn độ rộng cột */
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            white-space: nowrap;
        }
        .action-buttons a {
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }
        .action-buttons i {
            margin-right: 4px;
        }
        /* Thêm độ rộng cụ thể cho từng cột */
        table th:nth-child(1), 
        table td:nth-child(1) { width: 5%; } /* ID */
        table th:nth-child(2), 
        table td:nth-child(2) { width: 25%; } /* Câu hỏi */
        table th:nth-child(3), 
        table td:nth-child(3) { width: 40%; } /* Câu trả lời */
        table th:nth-child(4), 
        table td:nth-child(4) { width: 15%; } /* Ngày tạo */
        table th:nth-child(5), 
        table td:nth-child(5) { 
            width: 15%; 
            white-space: nowrap;
        } /* Hành động */
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-question-circle"></i> Quản lý FAQ</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" class="search-input" 
                       placeholder="Tìm kiếm FAQ..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>

        <div class="add-faq-form">
            <h2><i class="fas fa-plus-circle"></i> Thêm FAQ mới</h2>
            <form method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="question">Câu hỏi:</label>
                    <input type="text" id="question" name="question" required minlength="10">
                </div>

                <div class="form-group">
                    <label for="answer">Câu trả lời:</label>
                    <textarea id="answer" name="answer" required minlength="10"></textarea>
                </div>

                <button type="submit" name="add_faq" class="btn btn-primary">
                    <i class="fas fa-save"></i> Thêm FAQ
                </button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Câu hỏi</th>
                    <th>Câu trả lời</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td title="<?php echo htmlspecialchars($row['question']); ?>">
                                <?php echo htmlspecialchars(substr($row['question'], 0, 100)) . (strlen($row['question']) > 100 ? '...' : ''); ?>
                            </td>
                            <td title="<?php echo htmlspecialchars($row['answer']); ?>">
                                <?php echo htmlspecialchars(substr($row['answer'], 0, 150)) . (strlen($row['answer']) > 150 ? '...' : ''); ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_faq.php?id=<?php echo $row['id']; ?>" class="edit-btn">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <a href="manage_faq.php?delete=<?php echo $row['id']; ?>" 
                                       class="delete-btn" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa FAQ này?');">
                                        <i class="fas fa-trash"></i> Xóa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Không tìm thấy FAQ nào</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function validateForm() {
            const question = document.getElementById('question').value.trim();
            const answer = document.getElementById('answer').value.trim();
            
            if (question.length < 10) {
                alert('Câu hỏi phải có ít nhất 10 ký tự!');
                return false;
            }
            if (answer.length < 10) {
                alert('Câu trả lời phải có ít nhất 10 ký tự!');
                return false;
            }
            return true;
        }
    </script>

    <footer>
        © 2024 Your Forum - All Rights Reserved
    </footer>
</body>
</html>

<?php
$conn->close();
?>
