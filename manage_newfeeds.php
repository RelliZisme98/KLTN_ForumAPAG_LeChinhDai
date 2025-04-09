<?php
// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Thêm hoặc sửa bài đăng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $user_id = $_POST['user_id'];
        $content = $_POST['content'];
        $location_lat = $_POST['location_lat'];
        $location_lon = $_POST['location_lon'];
        $visibility = $_POST['visibility'];

        if (!empty($_POST['post_id'])) {
            // Cập nhật bài đăng
            $post_id = $_POST['post_id'];
            $stmt = $conn->prepare("UPDATE newfeeds SET user_id=?, content=?, location_lat=?, location_lon=?, visibility=? WHERE post_id=?");
            $stmt->bind_param("isssii", $user_id, $content, $location_lat, $location_lon, $visibility, $post_id);
        } else {
            // Thêm bài đăng mới
            $stmt = $conn->prepare("INSERT INTO newfeeds (user_id, content, location_lat, location_lon, visibility) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $user_id, $content, $location_lat, $location_lon, $visibility);
        }
        $stmt->execute();
    } elseif (isset($_POST['delete'])) {
        // Xóa bài đăng
        $post_id = $_POST['post_id'];
        $stmt = $conn->prepare("DELETE FROM newfeeds WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
    }
}

// Tìm kiếm bài đăng
$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT * FROM newfeeds WHERE user_id LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $newfeeds = $stmt->get_result();
} else {
    $newfeeds = $conn->query("SELECT * FROM newfeeds");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Newfeeds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.9/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<style>
    body {
        background-color: #f8f9fa;
        padding: 20px;
    }
    .card {
        box-shadow: 0 0 15px rgba(0,0,0,0.1);
        border: none;
    }
    .table th {
        background-color: #0d6efd;
        color: white;
    }
    .btn-action {
        margin: 2px;
    }
    .search-box {
        margin-bottom: 20px;
    }
</style>
<body>
    <div class="container">
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0">Manage Newfeeds</h3>
            </div>
            <div class="card-body">
                <!-- Search Form -->
                <div class="search-box">
                    <form method="get" class="row g-3">
                        <div class="col-auto">
                            <input type="text" class="form-control" name="search" 
                                placeholder="Search by User ID" 
                                value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#postModal">
                                Add New Post
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Post ID</th>
                                <th>User ID</th>
                                <th>Content</th>
                                <th>Location</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($newfeed = $newfeeds->fetch_assoc()): ?>
                            <tr>
                                <td><?= $newfeed['post_id'] ?></td>
                                <td><?= $newfeed['user_id'] ?></td>
                                <td><?= htmlspecialchars($newfeed['content']) ?></td>
                                <td>
                                    Lat: <?= htmlspecialchars($newfeed['location_lat'] ?? 'N/A') ?><br>
                                    Long: <?= htmlspecialchars($newfeed['location_lon'] ?? 'N/A') ?>
                                </td>
                                <td><?= $newfeed['visibility'] == 1 ? 'Public' : 'Private' ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm btn-action" 
                                            onclick="editPost(<?= htmlspecialchars(json_encode($newfeed)) ?>)">
                                        Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm btn-action" 
                                            onclick="deletePost(<?= $newfeed['post_id'] ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="postModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" id="postForm">
                    <div class="modal-body">
                        <input type="hidden" name="post_id" id="post_id">
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input type="text" class="form-control" name="user_id" id="user_id" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Content</label>
                            <textarea class="form-control" name="content" id="content" rows="3" required></textarea>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Latitude</label>
                                <input type="text" class="form-control" name="location_lat" id="location_lat">
                            </div>
                            <div class="col">
                                <label class="form-label">Longitude</label>
                                <input type="text" class="form-control" name="location_lon" id="location_lon">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Visibility</label>
                            <select class="form-select" name="visibility" id="visibility">
                                <option value="1">Public</option>
                                <option value="0">Private</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.1.9/dist/sweetalert2.all.min.js"></script>
    <script>
        function editPost(post) {
            document.getElementById('modalTitle').textContent = 'Edit Post';
            document.getElementById('post_id').value = post.post_id;
            document.getElementById('user_id').value = post.user_id;
            document.getElementById('content').value = post.content;
            document.getElementById('location_lat').value = post.location_lat;
            document.getElementById('location_lon').value = post.location_lon;
            document.getElementById('visibility').value = post.visibility;
            
            new bootstrap.Modal(document.getElementById('postModal')).show();
        }

        function deletePost(postId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="post_id" value="${postId}">
                        <input type="hidden" name="delete" value="1">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        <?php if (isset($_POST['add']) || isset($_POST['delete'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Operation completed successfully'
            });
        <?php endif; ?>
    </script>
</body>
</html>