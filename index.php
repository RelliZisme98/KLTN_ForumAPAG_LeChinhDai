<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
	die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra xem người dùng có đăng nhập không
if (!isset($_SESSION['user_id'])) {
	header('Location: login.php');
	exit();
}

// Lấy ID người dùng từ session
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
// Truy vấn sự kiện sinh nhật
$sql = "SELECT * FROM birthdays LIMIT 1";
$result = $conn->query($sql);

// Tạo biến lưu trữ dữ liệu sinh nhật
$birthday_data = [];

if ($result && $result->num_rows > 0) {
	$row = $result->fetch_assoc();

	// Phân tích ngày sinh nhật
	$birthday_date = new DateTime($row['birthday_date']);
	$day = $birthday_date->format('d');
	$month = $birthday_date->format('F');  // Lấy tên tháng

	// Lưu dữ liệu vào mảng
	$birthday_data = [
		'profile_image' => $row['profile_image'],
		'background_image' => $row['background_image'],
		'event_name' => $row['event_name'],
		'user_name' => $row['user_name'],
		'birthday_message' => $row['birthday_message'],
		'day' => $day,
		'month' => $month
	];
} else {
	// Nếu không có dữ liệu trả về từ truy vấn
	$birthday_data = [
		'profile_image' => 'images/default_profile.png',
		'background_image' => 'images/default_background.png',
		'event_name' => 'Birthday Event',
		'user_name' => 'User Name',
		'birthday_message' => 'Happy Birthday!',
		'day' => '01',
		'month' => 'January'
	];
}
// Truy vấn lấy thông tin cơ bản của người dùng

if ($user_id > 0) {
	// Truy vấn lấy thông tin cơ bản của người dùng
	$sql_user = "SELECT username, profile_picture FROM users WHERE id = ?";
	$stmt_user = $conn->prepare($sql_user);
	$stmt_user->bind_param('i', $user_id);
	$stmt_user->execute();
	$result_user = $stmt_user->get_result();

	// Kiểm tra nếu người dùng tồn tại
	if ($result_user->num_rows > 0) {
		$user = $result_user->fetch_assoc();

		// Kiểm tra xem người dùng có ảnh đại diện không, nếu không thì sử dụng ảnh mặc định
		$profile_picture = !empty($user['profile_picture']) ? '/uploads/profile_pictures/' . $user['profile_picture'] : '/images/resources/author.jpg';
		$username = $user['username'];
	} else {
		echo "Không tìm thấy người dùng.";
		exit;
	}

	$stmt_user->close();
} else {
	echo "Không tìm thấy session của người dùng.";
	exit();
}

// Truy vấn lấy số lượng tin nhắn chưa đọc
$sql_messages = "SELECT COUNT(*) AS total_messages FROM messages WHERE receiver_id = ? AND status = 'unread'";
$stmt_messages = $conn->prepare($sql_messages);
$stmt_messages->bind_param('i', $user_id);
$stmt_messages->execute();
$result_messages = $stmt_messages->get_result();
$total_messages = $result_messages->fetch_assoc()['total_messages'];

// Truy vấn lấy số lượng thông báo chưa đọc
$sql_notifications = "SELECT COUNT(*) AS total_notifications FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param('i', $user_id);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();
$total_notifications = $result_notifications->fetch_assoc()['total_notifications'];

// Truy vấn lấy số lượt thích
$sql_likes = "SELECT COUNT(*) AS total_likes FROM likes WHERE user_id = ?";
$stmt_likes = $conn->prepare($sql_likes);
$stmt_likes->bind_param('i', $user_id);
$stmt_likes->execute();
$result_likes = $stmt_likes->get_result();
$total_likes = $result_likes->fetch_assoc()['total_likes'];

// Lấy số lượng likes mới trong tuần
$sql_new_likes = "SELECT COUNT(*) AS new_likes FROM likes WHERE liked_at >= (NOW() - INTERVAL 1 WEEK)";
$result_new_likes = $conn->query($sql_new_likes);
$new_likes = $result_new_likes->fetch_assoc()['new_likes'];

// Truy vấn lấy số lượt xem
$sql_views = "SELECT COUNT(*) AS total_views FROM views WHERE user_id = ?";
$stmt_views = $conn->prepare($sql_views);
$stmt_views->bind_param('i', $user_id);
$stmt_views->execute();
$result_views = $stmt_views->get_result();
$total_views = $result_views->fetch_assoc()['total_views'];

// Lấy số lượng views mới trong tuần
$sql_new_views = "SELECT COUNT(*) AS new_views FROM views WHERE viewed_at >= (NOW() - INTERVAL 1 WEEK)";
$result_new_views = $conn->query($sql_new_views);
$new_views = $result_new_views->fetch_assoc()['new_views'];
// Truy vấn dữ liệu từ bảng profile_intro
$sql_intro = "SELECT about, fav_tv_show, favourit_music FROM profile_intro WHERE user_id = ?";
$stmt_intro = $conn->prepare($sql_intro);
$stmt_intro->bind_param("i", $user_id);
$stmt_intro->execute();
$result_intro = $stmt_intro->get_result();

// Lấy dữ liệu từ kết quả
if ($result_intro->num_rows > 0) {
	$profile_intro = $result_intro->fetch_assoc();
} else {
	// Nếu không có thông tin, tạo thông tin mặc định
	$profile_intro = [
		'about' => 'Chưa có thông tin.',
		'fav_tv_show' => 'Chưa có thông tin.',
		'favourit_music' => 'Chưa có thông tin.'
	];
}

// Truy vấn lấy sự kiện từ bảng 'events'
$sql_events = "SELECT event_name, event_link, event_icon, event_class FROM specialevents LIMIT 4"; // Lấy tối đa 4 sự kiện
$result_events = $conn->query($sql_events);

$explore_events = [];
if ($result_events->num_rows > 0) {
	while ($row = $result_events->fetch_assoc()) {
		$explore_events[] = [
			'event_name' => htmlspecialchars($row['event_name']),
			'event_link' => htmlspecialchars($row['event_link']),
			'event_icon' => htmlspecialchars($row['event_icon']),
			'event_class' => htmlspecialchars($row['event_class']),
		];
	}
} else {
	$explore_events = null;
}
// Truy vấn dữ liệu từ bảng recent_links
$sql_links = "SELECT title, image, url, created_at FROM recent_links ORDER BY created_at DESC LIMIT 3";
$result_links = $conn->query($sql_links);

// Mảng lưu các liên kết
$recent_links = [];

// Lấy dữ liệu từ CSDL
if ($result_links->num_rows > 0) {
	while ($link = $result_links->fetch_assoc()) {
		// Chuyển đổi thời gian tạo thành định dạng dễ đọc
		$created_at = strtotime($link['created_at']);
		$time_ago = time() - $created_at;

		// Chuyển đổi thời gian thành chuỗi mô tả
		if ($time_ago >= 31536000) {
			$time_ago_str = floor($time_ago / 31536000) . ' years ago';
		} elseif ($time_ago >= 2592000) {
			$time_ago_str = floor($time_ago / 2592000) . ' months ago';
		} elseif ($time_ago >= 604800) {
			$time_ago_str = floor($time_ago / 604800) . ' weeks ago';
		} elseif ($time_ago >= 86400) {
			$time_ago_str = floor($time_ago / 86400) . ' days ago';
		} elseif ($time_ago >= 3600) {
			$time_ago_str = floor($time_ago / 3600) . ' hours ago';
		} else {
			$time_ago_str = floor($time_ago / 60) . ' minutes ago';
		}

		// Thêm liên kết vào mảng
		$recent_links[] = [
			'title' => $link['title'],
			'image' => $link['image'],
			'url' => $link['url'],
			'time_ago' => $time_ago_str
		];
	}
} else {
	$recent_links[] = null; // Không có liên kết
}
$sql_activity = "SELECT activity_type, activity_content, activity_date, reference_link FROM activities ORDER BY activity_date DESC LIMIT 3";
$result_activity = $conn->query($sql_activity);
function time_elapsed_string($datetime, $full = false)
{
	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);

	// Mảng để lưu kết quả
	$string = [];

	// Tính số năm, tháng, ngày, giờ, phút, giây
	if ($diff->y) {
		$string[] = $diff->y . ' năm';
	}
	if ($diff->m) {
		$string[] = $diff->m . ' tháng';
	}
	if ($diff->d >= 7) {
		// Tính số tuần
		$weeks = floor($diff->d / 7);
		$string[] = $weeks . ' tuần';
		$diff->d -= $weeks * 7; // Giảm số ngày sau khi đã tính tuần
	}
	if ($diff->d) {
		$string[] = $diff->d . ' ngày';
	}
	if ($diff->h) {
		$string[] = $diff->h . ' giờ';
	}
	if ($diff->i) {
		$string[] = $diff->i . ' phút';
	}
	if ($diff->s) {
		$string[] = $diff->s . ' giây';
	}

	// Nếu không có kết quả nào, trả về "Vừa xong"
	if (empty($string)) {
		return 'Vừa xong';
	}

	// Nếu không muốn hiển thị tất cả các đơn vị thời gian, chỉ hiển thị đơn vị đầu tiên
	if (!$full) {
		return $string[0] . ' trước';
	}

	return implode(', ', $string) . ' trước';
}


// Truy vấn lấy dữ liệu "tweets" từ bảng
$sql_tweets = "SELECT username, twitter_handle, message, timestamp FROM twitter_feed ORDER BY timestamp DESC LIMIT 3";
$result_tweets = $conn->query($sql_tweets);

// Kiểm tra nếu có dữ liệu
if ($result_tweets->num_rows > 0) {
	$tweets = $result_tweets->fetch_all(MYSQLI_ASSOC);
} else {
	$tweets = [];
}


// Cập nhật câu truy vấn bạn bè để tránh trùng lặp và đếm bạn chung chính xác
$sql_friends = "WITH friend_list AS (
    SELECT DISTINCT 
        CASE 
            WHEN user_id = ? THEN friend_id
            WHEN friend_id = ? THEN user_id
        END as friend_id
    FROM friend 
    WHERE (user_id = ? OR friend_id = ?)
    AND status_add = 'accepted'
)
SELECT 
    u.id,
    u.username,
    u.profile_picture,
    'accepted' as status_add,
    (SELECT COUNT(DISTINCT f2.friend_id)
     FROM friend f2 
     WHERE f2.status_add = 'accepted'
     AND f2.friend_id IN (SELECT friend_id FROM friend_list)
     AND f2.friend_id != ?
     AND f2.user_id = ?) as mutual_friends_count
FROM friend_list fl
JOIN users u ON fl.friend_id = u.id
WHERE fl.friend_id IS NOT NULL
LIMIT 5";

$stmt_friends = $conn->prepare($sql_friends);
$stmt_friends->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt_friends->execute();
$result_friends = $stmt_friends->get_result();

// Truy vấn người theo dõi và đang theo dõi (đã loại bỏ trùng lặp)
$sql_followers = "SELECT DISTINCT
    u.id,
    u.username, 
    u.profile_picture,
    CASE WHEN f2.follower_id IS NOT NULL THEN 1 ELSE 0 END as is_following_back,
    CASE WHEN fr.friend_id IS NOT NULL THEN fr.status_add ELSE NULL END as friendship_status
FROM (
    SELECT DISTINCT follower_id 
    FROM follower 
    WHERE user_id = ?
) f
JOIN users u ON f.follower_id = u.id
LEFT JOIN follower f2 ON f2.user_id = f.follower_id AND f2.follower_id = ?
LEFT JOIN friend fr ON (fr.user_id = ? AND fr.friend_id = f.follower_id)
LIMIT 5";

$stmt_followers = $conn->prepare($sql_followers);
$stmt_followers->bind_param('iii', $user_id, $user_id, $user_id);
$stmt_followers->execute();
$result_followers = $stmt_followers->get_result();

// Lấy ID của bài đăng vừa được chèn


