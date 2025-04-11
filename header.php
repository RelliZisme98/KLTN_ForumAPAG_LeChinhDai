<?php
// Bắt đầu session
// session_start();

// Kết nối đến cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    echo "Bạn cần đăng nhập để xem trang này.";
    exit;
}

// Lấy ID từ session
$user_id = $_SESSION['user_id'];

// Truy vấn để lấy thông tin người dùng từ bảng `users`
$sql = "SELECT username, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'images/resources/author.jpg';
} else {
    echo "Không tìm thấy người dùng.";
    exit;
}

// Truy vấn để lấy dữ liệu từ bảng header_links
$sql_header = "SELECT * FROM header_links ORDER BY position ASC";
$result = $conn->query($sql_header);

$menu = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['parent_id'] === NULL) {
            $menu[$row['id']] = [
                'title' => $row['title'],
                'url' => $row['url'],
                'position' => $row['position'],
                'children' => []
            ];
        } else {
            foreach ($menu as $parent_id => $parent_item) {
                if ($parent_item['position'] == $row['parent_id']) {
                    $menu[$parent_id]['children'][] = [
                        'title' => $row['title'],
                        'url' => $row['url']
                    ];
                }
            }
        }
    }
}

// Truy vấn để lấy yêu cầu kết bạn
$sql_friend_requests = "SELECT fr.id, fr.sender_id, u.username, u.profile_picture, fr.created_at,
                       CASE 
                           WHEN f.status_add = 'accepted' THEN 'accepted'
                           WHEN fr2.id IS NOT NULL THEN 'pending'
                           ELSE NULL
                       END as friendship_status
                       FROM friend_requests fr 
                       JOIN users u ON fr.sender_id = u.id 
                       LEFT JOIN friend f ON (f.user_id = fr.sender_id AND f.friend_id = fr.receiver_id)
                                        OR (f.user_id = fr.receiver_id AND f.friend_id = fr.sender_id)
                       LEFT JOIN friend_requests fr2 ON fr2.sender_id = ? AND fr2.receiver_id = fr.sender_id
                       WHERE fr.receiver_id = ? AND fr.status = 'pending'";
$stmt_friend = $conn->prepare($sql_friend_requests);
$stmt_friend->bind_param("ii", $user_id, $user_id);
$stmt_friend->execute();
$friend_requests_result = $stmt_friend->get_result();
$friend_requests = $friend_requests_result->fetch_all(MYSQLI_ASSOC);
$friend_request_count = count($friend_requests);

// Truy vấn danh sách bạn bè
$sql_friends_list = "SELECT u.id, u.username, u.profile_picture, 
                    (SELECT COUNT(*) FROM friend f2 
                     WHERE f2.status_add = 'accepted' 
                     AND ((f2.user_id = u.id AND f2.friend_id = ?) 
                     OR (f2.friend_id = u.id AND f2.user_id = ?))) as is_friend
                    FROM users u
                    INNER JOIN friend f ON (f.user_id = u.id OR f.friend_id = u.id)
                    WHERE (f.user_id = ? OR f.friend_id = ?)
                    AND f.status_add = 'accepted'
                    AND u.id != ?
                    GROUP BY u.id
                    LIMIT 10";
$stmt_friends = $conn->prepare($sql_friends_list);
$stmt_friends->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt_friends->execute();
$friends_list = $stmt_friends->get_result()->fetch_all(MYSQLI_ASSOC);

// Truy vấn để lấy thông báo
$sql_notifications = "SELECT n.content, n.created_at, n.is_read, u.username, u.profile_picture 
                      FROM notifications n 
                      JOIN users u ON n.user_id = u.id 
                      WHERE n.user_id = ? 
                      ORDER BY n.created_at DESC";
$stmt_notif = $conn->prepare($sql_notifications);
$stmt_notif->bind_param("i", $user_id);
$stmt_notif->execute();
$notifications_result = $stmt_notif->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);
$notification_count = count(array_filter($notifications, function($n) { return $n['is_read'] == 0; })); // Đếm thông báo chưa đọc

$conn->close();
?>

