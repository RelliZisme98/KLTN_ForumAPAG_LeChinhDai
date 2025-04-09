<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

$message = '';
$messageType = '';

// Xử lý yêu cầu chuyển đổi quyền
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    if ($action === 'grant_admin') {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Check if user exists in users table
            $query = "SELECT * FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Check if user already exists in admin_users
                $check_admin = "SELECT id FROM admin_users WHERE id = ?";
                $check_stmt = $conn->prepare($check_admin);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception("Người dùng đã là admin!");
                }

                $insert_query = "INSERT INTO admin_users (id, username, password, created_at) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("isss", $user['id'], $user['username'], $user['password'], $user['created_at']);
                $insert_stmt->execute();

                $delete_query = "DELETE FROM users WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();

                $conn->commit();
                $message = "Đã chuyển người dùng thành admin thành công!";
                $messageType = "success";
            } else {
                throw new Exception("Người dùng không tồn tại.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $messageType = "danger";
        }
    } elseif ($action === 'revoke_admin') {
        // Start transaction
        $conn->begin_transaction();
        try {
            $query = "SELECT * FROM admin_users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Check if user already exists in users table
                $check_user = "SELECT id FROM users WHERE id = ?";
                $check_stmt = $conn->prepare($check_user);
                $check_stmt->bind_param("i", $user_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception("Người dùng đã tồn tại trong bảng users!");
                }

                $insert_query = "INSERT INTO users (id, username, password, email, created_at) VALUES (?, ?, ?, '', ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("isss", $admin['id'], $admin['username'], $admin['password'], $admin['created_at']);
                $insert_stmt->execute();

                $delete_query = "DELETE FROM admin_users WHERE id = ?";
                $delete_stmt = $conn->prepare($delete_query);
                $delete_stmt->bind_param("i", $user_id);
                $delete_stmt->execute();

                $conn->commit();
                $message = "Đã hạ quyền admin thành công!";
                $messageType = "success";
            } else {
                throw new Exception("Admin không tồn tại.");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Lỗi: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

$users_query = "SELECT id, username, created_at FROM users";
$users_result = $conn->query($users_query);

$admins_query = "SELECT id, username, created_at FROM admin_users";
$admins_result = $conn->query($admins_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Phân Quyền | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_roles.php">Quản lý phân quyền</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Quản lý phân quyền</li>
            </ol>
        </nav>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách người dùng</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên người dùng</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="manage_roles.php" class="d-inline" onsubmit="return confirmGrantAdmin(event)">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="grant_admin">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-arrow-up-circle"></i> Nâng cấp Admin
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Danh sách Admin</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Tên Admin</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $admins_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo date("d/m/Y H:i", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="manage_roles.php" class="d-inline" onsubmit="return confirmRevokeAdmin(event)">
                                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="action" value="revoke_admin">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="bi bi-arrow-down-circle"></i> Hạ quyền
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-3 mt-5">
        <p class="mb-0">&copy; 2024 Lê Chính Đại Forum. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmGrantAdmin(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Xác nhận nâng cấp',
            text: 'Bạn có chắc chắn muốn nâng cấp người dùng này thành Admin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                event.target.submit();
            }
        });
        return false;
    }

    function confirmRevokeAdmin(event) {
        event.preventDefault();
        Swal.fire({
            title: 'Xác nhận hạ quyền',
            text: 'Bạn có chắc chắn muốn hạ quyền Admin này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                event.target.submit();
            }
        });
        return false;
    }
    </script>
</body>
</html>
