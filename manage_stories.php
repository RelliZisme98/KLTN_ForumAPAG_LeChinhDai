<?php
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $user_id = $_POST['user_id'];
        $created_at = date("Y-m-d H:i:s");
        $expires_at = $_POST['expires_at'];
        
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "../uploads/stories/";
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $target_file = $target_dir . uniqid() . '.' . $imageFileType;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = str_replace('../', '', $target_file);
            } else {
                $message = "Error uploading file.";
                $image_url = $_POST['image_url'];
            }
        } else {
            $image_url = $_POST['image_url'];
        }

        // Update or Insert
        if (!empty($_POST['story_id'])) {
            $story_id = $_POST['story_id'];
            $stmt = $conn->prepare("UPDATE stories SET user_id=?, image_url=?, expires_at=? WHERE story_id=?");
            $stmt->bind_param("sssi", $user_id, $image_url, $expires_at, $story_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO stories (user_id, image_url, created_at, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $image_url, $created_at, $expires_at);
        }
        
        if ($stmt->execute()) {
            $message = isset($_POST['story_id']) ? "Story updated successfully!" : "Story added successfully!";
        } else {
            $message = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['delete'])) {
        $story_id = $_POST['story_id'];
        $stmt = $conn->prepare("DELETE FROM stories WHERE story_id = ?");
        $stmt->bind_param("i", $story_id);
        if ($stmt->execute()) {
            $message = "Story deleted successfully!";
            header("Location: manage_stories.php?message=" . urlencode($message));
            exit();
        }
    }
}

$searchQuery = "";
if (isset($_GET['search'])) {
    $searchTerm = "%" . $_GET['search'] . "%";
    $stmt = $conn->prepare("SELECT * FROM stories WHERE user_id LIKE ?");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $stories = $stmt->get_result();
} else {
    $stories = $conn->query("SELECT * FROM stories");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Stories</title>
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #f5f6fa;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --text-color: #2c3e50;
            --border-radius: 8px;
            --shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
            color: var(--text-color);
        }

        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: var(--text-color);
            font-size: 28px;
            margin: 0;
        }

        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 24px;
            margin-bottom: 24px;
        }

        .form-container {
            background: white;
            padding: 24px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        input[type="text"],
        input[type="datetime-local"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="datetime-local"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .button {
            padding: 10px 20px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            font-size: 14px;
        }

        .button-primary {
            background: var(--primary-color);
            color: white;
        }

        .button-danger {
            background: var(--danger-color);
            color: white;
        }

        .search-container {
            margin-bottom: 24px;
        }

        .search-container input {
            width: 300px;
            margin-right: 10px;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
            text-align: left;
            padding: 16px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #eee;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .preview-image {
            max-width: 150px;
            border-radius: 4px;
            box-shadow: var(--shadow);
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .message {
            padding: 16px;
            border-radius: var(--border-radius);
            margin-bottom: 24px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }

            .search-container input {
                width: 100%;
                margin-bottom: 10px;
            }
        }
        
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .story-image {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
            border-radius: 4px;
        }
        
        .image-input-container {
            display: flex;
            gap: 20px;
            align-items: start;
        }
        
        .image-upload {
            margin: 10px 0;
        }
        
        #imagePreview img {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="page-header">
            <h1>Manage Stories</h1>
            <a href="../Admin/index.php" class="button button-primary">Back to Home</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2><?= isset($_GET['edit']) ? 'Edit Story' : 'Add New Story' ?></h2>
            <form method="post" id="storyForm" enctype="multipart/form-data">
                <input type="hidden" name="story_id" value="<?= isset($_GET['edit']) ? $_GET['edit'] : '' ?>">
                
                <div class="form-group">
                    <label for="user_id">User ID</label>
                    <input type="text" id="user_id" name="user_id" value="<?= isset($_GET['user_id']) ? $_GET['user_id'] : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Image</label>
                    <div class="image-upload">
                        <input type="file" name="image" id="image" accept="image/*">
                        <p>OR</p>
                        <input type="text" id="image_url" name="image_url" placeholder="Enter image URL">
                    </div>
                    <div id="imagePreview"></div>
                </div>

                <div class="form-group">
                    <label for="expires_at">Expires At</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" required>
                </div>

                <div class="action-buttons">
                    <button type="submit" name="add" class="button button-primary">
                        <?= isset($_GET['edit']) ? 'Update Story' : 'Add Story' ?>
                    </button>
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="manage_stories.php" class="button button-danger">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="search-container">
            <form method="get" class="search-form">
                <input type="text" name="search" placeholder="Search by User ID" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                <button type="submit" class="button button-primary">Search</button>
            </form>
        </div>

        <table>
            <tr>
                <th>Story ID</th>
                <th>User ID</th>
                <th>Image</th>
                <th>Created At</th>
                <th>Expires At</th>
                <th>Actions</th>
            </tr>
            <?php while ($story = $stories->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($story['story_id']) ?></td>
                    <td><?= htmlspecialchars($story['user_id']) ?></td>
                    <td>
                        <?php 
                        $image_src = $story['image_url'];
                        if (!empty($image_src)) {
                            if (!preg_match("~^http(s)?://~i", $image_src)) {
                                $image_src = '../' . ltrim($image_src, '/');
                            }
                        ?>
                            <img src="<?= htmlspecialchars($image_src) ?>" 
                                 alt="Story Image" 
                                 class="story-image"
                                 onerror="this.onerror=null; this.src='../Admin/assets/images/placeholder.png';">
                        <?php } ?>
                    </td>
                    <td><?= htmlspecialchars($story['created_at']) ?></td>
                    <td><?= htmlspecialchars($story['expires_at']) ?></td>
                    <td class="action-buttons">
                        <a href="?edit=<?= $story['story_id'] ?>&user_id=<?= urlencode($story['user_id']) ?>&image_url=<?= urlencode($story['image_url']) ?>&expires_at=<?= urlencode($story['expires_at']) ?>" class="button button-primary">Edit</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this story?');">
                            <input type="hidden" name="story_id" value="<?= $story['story_id'] ?>">
                            <button type="submit" name="delete" class="button button-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        const urlInput = document.getElementById('image_url');
        const preview = document.getElementById('imagePreview');
        const form = document.getElementById('storyForm');

        function showPreview(src) {
            preview.innerHTML = src ? 
                `<img src="${src}" class="story-image" alt="Preview" onerror="this.onerror=null; this.src='../Admin/assets/images/placeholder.png';">` : '';
        }

        imageInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    showPreview(e.target.result);
                    urlInput.value = '';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        urlInput.addEventListener('input', function() {
            showPreview(this.value);
            imageInput.value = '';
        });

        // Set initial values for edit mode
        if (window.location.search.includes('edit')) {
            const urlParams = new URLSearchParams(window.location.search);
            const expiresAt = urlParams.get('expires_at');
            if (expiresAt) {
                document.getElementById('expires_at').value = expiresAt.slice(0, 16);
            }
            
            const imageUrl = urlParams.get('image_url');
            if (imageUrl) {
                urlInput.value = decodeURIComponent(imageUrl);
                showPreview(urlInput.value);
            }
        }

        // Prevent form submit on enter key
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'textarea') {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>