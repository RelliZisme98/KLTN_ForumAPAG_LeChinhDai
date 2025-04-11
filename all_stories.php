<?php
session_start();

// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    echo "Bạn cần đăng nhập để xem trang này.";
    exit; // Dừng thực thi nếu chưa đăng nhập
}

// Kiểm tra lỗi kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Lấy ID từ phiên
$user_id = $_SESSION['user_id']; 

// Thay đổi câu query để lấy thêm thông tin
$sql = "SELECT s.*, u.username, u.profile_picture
        FROM stories s
        JOIN users u ON s.user_id = u.id
        WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stories - NAPA Social Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
        }
        .central-meta {
            background: #fff;
            padding: 20px;
            margin: 20px auto;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .widget-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            text-align: center;
        }
        .story-postbox {
            overflow-x: scroll;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
            -webkit-overflow-scrolling: touch;
            position: relative;
            padding: 10px 0;
        }
        .story-postbox::-webkit-scrollbar {
            display: block !important;
            height: 8px;
            background: #f1f1f1;
            border-radius: 10px;
        }
        .story-postbox::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .story-postbox::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .story-container {
            display: flex;
            transition: transform 0.3s ease;
            width: max-content; /* Thay đổi từ 100% sang max-content */
            padding: 0 15px;
        }
        .story-box {
            position: relative;
            min-width: 200px; /* Tăng kích thước từ 150px lên 200px */
            height: 350px; /* Tăng chiều cao từ 250px lên 350px */
            margin: 0 15px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .story-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        }
        .story-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .story-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.7));
            padding: 20px;
            color: white;
        }
        .story-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 10px;
            padding: 15px;
        }
        .action-icon {
            background: rgba(255,255,255,0.95);
            padding: 8px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .action-icon:hover {
            background: white;
            transform: scale(1.1);
        }
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            gap: 10px;
        }
        .tab {
            padding: 10px 20px;
            background: #f0f2f5;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .tab.active {
            background: #3b5998;
            color: white;
        }
        .arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        .arrow:hover {
            background: white;
            transform: translateY(-50%) scale(1.1);
        }
        .arrow-left {
            left: 5px;
        }
        .arrow-right {
            right: 5px;
        }
        .create-story {
            position: relative;
            min-width: 200px;
            height: 350px;
            margin: 0 15px;
            border-radius: 20px;
            background: #fff;
            border: 3px dashed #3b5998;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: linear-gradient(145deg, #ffffff, #f0f2f5);
        }
        .create-story i {
            font-size: 40px;
            color: #3b5998;
            margin-bottom: 15px;
        }
        .create-story p {
            font-size: 18px;
            font-weight: 600;
            color: #3b5998;
        }
        .story-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
        }
        .story-modal.show {
            animation: modalFadeIn 0.3s ease;
        }
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .story-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(to bottom, #fff, #f8f9fa);
            padding: 30px;
            border-radius: 20px;
            width: 95%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .story-view-count {
            position: absolute;
            bottom: 45px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .text-story-container {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .story-options {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .color-option {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            cursor: pointer;
        }
        .font-option {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
        }
        .story-create-options {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .story-create-options button {
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            background: #3b5998;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .story-create-options button:hover {
            background: #2d4373;
        }
        #textStoryEditor, #photoStoryEditor {
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
        }
        #storyText {
            width: 100%;
            min-height: 100px;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px 0 0;
            border-top: 1px solid #ddd;
        }
        .modal-footer button {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
        }
        .submit-story {
            background: #3b5998;
            color: white;
        }
        .cancel-story {
            background: #f0f2f5;
        }
        .text-story {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
            color: white;
            font-size: 18px;
            line-height: 1.5;
            position: absolute;
            top: 0;
            left: 0;
        }
        .text-story p {
            margin: 0;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-height: 80%;
            overflow: hidden;
        }
        #textStoryEditor {
            padding: 20px;
        }
        #storyText {
            width: 100%;
            min-height: 150px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            resize: none;
        }
        .color-options {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.3s ease;
        }
        .color-option.selected {
            border-color: #3b5998;
            transform: scale(1.1);
        }
        .font-options {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        .font-option {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
            border-radius: 10px;
        }
        .font-option.selected {
            background: #3b5998;
            color: white;
            border-color: #3b5998;
        }
        .story-detail-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            z-index: 1001;
        }
        .story-detail-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 95%;
            max-width: 1000px;
            border-radius: 20px;
            overflow: hidden;
        }
        .story-detail-image {
            width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .story-detail-text {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            color: white;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
        }
        .story-overlay-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            width: 90%;
            z-index: 2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        #photoStoryEditor {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .story-text-overlay {
            width: 100%;
            min-height: 60px;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            resize: none;
            font-size: 16px;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .story-box {
                min-width: 160px;
                height: 280px;
            }
            .story-modal-content {
                width: 90%;
                padding: 20px;
            }
        }
        /* CSS cho thanh trượt ngang */
        .story-postbox::-webkit-scrollbar {
            display: block !important;
            height: 8px;
        }
        
        .story-postbox::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .story-postbox::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        .story-postbox::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* CSS cho avatar trong story */
        .story-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            position: absolute;
            bottom: 20px;
            left: 20px;
            border: 3px solid #3b5998;
            overflow: hidden;
        }

        .story-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .story-name {
            position: absolute;
            bottom: 80px;
            left: 20px;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.5);
        }

        /* Style cho avatar user */
        .story-user-thumb {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            position: absolute;
            bottom: 10px;
            left: 10px;
            border: 3px solid #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 3;
        }

        .story-user-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        /* Điều chỉnh lại phần create story */
        .create-story {
            position: relative;
            min-width: 200px;
            height: 350px;
            margin: 0 15px;
            border-radius: 20px;
            background: linear-gradient(145deg, #ffffff, #f0f2f5);
            border: 3px dashed #3b5998;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .story-delete {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .story-delete:hover {
            background: #ff4444;
            color: white;
        }
        
        .story-delete i {
            font-size: 16px;
            color: #ff4444;
        }
        
        .story-delete:hover i {
            color: white;
        }

        .story-reactions {
            position: absolute;
            bottom: 60px;
            right: 10px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 5px 10px;
            display: flex;
            gap: 5px;
            z-index: 3;
        }

        .story-reactions button {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .story-reactions button:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .story-comments {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px;
            display: none;
            max-height: 200px;
            overflow-y: auto;
        }

        .story-comment-input {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .story-comment-input input {
            flex: 1;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 20px;
        }

        .story-comment {
            margin: 5px 0;
            padding: 5px;
        }

        .story-comment .reply {
            margin-left: 20px;
            font-size: 0.9em;
        }
        .story-detail-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            padding: 15px;
            border-radius: 0 0 20px 20px;
        }

        .story-comments-section {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.9);
        }

        .story-reaction-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .reaction-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 20px;
            transition: all 0.3s;
        }

        .reaction-btn:hover {
            background: rgba(0,0,0,0.1);
            transform: scale(1.1);
        }

        .reaction-btn.active {
            background: #3b5998;
            color: white;
        }

        .comment-input {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .comment-input input {
            flex: 1;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
        }

        .comment-input button {
            background: #3b5998;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
        }

        .story-comment {
            padding: 8px;
            margin: 5px 0;
            border-radius: 10px;
            background: rgba(255,255,255,0.8);
        }

        .comment-author {
            font-weight: bold;
            margin-right: 5px;
        }

        .comment-time {
            font-size: 12px;
            color: #666;
        }

        .reply-section {
            margin-left: 20px;
            margin-top: 5px;
        }
        .story-detail-content {
            position: relative;
            background: white;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            color: white;
            font-size: 30px;
            cursor: pointer;
            z-index: 10;
        }

        .story-media {
            width: 100%;
            height: 60vh;
            background: #000;
        }

        .story-media img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .story-detail-footer {
            padding: 15px;
            background: white;
        }

        .story-reactions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            justify-content: center;
        }

        .reaction-btn {
            background: none;
            border: none;
            font-size: 24px;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 20px;
            transition: all 0.3s;
        }

        .reaction-btn:hover {
            transform: scale(1.2);
            background: #f0f2f5;
        }

        .reaction-btn.active {
            background: #e7f3ff;
        }

        .story-comments-section {
            border-top: 1px solid #eee;
            padding-top: 15px;
        }

        .comments-list {
            max-height: 200px;
            overflow-y: auto;
            margin-bottom: 15px;
        }

        .comment-input {
            display: flex;
            gap: 10px;
        }

        .comment-input input {
            flex: 1;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
        }

        .comment-input button {
            padding: 8px 20px;
            background: #1877f2;
            color: white;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="central-meta">
    <h4 class="widget-title">Stories</h4>
    
    <div class="tabs" style="margin-bottom: 20px;">
        <button class="tab active" onclick="loadStories('all')">Tất cả tin</button>
        <button class="tab" onclick="loadStories('my')">Tin của tôi</button>
    </div>

    <div class="story-wrapper">
        <div class="story-postbox">
            <div class="story-container" id="storyContainer">
                <div class="create-story" onclick="openStoryCreator()">
                    <figure>
                        <?php if(isset($_SESSION['profile_picture'])): ?>
                            <img src="uploads/profile_pictures/<?php echo $_SESSION['profile_picture']; ?>" alt="Your Profile">
                        <?php else: ?>
                            <img src="images/resources/default-avatar.jpg" alt="Default Avatar">
                        <?php endif; ?>
                    </figure>
                    <i class="fa fa-plus"></i>
                    <span>Tạo tin</span>
                </div>
            </div>
        </div>
        <button class="arrow arrow-left" id="prevBtn"><i class="fas fa-chevron-left"></i></button>
        <button class="arrow arrow-right" id="nextBtn"><i class="fas fa-chevron-right"></i></button>
    </div>
</div>

<!-- Story Creation Modal -->
<div id="storyModal" class="story-modal">
    <div class="story-modal-content">
        <h3>Create Story</h3>
        <div class="story-create-options">
            <button onclick="showTextStory()">Text Story</button>
            <button onclick="showPhotoStory()">Photo Story</button>
        </div>
        
        <div id="textStoryEditor" style="display:none;">
            <textarea placeholder="What's on your mind?" id="storyText"></textarea>
            <div class="story-options">
                <h4>Background Color</h4>
                <div class="color-options">
                    <div class="color-option selected" style="background: #3b5998" onclick="selectColor(this)"></div>
                    <div class="color-option" style="background: #192f6a" onclick="selectColor(this)"></div>
                    <div class="color-option" style="background: #c4302b" onclick="selectColor(this)"></div>
                    <div class="color-option" style="background: #2c4762" onclick="selectColor(this)"></div>
                    <div class="color-option" style="background: #25D366" onclick="selectColor(this)"></div>
                </div>
                <h4>Font Style</h4>
                <div class="font-options">
                    <span class="font-option selected" onclick="selectFont(this)">Arial</span>
                    <span class="font-option" onclick="selectFont(this)">Times New Roman</span>
                    <span class="font-option" onclick="selectFont(this)">Helvetica</span>
                    <span class="font-option" onclick="selectFont(this)">Georgia</span>
                </div>
            </div>
        </div>

        <div id="photoStoryEditor" style="display:none;">
            <input type="file" id="storyImage" accept="image/*" onchange="previewImage(this)">
            <img id="imagePreview" style="max-width: 100%; display: none;">
            <textarea id="storyImageText" class="story-text-overlay" 
                      placeholder="Add text to your story (optional)"></textarea>
        </div>

        <div class="modal-footer">
            <button class="submit-story" onclick="submitStory()">Share Story</button>
            <button class="cancel-story" onclick="closeStoryModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- Story Detail Modal -->
<div id="storyDetailModal" class="story-detail-modal">
    <div class="story-detail-content">
        <span class="close-modal" onclick="closeStoryModal()">&times;</span>
        <div class="story-media"></div>
        <div class="story-detail-footer">
            <div class="story-reactions">
                <button onclick="reactToStory(currentStoryId, 'like')" class="reaction-btn" data-type="like">👍</button>
                <button onclick="reactToStory(currentStoryId, 'love')" class="reaction-btn" data-type="love">❤️</button>
                <button onclick="reactToStory(currentStoryId, 'haha')" class="reaction-btn" data-type="haha">😆</button>
                <button onclick="reactToStory(currentStoryId, 'wow')" class="reaction-btn" data-type="wow">😮</button>
                <button onclick="reactToStory(currentStoryId, 'sad')" class="reaction-btn" data-type="sad">😢</button>
            </div>
            <div class="story-comments-section">
                <div class="comments-list"></div>
                <div class="comment-input">
                    <input type="text" placeholder="Viết bình luận..." id="commentInput">
                    <button onclick="submitComment()">Gửi</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentIndex = 0;
let activeTab = 'all';

function loadStories(type) {
    // Update active tab
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelector(`.tab[onclick="loadStories('${type}')"]`).classList.add('active');

    fetch(`fetch_${type}_stories.php`)
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data); // Debug log
            
            let html = '';
            
            // Add create story box
            if (data.current_user) {
                html += `
                    <div class="create-story" onclick="openStoryCreator()">
                        <div class="story-user-thumb">
                            <img src="${data.current_user.profile_picture}" alt="Your Profile">
                        </div>
                        <i class="fas fa-plus"></i>
                        <p>Tạo tin</p>
                    </div>`;
            }

            // Add stories
            if (data.stories && data.stories.length > 0) {
                data.stories.forEach(story => {
                    html += `
                        <div class="story-box">
                            ${story.content_text ? `
                                <div class="text-story" style="background-color: ${story.background_color};">
                                    <p style="font-family: ${story.font_style}">${story.content_text}</p>
                                </div>
                            ` : story.image_url ? `
                                <img class="story-image" src="uploads/stories/${story.image_url}" alt="Story">
                            ` : ''}
                            <div class="story-overlay">
                                <div class="story-name">${story.username}</div>
                                <div class="story-user-thumb">
                                    <img src="${story.profile_picture}" alt="${story.username}">
                                </div>
                                ${type === 'my' ? `
                                    <div class="story-delete" onclick="deleteStory(${story.story_id}, event)">
                                        <i class="fas fa-trash"></i>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
            } else {
                html += '<div style="text-align: center; padding: 20px;">Không có tin nào</div>';
            }
            
            document.getElementById('storyContainer').innerHTML = html;
            updateArrowVisibility();
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('storyContainer').innerHTML = `
                <div class="create-story" onclick="openStoryCreator()">
                    <i class="fas fa-plus-circle"></i>
                    <p>Tạo tin</p>
                </div>
                <div style="text-align: center; color: red; padding: 20px;">Đã xảy ra lỗi khi tải tin</div>
            `;
        });
}