// Xử lý các file đính kèm (nếu có)
// Kiểm tra tệp đính kèm và xử lý từng tệp

// File types allowed
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_file_size = 5 * 1024 * 1024; // 5MB
$upload_errors = [];
$success = false;

// Kiểm tra request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['action'])) {
		header('Content-Type: application/json');
		$response = array('success' => false);

		if ($_POST['action'] == 'comment') {
			header('Content-Type: application/json; charset=utf-8'); // Thêm header

			$post_id = $_POST['post_id'];
			$comment = $_POST['comment'];

			try {
				// Thêm thời gian tạo comment
				$sql = "INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('iis', $post_id, $user_id, $comment);

				if ($stmt->execute()) {
					// Lấy thông tin comment vừa tạo kèm thông tin user
					$sql = "SELECT c.*, u.username, u.profile_picture 
                           FROM comments c 
                           JOIN users u ON c.user_id = u.id 
                           WHERE c.comment_id = LAST_INSERT_ID()";
					$result = $conn->query($sql);
					$new_comment = $result->fetch_assoc();

					// Format thời gian
					$new_comment['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($new_comment['created_at']));

					// Cập nhật số lượng comment cho bài viết
					$sql = "UPDATE Newfeeds SET comments_count = comments_count + 1 WHERE post_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('i', $post_id);
					$stmt->execute();

					echo json_encode([
						'success' => true,
						'comment' => $new_comment
					]);
				} else {
					throw new Exception("Database error: " . $stmt->error);
				}
			} catch (Exception $e) {
				error_log("Comment Error: " . $e->getMessage());
				echo json_encode([
					'success' => false,
					'error' => "Không thể thêm bình luận: " . $e->getMessage()
				]);
			}
			exit;
		}
		// Add new case for comment deletion in the POST action handler
		if ($_POST['action'] == 'delete_comment') {
			header('Content-Type: application/json; charset=utf-8');

			$comment_id = $_POST['comment_id'];
			$user_id = $_SESSION['user_id'];

			try {
				// Check if user owns the comment
				$sql = "SELECT c.*, n.post_id FROM comments c 
                        JOIN Newfeeds n ON c.post_id = n.post_id 
                        WHERE c.comment_id = ? AND (c.user_id = ? OR n.user_id = ?)";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('iii', $comment_id, $user_id, $user_id);
				$stmt->execute();
				$result = $stmt->get_result();

				if ($result->num_rows > 0) {
					$comment = $result->fetch_assoc();

					// Delete the comment
					$sql = "DELETE FROM comments WHERE comment_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('i', $comment_id);

					if ($stmt->execute()) {
						// Update comment count
						$sql = "UPDATE Newfeeds SET comments_count = comments_count - 1 WHERE post_id = ?";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param('i', $comment['post_id']);
						$stmt->execute();

						echo json_encode(['success' => true]);
					} else {
						throw new Exception("Failed to delete comment");
					}
				} else {
					throw new Exception("Unauthorized to delete this comment");
				}
			} catch (Exception $e) {
				echo json_encode([
					'success' => false,
					'error' => $e->getMessage()
				]);
			}
			exit;
		}
		// Add these new cases for edit and delete
		if ($_POST['action'] == 'edit_post') {
			$post_id = $_POST['post_id'];
			$content = $_POST['content'];
			$user_id = $_SESSION['user_id'];

			try {
				// Verify post ownership
				$sql = "SELECT user_id FROM Newfeeds WHERE post_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('i', $post_id);
				$stmt->execute();
				$result = $stmt->get_result();
				$post = $result->fetch_assoc();

				if ($post && $post['user_id'] == $user_id) {
					// Update post
					$sql = "UPDATE Newfeeds SET content = ? WHERE post_id = ? AND user_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('sii', $content, $post_id, $user_id);

					if ($stmt->execute()) {
						echo json_encode(['success' => true]);
					} else {
						throw new Exception("Failed to update post");
					}
				} else {
					throw new Exception("Unauthorized to edit this post");
				}
			} catch (Exception $e) {
				echo json_encode(['success' => false, 'error' => $e->getMessage()]);
			}
			exit;
		}

		if ($_POST['action'] == 'delete_post') {
			$post_id = $_POST['post_id'];
			$user_id = $_SESSION['user_id'];

			try {
				// Verify post ownership
				$sql = "SELECT user_id, image_url FROM Newfeeds WHERE post_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('i', $post_id);
				$stmt->execute();
				$result = $stmt->get_result();
				$post = $result->fetch_assoc();

				if ($post && $post['user_id'] == $user_id) {
					// Delete associated images if they exist
					if (!empty($post['image_url'])) {
						$images = explode(',', $post['image_url']);
						foreach ($images as $image) {
							if (file_exists($image)) {
								unlink($image);
							}
						}
					}

					// Delete post
					$sql = "DELETE FROM Newfeeds WHERE post_id = ? AND user_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('ii', $post_id, $user_id);

					if ($stmt->execute()) {
						// Delete associated comments
						$sql = "DELETE FROM comments WHERE post_id = ?";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param('i', $post_id);
						$stmt->execute();

						// Delete associated likes
						$sql = "DELETE FROM likes WHERE post_id = ?";
						$stmt = $conn->prepare($sql);
						$stmt->bind_param('i', $post_id);
						$stmt->execute();

						echo json_encode(['success' => true]);
					} else {
						throw new Exception("Failed to delete post");
					}
				} else {
					throw new Exception("Unauthorized to delete this post");
				}
			} catch (Exception $e) {
				echo json_encode(['success' => false, 'error' => $e->getMessage()]);
			}
			exit;
		}
	} else {
		// Enable error logging
		error_log("Starting post processing");
		error_log("POST data: " . print_r($_POST, true));
		error_log("FILES data: " . print_r($_FILES, true));

		$content = trim($_POST['content'] ?? '');
		$user_id = $_SESSION['user_id'] ?? 0;

		if ($user_id > 0) {
			$conn->begin_transaction();
			try {
				$image_paths = [];

				// Kiểm tra và xử lý upload ảnh
				if (!empty($_FILES['attachments']['name'][0])) {
					$upload_dir = 'uploads/posts/';

					// Tạo thư mục nếu chưa tồn tại
					if (!file_exists($upload_dir)) {
						mkdir($upload_dir, 0777, true);
					}

					foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
						if (empty($tmp_name)) continue;

						$file_name = $_FILES['attachments']['name'][$key];
						$file_size = $_FILES['attachments']['size'][$key];
						$file_tmp = $_FILES['attachments']['tmp_name'][$key];
						$file_type = $_FILES['attachments']['type'][$key];
						$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

						error_log("Processing file: " . $file_name);

						// Kiểm tra định dạng file
						if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
							continue;
						}

						// Tạo tên file mới để tránh trùng lặp
						$new_filename = uniqid('post_') . '.' . $file_ext;
						$file_path = $upload_dir . $new_filename;

						if (move_uploaded_file($file_tmp, $file_path)) {
							$image_paths[] = $file_path;
							error_log("Successfully uploaded: " . $file_path);
						} else {
							error_log("Failed to move uploaded file: " . $file_tmp . " to " . $file_path);
						}
					}
				}

				// Chuẩn bị image_url cho database
				$image_urls = !empty($image_paths) ? implode(',', $image_paths) : null;
				error_log("Final image_urls: " . print_r($image_urls, true));

				// Insert vào database
				$sql = "INSERT INTO Newfeeds (user_id, content, image_url, published_at) VALUES (?, ?, ?, NOW())";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('iss', $user_id, $content, $image_urls);

				if (!$stmt->execute()) {
					throw new Exception("Failed to create post: " . $stmt->error);
				}

				$conn->commit();
				$success = true;

				// Chuyển hướng sau khi đăng thành công
				header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
				exit;
			} catch (Exception $e) {
				$conn->rollback();
				error_log("Error in post creation: " . $e->getMessage());
				$upload_errors[] = $e->getMessage();
			}
		}
	}
}

// Truy vấn lấy danh sách các stories
$sql_stories = "
    SELECT Stories.story_id, Stories.image_url, Stories.created_at, Stories.expires_at, 
           Users.username, Users.profile_picture 
    FROM Stories 
    INNER JOIN Users ON Stories.user_id = Users.id
    WHERE Stories.expires_at > NOW()
    ORDER BY Stories.created_at DESC
"; // giới hạn hiển thị 6 stories
$result = $conn->query($sql_stories);
if (!empty($_FILES['story_image']['name'])) {
	$user_id = $_SESSION['user_id']; // Giả sử bạn có user_id từ session
	$file_tmp = $_FILES['story_image']['tmp_name'];
	$file_name = uniqid() . '_' . basename($_FILES['story_image']['name']);
	$upload_dir = 'uploads/stories/';
	$upload_file = $upload_dir . $file_name;

	if (move_uploaded_file($file_tmp, $upload_file)) {
		$sql = "INSERT INTO stories (user_id, image_url, created_at) VALUES (?, ?, NOW())";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('is', $user_id, $file_name);
		$stmt->execute();
	}
}



// Giới hạn số bài viết hiển thị (ví dụ: 5 bài)
$limit = 10;

// Cập nhật câu truy vấn lấy bài viết, sắp xếp theo thời gian mới nhất
$sql_posts = "
    SELECT n.*, u.username, u.profile_picture,
           (SELECT COUNT(*) FROM likes WHERE post_id = n.post_id) as likes_count,
           (SELECT COUNT(*) FROM comments WHERE post_id = n.post_id) as comments_count,
           n.image_url as post_images
    FROM Newfeeds n
    JOIN users u ON n.user_id = u.id
    ORDER BY n.published_at DESC 
    LIMIT $limit
";

$posts = [];
if ($result_posts = $conn->query($sql_posts)) {
	while ($post = $result_posts->fetch_assoc()) {
		// Fetch comments for each post
		$sql_comments = "
            SELECT c.*, u.username, u.profile_picture 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.post_id = ?
            ORDER BY c.created_at DESC
        ";
		$stmt_comments = $conn->prepare($sql_comments);
		$stmt_comments->bind_param('i', $post['post_id']);
		$stmt_comments->execute();
		$result_comments = $stmt_comments->get_result();

		$post['comments'] = [];
		while ($comment = $result_comments->fetch_assoc()) {
			$post['comments'][] = $comment;
		}

		$posts[] = $post;
		$stmt_comments->close();
	}
}

