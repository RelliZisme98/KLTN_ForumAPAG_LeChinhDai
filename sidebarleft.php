<?php
// session_start(); // Khởi tạo session
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Truy vấn để lấy dữ liệu từ bảng header_links
$sql_header = "SELECT * FROM header_links ORDER BY position ASC";
$result = $conn->query($sql_header);

// Mảng chứa các liên kết
$sidebar_menu = [];

// Phân loại các liên kết cha và con

// Phân loại các liên kết cha và con
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Nếu không có parent_position, thì đây là mục cha
        if ($row['parent_id'] === NULL) {
            $sidebar_menu[$row['id']] = [
                'title' => $row['title'],
                'url' => $row['url'],
                'position' => $row['position'], // Lưu position
                'children' => []
            ];
        } else {
            // Nếu có parent_position, tìm mục cha và thêm vào mục con
            foreach ($sidebar_menu as $parent_id => $parent_item) {
                if ($parent_item['position'] == $row['parent_id']) {
                    $sidebar_menu[$parent_id]['children'][] = [
                        'title' => $row['title'],
                        'url' => $row['url']
                    ];
                }
            }
        }
    }
}
$conn->close();
?>

<!-- SIDEBAR LEFT -->
	<div class="fixed-sidebar left">
		<div class="menu-left">
			<ul class="left-menu">
				<li>
					<a class="menu-small" href="#" title="">
						<i class="ti-menu"></i>
					</a>
				</li>
				<li>
					<a href="forum.php" title="Forum" data-toggle="tooltip" data-placement="right">
						<i class="fa fa-forumbee"></i>
					</a>
				</li>
				<li>
					<a href="timeline_friends.php" title="Friends" data-toggle="tooltip" data-placement="right">
						<i class="ti-user"></i>
					</a>
				</li>
				
				<li>
					<a href="chat-messenger.php" title="Messages" data-toggle="tooltip" data-placement="right">
						<i class="ti-comment-alt"></i>
					</a>
				</li>
				<li>
					<a href="notifications.php" title="Notification" data-toggle="tooltip" data-placement="right">
						<i class="fa fa-bell-o"></i>
					</a>
				</li>
				<li>
					<a href="support-and-help.php" title="Help" data-toggle="tooltip" data-placement="right">
						<i class="fa fa-question-circle-o">
						</i>
					</a>
				</li>
				<li>
					<a href="faq.php" title="Faq's" data-toggle="tooltip" data-placement="right">
						<i class="ti-light-bulb"></i>
					</a>
				</li>
			</ul>
		</div>
		<div class="left-menu-full">
    <ul class="menu-slide">
        <li><a class="closd-f-menu" href="#" title=""><i class="ti-close"></i> close Menu</a></li>
        <!-- Hiển thị menu từ mảng sidebar_menu -->
        <?php foreach ($sidebar_menu as $item): ?>
            <li class="menu-item-has-children">
                <a class="" href="<?= $item['url'] ?>" title=""><?= $item['title'] ?></a>
                <?php if (!empty($item['children'])): ?>
                    <ul class="submenu">
                        <?php foreach ($item['children'] as $child): ?>
                            <li><a href="<?= $child['url'] ?>" title=""><?= $child['title'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
</div><!-- left sidebar menu -->
<!-- END SIDEBAR LEFT -->