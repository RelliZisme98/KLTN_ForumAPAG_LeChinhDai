<?php
// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thêm, sửa hoặc xóa bình luận
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $post_id = $_POST['post_id'];
        $user_id = $_POST['user_id'];
        $comment_text = $_POST['comment_text'];
        $likes_count = $_POST['likes_count'];

        if (!empty($_POST['comment_id'])) {
            // Cập nhật bình luận
            $comment_id = $_POST['comment_id'];
            $stmt = $conn->prepare("UPDATE comments SET post_id=?, user_id=?, comment_text=?, likes_count=? WHERE comment_id=?");
            $stmt->bind_param("iisii", $post_id, $user_id, $comment_text, $likes_count, $comment_id);
        } else {
            // Thêm bình luận mới
            $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment_text, likes_count) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $post_id, $user_id, $comment_text, $likes_count);
        }
        $stmt->execute();
    } elseif (isset($_POST['delete'])) {
        // Xóa bình luận
        $comment_id = $_POST['comment_id'];
        $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
    }
}

// Tìm kiếm bình luận
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT * FROM comments WHERE user_id LIKE ? OR post_id LIKE ?");
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $comments = $stmt->get_result();
} else {
    $comments = $conn->query("SELECT * FROM comments");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Comments</title>
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
        <h1 class="text-center mb-4">Manage Comments</h1>

        <!-- Search Form -->
        <div class="search-form">
            <form method="get" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search by User ID or Post ID" value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#commentModal">
                    <i class="fas fa-plus"></i> Add Comment
                </button>
            </form>
        </div>

        <!-- Comments Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Comment ID</th>
                        <th>Post ID</th>
                        <th>User ID</th>
                        <th>Comment Text</th>
                        <th>Likes Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($comment = $comments->fetch_assoc()): ?>
                    <tr>
                        <td><?= $comment['comment_id'] ?></td>
                        <td><?= $comment['post_id'] ?></td>
                        <td><?= $comment['user_id'] ?></td>
                        <td><?= $comment['comment_text'] ?></td>
                        <td><?= $comment['likes_count'] ?></td>
                        <td>
                            <button class="btn btn-warning btn-sm" onclick="editComment(<?= htmlspecialchars(json_encode($comment)) ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteComment(<?= $comment['comment_id'] ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Comment Modal -->
        <div class="modal fade" id="commentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Comment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" id="commentForm">
                        <div class="modal-body">
                            <input type="hidden" name="comment_id" id="comment_id">
                            <div class="mb-3">
                                <label class="form-label">Post ID</label>
                                <input type="text" name="post_id" id="post_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">User ID</label>
                                <input type="text" name="user_id" id="user_id" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comment Text</label>
                                <textarea name="comment_text" id="comment_text" class="form-control" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Likes Count</label>
                                <input type="number" name="likes_count" id="likes_count" class="form-control" value="0">
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
                        Are you sure you want to delete this comment?
                    </div>
                    <div class="modal-footer">
                        <form method="post">
                            <input type="hidden" name="comment_id" id="delete_comment_id">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function editComment(comment) {
                document.getElementById('modalTitle').textContent = 'Edit Comment';
                document.getElementById('comment_id').value = comment.comment_id;
                document.getElementById('post_id').value = comment.post_id;
                document.getElementById('user_id').value = comment.user_id;
                document.getElementById('comment_text').value = comment.comment_text;
                document.getElementById('likes_count').value = comment.likes_count;
                new bootstrap.Modal(document.getElementById('commentModal')).show();
            }

            function deleteComment(commentId) {
                document.getElementById('delete_comment_id').value = commentId;
                new bootstrap.Modal(document.getElementById('deleteModal')).show();
            }
        </script>
    </div>
</body>
</html>