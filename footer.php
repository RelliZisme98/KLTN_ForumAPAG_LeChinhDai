<?php
// Kết nối CSDL
$conn = new mysqli("localhost", "root", "", "ledai_forum");

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Truy vấn lấy các sections từ footer_sections
$sql_sections = "SELECT * FROM footer_sections ORDER BY position ASC";
$result_sections = $conn->query($sql_sections);
?>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-4">
                <div class="widget">
                    <div class="foot-logo">
                        <div class="logo">
                            <a href="index.php" title=""><img src="images/LogaNapacut.png" alt=""></a>
                        </div>  
                        <p>Học viện Hành chính Quốc gia</p>
                    </div>
                    <ul class="location">
                        <li><i class="fa fa-map-marker"></i><p>Trụ sở chính: Số 77 Nguyễn Chí Thanh – Đống Đa – Hà Nội Cơ sở: 36 Xuân La – Tây Hồ –Hà Nội Cơ sở: 371 Nguyễn Hoàng Tôn – Tây Hồ – Hà Nội </p></li>
                        <li><i class="fa fa-phone"></i><p>Điện thoại: 043-8343223. Fax: 043-8358943</p></li>
                    </ul>
                </div>
            </div>

            <?php
            // Duyệt qua từng section và hiển thị các liên kết
            if ($result_sections->num_rows > 0) {
                while ($section = $result_sections->fetch_assoc()) {
                    $section_id = $section['id'];
                    $section_title = $section['section_title'];

                    // Truy vấn các liên kết thuộc section này
                    $sql_links = "SELECT * FROM footer_links WHERE section_id = $section_id ORDER BY position ASC";
                    $result_links = $conn->query($sql_links);
            ?>

            <div class="col-lg-2 col-md-4 col-sm-6">
                <div class="widget">
                    <div class="widget-title"><h4><?php echo $section_title; ?></h4></div>
                    <ul class="list-style">
                        <?php
                        if ($result_links->num_rows > 0) {
                            while ($link = $result_links->fetch_assoc()) {
                                $link_title = $link['title'];
                                $link_url = $link['url'];
                                $icon_class = $link['icon_class'];
                        ?>
                        <li><i class="<?php echo $icon_class; ?>"></i> <a href="<?php echo $link_url; ?>" title=""><?php echo $link_title; ?></a></li>
                        <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>

            <?php
                }
            }
            ?>

        </div>
    </div>
</footer>

<!-- FOOTER -->
<div class="bottombar">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <span class="copyright">© Lê Chính Đại 2024. Diễn đàn Câu hỏi và Trả lời Học viện Hành chính Quốc gia</span>
                <i><img src="images/credit-cards.png" alt=""></i>
            </div>
        </div>
    </div>
</div>
</div>
<!-- END FOOTER -->

<?php
// Đóng kết nối
$conn->close();
?>