<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra nếu người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Xử lý tìm kiếm
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT r.id, u.username AS reporter, r.content, r.type, r.reference_id, r.created_at, r.status
        FROM reports r
        JOIN users u ON r.user_id = u.id
        WHERE u.username LIKE ? OR r.content LIKE ? OR r.type LIKE ?
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$searchTerm = "%$search%";
$stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Báo Cáo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
    <style>
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        .table td:nth-child(3) {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid py-3">
        <div class="content-wrapper">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold" style="color: #1a237e">Quản Lý Báo Cáo</h5>
                    <a href="create_report.php" class="btn btn-light btn-sm">
                        <i class="bx bx-plus"></i> Thêm Mới
                    </a>
                </div>
                <div class="card-body p-3">
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show py-2 mb-2">
                            <?php 
                                echo $_SESSION['message'];
                                unset($_SESSION['message']);
                                unset($_SESSION['message_type']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="GET" class="mb-3">
                        <div class="input-group input-group-sm">
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Tìm kiếm..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-search"></i>
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Người Báo Cáo</th>
                                    <th>Nội Dung</th>
                                    <th>Loại</th>
                                    <th>ID Tham Chiếu</th>
                                    <th>Thời Gian</th>
                                    <th>Trạng Thái</th>
                                    <th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['reporter']); ?></td>
                                    <td><?php echo htmlspecialchars($row['content']); ?></td>
                                    <td><?php echo htmlspecialchars($row['type']); ?></td>
                                    <td><?php echo $row['reference_id']; ?></td>
                                    <td><?php echo $row['created_at']; ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($row['status']) {
                                                'pending' => 'bg-warning',
                                                'resolved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit_report.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">Sửa</a>
                                        <a href="delete_report.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
