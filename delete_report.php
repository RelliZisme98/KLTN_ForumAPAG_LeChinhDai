<?php
session_start(); // Bắt đầu phiên làm việc
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra nếu người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Kiểm tra nếu có ID trong URL
if (!isset($_GET['id'])) {
    header("Location: manage_reports.php");
    exit();
}

$id = intval($_GET['id']);

// Truy vấn để xóa báo cáo
$delete_sql = "DELETE FROM reports WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param('i', $id);

// Thêm xác nhận xóa
if (!isset($_POST['confirm'])) {
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Xác nhận xóa báo cáo</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container py-3">
            <div class="row justify-content-center">
                <div class="col-md-4">
                    <div class="card shadow-sm">
                        <div class="card-body text-center p-3">
                            <h5 class="card-title mb-3">Xác nhận xóa</h5>
                            <p class="card-text small">Bạn có chắc chắn muốn xóa báo cáo này?</p>
                            <form method="POST">
                                <input type="hidden" name="confirm" value="1">
                                <button type="submit" class="btn btn-sm btn-danger">Xác nhận xóa</button>
                                <a href="manage_reports.php" class="btn btn-sm btn-secondary">Hủy</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
<?php
    exit();
}

// Thực hiện xóa khi đã xác nhận
if ($delete_stmt->execute()) {
    $_SESSION['message'] = "Xóa báo cáo thành công!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Xóa báo cáo thất bại!";
    $_SESSION['message_type'] = "danger";
}

header("Location: manage_reports.php");
exit();
?>