// Thêm xử lý like và comment qua AJAX
if (isset($_POST['action'])) {
	$response = array();

	if ($_POST['action'] == 'like') {
		$post_id = $_POST['post_id'];
		$sql = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param('ii', $post_id, $user_id);
		if ($stmt->execute()) {
			$response['success'] = true;
		};
		echo json_encode($response);
		exit;
	}

	if ($_POST['action'] == 'comment') {
		header('Content-Type: application/json; charset=utf-8'); // Thêm header

		$post_id = $_POST['post_id'];
		$comment = $_POST['comment'];

		try {
			// Thêm thời gian tạo comment
			$sql = "INSERT INTO comments (post_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('iis', $post_id, $user_id, $comment);

			if ($stmt->execute()) {
				// Lấy thông tin comment vừa tạo kèm thông tin user
				$sql = "SELECT c.*, u.username, u.profile_picture 
                       FROM comments c 
                       JOIN users u ON c.user_id = u.id 
                       WHERE c.comment_id = LAST_INSERT_ID()";
				$result = $conn->query($sql);
				$new_comment = $result->fetch_assoc();

				// Format thời gian
				$new_comment['created_at_formatted'] = date('Y-m-d H:i:s', strtotime($new_comment['created_at']));

				// Cập nhật số lượng comment cho bài viết
				$sql = "UPDATE Newfeeds SET comments_count = comments_count + 1 WHERE post_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('i', $post_id);
				$stmt->execute();

				echo json_encode([
					'success' => true,
					'comment' => $new_comment
				]);
			} else {
				throw new Exception("Database error: " . $stmt->error);
			}
		} catch (Exception $e) {
			error_log("Comment Error: " . $e->getMessage());
			echo json_encode([
				'success' => false,
				'error' => "Không thể thêm bình luận: " . $e->getMessage()
			]);
		}
		exit;
	}
	// Add new case for comment deletion in the POST action handler
	if ($_POST['action'] == 'delete_comment') {
		header('Content-Type: application/json; charset=utf-8');

		$comment_id = $_POST['comment_id'];
		$user_id = $_SESSION['user_id'];

		try {
			// Check if user owns the comment
			$sql = "SELECT c.*, n.post_id FROM comments c 
                    JOIN Newfeeds n ON c.post_id = n.post_id 
                    WHERE c.comment_id = ? AND (c.user_id = ? OR n.user_id = ?)";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('iii', $comment_id, $user_id, $user_id);
			$stmt->execute();
			$result = $stmt->get_result();

			if ($result->num_rows > 0) {
				$comment = $result->fetch_assoc();

				// Delete the comment
				$sql = "DELETE FROM comments WHERE comment_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('i', $comment_id);

				if ($stmt->execute()) {
					// Update comment count
					$sql = "UPDATE Newfeeds SET comments_count = comments_count - 1 WHERE post_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('i', $comment['post_id']);
					$stmt->execute();

					echo json_encode(['success' => true]);
				} else {
					throw new Exception("Failed to delete comment");
				}
			} else {
				throw new Exception("Unauthorized to delete this comment");
			}
		} catch (Exception $e) {
			echo json_encode([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
		exit;
	}
	if ($_POST['action'] == 'edit_post') {
		$post_id = $_POST['post_id'];
		$content = $_POST['content'];
		$user_id = $_SESSION['user_id'];

		try {
			// Verify post ownership
			$sql = "SELECT user_id FROM Newfeeds WHERE post_id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('i', $post_id);
			$stmt->execute();
			$result = $stmt->get_result();
			$post = $result->fetch_assoc();

			if ($post && $post['user_id'] == $user_id) {
				// Update post
				$sql = "UPDATE Newfeeds SET content = ? WHERE post_id = ? AND user_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('sii', $content, $post_id, $user_id);

				if ($stmt->execute()) {
					echo json_encode(['success' => true]);
				} else {
					throw new Exception("Failed to update post");
				}
			} else {
				throw new Exception("Unauthorized to edit this post");
			}
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'error' => $e->getMessage()]);
		}
		exit;
	}

	if ($_POST['action'] == 'delete_post') {
		$post_id = $_POST['post_id'];
		$user_id = $_SESSION['user_id'];

		try {
			// Verify post ownership
			$sql = "SELECT user_id, image_url FROM Newfeeds WHERE post_id = ?";
			$stmt = $conn->prepare($sql);
			$stmt->bind_param('i', $post_id);
			$stmt->execute();
			$result = $stmt->get_result();
			$post = $result->fetch_assoc();

			if ($post && $post['user_id'] == $user_id) {
				// Delete associated images if they exist
				if (!empty($post['image_url'])) {
					$images = explode(',', $post['image_url']);
					foreach ($images as $image) {
						if (file_exists($image)) {
							unlink($image);
						}
					}
				}

				// Delete post
				$sql = "DELETE FROM Newfeeds WHERE post_id = ? AND user_id = ?";
				$stmt = $conn->prepare($sql);
				$stmt->bind_param('ii', $post_id, $user_id);

				if ($stmt->execute()) {
					// Delete associated comments
					$sql = "DELETE FROM comments WHERE post_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('i', $post_id);
					$stmt->execute();

					// Delete associated likes
					$sql = "DELETE FROM likes WHERE post_id = ?";
					$stmt = $conn->prepare($sql);
					$stmt->bind_param('i', $post_id);
					$stmt->execute();

					echo json_encode(['success' => true]);
				} else {
					throw new Exception("Failed to delete post");
				}
			} else {
				throw new Exception("Unauthorized to delete this post");
			}
		} catch (Exception $e) {
			echo json_encode(['success' => false, 'error' => $e->getMessage()]);
		}
		exit;
	}
}

// Truy vấn để lấy danh sách người dùng có thể kết bạn
$sql_potential_friends = "SELECT u.id, u.username, u.profile_picture,
    CASE 
        WHEN fr.status = 'pending' THEN 'pending'
        WHEN f.status_add = 'accepted' THEN 'accepted'
        ELSE NULL
    END as friendship_status
    FROM users u
    LEFT JOIN friend_requests fr ON (fr.sender_id = ? AND fr.receiver_id = u.id) 
        OR (fr.receiver_id = ? AND fr.sender_id = u.id)
    LEFT JOIN friend f ON (f.user_id = ? AND f.friend_id = u.id)
        OR (f.friend_id = ? AND f.user_id = u.id)
    WHERE u.id != ? 
    AND (f.status_add IS NULL OR f.status_add != 'accepted')
    AND (fr.status IS NULL OR fr.status = 'pending')
    LIMIT 5";

$stmt_potential = $conn->prepare($sql_potential_friends);
$stmt_potential->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt_potential->execute();
$potential_friends = $stmt_potential->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt_intro->close();
$stmt_messages->close();
$stmt_notifications->close();
$stmt_likes->close();
$stmt_views->close();
$stmt_friends->close();
$stmt_followers->close();
$conn->close();
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
	<link rel="stylesheet" href="css/weather-icons.min.css">
	<link rel="stylesheet" href="css/toast-notification.css">
	<link rel="stylesheet" href="css/page-tour.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/color.css">
	<link rel="stylesheet" href="css/responsive.css">
	<style>
		.comment-item {
			display: flex;
			gap: 8px;
			margin-bottom: 12px;
		}

		.comet-avatar {
			flex-shrink: 0;
		}

		.comet-avatar img {
			width: 32px !important;
			/* Reduced from 40px */
			height: 32px !important;
			/* Reduced from 40px */
			border-radius: 50%;
			object-fit: cover;
		}

		.we-comment {
			flex-grow: 1;
			background: #f8f9fa;
			border-radius: 8px;
			padding: 8px;
		}

		.we-comment h5 {
			font-size: 13px;
			/* Reduced from default size */
			margin: 0 0 5px 0;
		}

		.we-comment p {
			font-size: 13px;
			margin: 5px 0;
		}

		.comment-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.inline-itms {
			font-size: 12px;
			color: #666;
			margin-top: 5px;
		}

		.inline-itms a {
			margin-left: 8px;
		}

		.comment-form textarea {
			font-size: 13px;
			padding: 8px;
			height: 36px;
			min-height: 36px;
		}

		.btn-submit {
			padding: 6px 15px;
			font-size: 13px;
		}

		.comment-box-wrapper {
			display: flex;
			align-items: flex-start;
			gap: 10px;
			width: 100%;
		}

		.comment-form textarea {
			flex: 1;
			min-height: 40px;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 4px;
			resize: vertical;
		}

		.btn-submit {
			background: #ff5e3a;
			color: white;
			border: none;
			padding: 8px 20px;
			border-radius: 4px;
			cursor: pointer;
			font-weight: 500;
			margin-top: 0;
			min-width: 60px;
			height: fit-content;
		}

		.delete-comment {
			background: none;
			border: none;
			color: #ff5e3a;
			cursor: pointer;
			padding: 4px 8px;
			opacity: 0.7;
			transition: opacity 0.2s;
		}

		.delete-comment:hover {
			opacity: 1;
		}

		.comment-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.post-comt-box {
			width: 100%;
			padding: 10px;
		}

		.btn-submit:hover {
			background: #066fa1;
		}
	</style>
	<script>
		function previewFiles(input) {
			const previewBox = document.getElementById('preview-box');
			const previewImages = previewBox.querySelector('.preview-images');
			previewImages.innerHTML = '';
			previewBox.style.display = 'block';

			Array.from(input.files).forEach(file => {
				if (file.type.startsWith('image/')) {
					const reader = new FileReader();
					reader.onload = function(e) {
						const img = document.createElement('img');
						img.src = e.target.result;
						img.style.maxWidth = '200px';
						img.style.margin = '5px';
						previewImages.appendChild(img);
					}
					reader.readAsDataURL(file);
				}
			});
		}
	</script>

	<style>
		.preview-images {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
			margin-top: 10px;
		}

		.preview-images img {
			max-width: 200px;
			height: auto;
			border-radius: 8px;
		}

		#preview-box {
			margin: 15px 0;
			padding: 10px;
			border: 1px solid #ddd;
			border-radius: 8px;
		}
	</style>
</head>