<!-- Header -->
<div class="topbar stick">
    <div class="logo">
        <a title="" href="newsfeed.html"></a>
    </div>
    <div class="top-area">
        <div class="main-menu">
            <span>
                <i class="fa fa-braille"></i>
            </span>
        </div>
        <div class="top-search">
            <form method="get" action="search.php" class="search-form">
                <input type="text" placeholder="Search People, Pages, Groups etc">
                <button data-ripple><i class="ti-search"></i></button>
            </form>
        </div>
        <div class="page-name">
            <span>Newsfeed</span>
        </div>
        <ul class="setting-area">
            <li><a href="newsfeed.html" title="Home" data-ripple=""><i class="fa fa-home"></i></a></li>
            <li>
                <a href="#" title="Friend Requests" data-ripple="">
                    <i class="fa fa-user"></i><em class="bg-red"><?php echo $friend_request_count; ?></em>
                </a>
                <div class="dropdowns">
                    <span><?php echo $friend_request_count; ?> New Requests <a href="#" title="">View all Requests</a></span>
                    <ul class="drops-menu">
                    <?php if ($friend_request_count > 0): ?>
                        <?php foreach ($friend_requests as $request): 
                            $friendship_status = $request['friendship_status'];
                            $request_sent = $friendship_status === 'pending';
                        ?>
                            <li>
                                <div>
                                    <figure>
                                        <img src="<?php echo htmlspecialchars($request['profile_picture'] ?: 'images/resources/author.jpg'); ?>" alt="">
                                    </figure>
                                    <div class="mesg-meta">
                                        <h6><a href="#" title=""><?php echo htmlspecialchars($request['username']); ?></a></h6>
                                        <span>Friend request</span>
                                        <i><?php echo date('Y-m-d H:i', strtotime($request['created_at'])); ?></i>
                                    </div>
                                    <div class="add-deriends">
                                        <?php if (!$friendship_status): ?>
                                        <form method="post" action="handle_friend_request.php" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="accept-btn" style="background:none;border:none;padding:0;">
                                                <i class="fa fa-heart"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="handle_friend_request.php" style="display:inline;">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="delete-btn" style="background:none;border:none;padding:0;">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <span class="friend-request-status">
                                        <?php if ($friendship_status === 'accepted'): ?>
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
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><div>No new friend requests.</div></li>
                    <?php endif; ?>
                </ul>
                    <a href="#" title="" class="more-mesg">View All</a>
                </div>
            </li>
            <li>
                <a href="#" title="Friends" data-ripple="">
                    <i class="fa fa-users"></i>
                </a>
                <div class="dropdowns">
                    <span>Bạn bè <a href="friends.php" title="">Xem tất cả</a></span>
                    <ul class="drops-menu">
                        <?php if (!empty($friends_list)): ?>
                            <?php foreach ($friends_list as $friend): ?>
                                <li>
                                    <div>
                                        <figure>
                                            <img src="<?php echo htmlspecialchars($friend['profile_picture'] ?: 'images/resources/author.jpg'); ?>" alt="">
                                        </figure>
                                        <div class="mesg-meta">
                                            <h6><a href="profile.php?id=<?php echo $friend['id']; ?>" title=""><?php echo htmlspecialchars($friend['username']); ?></a></h6>
                                            <span>Bạn bè</span>
                                            <button class="unfriend-btn" data-friend-id="<?php echo $friend['id']; ?>" style="background:none;border:none;color:red;padding:0;margin-left:10px;">
                                                <i class="fa fa-user-times"></i> Hủy kết bạn
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><div>Chưa có bạn bè nào.</div></li>
                        <?php endif; ?>
                    </ul>
                    <a href="friends.php" title="" class="more-mesg">Xem tất cả bạn bè</a>
                </div>
            </li>
            <li>
                <a href="#" title="Notification" data-ripple="">
                    <i class="fa fa-bell"></i><em class="bg-purple"><?php echo $notification_count; ?></em>
                </a>
                <div class="dropdowns">
    <span><?php echo $notification_count; ?> New Notifications 
        <form method="post" action="handle_notifications.php" style="display:inline;">
            <button type="submit" name="mark_all_read" value="1" style="background:none;border:none;color:#069;text-decoration:underline;">Mark all as read</button>
        </form>
    </span>
    <ul class="drops-menu">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $notif): ?>
                <li>
                    <a href="#" title="">
                        <figure>
                            <img src="<?php echo htmlspecialchars($notif['profile_picture'] ?: 'images/resources/author.jpg'); ?>" alt="">
                            <span class="status f-online"></span>
                        </figure>
                        <div class="mesg-meta">
                            <h6><?php echo htmlspecialchars($notif['username']); ?></h6>
                            <span><?php echo htmlspecialchars($notif['content']); ?></span>
                            <i><?php echo date('Y-m-d H:i', strtotime($notif['created_at'])); ?></i>
                        </div>
                    </a>
                    <?php if ($notif['is_read'] == 0): ?>
                        <span class="tag">New</span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li><div>No new notifications.</div></li>
        <?php endif; ?>
    </ul>
    <a href="#" title="" class="more-mesg">View All</a>
