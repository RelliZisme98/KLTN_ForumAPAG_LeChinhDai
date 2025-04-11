<?php
session_start();

// Database connection first
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thread_id từ URL
$thread_id = isset($_GET['thread_id']) ? (int)$_GET['thread_id'] : 0;

// Sửa lại cấu trúc điều kiện
if (!isset($_GET['thread_id'])) {
    // Code hiển thị danh sách chủ đề giữ nguyên
    $all_threads_query = $conn->prepare("
        SELECT 
            t.*,
            u.username,
            (SELECT COUNT(*) FROM questions WHERE thread_id = t.id) as total_questions,
            (SELECT MAX(created_at) FROM questions WHERE thread_id = t.id) as last_activity
        FROM threads t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ");
    $all_threads_query->execute();
    $threads = $all_threads_query->get_result();
    
    // Đặt các biến này là null vì chúng ta đang ở chế độ xem danh sách
    $thread = null;
    $questions = null;
} else {
    // Code xem chi tiết chủ đề
    $thread_id = (int)$_GET['thread_id'];
    
    if ($thread_id <= 0) {
        header('Location: forum.php');
        exit();
    }

    // Lấy thông tin của chủ đề
    $thread_query = $conn->prepare("
        SELECT threads.*, users.username 
        FROM threads 
        JOIN users ON threads.user_id = users.id 
        WHERE threads.id = ?
    ");
    $thread_query->bind_param('i', $thread_id);
    $thread_query->execute();
    $thread = $thread_query->get_result()->fetch_assoc();

    if (!$thread) {
        header('Location: forum.php');
        exit();
    }

    // Sửa lại truy vấn lấy danh sách câu hỏi của chủ đề
    $questions_query = $conn->prepare("
        SELECT 
            q.*,
            u.username,
            u.profile_picture,
            (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
            (SELECT MAX(created_at) FROM answers WHERE question_id = q.id) as last_reply_date
        FROM questions q
        LEFT JOIN users u ON q.user_id = u.id  /* Đổi JOIN thành LEFT JOIN */
        WHERE q.thread_id = ? 
        AND q.status = 1
        ORDER BY q.created_at DESC");

    $questions_query->bind_param('i', $thread_id);
    $questions_query->execute();
    $questions = $questions_query->get_result();

    // Thêm debug để kiểm tra
    $total_questions = $questions->num_rows;
    error_log("Thread ID: $thread_id - Total questions: $total_questions");
    
    // Đặt biến threads là null vì chúng ta đang xem chi tiết
    $threads = null;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="" />
    <meta name="keywords" content="" />
	<title>NAPA Social Network</title> 
    <link rel="stylesheet" href="css/main.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/color.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        .forum-list h3 {
            margin-bottom: 15px;
            color: #333;
        }

        .thread-description {
            color: #666;
            margin-bottom: 20px;
        }

        .forum-stats {
            margin-bottom: 20px;
            color: #666;
        }

        .forum-stats span {
            margin-right: 20px;
        }

        .topic-title h5 {
            margin-bottom: 5px;
        }

        .topic-title p {
            font-size: 0.9em;
            color: #666;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .topic-replies, .topic-views {
            text-align: center;
        }

        .topic-last-reply {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
<div class="wavy-wraper">
		<div class="wavy">
		  <span style="--i:1;">f</span>
		  <span style="--i:2;">o</span>
		  <span style="--i:3;">r</span>
		  <span style="--i:4;">u</span>
		  <span style="--i:5;">m</span>
		  <span style="--i:6;">N</span>
		  <span style="--i:7;">A</span>
		  <span style="--i:8;">P</span>
		  <span style="--i:9;">A</span>
		</div>
	</div>
<div class="theme-layout">
	
<?php include 'component/header.php'; ?>
	
		
	<section>
		<div class="page-header">
			<div class="header-inner">
			<h2>Diễn đàn Câu hỏi và Trả lời Học viện Hành Chính Quốc gia</h2>
				<p>
					Chào mừng đến với NAPA Social Network. Diễn đàn là nơi giúp các bạn sinh viên và giảng viên đặt các câu hỏi về các lĩnh vực liên quan đến các chủ đề học tập và làm việc.
				</p>
			</div>
			<figure><img src="images/resources/baner-forum.png" alt=""></figure>
		</div>
	</section><!-- sub header -->
	
	<section>
		<div class="gap gray-bg">
			<div class="container">
				<div class="row merged20">
					<div class="col-lg-9">
						<div class="forum-warper">
							<div class="central-meta">
								<div class="title-block">
									<div class="row">
										<div class="col-lg-6">
											<div class="align-left">
												<h5>Forum Category</h5>
											</div>
										</div>
										<div class="col-lg-6">
										<div class="row merged20">
        <div class="col-lg-7 col-md-7 col-sm-7">
            <!-- Thanh tìm kiếm -->
            <form method="get" action="search.php" class="search-form">
                <input type="text" name="query" placeholder="Tìm kiếm câu hỏi..." value="<?php echo isset($_GET['query']) ? htmlspecialchars($_GET['query']) : ''; ?>">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>
		<div class="col-lg-4 col-md-4 col-sm-4">
            <div class="select-options">
                <select class="select" name="sort">
                    <option value="">Sắp xếp theo</option>
                    <option value="all">Xem Tất cả</option>
                    <option value="newest">Mới nhất</option>
                    <option value="oldest">Cũ nhất</option>
                    <option value="atoz">A đến Z</option>
                </select>
            </div>
        </div>
        <div class="col-lg-1 col-md-1 col-sm-1">
            <div class="option-list">
                <i class="fa fa-ellipsis-v"></i>
                <ul>
                    <li class="active"><i class="fa fa-check"></i><a title="" href="#">Hiện công khai</a></li>
                    <li><a title="" href="#">Chỉ hiện bạn bè</a></li>
                    <li><a title="" href="#">Ẩn tất cả bài viết</a></li>
                    <li><a title="" href="#">Tắt thông báo</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

								</div>
							</div><!-- title block -->
						</div>
					<!-- Hiển thị dữ liệu từ bảng threads -->

					<div class="central-meta">
    <div class="forum-list">
        <?php if (!isset($_GET['thread_id'])): ?>
            <!-- Hiển thị danh sách chủ đề -->
            <h3>Danh sách chủ đề</h3>
            <table class="table table-responsive">
                <thead>
                    <tr>
                        <th>Chủ đề</th>
                        <th>Người tạo</th>
                        <th>Số câu hỏi</th>
                        <th>Hoạt động cuối</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($thread = $threads->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <h5>
                                    <a href="forum_category.php?thread_id=<?php echo $thread['id']; ?>">
                                        <?php echo htmlspecialchars($thread['title']); ?>
                                    </a>
                                </h5>
                                <p><?php echo htmlspecialchars($thread['description']); ?></p>
                            </td>
                            <td><?php echo htmlspecialchars($thread['username']); ?></td>
                            <td><?php echo $thread['total_questions']; ?></td>
                            <td>
                                <?php 
                                if ($thread['last_activity']) {
                                    echo date('d/m/Y H:i', strtotime($thread['last_activity']));
                                } else {
                                    echo 'Chưa có hoạt động';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Hiển thị chi tiết chủ đề và câu hỏi -->
            <?php if ($thread): ?>
                <div class="forum-header">
                    <div class="row">
                        <div class="col-lg-6">
                            <h3><?php echo htmlspecialchars($thread['title']); ?></h3>
                            <p><?php echo htmlspecialchars($thread['description']); ?></p>
                        </div>
                        <div class="col-lg-6 text-right">
                            <a href="create_topic_forum.php?thread_id=<?php echo $thread_id; ?>" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Tạo câu hỏi mới
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="forum-stats">
                    <span>Tạo bởi: <?php echo htmlspecialchars($thread['username']); ?></span>
                    <span>Ngày tạo: <?php echo date('d/m/Y', strtotime($thread['created_at'])); ?></span>
                </div>

                <!-- Hiển thị danh sách câu hỏi -->
                <table class="table table-responsive">
                    <thead>
                        <tr>
                            <th>Câu hỏi</th>
                            <th>Tác giả</th>
                            <th>Trả lời</th>
                            <th>Lượt xem</th>
                            <th>Hoạt động cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($questions && $questions->num_rows > 0): ?>
                            <?php while ($question = $questions->fetch_assoc()): ?>
                                <tr>
                                    <td class="topic-title">
                                        <h5>
                                            <a href="view_question.php?id=<?php echo $question['id']; ?>">
                                                <?php echo htmlspecialchars($question['title']); ?>
                                            </a>
                                        </h5>
                                        <p><?php echo mb_substr(htmlspecialchars($question['content']), 0, 100) . '...'; ?></p>
                                    </td>
                                    <td class="topic-author">
                                        <div class="user-info">
                                            <img src="<?php echo htmlspecialchars($question['profile_picture']); ?>" alt="Avatar">
                                            <span><?php echo htmlspecialchars($question['username']); ?></span>
                                        </div>
                                    </td>
                                    <td class="topic-replies">
                                        <?php echo $question['answer_count']; ?>
                                    </td>
                                    <td class="topic-views">
                                        <?php echo $question['views']; ?>
                                    </td>
                                    <td class="topic-last-reply">
                                        <?php 
                                        $last_activity = $question['last_reply_date'] ?? $question['created_at'];
                                        echo date('d/m/Y H:i', strtotime($last_activity));
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    Chưa có câu hỏi nào trong chủ đề này.
                                    <a href="create_topic_forum.php?thread_id=<?php echo $thread_id; ?>" class="btn btn-primary">
                                        Tạo câu hỏi mới
                                    </a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Không tìm thấy chủ đề.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
					<div class="col-lg-3">
						<aside class="sidebar static">
							<div class="widget">
							
							</div>
						
						</aside>	
					</div>
				</div>
			</div>
		</div>
	</section>
	
	<section>
		<div class="getquot-baner purple high-opacity">
			<div class="bg-image" style="background-image:url(images/resources/animated-bg2.png)"></div>
			<span>Want to join our awesome forum and start interacting with others?</span>
			<a title="" href="#">Sign up</a>
		</div>
	</section>
	
	<?php include 'component/footer.php'; ?>
	
</div>
	
<?php
// Move cleanup function and registration to end of file
function cleanupCategoryResources() {
    static $cleaned = false;
    if ($cleaned) return;
    
    global $conn, $all_threads_query, $thread_query, 
           $questions_query, $threads, $questions;
    
    try {
        // Free results first
        if ($threads instanceof mysqli_result) {
            $threads->free_result();
        }
        if ($questions instanceof mysqli_result) {
            $questions->free_result();
        }
        
        // Close statements
        $statements = [$all_threads_query, $thread_query, $questions_query];
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

register_shutdown_function('cleanupCategoryResources');
?>

	<script src="js/main.min.js"></script>
	<script src="js/script.js"></script>

</body>	
</html>