<body>
	<!-- <div class="wavy-wraper">
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
	</div> -->
	<div class="theme-layout">
		<div class="postoverlay"></div>
		<!-- Header -->
		<?php include 'component/header.php'; ?>
		<!-- END HEADER	 -->

		<!-- SIDEBAR RIGHT -->
		<?php include 'component/sidebarright.php'; ?>
		<!-- END SIDEBAR RIGHT -->

		<!-- SIDEBAR LEFT -->
		<?php include 'component/sidebarleft.php'; ?>
		<!-- END SIDEBAR LEFT -->
		<section>
			<div class="gap2 gray-bg">
				<div class="container">
					<div class="row">
						<div class="col-lg-12">
							<div class="row merged20" id="page-contents">
								<!-- Left Sidebar -->
								<div class="col-lg-3">
									<aside class="sidebar static left">
										<div class="widget">
											<div class="weather-widget low-opacity bluesh">
												<div class="bg-image" style="background-image: url(images/resources/weather.jpg)"></div>
												<span class="refresh-content" onclick="getWeather()"><i class="fa fa-refresh"></i></span>
												<div class="weather-week">
													<div class="icon sun-shower">
														<div class="cloud"></div>
														<div class="sun">
															<div class="rays"></div>
														</div>
														<div class="rain"></div>
													</div>
												</div>
												<div class="weather-infos">
													<span class="weather-tem">--</span>
													<h3 id="weather-desc">Loading weather...<i>Hanoi, Vietnam</i></h3>
													<div class="weather-date skyblue-bg">
														<span id="weather-date">---<strong>--</strong></span>
													</div>
												</div>
												<div class="monthly-weather">
													<ul id="weekly-forecast">
														<!-- Dự báo thời tiết hàng ngày sẽ được thêm vào đây bằng JavaScript -->
													</ul>
												</div>
											</div>
										</div>
										<script>
											document.addEventListener('DOMContentLoaded', function() {
												const apiKey = 'da9f770c8cb4fa4c253c1aa2523dfbf1';
												const city = 'Hanoi';

												async function getWeather() {
													try {
														// Hiển thị trạng thái loading
														document.querySelector('.weather-tem').textContent = '--';
														document.getElementById('weather-desc').innerHTML = 'Loading...<i>Hanoi, Vietnam</i>';

														// Lấy thời tiết hiện tại
														const currentResponse = await fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`);

														// Lấy dự báo 5 ngày
														const forecastResponse = await fetch(`https://api.openweathermap.org/data/2.5/forecast?q=${city}&appid=${apiKey}&units=metric`);

														if (!currentResponse.ok || !forecastResponse.ok) {
															throw new Error('Network response was not ok');
														}

														const currentData = await currentResponse.json();
														const forecastData = await forecastResponse.json();

														// Cập nhật thời tiết hiện tại
														const temp = Math.round(currentData.main.temp);
														const description = currentData.weather[0].description;
														const date = new Date();
														const day = date.getDate();
														const month = date.toLocaleString('default', {
															month: 'short'
														});

														document.querySelector('.weather-tem').textContent = `${temp}°`;
														document.getElementById('weather-desc').innerHTML = `${description}<i>Hanoi, Vietnam</i>`;
														document.getElementById('weather-date').innerHTML = `${month.toUpperCase()}<strong>${day}</strong>`;

														// Cập nhật dự báo hàng ngày
														const weeklyForecast = document.getElementById('weekly-forecast');
														weeklyForecast.innerHTML = ''; // Xóa nội dung cũ

														// Lấy dự báo cho mỗi ngày (mỗi 24h)
														const dailyForecasts = forecastData.list.filter((item, index) => index % 8 === 0).slice(0, 7);

														const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

														dailyForecasts.forEach(forecast => {
															const forecastDate = new Date(forecast.dt * 1000);
															const dayName = days[forecastDate.getDay()];
															const temp = Math.round(forecast.main.temp);
															const weather = forecast.weather[0].main;

															// Chọn icon dựa trên thời tiết
															let weatherIcon = 'wi-day-sunny';
															if (weather.includes('Rain')) weatherIcon = 'wi-rain';
															else if (weather.includes('Cloud')) weatherIcon = 'wi-cloudy';
															else if (weather.includes('Snow')) weatherIcon = 'wi-snow';

															const forecastHtml = `
          <li>
            <span>${dayName}</span>
            <a href="#" title="${weather}"><i class="wi ${weatherIcon}"></i></a>
            <em>${temp}°</em>
          </li>
        `;

															weeklyForecast.innerHTML += forecastHtml;
														});

													} catch (error) {
														console.error('Weather API Error:', error);
														document.querySelector('.weather-tem').textContent = 'Error';
														document.getElementById('weather-desc').innerHTML = 'Failed to load weather<i>Hanoi, Vietnam</i>';
													}
												}

												// Gọi lần đầu khi tải trang
												getWeather();

												// Tự động cập nhật mỗi 30 phút
												setInterval(getWeather, 30 * 60 * 1000);
											});
										</script>
										<?php if (!empty($birthday_data)): ?>
											<div class="widget whitish low-opacity">
												<div style="background-image: url('<?php echo $birthday_data['background_image']; ?>')" class="bg-image"></div>
												<div class="dob-head">
													<img src="<?php echo $birthday_data['profile_image']; ?>" alt="">
													<span><?php echo $birthday_data['event_name']; ?></span>
													<div class="dob">
														<i><?php echo $birthday_data['day']; ?></i>
														<span><?php echo $birthday_data['month']; ?></span>
													</div>
												</div>
												<div class="dob-meta">
													<figure><img src="images/resources/dob-cake.gif" alt=""></figure>
													<h6><a href="#" title=""><?php echo $birthday_data['user_name']; ?></a> <?php echo $birthday_data['event_name']; ?></h6>
													<p><?php echo $birthday_data['birthday_message']; ?></p>
												</div>
											</div><!-- birthday widget -->
										<?php else: ?>
											<p>Không có dữ liệu sinh nhật để hiển thị.</p>
										<?php endif; ?>
										<div class="widget">
											<h4 class="widget-title">Bảng tin Twitter</h4>
											<ul class="twiter-feed">
												<?php foreach ($tweets as $tweet): ?>
													<li>
														<i class="fa fa-twitter"></i>
														<span>
															<i><?php echo htmlspecialchars($tweet['username']); ?></i>
															@<?php echo htmlspecialchars($tweet['twitter_handle']); ?>
														</span>
														<p>
															<?php echo htmlspecialchars($tweet['message']); ?>
															<a href="#" title="">#laptrinhphp</a>
														</p>
														<em><?php echo htmlspecialchars($tweet['timestamp']); ?></em>
													</li>
												<?php endforeach; ?>
											</ul>
										</div><!-- twitter feed-->
										<script>
											// Array of image sources
											let bannerImages = [
												"images/anhbanner1.jpeg",
												"images/anhbanner2.jpeg",
												"images/anhbanner3.jpeg",
												"images/anhbanner4.jpg",
												"images/anhbanner5.png",
												"images/anhbanner6.png",
												"images/anhbanner7.jpeg",
												"images/anhbanner8.jpg",
												"images/anhbanner9.jpg",
												"images/anhbanner10.png",
												"images/anhbanner11.jpg",
												"images/anhbanner12.jpg"
											];

											let currentIndex = 0; // Start with the first image

											// Function to change the image
											function changeBannerImage() {
												currentIndex++; // Move to the next image
												if (currentIndex >= bannerImages.length) {
													currentIndex = 0; // Loop back to the first image
												}
												document.getElementById("bannerImage").src = bannerImages[currentIndex];
											}

											// Set interval to change image every 3 seconds
											setInterval(changeBannerImage, 3000);
										</script>
										<div class="advertisment-box">
											<h4 class="">advertisment</h4>
											<figure>
												<a href="https://www1.napa.vn/" target="_blank" title="Advertisment">
													<img id="bannerImage" src="images/anhbanner1.jpeg" alt="Quảng cáo">
												</a>
											</figure>
										</div>
										<div class="widget">
											<h4 class="widget-title">Lối tắt</h4>
											<ul class="naves">
												<li>
													<i class="ti-clipboard"></i>
													<a href="index.php" title="">Bảng tin</a>
												</li>

												<li>
													<i class="ti-files"></i>
													<a href="about.php" title="">Trang của tôi</a>
												</li>
												<li>
													<i class="ti-user"></i>
													<a href="timeline_friends.php" title="">Bạn bè</a>
												</li>
												<li>
													<i class="ti-image"></i>
													<a href="timeline_photos.php" title="">Ảnh</a>
												</li>
												<li>
													<i class="ti-video-camera"></i>
													<a href="timeline_videos.php" title="">Videos</a>
												</li>
												<li>
													<i class="ti-comments-smiley"></i>
													<a href="messages.php" title="">Tin nhắn</a>
													<ins><?php echo $total_messages; ?></ins>
												</li>
												<li>
													<i class="ti-bell"></i>
													<a href="notifications.php" title="">Thông báo</a>
													<ins><?php echo $total_notifications; ?></ins>
												</li>
												<li>
													<i class="ti-power-off"></i>
													<a href="logout.php" title="">Đăng xuất</a>
												</li>
											</ul>
										</div><!-- Shortcuts -->
										<div class="widget">
											<h4 class="widget-title">Hoạt động gần đây</h4>
											<ul class="activitiez">
												<?php while ($activity = $result_activity->fetch_assoc()): ?>
													<li>
														<div class="activity-meta">
															<i><?php echo time_elapsed_string($activity['activity_date']); ?></i>
															<span><a href="<?php echo $activity['reference_link']; ?>" title=""><?php echo $activity['activity_content']; ?></a></span>
														</div>
													</li>
												<?php endwhile; ?>
											</ul>
										</div>
										<!-- recent activites -->
										<!-- Hiển thị bạn bè -->
										<div class="widget stick-widget">
											<h4 class="widget-title">Đang theo dõi</h4>
											<ul class="followers">
												<?php
												$widget_conn = new mysqli('localhost', 'root', '', 'ledai_forum');
												if ($widget_conn->connect_error) {
													die("Kết nối widget thất bại: " . $widget_conn->connect_error);
												}

												if (!isset($result_friends)) {
													$sql_friends = "WITH friend_list AS (
														SELECT DISTINCT 
															CASE 
																WHEN user_id = ? THEN friend_id
																WHEN friend_id = ? THEN user_id
															END as friend_id
														FROM friend 
														WHERE (user_id = ? OR friend_id = ?)
														AND status_add = 'accepted'
													)
													SELECT 
														u.id,
														u.username,
														u.profile_picture,
														'accepted' as status_add,
														(SELECT COUNT(DISTINCT f2.friend_id)
														FROM friend f2 
														WHERE f2.status_add = 'accepted'
														AND f2.friend_id IN (SELECT friend_id FROM friend_list)
														AND f2.friend_id != ?
														AND f2.user_id = ?) as mutual_friends_count
													FROM friend_list fl
													JOIN users u ON fl.friend_id = u.id
													WHERE fl.friend_id IS NOT NULL
													LIMIT 5";

													$stmt_friends = $widget_conn->prepare($sql_friends);
													$stmt_friends->bind_param('iiiiii', $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']);
													$stmt_friends->execute();
													$result_friends = $stmt_friends->get_result();
													$stmt_friends->close();
												}

												while ($friend = $result_friends->fetch_assoc()) {
													$friend_id = $friend['id'];
													$sql_check = "SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'";
													$stmt_check = $widget_conn->prepare($sql_check);
													$stmt_check->bind_param("ii", $_SESSION['user_id'], $friend_id);
													$stmt_check->execute();
													$check_result = $stmt_check->get_result();
													$request_sent = $check_result->num_rows > 0;
													$stmt_check->close();
												?>
													<li data-friend-id="<?php echo $friend_id; ?>">
														<figure><img src="<?php echo htmlspecialchars($friend['profile_picture'] ?: 'images/resources/author.jpg'); ?>" alt=""></figure>
														<div class="friend-meta">
															<h4><a href="timeline.php" title=""><?php echo htmlspecialchars($friend['username']); ?></a></h4>
															<p>Bạn chung <?php echo htmlspecialchars($friend['mutual_friends_count']); ?></p>
															<span class="friend-status">
																<?php if ($friend['status_add'] === 'accepted'): ?>
																	<p class="underline">Bạn bè</p>
																<?php elseif ($request_sent): ?>
																	<p class="underline">Yêu cầu đã gửi</p>  
																<?php else: ?>
																	<button class="add-friend-btn underline" data-receiver-id="<?php echo $friend_id; ?>" style="background:none;border:none;padding:0;color:#069;text-decoration:underline;">
																		Add Friend
																	</button>
																<?php endif; ?>
															</span>
														</div>
													</li>
												<?php
												}
												$widget_conn->close();
												?>
											</ul>
										</div>

										<!-- Script AJAX cho Add Friend -->
										<script>
										// Xử lý từ chối yêu cầu kết bạn
										const rejectButtons = document.querySelectorAll('.reject-friend-request');
											rejectButtons.forEach(button => {
												button.addEventListener('click', function(e) {
													e.preventDefault();
													const senderId = this.getAttribute('data-sender-id');
													const notificationDiv = this.closest('.notification');
													const notificationId = notificationDiv.getAttribute('data-notification-id');

													if (!confirm('Bạn có chắc chắn muốn từ chối yêu cầu này?')) return;

													fetch('reject_friend_request.php', {
														method: 'POST',
														headers: {
															'Content-Type': 'application/x-www-form-urlencoded',
														},
														body: `sender_id=${senderId}&notification_id=${notificationId}`
													})
													.then(response => response.json())
													.then(data => {
														if (data.success) {
															notificationDiv.remove();
															const countElement = document.querySelector('.setting-area li:nth-child(3) em');
															let count = parseInt(countElement.textContent) - 1;
															countElement.textContent = count > 0 ? count : '';

															// Cập nhật widget "Đang theo dõi" nếu có
															const friendItem = document.querySelector(`.followers li[data-friend-id="${senderId}"]`);
															if (friendItem) {
																const statusSpan = friendItem.querySelector('.friend-request-status');
																statusSpan.innerHTML = `
																	<button class="add-friend-btn underline" data-receiver-id="${senderId}" style="background:none;border:none;padding:0;color:#069;text-decoration:underline;">
																		Add Friend
																	</button>`;
																const newButton = statusSpan.querySelector('.add-friend-btn');
																newButton.addEventListener('click', function(e) {
																	e.preventDefault();
																	const receiverId = this.getAttribute('data-receiver-id');
																	const newStatusSpan = this.closest('.friend-meta').querySelector('.friend-request-status');

																	this.disabled = true;
																	this.textContent = 'Đang gửi...';

																	fetch('send_friend_request.php', {
																		method: 'POST',
																		headers: {
																			'Content-Type': 'application/x-www-form-urlencoded',
																		},
																		body: `receiver_id=${receiverId}&send_request=1`
																	})
																	.then(response => response.json())
																	.then(data => {
																		if (data.success) {
																			newStatusSpan.innerHTML = '<p class="underline">Yêu cầu đã gửi</p>';
																			alert('Yêu cầu kết bạn đã được gửi!');
																		} else {
																			throw new Error(data.message || 'Không thể gửi yêu cầu');
																		}
																	})
																	.catch(error => {
																		console.error('Error:', error);
																		alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
																		this.disabled = false;
																		this.textContent = 'Add Friend';
																	});
																});
															}
														} else {
															throw new Error(data.message || 'Không thể từ chối yêu cầu');
														}
													})
													.catch(error => {
														console.error('Error:', error);
														alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
													});
												});
											});
										document.addEventListener('DOMContentLoaded', function() {
											const addFriendButtons = document.querySelectorAll('.add-friend-btn');

											addFriendButtons.forEach(button => {
												button.addEventListener('click', function(e) {
													e.preventDefault();
													const receiverId = this.getAttribute('data-receiver-id');
													const statusSpan = this.closest('.friend-meta').querySelector('.friend-request-status');

													button.disabled = true;
													button.textContent = 'Đang gửi...';

													fetch('send_friend_request.php', {
														method: 'POST',
														headers: {
															'Content-Type': 'application/x-www-form-urlencoded',
														},
														body: `receiver_id=${receiverId}&send_request=1`
													})
													.then(response => response.json())
													.then(data => {
														if (data.success) {
															statusSpan.innerHTML = '<p class="underline">Yêu cầu đã gửi</p>';
															alert('Yêu cầu kết bạn đã được gửi!');
														} else {
															throw new Error(data.message || 'Không thể gửi yêu cầu');
														}
													})
													.catch(error => {
														console.error('Error:', error);
														alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
														button.disabled = false;
														button.textContent = 'Add Friend';
													});
												});
											});
										});
										</script>
										<!-- Hiển thị người theo dõi -->
										<div class="widget">
											<h4 class="widget-title">Theo dõi lại</h4>
											<ul class="followers">
												<?php
												while ($follower = $result_followers->fetch_assoc()) {
												?>
													<li>
														<figure><img src="<?php echo htmlspecialchars($follower['profile_picture']); ?>" alt=""></figure>
														<div class="friend-meta">
															<h4><a href="timeline.php" title=""><?php echo htmlspecialchars($follower['username']); ?></a></h4>
															<a href="#" title="" class="underline">Follow back</a>
														</div>
													</li>
												<?php
												}
												?>
											</ul>
										</div>
										<!-- Thay đổi phần hiển thị widget Đang theo dõi -->
										<div class="widget stick-widget">
											<h4 class="widget-title">Gợi ý kết bạn</h4>
											<ul class="followers">
												<?php if (!empty($potential_friends)): ?>
													<?php foreach ($potential_friends as $potential_friend): ?>
														<li data-friend-id="<?php echo $potential_friend['id']; ?>">
															<figure>
																<img src="<?php echo htmlspecialchars($potential_friend['profile_picture'] ?: 'images/resources/author.jpg'); ?>" alt="">
															</figure>
															<div class="friend-meta">
																<h4>
																	<a href="timeline.php" title=""><?php echo htmlspecialchars($potential_friend['username']); ?></a>
																</h4>
																<span class="friend-request-status">
																	<?php if ($potential_friend['friendship_status'] === 'accepted'): ?>
																		<p class="underline">Bạn bè</p>
																	<?php elseif ($potential_friend['friendship_status'] === 'pending'): ?>
																		<p class="underline">Đã gửi yêu cầu</p>
																	<?php else: ?>
																		<button class="add-friend-btn underline" 
																				data-receiver-id="<?php echo $potential_friend['id']; ?>"
																				style="background:none;border:none;padding:0;color:#069;text-decoration:underline;">
																			Kết bạn
																		</button>
																	<?php endif; ?>
																</span>
															</div>
														</li>
													<?php endforeach; ?>
												<?php else: ?>
													<li>
														<div class="friend-meta">
															<p>Không có gợi ý kết bạn nào.</p>
														</div>
													</li>
												<?php endif; ?>
											</ul>
										</div>
									</aside>
								</div><!-- sidebar -->
								<!-- Central Content Area -->
								<div class="col-lg-6">
									<div class="central-meta postbox">
										<span class="create-post">Đăng tin</span>
										<div class="new-postbox">
											<figure>
												<!-- <img src="uploads/profile_pictures/<?php echo htmlspecialchars($user['profile_picture'] ?? 'default-avatar.jpg'); ?>" alt="Profile Picture"> -->
											</figure>
											<div class="newpst-input">
												<?php if (!empty($upload_errors)): ?>
													<div class="alert alert-danger">
														<?php foreach ($upload_errors as $error): ?>
															<p><?php echo htmlspecialchars($error); ?></p>
														<?php endforeach; ?>
													</div>
												<?php endif; ?>
												<?php if ($success): ?>
													<div class="alert alert-success">
														<p>Đăng tin thành công!</p>
													</div>
												<?php endif; ?>

												<form method="post" enctype="multipart/form-data" id="postForm" class="newpst-input">
													<textarea name="content" rows="2" placeholder="Bạn đang nghĩ gì?"></textarea>

													<!-- Thêm div xem trước -->
													<div id="preview-box" style="display: none;" class="post-preview-box">
														<h4>Xem trước bài đăng</h4>
														<div class="preview-content"></div>
														<div class="preview-images"></div>
													</div>
													<div class="attachments">
														<ul>
															<li>
																<span class="add-loc">
																	<i class="fa fa-map-marker"></i>
																</span>
															</li>
															<li>
																<i class="fa fa-music"></i>
																<label class="fileContainer">
																	<input type="file" name="attachments[]" multiple>
																</label>
															</li>
															<li>
																<i class="fa fa-image"></i>
																<label class="fileContainer">
																	<input type="file" name="attachments[]" accept="image/*" multiple onchange="previewFiles(this)">
																</label>
															</li>
															<li>
																<i class="fa fa-camera"></i>
																<label class="fileContainer">
																	<input type="file" name="photos[]" accept="image/*" capture="camera">
																</label>
															</li>
															<li class="preview-btn">
																<button class="post-btn-preview" type="button" data-ripple="" onclick="previewPost()">Xem trước</button>
															</li>
														</ul>
														<button class="post-btn" type="submit" data-ripple="">Đăng</button>
													</div>
												</form>
											</div>
											<div class="add-location-post">
												<span>Drag map point to selected area</span>
												<div class="row">

													<div class="col-lg-6">
														<label class="control-label">Lat :</label>
														<input type="text" class="" id="us3-lat" />
													</div>
													<div class="col-lg-6">
														<label>Long :</label>
														<input type="text" class="" id="us3-lon" />
													</div>
												</div>
												<!-- map -->
												<div id="us3"></div>
											</div>
										</div><!-- add post new box -->
										<div class="central-meta">
											<span class="create-post">Tin gần đây <a href="all_stories.php" title="">Xem tất cả</a></span>
											<div class="story-postbox">
												<div class="row">
													<!-- Story của người dùng thêm mới -->
													<div class="col-lg-3 col-md-3 col-sm-3">
														<div class="story-box">
															<figure>
																<img src="images/resources/story-1.jpg" alt="">
																<span>Tin của bạn</span>
															</figure>
															<!-- Tạo dấu + mở form tải lên -->
															<div class="story-thumb" data-toggle="tooltip" title="Add Your Story" style="cursor: pointer;" onclick="document.getElementById('storyUploadInput').click()">
																<i class="fa fa-plus"></i>
															</div>
															<!-- Input file ẩn để tải lên ảnh -->
															<form method="post" enctype="multipart/form-data" style="display: none;">
																<input id="storyUploadInput" type="file" name="story_image" onchange="this.form.submit()" style="display: none;">
															</form>
														</div>
													</div>
													<!-- Lặp qua từng story từ cơ sở dữ liệu -->
													<?php while ($story = $result->fetch_assoc()): ?>
														<div class="col-lg-3 col-md-3 col-sm-3">
															<div class="story-box">
																<figure>
																	<img src="uploads/stories/<?php echo htmlspecialchars($story['image_url']); ?>" alt="">
																	<span><?php echo htmlspecialchars($story['username']); ?></span>
																</figure>
																<div class="story-thumb" data-toggle="tooltip" title="<?php echo htmlspecialchars($story['username']); ?>">
																	<img src="uploads/profile_pictures/<?php echo htmlspecialchars($story['profile_picture']); ?>" alt="">
																</div>
															</div>
														</div>
													<?php endwhile; ?>
												</div>
											</div>
										</div>

										<div class="loadMore">
											<?php foreach ($posts as $post): ?>
												<div class="central-meta item" data-post-id="<?php echo $post['post_id']; ?>">
													<div class="user-post">
														<div class="friend-info">
															<figure>
																<!-- Hiển thị ảnh đại diện người dùng -->
																<img src="<?php echo $post['profile_picture']; ?>" alt="Avatar"
																	style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
															</figure>
															<div class="friend-name">
																<div class="more">
																	<div class="more-post-optns"><i class="ti-more-alt"></i>
																		<ul>
																			<?php if ($post['user_id'] == $user_id): ?>
																				<li><a href="javascript:void(0);" onclick="editPost('<?php echo $post['post_id']; ?>', this)"><i class="fa fa-pencil-square-o"></i>Edit Post</a></li>
																				<li><a href="javascript:void(0);" onclick="deletePost('<?php echo $post['post_id']; ?>', this)"><i class="fa fa-trash-o"></i>Delete Post</a></li>
																			<?php endif; ?>
																			<li class="bad-report"><i class="fa fa-flag"></i>Report Post</li>
																		</ul>
																	</div>
																</div>
																<!-- Hiển thị tên người dùng -->
																<ins><a href="timeline.php" title=""><?php echo $post['username']; ?></a> Post Album</ins>
																<span><i class="fa fa-globe"></i> published: <?php echo $post['published_at']; ?> </span>
															</div>
															<div class="post-meta">
																<p><?php echo $post['content']; ?></p>
																<?php if (!empty($post['image_url'])): ?>
																	<div class="post-images">
																		<?php foreach (explode(',', $post['image_url']) as $image): ?>
																			<img src="<?php echo htmlspecialchars($image); ?>" alt="Post Image" style="max-width: 100%; margin: 5px 0;">
																		<?php endforeach; ?>
																	</div>
																<?php endif; ?>
															</div>
															<div class="we-video-info">
																<ul>
																	<li><span class="views" title="views"><i class="fa fa-eye"></i><ins><?php echo $post['views_count']; ?></ins></span></li>
																	<li>
																		<div class="likes heart" title="Like/Dislike">❤ <span id="like-count-<?php echo $post['post_id']; ?>"><?php echo $post['likes_count']; ?></span></div>
																	</li>
																	<li><span class="comment" title="Comments"><i class="fa fa-commenting"></i><ins><?php echo $post['comments_count']; ?></ins></span></li>
																	<li><span><a class="share-pst" href="#" title="Share"><i class="fa fa-share-alt"></i></a><ins><?php echo $post['shares_count']; ?></ins></span></li>
																</ul>
															</div>
														</div>
														<div class="coment-area" id="comments-section-<?php echo $post['post_id']; ?>" style="display: block;">
															<ul class="we-comet">
																<?php if (!empty($post['comments'])): ?>
																	<?php foreach ($post['comments'] as $comment): ?>
																		<?php if (is_array($comment)): ?> <!-- Add check to ensure comment is an array -->
																			<li class="comment-item" data-comment-id="<?php echo $comment['comment_id']; ?>">
																				<div class="comet-avatar">
																					<img src="<?php echo !empty($comment['profile_picture']) ? htmlspecialchars($comment['profile_picture']) : '/images/resources/default-avatar.jpg'; ?>"
																						alt="Avatar"
																						style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 10px;">
																				</div>
																				<div class="we-comment">
																					<div class="comment-header">
																						<h5>
																							<a href="timeline.php" title=""><?php echo !empty($comment['username']) ? htmlspecialchars($comment['username']) : 'Unknown User'; ?></a>
																						</h5>
																						
																							<button class="delete-comment" onclick="deleteComment(<?php echo $comment['comment_id']; ?>, this)">
																								<i class="fa fa-trash"></i>
																							</button>
																				
																					</div>
																					<p><?php echo !empty($comment['comment_text']) ? htmlspecialchars($comment['comment_text']) : ''; ?></p>
																					<div class="inline-itms">
																						<span><?php echo !empty($comment['created_at']) ? htmlspecialchars($comment['created_at']) : ''; ?></span>
																						<a class="we-reply" href="#" title="Reply"><i class="fa fa-reply"></i></a>
																						<a href="#" title=""><i class="fa fa-heart"></i><span><?php echo !empty($comment['likes_count']) ? htmlspecialchars($comment['likes_count']) : '0'; ?></span></a>
																					</div>
																				</div>
																			</li>
																		<?php endif; ?>
																	<?php endforeach; ?>
																<?php endif; ?>


																<!-- Sửa phần form comment trong vòng lặp posts -->
																<li class="post-comment">
																	<div class="comet-avatar">
																		<img src="<?php echo !empty($post['profile_picture']) ? htmlspecialchars($post['profile_picture']) : '/images/resources/default-avatar.jpg'; ?>"
																			alt="Avatar"
																			style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
																	</div>
																	<div class="post-comt-box">
																		<form class="comment-form" data-post-id="<?php echo $post['post_id']; ?>">
																			<div class="comment-box-wrapper">
																				<textarea name="comment" placeholder="Viết bình luận..."></textarea>
																				<button type="submit" class="btn-submit">Gửi</button>
																			</div>
																		</form>
																	</div>
																</li>

																<!-- Thêm script mới để xử lý comment -->
																<script>
																	document.addEventListener('DOMContentLoaded', function() {
																		const commentForms = document.querySelectorAll('.comment-form');

																		commentForms.forEach(form => {
																			form.addEventListener('submit', function(e) {
																				e.preventDefault();
																				const postId = this.getAttribute('data-post-id');
																				const textarea = this.querySelector('textarea[name="comment"]');
																				const commentText = textarea.value.trim();
																				const submitBtn = this.querySelector('.btn-submit');

																				if (!commentText) return;

																				// Disable button while submitting
																				submitBtn.disabled = true;
																				submitBtn.textContent = 'Đang gửi...';

																				fetch('index.php', {
																						method: 'POST',
																						headers: {
																							'Content-Type': 'application/x-www-form-urlencoded',
																						},
																						body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
																					})
																					.then(response => response.json())
																					.then(data => {
																						if (data.success) {
																							// Find comments container
																							const commentsList = form.closest('.we-comet');

																							// Create new comment element
																							const newComment = createCommentElement(data.comment);

																							// Add new comment to top of list
																							const firstComment = commentsList.querySelector('.comment-item');
																							if (firstComment) {
																								commentsList.insertBefore(newComment, firstComment);
																							} else {
																								const postComment = commentsList.querySelector('.post-comment');
																								commentsList.insertBefore(newComment, postComment);
																							}

																							// Clear textarea
																							textarea.value = '';

																							// Update comment count
																							const commentCount = document.querySelector(`[data-post-id="${postId}"]`)
																								.closest('.central-meta')
																								.querySelector('.comment ins');
																							if (commentCount) {
																								commentCount.textContent = parseInt(commentCount.textContent || '0') + 1;
																							}
																						} else {
																							throw new Error(data.error || 'Không thể thêm bình luận');
																						}
																					})
																					.catch(error => {
																						console.error('Error:', error);
																						alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
																					})
																					.finally(() => {
																						// Re-enable button
																						submitBtn.disabled = false;
																						submitBtn.textContent = 'Gửi';
																					});
																			});
																		});
																	});

																	function createCommentElement(comment) {
																		const li = document.createElement('li');
																		li.className = 'comment-item';
																		li.dataset.commentId = comment.comment_id;
																		li.innerHTML = `
																<div class="comet-avatar">
																	<img src="${comment.profile_picture || '/images/resources/default-avatar.jpg'}" 
																		 alt="Avatar" 
																		 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
																</div>
																<div class="we-comment">
																	<div class="comment-header">
																		<h5><a href="timeline.php">${comment.username}</a></h5>
																		${comment.user_id == <?php echo $user_id; ?> ? 
																			`<button class="delete-comment" onclick="deleteComment(${comment.comment_id}, this)">
																				<i class="fa fa-trash"></i>
																			</button>` : ''
																		}
																	</div>
																	<p>${comment.comment_text}</p>
																	<div class="inline-itms">
																		<span>Vừa xong</span>
																		<a class="we-reply" href="#" title="Reply"><i class="fa fa-reply"></i></a>
																		<a href="#" title=""><i class="fa fa-heart"></i><span>0</span></a>
																	</div>
																</div>`;
																		return li;
																	}
																</script>

															</ul>
														</div>
													</div>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
									</0div>
								</div>
								<!-- Right Sidebar -->
								<div class="col-lg-3">
									<aside class="sidebar static">
										<!-- Your page widget -->
										<div class="widget">
											<h4 class="widget-title">Your page</h4>
											<div class="page-meta">
												<a href="#" title="" class="underline"><?php echo htmlspecialchars($username); ?></a>
												<span><i class="ti-comment"></i><a href="insight.html" title="">Messages <em><?php echo $total_messages; ?></em></a></span>
												<span><i class="ti-bell"></i><a href="insight.html" title="">Notifications <em><?php echo $total_notifications; ?></em></a></span>
											</div>
											<ul class="page-publishes">
												<li><span><i class="ti-pencil-alt"></i>Publish</span></li>
												<li><span><i class="ti-camera"></i>Photo</span></li>
												<li><span><i class="ti-video-camera"></i>Live</span></li>
												<li><span><i class="fa fa-user-plus"></i>Invite</span></li>
											</ul>
											<div class="page-likes">
												<ul class="nav nav-tabs likes-btn">
													<li class="nav-item"><a class="active" href="#link1" data-toggle="tab" data-ripple="">likes</a></li>
													<li class="nav-item"><a class="" href="#link2" data-toggle="tab" data-ripple="">views</a></li>
												</ul>
												<div class="tab-content">
													<div class="tab-pane active fade show" id="link1">
														<span><i class="ti-heart"></i><?php echo $total_likes; ?></span>
														<a href="#" title="weekly-likes"><?php echo $new_likes; ?> new likes this week</a>
													</div>
													<div class="tab-pane fade" id="link2">
														<span><i class="fa fa-eye"></i><?php echo $total_views; ?></span>
														<a href="#" title="weekly-views"><?php echo $new_views; ?> new views this week</a>
													</div>
												</div>
											</div>
										</div>

										<!-- Khám phá sự kiện widget -->
										<div class="widget">
											<h4 class="widget-title">Khám phá sự kiện <a title="" href="#" class="see-all">Xem tất cả</a></h4>
											<?php if ($explore_events): ?>
												<?php foreach ($explore_events as $event): ?>
													<div class="rec-events <?php echo $event['event_class']; ?>">
														<i class="<?php echo $event['event_icon']; ?>"></i>
														<h6><a href="<?php echo $event['event_link']; ?>" title=""><?php echo $event['event_name']; ?></a></h6>
														<img src="images/clock.png" alt="">
													</div>
												<?php endforeach; ?>
											<?php else: ?>
												<p>Không có sự kiện nào.</p>
											<?php endif; ?>
										</div>

										<!-- Tiểu sử widget -->
										<div class="widget">
											<h4 class="widget-title">Tiểu sử</h4>
											<ul class="short-profile">
												<li>
													<span>Giới thiệu</span>
													<p><?php echo htmlspecialchars($profile_intro['about']); ?></p>
												</li>
												<li>
													<span>Chương trình yêu thích</span>
													<p><?php echo htmlspecialchars($profile_intro['fav_tv_show']); ?></p>
												</li>
												<li>
													<span>Bài hát yêu thích</span>
													<p><?php echo htmlspecialchars($profile_intro['favourit_music']); ?></p>
												</li>
											</ul>
										</div>

										<!-- Recent Links widget -->
										<div class="widget stick-widget">
											<h4 class="widget-title">Recent Links <a title="" href="#" class="see-all">See All</a></h4>
											<ul class="recent-links">
												<?php
												if (!empty($recent_links) && $recent_links[0] != null) {
													foreach ($recent_links as $link) {
														echo '<li>
                                                            <figure><img src="' . $link['image'] . '" alt=""></figure>
                                                            <div class="re-links-meta">
                                                                <h6><a href="' . $link['url'] . '" title="">' . $link['title'] . '</a></h6>
                                                                <span>' . $link['time_ago'] . '</span>
                                                            </div>
                                                        </li>';
													}
												} else {
													echo '<li><p>No recent links found.</p></li>';
												}
												?>
											</ul>
										</div>
									</aside>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>


		<!-- FOOTER -->
		<?php include 'component/footer.php'; ?>
		<!-- END FOOTER -->
		<div class="side-panel">
			<h4 class="panel-title">General Setting</h4>
			<form method="post">
				<div class="setting-row">
					<span>use night mode</span>
					<input type="checkbox" id="nightmode1" />
					<label for="nightmode1" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Notifications</span>
					<input type="checkbox" id="switch22" />
					<label for="switch22" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Notification sound</span>
					<input type="checkbox" id="switch33" />
					<label for="switch33" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>My profile</span>
					<input type="checkbox" id="switch44" />
					<label for="switch44" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Show profile</span>
					<input type="checkbox" id="switch55" />
					<label for="switch55" data-on-label="ON" data-off-label="OFF"></label>
				</div>
			</form>
			<h4 class="panel-title">Account Setting</h4>
			<form method="post">
				<div class="setting-row">
					<span>Sub users</span>
					<input type="checkbox" id="switch66" />
					<label for="switch66" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>personal account</span>
					<input type="checkbox" id="switch77" />
					<label for="switch77" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Business account</span>
					<input type="checkbox" id="switch88" />
					<label for="switch88" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Show me online</span>
					<input type="checkbox" id="switch99" />
					<label for="switch99" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Delete history</span>
					<input type="checkbox" id="switch101" />
					<label for="switch101" data-on-label="ON" data-off-label="OFF"></label>
				</div>
				<div class="setting-row">
					<span>Expose author name</span>
					<input type="checkbox" id="switch111" />
					<label for="switch111" data-on-label="ON" data-off-label="OFF"></label>
				</div>
			</form>
		</div><!-- side panel -->

		<div class="popup-wraper2">
			<div class="popup post-sharing">
				<span class="popup-closed"><i class="ti-close"></i></span>
				<div class="popup-meta">
					<div class="popup-head">
						<select data-placeholder="Share to friends..." multiple class="chosen-select multi">
							<option>Share in your feed</option>
							<option>Share in friend feed</option>
							<option>Share in a page</option>
							<option>Share in a group</option>
							<option>Share in message</option>
						</select>
						<div class="post-status">
							<span><i class="fa fa-globe"></i></span>
							<ul>
								<li><a href="#" title=""><i class="fa fa-globe"></i> Post Globaly</a></li>
								<li><a href="#" title=""><i class="fa fa-user"></i> Post Private</a></li>
								<li><a href="#" title=""><i class="fa fa-user-plus"></i> Post Friends</a></li>
							</ul>
						</div>
					</div>
					<div class="postbox">
						<div class="post-comt-box">
							<form method="post">
								<input type="text" placeholder="Search Friends, Pages, Groups, etc....">
								<textarea placeholder="Say something about this..."></textarea>
								<div class="add-smiles">
									<span title="add icon" class="em em-expressionless"></span>
									<div class="smiles-bunch">
										<i class="em em---1"></i>
										<i class="em em-smiley"></i>
										<i class="em em-anguished"></i>
										<i class="em em-laughing"></i>
										<i class="em em-angry"></i>
										<i class="em em-astonished"></i>
										<i class="em em-blush"></i>
										<i class="em em-disappointed"></i>
										<i class="em em-worried"></i>
										<i class="em em-kissing_heart"></i>
										<i class="em em-rage"></i>
										<i class="em em-stuck_out_tongue"></i>
									</div>
								</div>

								<button type="submit"></button>
							</form>
						</div>
						<figure><img src="images/resources/share-post.jpg" alt=""></figure>
						<div class="friend-info">
							<figure>
								<img alt="" src="images/resources/admin.jpg">
							</figure>
							<div class="friend-name">
								<ins><a title="" href="timeline.php">Jack Carter</a> share <a title="" href="#">link</a></ins>
								<span>Yesterday with @Jack Piller and @Emily Stone at the concert of # Rock'n'Rolla in Ontario.</span>
							</div>
						</div>
						<div class="share-to-other">
							<span>Share to other socials</span>
							<ul>
								<li><a class="facebook-color" href="#" title=""><i class="fa fa-facebook-square"></i></a></li>
								<li><a class="twitter-color" href="#" title=""><i class="fa fa-twitter-square"></i></a></li>
								<li><a class="dribble-color" href="#" title=""><i class="fa fa-dribbble"></i></a></li>
								<li><a class="instagram-color" href="#" title=""><i class="fa fa-instagram"></i></a></li>
								<li><a class="pinterest-color" href="#" title=""><i class="fa fa-pinterest-square"></i></a></li>
							</ul>
						</div>
						<div class="copy-email">
							<span>Copy & Email</span>
							<ul>
								<li><a href="#" title="Copy Post Link"><i class="fa fa-link"></i></a></li>
								<li><a href="#" title="Email this Post"><i class="fa fa-envelope"></i></a></li>
							</ul>
						</div>
						<div class="we-video-info">
							<ul>
								<li>
									<span title="" data-toggle="tooltip" class="views" data-original-title="views">
										<i class="fa fa-eye"></i>
										<ins>1.2k</ins>
									</span>
								</li>
								<li>
									<span title="" data-toggle="tooltip" class="views" data-original-title="views">
										<i class="fa fa-share-alt"></i>
										<ins>20k</ins>
									</span>
								</li>
							</ul>
							<button class="main-btn color" data-ripple="">Submit</button>
							<button class="main-btn cancel" data-ripple="">Cancel</button>
						</div>
					</div>
				</div>
			</div><!-- share popup -->

			<div class="popup-wraper3">
				<div class="popup">
					<span class="popup-closed"><i class="ti-close"></i></span>
					<div class="popup-meta">
						<div class="popup-head">
							<h5>Report Post</h5>
						</div>
						<div class="Rpt-meta">
							<span>We're sorry something's wrong. How can we help?</span>
							<form method="post" class="c-form">
								<div class="form-radio">
									<div class="radio">
										<label>
											<input type="radio" name="radio" checked="checked"><i class="check-box"></i>It's spam or abuse
										</label>
									</div>
									<div class="radio">
										<label>
											<input type="radio" name="radio"><i class="check-box"></i>It breaks r/technology's rules
										</label>
									</div>
									<div class="radio">
										<label>
											<input type="radio" name="radio"><i class="check-box"></i>Not Related
										</label>
									</div>
									<div class="radio">
										<label>
											<input type="radio" name="radio"><i class="check-box"></i>Other issues
										</label>
									</div>
								</div>
								<div>
									<label>Write about Report</label>
									<textarea placeholder="write someting about Post" rows="2"></textarea>
								</div>
								<div>
									<button data-ripple="" type="submit" class="main-btn">Submit</button>
									<a href="#" data-ripple="" class="main-btn3 cancel">Close</a>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div><!-- report popup -->

			<div class="popup-wraper1">
				<div class="popup direct-mesg">
					<span class="popup-closed"><i class="ti-close"></i></span>
					<div class="popup-meta">
						<div class="popup-head">
							<h5>Send Message</h5>
						</div>
						<div class="send-message">
							<form method="post" class="c-form">
								<input type="text" placeholder="Sophia">
								<textarea placeholder="Write Message"></textarea>
								<button type="submit" class="main-btn">Send</button>
							</form>
							<div class="add-smiles">
								<div class="uploadimage">
									<i class="fa fa-image"></i>
									<label class="fileContainer">
										<input type="file">
									</label>
								</div>
								<span title="add icon" class="em em-expressionless"></span>
								<div class="smiles-bunch">
									<i class="em em---1"></i>
									<i class="em em-smiley"></i>
									<i class="em em-anguished"></i>
									<i class="em em-laughing"></i>
									<i class="em em-angry"></i>
									<i class="em em-astonished"></i>
									<i class="em em-blush"></i>
									<i class="em em-disappointed"></i>
									<i class="em em-worried"></i>
									<i class="em em-kissing_heart"></i>
									<i class="em em-rage"></i>
									<i class="em em-stuck_out_tongue"></i>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div><!-- send message popup -->

			<div class="modal fade" id="img-comt">
				<div class="modal-dialog">
					<div class="modal-content">

						<!-- Modal Header -->
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal">×</button>
						</div>

						<!-- Modal body -->
						<div class="modal-body">
							<div class="row">
								<div class="col-lg-8">
									<div class="pop-image">
										<div class="pop-item">
											<figure><img src="images/resources/blog-detail.jpg" alt=""></figure>
										</div>
									</div>
								</div>
								<div class="col-lg-4">
									<div class="user">
										<figure><img src="images/resources/user1.jpg" alt=""></figure>
										<div class="user-information">
											<h4><a href="#" title="">Danile Walker</a></h4>
											<span>2 hours ago</span>
										</div>
										<a href="#" title="Follow" data-ripple="">Follow</a>
									</div>
									<div class="we-video-info">
										<ul>
											<li>
												<div title="Like/Dislike" class="likes heart">❤ <span>2K</span></div>
											</li>
											<li>
												<span title="Comments" class="comment">
													<i class="fa fa-commenting"></i>
													<ins>52</ins>
												</span>
											</li>

											<li>
												<span>
													<a title="Share" href="#" class="">
														<i class="fa fa-share-alt"></i>
													</a>
													<ins>20</ins>
												</span>
											</li>
										</ul>
										<div class="users-thumb-list">
											<a href="#" title="" data-toggle="tooltip" data-original-title="Anderw">
												<img src="images/resources/userlist-1.jpg" alt="">
											</a>
											<a href="#" title="" data-toggle="tooltip" data-original-title="frank">
												<img src="images/resources/userlist-2.jpg" alt="">
											</a>
											<a href="#" title="" data-toggle="tooltip" data-original-title="Sara">
												<img src="images/resources/userlist-3.jpg" alt="">
											</a>
											<a href="#" title="" data-toggle="tooltip" data-original-title="Amy">
												<img src="images/resources/userlist-4.jpg" alt="">
											</a>
											<span><strong>You</strong>, <b>Sarah</b> và <a title="" href="#">24+ more</a> liked</span>
										</div>
									</div>
									<div style="display: block;" class="coment-area">
										<ul class="we-comet">
											<li>
												<div class="comet-avatar">
													<img alt="" src="images/resources/nearly3.jpg">
												</div>
												<div class="we-comment">
													<h5><a title="" href="timeline.php">Jason borne</a></h5>
													<p>we are working for the dance and sing songs. this video is very awesome for the youngster. please vote this video and like our channel</p>
													<div class="inline-itms">
														<span>1 year ago</span>
														<a title="Reply" href="#" class="we-reply"><i class="fa fa-reply"></i></a>
														<a title="" href="#"><i class="fa fa-heart"></i><span>20</span></a>
													</div>
												</div>

											</li>
											<li>
												<div class="comet-avatar">
													<img alt="" src="images/resources/comet-4.jpg">
												</div>
												<div class="we-comment">
													<h5><a title="" href="timeline.php">Sophia</a></h5>
													<p>we are working for the dance and sing songs. this video is very awesome for the youngster.
														<i class="em em-smiley"></i>
													</p>
													<div class="inline-itms">
														<span>1 year ago</span>
														<a title="Reply" href="#" class="we-reply"><i class="fa fa-reply"></i></a>
														<a title="" href="#"><i class="fa fa-heart"></i><span>20</span></a>
													</div>
												</div>
											</li>
											<li>
												<div class="comet-avatar">
													<img alt="" src="images/resources/comet-4.jpg">
												</div>
												<div class="we-comment">
													<h5><a title="" href="timeline.php">Sophia</a></h5>
													<p>we are working for the dance and sing songs. this video is very awesome for the youngster.
														<i class="em em-smiley"></i>
													</p>
													<div class="inline-itms">
														<span>1 year ago</span>
														<a title="Reply" href="#" class="we-reply"><i class="fa fa-reply"></i></a>
														<a title="" href="#"><i class="fa fa-heart"></i><span>20</span></a>
													</div>
												</div>
											</li>
											<li>
												<a class="showmore underline" title="" href="#">more comments+</a>
											</li>
											<li class="post-comment">
												<div class="comet-avatar">
													<img alt="" src="images/resources/nearly1.jpg">
												</div>
												<div class="post-comt-box">
													<form method="post">
														<textarea placeholder="Post your comment"></textarea>
														<div class="add-smiles">
															<div class="uploadimage">
																<i class="fa fa-image"></i>
																<label class="fileContainer">
																	<input type="file">
																</label>
															</div>
															<span title="add icon" class="em em-expressionless"></span>
															<div class="smiles-bunch">
																<i class="em em---1"></i>
																<i class="em em-smiley"></i>
																<i class="em em-anguished"></i>
																<i class="em em-laughing"></i>
																<i class="em em-angry"></i>
																<i class="em em-astonished"></i>
																<i class="em em-blush"></i>
																<i class="em em-disappointed"></i>
																<i class="em em-worried"></i>
																<i class="em em-kissing_heart"></i>
																<i class="em em-rage"></i>
																<i class="em em-stuck_out_tongue"></i>
															</div>
														</div>

														<button type="submit"></button>
													</form>
												</div>
											</li>
										</ul>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div><!-- The Scrolling Modal image with comment -->

			<script src="js/main.min.js"></script>
			<script src="js/jquery-stories.js"></script>
			<script src="js/toast-notificatons.js"></script>
			<script src="../../../cdnjs.cloudflare.com/ajax/libs/gsap/1.18.2/TweenMax.min.js"></script><!-- For timeline slide show -->
			<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA8c55_YHLvDHGACkQscgbGLtLRdxBDCfI"></script><!-- for location picker map -->
			<script src="js/locationpicker.jquery.js"></script><!-- for loaction picker map -->
			<script src="js/map-init.js"></script><!-- map initilasition -->
			<script src="js/page-tourintro.js"></script>
			<script src="js/page-tour-init.js"></script>
			<script src="js/script.js"></script>
			<script>
				jQuery(document).ready(function($) {
					$('#us3').locationpicker({
						location: {
							latitude: 21.028511, // Vĩ độ của Hà Nội
							longitude: 105.804817 // Kinh độ của Hà Nội
						},
						radius: 0,
						inputBinding: {
							latitudeInput: $('#us3-lat'),
							longitudeInput: $('#us3-lon'),
							radiusInput: $('#us3-radius'),
							locationNameInput: $('#us3-address')
						},
						enableAutocomplete: true,
						onchanged: function(currentLocation, radius, isMarkerDropped) {
							// Uncomment line below to show alert on each Location Changed event
							//alert("Location changed. New location (" + currentLocation.latitude + ", " + currentLocation.longitude + ")");
						}
					});

					if ($.isFunction($.fn.toast)) {
						$.toast({
							heading: 'Welcome To NAPA Social Network',
							text: '',
							showHideTransition: 'slide',
							icon: 'success',
							loaderBg: '#fa6342',
							position: 'bottom-right',
							hideAfter: 3000,
						});

						$.toast({
							heading: 'Information',
							text: 'Now you can full demo of NAPA Social Network and hope you like',
							showHideTransition: 'slide',
							icon: 'info',
							hideAfter: 5000,
							loaderBg: '#fa6342',
							position: 'bottom-right',
						});
						$.toast({
							heading: 'Support & Help',
							text: 'Report any <a href="#">issues</a> by email',
							showHideTransition: 'fade',
							icon: 'error',
							hideAfter: 7000,
							loaderBg: '#fa6342',
							position: 'bottom-right',
						});
					}

				});
			</script>
			<script>
				// Add this function to handle story upload from index page
				function handleStoryUpload(input) {
					const file = input.files[0];
					if (!file) return;

					const formData = new FormData();
					formData.append('type', 'image');
					formData.append('image', file);

					fetch('create_story.php', {
							method: 'POST',
							body: formData
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								alert('Story created successfully!');
								// Refresh the stories section
								location.reload();
							} else {
								alert(data.message || 'Failed to create story');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							alert('Failed to create story');
						});
				}

				// Modify your existing click handler
				document.querySelector('.story-thumb[data-toggle="tooltip"][title="Add Your Story"]').onclick = function() {
					const input = document.createElement('input');
					input.type = 'file';
					input.accept = 'image/*';
					input.onchange = function() {
						handleStoryUpload(this);
					};
					input.click();
				};
			</script>
			<script>
				// Hàm xem trước bài đăng
				function previewPost() {
					const content = document.querySelector('textarea[name="content"]').value;
					const files = document.querySelector('input[type="file"]').files;
					const previewBox = document.getElementById('preview-box');
					const previewContent = previewBox.querySelector('.preview-content');
					const previewImages = previewBox.querySelector('.preview-images');

					// Hiển thị nội dung
					previewContent.innerHTML = content.replace(/\n/g, '<br>');

					// Hiển thị ảnh xem trước
					previewImages.innerHTML = '';
					Array.from(files).forEach(file => {
						if (file.type.startsWith('image/')) {
							const reader = new FileReader();
							reader.onload = function(e) {
								const img = document.createElement('img');
								img.src = e.target.result;
								img.style.maxWidth = '200px';
								img.style.margin = '5px';
								previewImages.appendChild(img);
							}
							reader.readAsDataURL(file);
						}
					});

					previewBox.style.display = 'block';
				}

				// Xử lý like bài viết
				function likePost(postId) {
					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=like&post_id=${postId}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								const likeCount = document.querySelector(`#like-count-${postId}`);
								likeCount.textContent = parseInt(likeCount.textContent) + 1;
							}
						});
				}

				// Xử lý comment
				function submitComment(event, postId) {
					event.preventDefault();
					const form = event.target;
					const commentText = form.querySelector('textarea[name="comment"]').value;

					if (!commentText.trim()) return;

					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								// Find the specific post's comment section
								const commentsList = document.querySelector(`#comments-section-${postId} .we-comet`);
								const newComment = createCommentElement(data.comment);

								// Insert new comment before the comment form
								const commentForm = commentsList.querySelector('.post-comment');
								commentsList.insertBefore(newComment, commentForm);

								// Clear form
								form.reset();
							}
						});
				}

				function createCommentElement(comment) {
					const li = document.createElement('li');
					li.className = 'comment-item';
					li.innerHTML = `
			<div class="comet-avatar">
				<img src="${comment.profile_picture || '/images/resources/default-avatar.jpg'}" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
			</div>
			<div class="we-comment">
				<h5><a href="timeline.php">${comment.username}</a></h5>
				<p>${comment.comment_text}</p>
				<div class="inline-itms">
						<span>${comment.created_at_formatted || 'Vừa xong'}</span>
					<a class="we-reply" href="#" title="Reply"><i class="fa fa-reply"></i></a>
					<a href="#" title=""><i class="fa fa-heart"></i><span>0</span></a>
				</div>
			</div>`;
					return li;
				}

				document.addEventListener('DOMContentLoaded', function() {
					const commentForms = document.querySelectorAll('.comment-form');

					commentForms.forEach(form => {
						form.addEventListener('submit', function(e) {
							e.preventDefault();
							const postId = this.getAttribute('data-post-id');
							const commentText = this.querySelector('textarea[name="comment"]').value;

							if (!commentText.trim()) return;

							fetch('index.php', {
									method: 'POST',
									headers: {
										'Content-Type': 'application/x-www-form-urlencoded',
									},
									body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
								})
								.then(response => {
									if (!response.ok) {
										throw new Error('Network response was not ok');
									}
									return response.json();
								})
								.then(data => {
									if (data.success) {
										const commentsList = document.querySelector(`#comments-section-${postId} .we-comet`);
										const newComment = createCommentElement(data.comment);
										const commentForm = commentsList.querySelector('.post-comment');
										commentsList.insertBefore(newComment, commentForm);
										this.reset();

										// Update comment count
										const commentCount = document.querySelector(`#comments-section-${postId} .comment ins`);
										if (commentCount) {
											commentCount.textContent = parseInt(commentCount.textContent || 0) + 1;
										}
									} else {
										throw new Error(data.error || 'Không thể thêm bình luận');
									}
								})
								.catch(error => {
									console.error('Error:', error);
									alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
								})
								.finally(() => {
									const submitBtn = this.querySelector('.btn-submit');
									submitBtn.disabled = false;
									submitBtn.textContent = 'Gửi';
								});
						});
					});
				});

				function deleteComment(commentId, button) {
					if (!confirm('Bạn có chắc chắn muốn xóa bình luận này?')) return;

					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=delete_comment&comment_id=${commentId}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								const commentItem = button.closest('.comment-item');
								const commentSection = commentItem.closest('.coment-area');
								const centralMeta = commentSection.closest('.central-meta');
								const commentCount = centralMeta.querySelector('.comment ins');

								// Remove the comment element
								commentItem.remove();

								// Update comment count if element exists
								if (commentCount) {
									const currentCount = parseInt(commentCount.textContent || '0');
									commentCount.textContent = Math.max(0, currentCount - 1);
								}
							} else {
								throw new Error(data.error || 'Không thể xóa bình luận');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							alert(error.message || 'Đã xảy ra lỗi khi xóa bình luận');
						});
				}
			</script>
			<style>
				.comment-box-wrapper {
					display: flex;
					align-items: flex-start;
					gap: 10px;
					width: 100%;
				}

				.comment-form textarea {
					flex: 1;
					min-height: 40px;
					padding: 10px;
					border: 1px solid #ddd;
					border-radius: 4px;
					resize: vertical;
				}

				.btn-submit {
					background: #ff5e3a;
					color: white;
					border: none;
					padding: 8px 20px;
					border-radius: 4px;
					cursor: pointer;
					font-weight: 500;
					margin-top: 0;
					min-width: 60px;
					height: fit-content;
				}

				.delete-comment {
					background: none;
					border: none;
					color: #ff5e3a;
					cursor: pointer;
					padding: 4px 8px;
					opacity: 0.7;
					transition: opacity 0.2s;
				}

				.delete-comment:hover {
					opacity: 1;
				}

				.comment-header {
					display: flex;
					align-items: center;
					justify-content: space-between;
				}

				.post-comt-box {
					width: 100%;
					padding: 10px;
				}

				.btn-submit:hover {
					background: #066fa1;
				}
			</style>
			<script>
				document.addEventListener('DOMContentLoaded', function() {
					// Remove all existing event listeners by cloning and replacing forms
					const commentForms = document.querySelectorAll('.comment-form');

					commentForms.forEach(form => {
						const newForm = form.cloneNode(true);
						form.parentNode.replaceChild(newForm, form);

						// Add single event listener to new form
						newForm.addEventListener('submit', handleCommentSubmit);
					});
				});
				function updateCommentCount(postId, increment) {
					const commentCountElement = document.querySelector(`#comments-section-${postId} .comment ins`);
					if (commentCountElement) {
						const currentCount = parseInt(commentCountElement.textContent || '0');
						commentCountElement.textContent = currentCount + increment;
					}
				}
				function handleCommentSubmit(e) {
					e.preventDefault();
					const form = e.target;
					const postId = form.dataset.postId;
					const textarea = form.querySelector('textarea[name="comment"]');
					const commentText = textarea.value.trim();
					const submitBtn = form.querySelector('.btn-submit');

					if (!commentText) return;

					// Disable button while submitting
					submitBtn.disabled = true;
					submitBtn.textContent = 'Đang gửi...';

					// Submit comment with single request
					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								// Add new comment to DOM
								const commentsList = form.closest('.we-comet');
								const newComment = createCommentElement(data.comment);
								const firstComment = commentsList.querySelector('.comment-item');
								if (firstComment) {
									commentsList.insertBefore(newComment, firstComment);
								} else {
									const postComment = commentsList.querySelector('.post-comment');
									commentsList.insertBefore(newComment, postComment);
								}

								// Clear form and update count
								textarea.value = '';
								updateCommentCount(postId, 1);
							} else {
								throw new Error(data.error || 'Không thể thêm bình luận');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							alert(error.message || 'Đã xảy ra lỗi. Vui lòng thử lại.');
						})
						.finally(() => {
							submitBtn.disabled = false;
							submitBtn.textContent = 'Gửi';
						});
				}
			</script>
			<!-- Edit Post Modal -->
			<div class="modal fade" id="editPostModal" tabindex="-1" role="dialog" aria-hidden="true">
				<div class="modal-dialog" role="document">
					<div class="modal-content">
						<div class="modal-header">
							<h5 class="modal-title">Edit Post</h5>
							<button type="button" class="close" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<form id="editPostForm">
								<input type="hidden" name="post_id" id="editPostId">
								<textarea class="form-control" id="editPostContent" rows="4"></textarea>
							</form>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
							<button type="button" class="btn btn-primary" onclick="updatePost()">Save Changes</button>
						</div>
					</div>
				</div>
			</div>

			<script>
				function editPost(postId, element) {
					const postContent = element.closest('.friend-info').querySelector('.post-meta p').textContent;
					document.getElementById('editPostId').value = postId;
					document.getElementById('editPostContent').value = postContent;
					$('#editPostModal').modal('show');
				}

				function updatePost() {
					const postId = document.getElementById('editPostId').value;
					const content = document.getElementById('editPostContent').value;

					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=edit_post&post_id=${postId}&content=${encodeURIComponent(content)}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								// Update post content in the DOM
								const postElement = document.querySelector(`[data-post-id="${postId}"]`);
								postElement.querySelector('.post-meta p').textContent = content;
								$('#editPostModal').modal('hide');

								// Show success message
								$.toast({
									heading: 'Success',
									text: 'Post updated successfully',
									showHideTransition: 'slide',
									icon: 'success',
									position: 'top-right'
								});
							} else {
								throw new Error(data.error || 'Failed to update post');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							$.toast({
								heading: 'Error',
								text: error.message || 'Failed to update post',
								showHideTransition: 'fade',
								icon: 'error',
								position: 'top-right'
							});
						});
				}

				function deletePost(postId, element) {
					if (!confirm('Are you sure you want to delete this post?')) {
						return;
					}

					fetch('index.php', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded',
							},
							body: `action=delete_post&post_id=${postId}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.success) {
								// Remove post from DOM
								element.closest('.central-meta.item').remove();

								// Show success message
								$.toast({
									heading: 'Success',
									text: 'Post deleted successfully',
									showHideTransition: 'slide',
									icon: 'success',
									position: 'top-right'
								});
							} else {
								throw new Error(data.error || 'Failed to delete post');
							}
						})
						.catch(error => {
							console.error('Error:', error);
							$.toast({
								heading: 'Error',
								text: error.message || 'Failed to delete post',
								showHideTransition: 'fade',
								icon: 'error',
								position: 'top-right'
							});
						});
				}
			</script>
</body>


</html>