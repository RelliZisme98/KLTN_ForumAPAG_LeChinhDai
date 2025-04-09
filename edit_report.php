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

// Truy vấn để lấy thông tin báo cáo
$sql = "SELECT * FROM reports WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_reports.php");
    exit();
}

$report = $result->fetch_assoc();

// Xử lý dữ liệu khi gửi biểu mẫu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $status = $_POST['status'];

    $update_sql = "UPDATE reports SET content = ?, status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param('ssi', $content, $status, $id);
    
    if ($update_stmt->execute()) {
        header("Location: manage_reports.php");
        exit();
    } else {
        echo "Cập nhật không thành công!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Báo Cáo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-3">
        <div class="content-wrapper" style="max-width: 600px; margin: 0 auto;">
            <div class="card shadow-sm">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold" style="color: #1a237e">Chỉnh Sửa Báo Cáo #<?php echo $id; ?></h5>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nội Dung Báo Cáo</label>
                            <textarea name="content" class="form-control" 
                                    rows="4" required><?php echo htmlspecialchars($report['content']); ?></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Trạng Thái</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending" <?php if ($report['status'] === 'pending') echo 'selected'; ?>>
                                    Chờ xử lý
                                </option>
                                <option value="resolved" <?php if ($report['status'] === 'resolved') echo 'selected'; ?>>
                                    Đã giải quyết
                                </option>
                                <option value="rejected" <?php if ($report['status'] === 'rejected') echo 'selected'; ?>>
                                    Đã từ chối
                                </option>
                            </select>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bx bx-save me-1"></i> Lưu
                            </button>
                            <a href="manage_reports.php" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Quay Lại
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