function updateArrowVisibility() {
    const container = document.querySelector('.story-postbox');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    // Hiển thị/ẩn nút prev dựa trên vị trí cuộn
    prevBtn.style.display = container.scrollLeft <= 0 ? 'none' : 'flex';

    // Hiển thị/ẩn nút next dựa trên việc còn content để cuộn không
    nextBtn.style.display = 
        container.scrollLeft + container.clientWidth >= container.scrollWidth 
        ? 'none' 
        : 'flex';
}

function reactToStory(storyId, reaction) {
    // Implement story reaction logic
}

function sendMessage(userId) {
    // Implement message sending logic
}

// Initial load
loadStories('all');

document.getElementById('nextBtn').onclick = function() {
    if (currentIndex < totalStories - 1) {
        currentIndex++;
        updateCarousel();
    }
};

document.getElementById('prevBtn').onclick = function() {
    if (currentIndex > 0) {
        currentIndex--;
        updateCarousel();
    }
};

function updateCarousel() {
    const container = document.querySelector('.story-postbox');
    const scrollAmount = 400; // Điều chỉnh khoảng cách cuộn

    document.getElementById('nextBtn').onclick = () => {
        container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        updateArrowVisibility();
    };

    document.getElementById('prevBtn').onclick = () => {
        container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        updateArrowVisibility();
    };
}