</div>
            </li>
            <li><a href="#" title="Help" data-ripple=""><i class="fa fa-question-circle"></i></a>
                <div class="dropdowns helps">
                    <span>Quick Help</span>
                    <form method="post">
                        <input type="text" placeholder="How can we help you?">
                    </form>
                    <span>Help with this page</span>
                    <ul class="help-drop">
                        <li><a href="forum.php" title=""><i class="fa fa-book"></i>Community & Forum</a></li>
                        <li><a href="faq.php" title=""><i class="fa fa-question-circle-o"></i>FAQs</a></li>
                        <li><a href="privacy.php" title=""><i class="fa fa-pencil-square-o"></i>Terms & Policy</a></li>
                        <li><a href="#" title=""><i class="fa fa-map-marker"></i>Contact</a></li>
                        <li><a href="#" title=""><i class="fa fa-exclamation-triangle"></i>Report a Problem</a></li>
                    </ul>
                </div>
            </li>
        </ul>
        <div class="user-img">
            <h5><?php echo htmlspecialchars($user['username']); ?></h5>
            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
            <span class="status f-online"></span>
            <div class="user-setting">
                <span class="seting-title">Chat setting <a href="#" title="">see all</a></span>
                <ul class="chat-setting">
                    <li><a href="#" title=""><span class="status f-online"></span>online</a></li>
                    <li><a href="#" title=""><span class="status f-away"></span>away</a></li>
                    <li><a href="#" title=""><span class="status f-off"></span>offline</a></li>
                </ul>
                <span class="seting-title">User setting <a href="#" title="">see all</a></span>
                <ul class="log-out">
                    <li><a href="about.php" title=""><i class="ti-user"></i> view profile</a></li>
                    <li><a href="settingaccount.php" title=""><i class="ti-pencil-alt"></i>edit profile</a></li>
                    <li><a href="#" title=""><i class="ti-target"></i>activity log</a></li>
                    <li><a href="settingaccount.php" title=""><i class="ti-settings"></i>account setting</a></li>
                    <li><a href="logout.php" title=""><i class="ti-power-off"></i>log out</a></li>
                </ul>
            </div>
        </div>
        <span class="ti-settings main-menu" data-ripple=""></span>
    </div>

    <!-- Hiển thị menu điều hướng -->
    <nav>
        <ul class="nav-list">
            <?php foreach ($menu as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>" title=""><?= $item['title'] ?></a>
                    <?php if (!empty($item['children'])): ?>
                        <ul>
                            <?php foreach ($item['children'] as $child): ?>
                                <li><a href="<?= $child['url'] ?>" title=""><?= $child['title'] ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</div><!-- topbar -->
<!-- END HEADER -->

<script>
document.querySelectorAll('.accept-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const requestId = this.closest('form').querySelector('input[name="request_id"]').value;
        const friendRequestItem = this.closest('li');

        fetch('handle_friend_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `request_id=${requestId}&action=accept`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to show friend status
                const statusSpan = friendRequestItem.querySelector('.friend-request-status');
                if (statusSpan) {
                    statusSpan.innerHTML = '<p class="underline">Bạn bè</p>';
                }
                
                // Remove friend request from list
                friendRequestItem.remove();
                
                // Update friend request count
                const requestCount = document.querySelector('.friend-requests-count');
                if (requestCount) {
                    const currentCount = parseInt(requestCount.textContent) - 1;
                    requestCount.textContent = currentCount > 0 ? currentCount : '';
                }

                // Show success notification
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xử lý yêu cầu kết bạn');
        });
    });
});

document.querySelectorAll('.unfriend-btn').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Bạn có chắc chắn muốn hủy kết bạn?')) {
            return;
        }

        const friendId = this.dataset.friendId;
        const friendItem = this.closest('li');

        fetch('handle_unfriend.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `friend_id=${friendId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove friend from list
                friendItem.remove();
                alert(data.message);
            } else {
                alert(data.message || 'Có lỗi xảy ra khi hủy kết bạn');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi hủy kết bạn');
        });
    });
});
</script>