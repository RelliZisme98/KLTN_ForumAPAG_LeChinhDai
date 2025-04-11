<?php
$error_message = '';
$success_message = '';
$registered_people = 0;
$posts_published = 0;
$online_users = 0;

// Kết nối database
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy thống kê
$registered_query = $conn->query("SELECT COUNT(id) AS total_registered FROM users");
$registered_data = $registered_query->fetch_assoc();
$registered_people = $registered_data['total_registered'];

$posts_query = $conn->query("SELECT COUNT(id) AS total_posts FROM threads");
$posts_data = $posts_query->fetch_assoc();
$posts_published = $posts_data['total_posts'];

$time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
$online_users_query = $conn->query("SELECT COUNT(id) AS total_online FROM active_users WHERE last_activity > '$time_limit'");
$online_users_data = $online_users_query->fetch_assoc();
$online_users = $online_users_data['total_online'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = "Vui lòng điền đầy đủ thông tin.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error_message = "Tên đăng nhập phải từ 3-20 ký tự.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ.";
    } elseif (strlen($password) < 6) {
        $error_message = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        
        // Kiểm tra username/email tồn tại
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error_message = "Tên đăng nhập hoặc email đã tồn tại.";
        } else {
            // Thêm user mới
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sss", $username, $email, $password);
            
            if ($stmt->execute()) {
                $success_message = "Đăng ký thành công! Đang chuyển hướng...";
                header("Refresh: 2; url=login.php");
            } else {
                $error_message = "Có lỗi xảy ra, vui lòng thử lại.";
            }
        }
        $stmt->close();
        $conn->close();
    }
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
    <link rel="stylesheet" href="css/weather-icon.css">
    <link rel="stylesheet" href="css/weather-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/color.css">
    <link rel="stylesheet" href="css/responsive.css">

</head>

<body>
<div class="www-layout">
        <section>
            <div class="gap no-gap signin whitish medium-opacity">
                <div class="bg-image" style="background-image:url(images/resources/theme-bg.jpg)"></div>
                <div class="container">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="big-ad">
                                <figure><img src="images/LogaNapacut.png" alt=""></figure>
                                <h1>Welcome to the NAPA Social Network</h1>
                                <p>
                                NAPA là một mạng xã hội có thể được sử dụng để kết nối mọi người. Bạn có thể sử dụng mẫu này cho nhiều hoạt động xã hội khác nhau như tìm việc, hẹn hò, đăng bài, viết blog và nhiều hoạt động khác nữa. Hãy tham gia ngay và kết bạn thú vị khắp thế giới!
                                </p>
                                
                                <div class="fun-fact">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-4">
                                            <div class="fun-box">
                                                <i class="ti-check-box"></i>
                                                <h6>Tổng số người đã đăng kí</h6>
                                                <span><?php echo number_format($registered_people); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-4">
                                            <div class="fun-box">
                                                <i class="ti-layout-media-overlay-alt-2"></i>
                                                <h6>Tổng số bài đã đăng</h6>
                                                <span><?php echo number_format($posts_published); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-4">
                                            <div class="fun-box">
                                                <i class="ti-user"></i>
                                                <h6>Số thành viên đang trực tuyến</h6>
                                                <span><?php echo number_format($online_users); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- <div class="barcode">
                                    <figure><img src="images/resources/Barcode.jpg" alt=""></figure>
                                    <div class="app-download">
                                        <span>Download Mobile App and Scan QR Code to login</span>
                                        <ul class="colla-apps">
                                            <li><a title="" href="https://play.google.com/store?hl=en"><img src="images/android.png" alt="">android</a></li>
                                            <li><a title="" href="https://www.apple.com/lae/ios/app-store/"><img src="images/apple.png" alt="">iPhone</a></li>
                                            <li><a title="" href="https://www.microsoft.com/store/apps"><img src="images/windows.png" alt="">Windows</a></li>
                                        </ul>
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="we-login-register">
                                <div class="form-title">
                                    <i class="fa fa-key"></i>Sign Up
                                    <span>Sign Up now and meet the awesome friends around the world.</span>
                                </div>
                                <form class="we-form" method="post">
                                    <?php if ($error_message): ?>
                                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                                    <?php endif; ?>
                                    <?php if ($success_message): ?>
                                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                                    <?php endif; ?>
                                    <input type="text" name="username" placeholder="Username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                    <input type="email" name="email" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    <input type="password" name="password" placeholder="Password">
                                    <!-- <input type="checkbox"><label>Send code to Mobile</label> -->
                                    <button type="submit" data-ripple="">Register</button>
                                    <a class="forgot underline" href="forgot_password.php" title="">forgot password?</a>
                                </form>

                                <a data-ripple="" title="" href="#" class="with-smedia facebook"><i class="fa fa-facebook"></i></a>
                                <!-- <a data-ripple="" title="" href="#" class="with-smedia twitter"><i class="fa fa-twitter"></i></a> -->
                                <!-- <a data-ripple="" title="" href="#" class="with-smedia instagram"><i class="fa fa-instagram"></i></a> -->
                                <a data-ripple="" title="" href="#" class="with-smedia google"><i class="fa fa-google-plus"></i></a>
                                <span>already have an account? <a class="we-account underline" href="login.php" title="">Sign in</a></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

    </div>

    <script src="js/main.min.js"></script>
    <script src="js/script.js"></script>
</body>

</html>