<?php
// Check if composer autoloader exists
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Please install Facebook SDK: composer require facebook/graph-sdk');
}

require_once __DIR__ . '/vendor/autoload.php';

use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Helpers\FacebookRedirectLoginHelper;

session_start();

try {
    $fb = new \Facebook\Facebook([
        'app_id' => '2831919040299540',
        'app_secret' => '4c0a29b6eae23528b334ddc1736d8d84',
        'default_graph_version' => 'v20.0',
        'persistent_data_handler' => 'session'
    ]);

    $helper = $fb->getRedirectLoginHelper();

    try {
        $accessToken = $helper->getAccessToken(); // Lấy access token
    } catch(FacebookResponseException $e) {
        // Khi Graph API trả về lỗi
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        // Khi SDK trả về lỗi
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }

    if (!isset($accessToken)) {
        echo 'No OAuth data could be obtained from the Facebook redirect.';
        exit;
    }

    // Lấy thông tin người dùng
    try {
        $response = $fb->get('/me?fields=id,name,email', $accessToken);
        $user = $response->getGraphUser();
        
        // Lưu thông tin người dùng vào session hoặc cơ sở dữ liệu
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        
        // Chuyển hướng về trang chủ
        header('Location: index.php');
        exit;
    } catch(FacebookResponseException $e) {
        // Lỗi khi gọi Graph API
        echo 'Graph returned an error: ' . $e->getMessage();
        exit;
    } catch(FacebookSDKException $e) {
        // Lỗi khi sử dụng SDK
        echo 'Facebook SDK returned an error: ' . $e->getMessage();
        exit;
    }
} catch (\Exception $e) {
    error_log('Facebook SDK initialization error: ' . $e->getMessage());
    die('Error initializing Facebook SDK');
}
?>