function openStoryCreator() {
    document.getElementById('storyModal').style.display = 'block';
    document.getElementById('storyModal').classList.add('show');
}

function closeStoryModal() {
    const modal = document.getElementById('storyModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function showTextStory() {
    document.getElementById('textStoryEditor').style.display = 'block';
    document.getElementById('photoStoryEditor').style.display = 'none';
}

function showPhotoStory() {
    document.getElementById('textStoryEditor').style.display = 'none';
    document.getElementById('photoStoryEditor').style.display = 'block';
}

function submitStory() {
    const formData = new FormData();
    const textContent = document.getElementById('storyText').value;
    const imageFile = document.getElementById('storyImage').files[0];
    const imageText = document.getElementById('storyImageText').value;
    const backgroundColor = document.querySelector('.color-option.selected')?.style.background || '#3b5998';
    const fontStyle = document.querySelector('.font-option.selected')?.textContent || 'Arial';

    if (textContent) {
        formData.append('type', 'text');
        formData.append('content_text', textContent);
        formData.append('background_color', backgroundColor);
        formData.append('font_style', fontStyle);
    } else if (imageFile) {
        formData.append('type', 'image');
        formData.append('image', imageFile);
        formData.append('image_text', imageText);
    } else {
        alert('Please add some content to your story');
        return;
    }

    fetch('create_story.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeStoryModal();
            loadStories(activeTab);
        } else {
            alert(data.message || 'Failed to create story');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to create story');
    });
}

