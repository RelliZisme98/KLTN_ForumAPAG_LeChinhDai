<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_condition = !empty($search) ? "WHERE title LIKE '%$search%'" : "";
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Header & Footer</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<style>
body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-top: 20px;
        }

        .main-content {
            width: 80%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: center;
            color: #333;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        footer {
            text-align: center;
            padding: 20px;
            background-color: #007bff;
            color: white;
            margin-top: 40px;
            border-top: 1px solid #ddd;
        }

        footer p {
            margin: 0;
            font-size: 14px;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #0056b3;
        }

        input[type="text"] {
            width: 70%;
            padding: 10px;
            margin: 20px 0;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .search-box {
            margin: 20px 0;
            text-align: center;
        }
        
        .search-box input[type="text"] {
            width: 300px;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
        }
        
        .btn-primary { background-color: #007bff; color: white; }
        .btn-danger { background-color: #dc3545; color: white; }
        .btn-warning { background-color: #ffc107; color: black; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 10px 0;
            border-bottom: 2px solid #eee;
        }

        .section-header h2 {
            margin: 0;
        }

        .search-form {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
<body>
    <div class="main-content">
        <h1>Quản lý Header & Footer</h1>

        <!-- Search form -->
        <div class="search-box">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Tìm kiếm..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tìm kiếm</button>
            </form>
        </div>

        <div class="section-header">
            <h2>Quản lý Header Links</h2>
            <a href="add_header_link.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Header Link</a>
        </div>
        <table>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>URL</th>
                <th>Position</th>
                <th>Logged In Only</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php
            $header_links_query = "SELECT * FROM header_links $search_condition ORDER BY position";
            $header_links_result = $conn->query($header_links_query);

            while($header = $header_links_result->fetch_assoc()) {
                echo "<tr>
                        <td>{$header['id']}</td>
                        <td>{$header['title']}</td>
                        <td>{$header['url']}</td>
                        <td>{$header['position']}</td>
                        <td>" . ($header['is_logged_in'] ? 'Yes' : 'No') . "</td>
                        <td>{$header['created_at']}</td>
                        <td class='action-buttons'>
                            <a href='edit_header_link.php?id={$header['id']}' class='btn btn-warning'>Edit</a>
                            <a href='javascript:void(0);' onclick='confirmDelete({$header['id']}, \"header_link\")' class='btn btn-danger'>Delete</a>
                        </td>
                    </tr>";
            }
            ?>
        </table>

        <div class="section-header">
            <h2>Quản lý Footer Sections</h2>
            <a href="add_footer_section.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Footer Section</a>
        </div>
        <table>
            <tr>
                <th>ID</th>
                <th>Section Title</th>
                <th>Position</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php
            $footer_sections_query = "SELECT * FROM footer_sections $search_condition ORDER BY position";
            $footer_sections_result = $conn->query($footer_sections_query);

            while($section = $footer_sections_result->fetch_assoc()) {
                echo "<tr>
                        <td>{$section['id']}</td>
                        <td>{$section['section_title']}</td>
                        <td>{$section['position']}</td>
                        <td>{$section['created_at']}</td>
                        <td class='action-buttons'>
                            <a href='edit_footer_section.php?id={$section['id']}' class='btn btn-warning'>Edit</a>
                            <a href='javascript:void(0);' onclick='confirmDelete({$section['id']}, \"footer_section\")' class='btn btn-danger'>Delete</a>
                        </td>
                    </tr>";
            }
            ?>
        </table>

        <div class="section-header">
            <h2>Quản lý Footer Links</h2>
            <a href="add_footer_link.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Footer Link</a>
        </div>
        <table>
            <tr>
                <th>ID</th>
                <th>Section ID</th>
                <th>Title</th>
                <th>URL</th>
                <th>Icon Class</th>
                <th>Position</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            <?php
            $footer_links_query = "SELECT * FROM footer_links $search_condition ORDER BY position";
            $footer_links_result = $conn->query($footer_links_query);

            while($link = $footer_links_result->fetch_assoc()) {
                echo "<tr>
                        <td>{$link['id']}</td>
                        <td>{$link['section_id']}</td>
                        <td>{$link['title']}</td>
                        <td>{$link['url']}</td>
                        <td>{$link['icon_class']}</td>
                        <td>{$link['position']}</td>
                        <td>{$link['created_at']}</td>
                        <td class='action-buttons'>
                            <a href='edit_footer_link.php?id={$link['id']}' class='btn btn-warning'>Edit</a>
                            <a href='javascript:void(0);' onclick='confirmDelete({$link['id']}, \"footer_link\")' class='btn btn-danger'>Delete</a>
                        </td>
                    </tr>";
            }
            ?>
        </table>
    </div>

    <script>
    function confirmDelete(id, type) {
        if(confirm('Bạn có chắc chắn muốn xóa mục này?')) {
            window.location.href = 'delete_' + type + '.php?id=' + id;
        }
    }
    </script>
</body>
</html>
