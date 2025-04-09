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
        $friend_id = $_POST['friend_id'];
        $mutual_friends_counts = $_POST['mutual_friends_count'];
        $status = $_POST['status'];
        $status_add = $_POST['status_add'];

        if (!empty($_POST['friend_id'])) {
            $friend_relationship_id = $_POST['friend_id'];
            $stmt = $conn->prepare("UPDATE friend SET user_id=?, friend_id=?, mutual_friends_count=?, status=?, status_add=?, updated_at=NOW() WHERE id=?");
            $stmt->bind_param("iisiii", $user_id, $friend_id, $mutual_friends_counts, $status, $status_add, $friend_relationship_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO friend (user_id, friend_id, mutual_friends_count, status, status_add, updated_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("iisii", $user_id, $friend_id, $mutual_friends_counts, $status, $status_add);
        }
        $stmt->execute();
    } elseif (isset($_POST['delete'])) {
        $friend_relationship_id = $_POST['friend_relationship_id'];
        $stmt = $conn->prepare("DELETE FROM friend WHERE id = ?");
        $stmt->bind_param("i", $friend_relationship_id);
        $stmt->execute();
    }
}

$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT f.*, 
        COALESCE(f.status_add, 'pending') as status_add,
        COALESCE(f.updated_at, NOW()) as updated_at 
        FROM friend f 
        WHERE f.user_id LIKE ? OR f.friend_id LIKE ?
        ORDER BY f.updated_at DESC");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $friends = $stmt->get_result();
} else {
    $stmt = $conn->prepare("SELECT *, 
        COALESCE(status_add, 'pending') as status_add,
        COALESCE(updated_at, NOW()) as updated_at 
        FROM friend 
        ORDER BY updated_at DESC");
    $stmt->execute();
    $friends = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Friends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .search-form { margin-bottom: 20px; }
        .table-responsive { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-4">Manage Friends</h1>

        <!-- Search Form -->
        <div class="search-form">
            <form method="get" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search by User ID or Friend ID" 
                       value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#friendModal">
                    <i class="fas fa-plus"></i> Add Friend
                </button>
            </form>
        </div>

        <!-- Friends Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Friend ID</th>
                        <th>Mutual Friends</th>
                        <th>Status</th>
                        <th>Status Add</th>
                        <th>Updated At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($friend = $friends->fetch_assoc()): ?>
                    <tr>
                        <td><?= $friend['id'] ?></td>
                        <td><?= $friend['user_id'] ?></td>
                        <td><?= $friend['friend_id'] ?></td>
                        <td><?= $friend['mutual_friends_count'] ?></td>
                        <td><?= $friend['status'] ?></td>
                        <td><?= htmlspecialchars($friend['status_add']) ?></td>
                        <td><?= htmlspecialchars($friend['updated_at']) ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editFriend(<?= htmlspecialchars(json_encode($friend)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteFriend(<?= $friend['id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Friend Modal -->
        <div class="modal fade" id="friendModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Friend</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" id="friendForm">
                        <div class="modal-body">
                            <input type="hidden" name="friend_relationship_id" id="friend_relationship_id">
                            <div class="mb-3">
                                <label class="form-label">User ID</label>
                                <input type="text" name="user_id" id="user_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Friend ID</label>
                                <input type="text" name="friend_id" id="friend_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mutual Friends Count</label>
                                <input type="number" name="mutual_friends_count" id="mutual_friends_count" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="status" class="form-control" required>
                                    <option value="pending">Pending</option>
                                    <option value="accepted">Accepted</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status Add</label>
                                <select name="status_add" id="status_add" class="form-control" required>
                                    <option value="pending">Pending</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" name="add" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this friend relationship?
                    </div>
                    <div class="modal-footer">
                        <form method="post">
                            <input type="hidden" name="friend_relationship_id" id="delete_friend_id">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function editFriend(friend) {
                document.getElementById('modalTitle').textContent = 'Edit Friend';
                document.getElementById('friend_relationship_id').value = friend.id;
                document.getElementById('user_id').value = friend.user_id;
                document.getElementById('friend_id').value = friend.friend_id;
                document.getElementById('mutual_friends_count').value = friend.mutual_friends_count;
                document.getElementById('status').value = friend.status;
                document.getElementById('status_add').value = friend.status_add;
                new bootstrap.Modal(document.getElementById('friendModal')).show();
            }

            function deleteFriend(friendId) {
                document.getElementById('delete_friend_id').value = friendId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            }
        </script>
    </div>
</body>
</html>