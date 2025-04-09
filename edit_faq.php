<?php
session_start();

// Kiểm tra xem admin đã đăng nhập hay chưa
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thông tin FAQ cần chỉnh sửa
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM faqs WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $faq = $result->fetch_assoc();
    $stmt->close();
}

$message = '';
$message_type = '';

// Cập nhật FAQ sau khi chỉnh sửa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question = trim($_POST['question']);
    $answer = trim($_POST['answer']);
    
    if (strlen($question) < 10 || strlen($answer) < 10) {
        $message = "Câu hỏi và câu trả lời phải có ít nhất 10 ký tự!";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("UPDATE faqs SET question = ?, answer = ? WHERE id = ?");
        $stmt->bind_param("ssi", $question, $answer, $id);
        if ($stmt->execute()) {
            $message = "Cập nhật FAQ thành công!";
            $message_type = "success";
            // Reload FAQ data after update
            $faq['question'] = $question;
            $faq['answer'] = $answer;
        } else {
            $message = "Có lỗi xảy ra khi cập nhật!";
            $message_type = "error";
        }
        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa FAQ</title>
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
            max-width: 800px;
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
        .edit-form {
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Sửa FAQ</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="question">Câu hỏi:</label>
                    <input type="text" id="question" name="question" 
                           value="<?php echo htmlspecialchars($faq['question']); ?>" 
                           required minlength="10">
                </div>

                <div class="form-group">
                    <label for="answer">Câu trả lời:</label>
                    <textarea id="answer" name="answer" required minlength="10"><?php echo htmlspecialchars($faq['answer']); ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <a href="manage_faq.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>
            </form>
        </div>
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
</body>
</html>

<?php
$conn->close();
?>