let currentStoryId = null;

function viewStory(story) {
    currentStoryId = story.story_id;
    const modal = document.getElementById('storyDetailModal');
    const mediaContainer = modal.querySelector('.story-media');
    
    // Hiển thị nội dung story
    if (story.content_text) {
        mediaContainer.innerHTML = `
            <div class="text-story" style="background-color: ${story.background_color};">
                <p style="font-family: ${story.font_style}">${story.content_text}</p>
            </div>
        `;
    } else if (story.image_url) {
        mediaContainer.innerHTML = `
            <img src="uploads/stories/${story.image_url}" alt="Story">
        `;
    }

    // Load reactions và comments
    loadStoryReactions(story.story_id);
    loadStoryComments(story.story_id);
    
    modal.style.display = 'block';
}

function closeStoryModal() {
    document.getElementById('storyDetailModal').style.display = 'none';
    currentStoryId = null;
}

// Cập nhật các hàm xử lý react và comment
function submitComment() {
    const input = document.getElementById('commentInput');
    const comment = input.value.trim();
    
    if (!comment || !currentStoryId) return;

    fetch('story_comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            story_id: currentStoryId,
            comment: comment
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadStoryComments(currentStoryId);
        }
    });
}

function loadStoryComments(storyId) {
    fetch(`get_story_comments.php?story_id=${storyId}`)
        .then(response => response.json())
        .then(data => {
            const commentsList = document.querySelector('.comments-list');
            let html = '';
            
            data.comments.forEach(comment => {
                html += `
                    <div class="comment-item">
                        <strong>${comment.username}</strong>
                        <span>${comment.comment}</span>
                        <div class="comment-actions">
                            <small>${formatTime(comment.created_at)}</small>
                            <button onclick="showReplyForm(${comment.id})">Trả lời</button>
                        </div>
                        ${comment.replies ? comment.replies.map(reply => `
                            <div class="reply-item">
                                <strong>${reply.username}</strong>
                                <span>${reply.comment}</span>
                                <small>${formatTime(reply.created_at)}</small>
                            </div>
                        `).join('') : ''}
                    </div>
                `;
            });
            
            commentsList.innerHTML = html;
        });
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Vừa xong';
    if (diff < 3600000) return Math.floor(diff/60000) + ' phút trước';
    if (diff < 86400000) return Math.floor(diff/3600000) + ' giờ trước';
    return Math.floor(diff/86400000) + ' ngày trước';
}

// Thêm event listener để cập nhật visibility của arrows khi cuộn
document.querySelector('.story-postbox').addEventListener('scroll', updateArrowVisibility);

// Khởi tạo carousel khi load trang
document.addEventListener('DOMContentLoaded', function() {
    updateCarousel();
    updateArrowVisibility();
});

function deleteStory(storyId, event) {
    event.stopPropagation(); // Prevent story view modal from opening
    
    if (confirm('Bạn có chắc chắn muốn xóa tin này không?')) {
        fetch('delete_story.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                story_id: storyId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadStories('my'); // Reload stories after deletion
            } else {
                alert(data.message || 'Không thể xóa tin');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã xảy ra lỗi khi xóa tin');
        });
    }
}
</script>

</body>
</html>
