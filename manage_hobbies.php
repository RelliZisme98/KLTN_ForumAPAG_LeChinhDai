<?php
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $user_id = $_POST['user_id'];
        $hobby_name = $_POST['hobby_name'];
        $is_main = isset($_POST['is_main']) ? 1 : 0;

        if (!empty($_POST['hobby_id'])) {
            $stmt = $conn->prepare("UPDATE hobbies SET user_id=?, hobby_name=?, is_main=? WHERE id=?");
            $stmt->bind_param("ssii", $user_id, $hobby_name, $is_main, $_POST['hobby_id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO hobbies (user_id, hobby_name, is_main) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $user_id, $hobby_name, $is_main);
        }
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } elseif (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM hobbies WHERE id = ?");
        $stmt->bind_param("i", $_POST['hobby_id']);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT h.*, u.username FROM hobbies h LEFT JOIN users u ON h.user_id = u.id WHERE h.user_id LIKE ? OR u.username LIKE ?");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $hobbies = $stmt->get_result();
} else {
    $hobbies = $conn->query("SELECT h.*, u.username FROM hobbies h LEFT JOIN users u ON h.user_id = u.id");
}

// Get users for dropdown
$users = $conn->query("SELECT id, username FROM users");
$usersList = [];
while ($user = $users->fetch_assoc()) {
    $usersList[] = $user;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Hobbies</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .search-container {
            margin: 20px 0;
        }
        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center mb-4">Manage Hobbies</h1>

        <div class="row search-container">
            <div class="col-md-6">
                <form class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search by User ID or Username" 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#hobbyModal">
                    Add New Hobby
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Hobby Name</th>
                        <th>Main Hobby</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($hobby = $hobbies->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($hobby['id']) ?></td>
                        <td><?= htmlspecialchars($hobby['username']) ?> (ID: <?= htmlspecialchars($hobby['user_id']) ?>)</td>
                        <td><?= htmlspecialchars($hobby['hobby_name']) ?></td>
                        <td><?= $hobby['is_main'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editHobby(<?= htmlspecialchars(json_encode($hobby)) ?>)">
                                <i class="bi bi-pencil"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteHobby(<?= $hobby['id'] ?>)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="hobbyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Hobby</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="hobbyForm">
                    <div class="modal-body">
                        <input type="hidden" name="hobby_id" id="hobby_id">
                        <div class="mb-3">
                            <label class="form-label">User</label>
                            <select name="user_id" id="user_id" class="form-select" required>
                                <option value="">Select User</option>
                                <?php foreach ($usersList as $user): ?>
                                    <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (ID: <?= $user['id'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hobby Name</label>
                            <input type="text" name="hobby_name" id="hobby_name" class="form-control" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_main" id="is_main" class="form-check-input">
                            <label class="form-check-label">Main Hobby</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editHobby(hobby) {
            document.getElementById('modalTitle').textContent = 'Edit Hobby';
            document.getElementById('hobby_id').value = hobby.id;
            document.getElementById('user_id').value = hobby.user_id;
            document.getElementById('hobby_name').value = hobby.hobby_name;
            document.getElementById('is_main').checked = hobby.is_main == 1;
            new bootstrap.Modal(document.getElementById('hobbyModal')).show();
        }

        function deleteHobby(id) {
            if (confirm('Are you sure you want to delete this hobby?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="hobby_id" value="${id}">
                    <input type="hidden" name="delete" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Reset form when modal is closed
        document.getElementById('hobbyModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('hobbyForm').reset();
            document.getElementById('hobby_id').value = '';
            document.getElementById('modalTitle').textContent = 'Add New Hobby';
        });
    </script>
</body>
</html>