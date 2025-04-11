<?php
session_start();
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý người dùng trực tuyến
$is_logged_in = isset($_SESSION['username']);
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$session_id = session_id();
$ip_address = $_SERVER['REMOTE_ADDR'];
$timeout = 5 * 60;
$expired_time = time() - $timeout;

// Cập nhật người dùng trực tuyến
$conn->query("DELETE FROM active_users WHERE UNIX_TIMESTAMP(last_activity) < $expired_time");
$stmt = $conn->prepare("SELECT id FROM active_users WHERE session_id = ?");
$stmt->bind_param("s", $session_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt_update = $conn->prepare("UPDATE active_users SET last_activity = CURRENT_TIMESTAMP WHERE session_id = ?");
    $stmt_update->bind_param("s", $session_id);
    $stmt_update->execute();
} elseif ($is_logged_in) {
    $stmt_insert = $conn->prepare("INSERT INTO active_users (user_id, session_id, ip_address) VALUES (?, ?, ?)");
    $stmt_insert->bind_param("iss", $user_id, $session_id, $ip_address);
    $stmt_insert->execute();
}

// Truy vấn thống kê cơ bản
$queries = [
    'active_users' => "SELECT COUNT(*) AS count FROM active_users",
    'new_posts_today' => "SELECT COUNT(*) AS count FROM questions WHERE DATE(created_at) = CURDATE()",
    'total_members' => "SELECT COUNT(*) AS count FROM users",
    'total_topics' => "SELECT COUNT(*) AS count FROM threads"
];

$stats = [];
foreach ($queries as $key => $query) {
    $result = $conn->query($query);
    $stats[$key] = $result->fetch_assoc()['count'];
}

// Truy vấn chính
$latest_questions_query = "
    SELECT 
        q.*, 
        t.title AS thread_title,
        u.username,
        u.profile_picture,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
        q.image_url,
        q.file_url
    FROM questions q
    LEFT JOIN users u ON q.user_id = u.id
    LEFT JOIN threads t ON q.thread_id = t.id
    WHERE q.status = 1
    ORDER BY q.created_at DESC, q.id DESC
    LIMIT 8";

$latest_questions = $conn->query($latest_questions_query);
if (!$latest_questions) {
    die("Lỗi truy vấn: " . $conn->error);
}

// Truy vấn câu hỏi nổi bật
$popular_questions_query = "
    SELECT q.*, u.username 
    FROM questions q
    JOIN users u ON q.user_id = u.id
    ORDER BY q.views DESC 
    LIMIT 5";
$popular_questions = $conn->query($popular_questions_query);

// Truy vấn chủ đề mới nhất
$latest_threads_query = "
    SELECT t.*, COUNT(q.id) as question_count 
    FROM threads t
    LEFT JOIN questions q ON t.id = q.thread_id
    GROUP BY t.id
    ORDER BY t.created_at DESC 
    LIMIT 5";
$latest_threads = $conn->query($latest_threads_query);

// Truy vấn thành viên tiêu biểu
$top_members_query = "
    SELECT u.id, u.username, COUNT(q.id) AS total_questions
    FROM users u
    JOIN questions q ON u.id = q.user_id
    GROUP BY u.id
    ORDER BY total_questions DESC
    LIMIT 5";
$top_members = $conn->query($top_members_query);

// Thêm phần hiển thị danh sách chủ đề
$all_threads_query = "
    SELECT 
        t.*,
        (SELECT COUNT(*) FROM questions WHERE thread_id = t.id) as question_count
    FROM threads t
    LEFT JOIN users u ON t.user_id = u.id";
$all_threads = $conn->query($all_threads_query);
if (!$all_threads) {
    die("Lỗi truy vấn chủ đề: " . $conn->error);
}

// Move cleanup function and registration to end of file, just before closing PHP tag
function cleanupForumResources() {
    static $cleaned = false;
    if ($cleaned) return;
    
    global $conn, $stmt, $stmt_update, $stmt_insert, 
           $latest_questions, $popular_questions, 
           $latest_threads, $top_members, $all_threads;
    
    try {
        // Free results first
        $results = [$latest_questions, $popular_questions, $latest_threads, $top_members, $all_threads];
        foreach ($results as $result) {
            if ($result instanceof mysqli_result) {
                $result->free_result();
            }
        }
        
        // Close statements
        $statements = [$stmt, $stmt_update, $stmt_insert];
        foreach ($statements as $statement) {
            if ($statement instanceof mysqli_stmt) {
                try {
                    $statement->close();
                } catch (Exception $e) {
                    // Statement might already be closed
                }
            }
        }
        
        // Close connection last
        // if ($conn instanceof mysqli && $conn->ping()) {
        //     $conn->close();
        // }
    } catch (Exception $e) {
        error_log('Cleanup error: ' . $e->getMessage());
    }
    
    $cleaned = true;
}

