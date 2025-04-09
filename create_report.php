<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Lấy danh sách users cho dropdown
$users_sql = "SELECT id, username FROM users";
$users_result = $conn->query($users_sql);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $content = $_POST['content'];
    $type = $_POST['type'];
    $reference_id = $_POST['reference_id'];
    $status = $_POST['status'];

    $sql = "INSERT INTO reports (user_id, content, type, reference_id, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issis', $user_id, $content, $type, $reference_id, $status);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Thêm báo cáo thành công!";
        $_SESSION['message_type'] = "success";
        header("Location: manage_reports.php");
        exit();
    } else {
        $error = "Thêm báo cáo thất bại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Báo Cáo Mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Thêm Báo Cáo Mới</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Người Báo Cáo</label>
                                <select name="user_id" class="form-select" required>
                                    <?php while ($user = $users_result->fetch_assoc()): ?>
                                        <option value="<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Loại Báo Cáo</label>
                                <select name="type" class="form-select" required>
                                    <option value="post">Bài viết</option>
                                    <option value="comment">Bình luận</option>
                                    <option value="user">Người dùng</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">ID Tham Chiếu</label>
                                <input type="number" name="reference_id" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Nội Dung</label>
                                <textarea name="content" class="form-control" rows="4" required></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Trạng Thái</label>
                                <select name="status" class="form-select" required>
                                    <option value="pending">Chờ xử lý</option>
                                    <option value="resolved">Đã giải quyết</option>
                                    <option value="rejected">Đã từ chối</option>
                                </select>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Thêm Báo Cáo</button>
                                <a href="manage_reports.php" class="btn btn-secondary">Quay Lại</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
