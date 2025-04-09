<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $url = trim($_POST['url']);
    $position = $_POST['position'];
    $is_logged_in = isset($_POST['is_logged_in']) ? 1 : 0;

    if (empty($title) || empty($url)) {
        $error = "Vui lòng điền đầy đủ thông tin!";
    } else {
        $query = "INSERT INTO header_links (title, url, position, is_logged_in, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ssii', $title, $url, $position, $is_logged_in);

        if ($stmt->execute()) {
            $success = "Thêm liên kết thành công!";
            header("refresh:1;url=manage_header_footer.php");
        } else {
            $error = "Có lỗi xảy ra: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm Header Link</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .form-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-danger { background-color: #f8d7da; color: #721c24; }
        .alert-success { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1><i class="fas fa-link"></i> Thêm Header Link</h1>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label for="url">URL:</label>
                <input type="text" name="url" required>
            </div>
            <div class="form-group">
                <label for="position">Position:</label>
                <input type="number" name="position" required>
            </div>
            <div class="form-group">
                <label for="is_logged_in">Show when logged in only:</label>
                <input type="checkbox" name="is_logged_in">
            </div>
            <button type="submit">Add Header Link</button>
        </form>
    </div>
</body>
</html>