// Register cleanup at the end
register_shutdown_function('cleanupForumResources');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NAPA Forum</title>
    <link rel="stylesheet" href="css/main.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/color.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <div class="wavy-wraper">
        <div class="wavy">
            <span style="--i:1;">F</span>
            <span style="--i:2;">O</span>
            <span style="--i:3;">R</span>
            <span style="--i:4;">U</span>
            <span style="--i:5;">M</span>
            <span style="--i:6;">-</span>
            <span style="--i:7;">N</span>
            <span style="--i:8;">A</span>
            <span style="--i:9;">P</span>
            <span style="--i:10;">A</span>
        </div>
    </div>

    <div class="theme-layout">
        <?php include 'component/header.php'; ?>

        <!-- Banner Section -->
        <section>
            <div class="page-header">
                <div class="header-inner">
                    <h2>Diễn đàn NAPA - Nơi Chia Sẻ Kiến Thức</h2>
                    <p>Diễn đàn trao đổi học tập dành cho sinh viên và giảng viên Học viện Hành chính Quốc gia</p>
                </div>
                <figure><img src="images/resources/baner-forum.png" alt=""></figure>
            </div>
        </section>

        <!-- Main Content Section -->
        <section>
            <div class="gap gray-bg">
                <div class="container">
                    <div class="row merged20">
                        <div class="col-lg-9">
                            <!-- Tạo chủ đề mới -->
                            <div class="forum-warper">
                                <div class="central-meta">
                                    <div class="title-block">
                                        <h4>Tạo mới</h4>
                                        <a href="create_topic_forum.php" class="btn-create-new">
                                            <i class="fa fa-plus"></i> Tạo chủ đề hoặc câu hỏi
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Câu hỏi mới nhất -->
                            <div class="central-meta">
                                <div class="forum-open">
                                    <h5><i class="fa fa-star"></i> Câu hỏi mới nhất</h5>
                                    <p class="view-category">
                                        <a href="forum_category.php" class="btn-view-category btn-primary">
                                            <i class="fa fa-folder-open"></i> Xem tất cả chủ đề
                                        </a>
                                    </p>
                                    <table class="table table-responsive">
                                        <thead>
                                            <tr>
                                                <th>Tác giả</th>
                                                <th>Ngày đăng</th>
                                                <th>Chủ đề</th>
                                                <th>Tệp đính kèm</th>
                                                <th>Bài viết</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($question = $latest_questions->fetch_assoc()) : ?>
                                            <tr>
                                                <td class="topic-data">
                                                    <img src="<?php echo htmlspecialchars($question['profile_picture']); ?>" alt="">
                                                    <span><?php echo htmlspecialchars($question['username']); ?></span>
                                                    <em>Người tham gia</em>
                                                </td>
                                                <td class="date-n-reply">
                                                    <span><?php echo date("d/m/Y H:i", strtotime($question['created_at'])); ?></span>
                                                    <a href="view_question.php?id=<?php echo $question['id']; ?>">Trả lời</a>
                                                </td>
                                                <td class="question-topic">
                                                    <span><?php echo htmlspecialchars($question['thread_title']); ?></span>
                                                </td>
                                                <td class="question-attachments">
                                                    <?php if (!empty($question['image_url'])): ?>
                                                        <div class="attachment-preview">
                                                            <?php if (strpos($question['image_url'], '.pdf') !== false): ?>
                                                                <a href="<?php echo htmlspecialchars($question['image_url']); ?>" target="_blank">
                                                                    <i class="fa fa-file-pdf"></i> Xem PDF
                                                                </a>
                                                            <?php elseif (strpos($question['image_url'], '.doc') !== false || strpos($question['image_url'], '.docx') !== false): ?>
                                                                <a href="<?php echo htmlspecialchars($question['image_url']); ?>" download>
                                                                    <i class="fa fa-file-word"></i> Tải Word
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="<?php echo htmlspecialchars($question['image_url']); ?>" target="_blank">
                                                                    <img src="<?php echo htmlspecialchars($question['image_url']); ?>" alt="Preview" style="max-width: 50px;">
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if (!empty($question['file_url'])): ?>
                                                        <div class="file-attachment">
                                                            <a href="<?php echo htmlspecialchars($question['file_url']); ?>" download>
                                                                <i class="fa fa-download"></i> Tải file
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="topic-detail">
                                                    <p>
                                                        <a href="view_question.php?id=<?php echo $question['id']; ?>">
                                                            <?php echo htmlspecialchars($question['title']); ?>
                                                        </a>
                                                    </p>
                                                    <?php if (!empty($question['image_url'])): ?>
                                                        <div class="question-media">
                                                            <?php if (strpos($question['image_url'], '.pdf') !== false): ?>
                                                                <i class="fa fa-file-pdf"></i> 
                                                                <a href="<?php echo $question['image_url']; ?>" target="_blank">Xem PDF</a>
                                                            <?php else: ?>
                                                                <img src="<?php echo $question['image_url']; ?>" alt="Question image" style="max-width:200px;">
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p>
                                                        Lượt xem: <?php echo number_format($question['views']); ?> | 
                                                        Trả lời: <?php echo $question['answer_count']; ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Thành viên tiêu biểu -->
                            <!-- Add top members content here -->

                            <!-- Thành viên mới nhất -->
                            <!-- Add newest member content here -->

                            <!-- Thông báo chưa đọc -->
                            <!-- Add notifications content here -->
                        </div>

                        <!-- Sidebar -->
                        <div class="col-lg-3">
                            <aside class="sidebar static">
                                <!-- Forum Statistics -->
                                <div class="widget">
                                    <h4 class="widget-title">Thống kê</h4>
                                    <ul class="forum-stats">
                                        <li><i class="fa fa-users"></i> Đang online: <?php echo $stats['active_users']; ?></li>
                                        <li><i class="fa fa-file-text"></i> Bài mới hôm nay: <?php echo $stats['new_posts_today']; ?></li>
                                        <li><i class="fa fa-user"></i> Tổng thành viên: <?php echo $stats['total_members']; ?></li>
                                        <li><i class="fa fa-folder"></i> Tổng chủ đề: <?php echo $stats['total_topics']; ?></li>
                                    </ul>
                                </div>

                                <!-- Recent Topics Widget -->
                                <div class="widget">
                                    <h4 class="widget-title">Chủ đề mới nhất</h4>
                                    <ul class="recent-topics">
                                        <?php while ($thread = $latest_threads->fetch_assoc()): ?>
                                            <li>
                                                <a href="forum_category.php?thread_id=<?php echo $thread['id']; ?>">
                                                    <?php echo htmlspecialchars($thread['title']); ?>
                                                </a>
                                                <span>Ngày tạo: <?php echo date("d/m/Y", strtotime($thread['created_at'])); ?></span>
                                                <span>Số câu hỏi: <?php echo $thread['question_count']; ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>

                                <!-- Popular Questions Widget -->
                                <div class="widget">
                                    <h4 class="widget-title">Câu hỏi nổi bật</h4>
                                    <ul class="feature-topics">
                                        <?php while ($question = $popular_questions->fetch_assoc()): ?>
                                            <li>
                                                <i class="fa fa-star"></i>
                                                <a href="view_question.php?id=<?php echo $question['id']; ?>">
                                                    <?php echo htmlspecialchars($question['title']); ?>
                                                </a>
                                                <span><?php echo date("d/m/Y", strtotime($question['created_at'])); ?> | 
                                                      Lượt xem: <?php echo number_format($question['views']); ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>

                                <!-- Thành viên tiêu biểu -->
                                <div class="widget">
                                    <h4 class="widget-title">Thành viên tiêu biểu</h4>
                                    <ul class="top-members">
                                        <?php while ($member = $top_members->fetch_assoc()): ?>
                                            <li>
                                                <i class="fa fa-user"></i>
                                                <a href="profile.php?id=<?php echo $member['id']; ?>">
                                                    <?php echo htmlspecialchars($member['username']); ?>
                                                </a>
                                                <span><?php echo $member['total_questions']; ?> câu hỏi</span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                </div>
                            </aside>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php include 'component/footer.php'; ?>
    </div>

    <script src="js/main.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>