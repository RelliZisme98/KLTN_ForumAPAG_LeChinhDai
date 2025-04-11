<?php
// session_start(); // Khởi tạo session
// Kết nối cơ sở dữ liệu
$conn = new mysqli('localhost', 'root', '', 'ledai_forum');
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    die("Vui lòng đăng nhập để xem danh sách bạn bè.");
}

$user_id = $_SESSION['user_id']; // Lấy user_id từ session

// Cập nhật thời gian hoạt động của người dùng hiện tại
$sql = "UPDATE users SET last_activity = NOW() WHERE id = $user_id";
$conn->query($sql);
?>

<head>
    <!-- Required libraries in correct order -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/socket.io/4.5.4/socket.io.js"></script>
    <script src="https://unpkg.com/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/simple-peer/9.11.1/simplepeer.min.js"></script>
    <!-- <script src="/call-server/server.js"></script> -->
    <script src="https://unpkg.com/simple-peer@9.11.1/simplepeer.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="/voice-video-chat/public/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="/css/call-notification.css" rel="stylesheet">
   
</head>

<script>
    // Khai báo các biến toàn cục ở đầu file
    var currentFriendId;
    var mediaRecorder = null;
    var audioChunks = [];
    var isRecording = false;
    var audioUrl = null;
    var timerInterval;

    function openChat(friend_id) {
        currentFriendId = friend_id; // Cập nhật friend_id hiện tại
        const chatBox = document.getElementById('custom-chat-box');

        if (chatBox.classList.contains('show')) {
            return;
        }

        chatBox.classList.add('show');

        // Tải tên bạn bè
        $.ajax({
            url: 'get_friend_name.php',
            method: 'GET',
            data: {
                friend_id: friend_id
            },
            success: function(friendName) {
                $('#custom-chat-friend-name').text(friendName);
            },
            error: function() {
                console.error("Lỗi khi tải tên bạn bè.");
            }
        });

        // Tải nội dung chat với người bạn được chọn
        $.ajax({
            url: 'load_chat.php',
            method: 'GET',
            data: {
                friend_id: friend_id
            },
            success: function(data) {
                $('#custom-chat-content').html(data);
                $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight); // Tự động cuộn xuống cuối
            },
            error: function() {
                console.error("Lỗi khi tải nội dung chat.");
            }
        });

        // Đánh dấu các tin nhắn là "đã xem"
        markMessagesAsSeen(); // Gọi hàm để đánh dấu các tin nhắn là đã xem
    }

    function closeChat() {
        const chatBox = document.getElementById('custom-chat-box');
        chatBox.classList.remove('show'); // Xóa lớp show để đóng hộp chat
    }

    function sendMessage() {
        var message = document.getElementById('custom-chat-input').value;
        if (message.trim() === "") {
            alert("Vui lòng nhập tin nhắn.");
            return;
        }

        $.ajax({
            url: 'send_message.php',
            method: 'POST',
            data: {
                message: message,
                receiver_id: currentFriendId
            },
            success: function(data) {
                $('#custom-chat-content').append(data); // Hiển thị tin nhắn
                document.getElementById('custom-chat-input').value = '';
                $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight); // Tự động cuộn xuống
            },
            error: function(xhr, status, error) {
                console.error("Lỗi khi gửi tin nhắn: " + error);
            },

        });
    }

    function loadChatMessages() {
        $.ajax({
            url: 'load_chat.php',
            method: 'GET',
            data: {
                friend_id: currentFriendId
            },
            success: function(data) {
                $('#custom-chat-content').html(data); // Cập nhật nội dung chat
                $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight); // Tự động cuộn xuống cuối
                bindImageClickEvents(); // Thêm dòng này
            },
            error: function(xhr, status, error) {
                console.error("Lỗi khi tải nội dung chat: " + error);
            }
        });
    }

   

    function markMessagesAsSeen() {
        $.ajax({
            url: 'mark_as_seen.php',
            method: 'POST',
            data: {
                friend_id: currentFriendId
            },
            success: function() {
                // Cập nhật lại nội dung chat để hiển thị trạng thái "Đã xem"
                loadChatMessages();
            },
            error: function() {
                console.error("Lỗi khi đánh dấu tin nhắn đã xem.");
            }
        });
    }

    // Gọi hàm này khi người dùng cuộn tới cuối nội dung chat
    $('#custom-chat-content').scroll(function() {
        if ($('#custom-chat-content').scrollTop() + $('#custom-chat-content').innerHeight() >= $('#custom-chat-content')[0].scrollHeight) {
            markMessagesAsSeen();
        }
    });

    // Xử lý xem trước ảnh và gửi
    function previewImage(event) {
        const file = event.target.files[0];

        if (!file) return;

        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Vui lòng chỉ chọn file ảnh');
            return;
        }

        // Check file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Kích thước file không được vượt quá 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview-container').style.display = 'block';
            document.getElementById('preview-content').innerHTML = `
            <div class="preview-image-wrapper">
                <img src="${e.target.result}" alt="Xem trước ảnh">
            </div>
        `;

            // Show preview container and control buttons
            document.querySelector('.custom-text-box').style.display = 'none';
            document.getElementById('preview-container').style.display = 'block';
        }
        reader.readAsDataURL(file);
    }

    function confirmSend() {
        const fileInput = document.getElementById('image-upload');
        if (fileInput.files.length > 0) {
            sendImage();
            cancelPreview();
        }
    }

    function cancelPreview() {
        document.getElementById('preview-container').style.display = 'none';
        document.getElementById('preview-content').innerHTML = '';
        document.getElementById('image-upload').value = '';
        document.querySelector('.custom-text-box').style.display = 'flex';
    }

    function sendImage() {
        const fileInput = document.getElementById('image-upload');
        const file = fileInput.files[0];

        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);
        formData.append('receiver_id', currentFriendId);

        // Show loading indicator
        const loadingHtml = "<div class='loading-indicator'>Đang gửi ảnh...</div>";
        $('#custom-chat-content').append(loadingHtml);

        $.ajax({
            url: 'send_image.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('.loading-indicator').remove();
                try {
                    // Kiểm tra xem response có phải là JSON không
                    const jsonResponse = JSON.parse(response);
                    if (jsonResponse.error) {
                        alert(jsonResponse.error);
                    }
                } catch (e) {
                    // Nếu không phải JSON, giả sử là HTML
                    if (response.includes('<img')) {
                        $('#custom-chat-content').append(response);
                        $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight);
                    } else {
                        console.error("Invalid response:", response);
                        alert('Định dạng phản hồi không hợp lệ');
                    }
                }
            },
            error: function(xhr, status, error) {
                $('.loading-indicator').remove();
                console.error('Ajax error:', status, error);
                alert('Lỗi kết nối: ' + error);
            }
        });

        // Reset file input
        fileInput.value = '';
    }

    function sendFile() {
        var fileInput = document.getElementById('file-upload');
        var file = fileInput.files[0];

        if (file) {
            var formData = new FormData();
            formData.append('file', file);
            formData.append('receiver_id', currentFriendId);

            $.ajax({
                url: 'send_file.php',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(data) {
                    $('#custom-chat-content').append(data); // Hiển thị tệp đã gửi
                    $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight); // Tự động cuộn xuống cuối
                },
                error: function(xhr, status, error) {
                    console.error("Lỗi khi gửi tệp.");
                }
            });
        }
    }

    function startRecording() {
        console.log('Starting recording...');
        
        // Reset any existing recordings
        if (mediaRecorder) {
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
            mediaRecorder = null;
        }
        
        // Add debug info
        console.log('Browser capabilities:', {
            mediaDevices: !!navigator.mediaDevices,
            getUserMedia: !!navigator.mediaDevices?.getUserMedia,
            permissions: !!navigator.permissions
        });

        // Force HTTPS check
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
            alert('Microphone access requires HTTPS. Please use HTTPS or localhost.');
            return;
        }

        // Check browser support first
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Trình duyệt không hỗ trợ ghi âm. Vui lòng sử dụng Chrome, Firefox hoặc Edge mới nhất.');
            return;
        }

        // Get current permission state
        navigator.permissions.query({name: 'microphone'})
            .then(permissionStatus => {
                console.log('Current permission status:', permissionStatus.state);
                
                if (permissionStatus.state === 'denied') {
                    alert('Quyền truy cập microphone đã bị chặn. Vui lòng:\n\n' +
                          '1. Click vào biểu tượng 🔒 bên trái thanh địa chỉ\n' +
                          '2. Tìm mục Microphone\n' +
                          '3. Chọn "Allow"\n' +
                          '4. Tải lại trang');
                    return;
                }

                // Request microphone access with explicit options
                const constraints = {
                    audio: {
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                        channelCount: 1,
                        sampleRate: 44100
                    }
                };

                navigator.mediaDevices.getUserMedia(constraints)
                    .then(stream => {
                        console.log('Got media stream:', stream);
                        isRecording = true;
                        audioChunks = [];
                        
                        try {
                            mediaRecorder = new MediaRecorder(stream, {
                                mimeType: 'audio/webm;codecs=opus'
                            });
                            
                            // Show recording UI
                            document.getElementById('voice-message-form').style.display = 'block';
                            document.getElementById('voice-controls').style.display = 'flex';
                            document.querySelector('.custom-text-box').style.display = 'none';
                            
                            mediaRecorder.ondataavailable = e => {
                                audioChunks.push(e.data);
                                console.log('Data chunk received:', e.data.size, 'bytes');
                            };

                            mediaRecorder.onstop = () => {
                                console.log('Recording stopped, processing...');
                                const audioBlob = new Blob(audioChunks, {type: 'audio/webm'});
                                audioUrl = URL.createObjectURL(audioBlob);
                                showAudioPreview(audioUrl);
                            };

                            mediaRecorder.start(1000); // Record in 1-second chunks
                            startTimer();
                            console.log('Recording started successfully');

                        } catch (err) {
                            console.error('MediaRecorder error:', err);
                            alert('Lỗi khởi tạo recorder: ' + err.message);
                            resetRecordingUI();
                        }
                    })
                    .catch(err => {
                        console.error('getUserMedia error:', err);
                        handleMicrophoneError(err);
                    });
            })
            .catch(err => {
                console.error('Permission query failed:', err);
                // Fallback to direct getUserMedia request
                tryDirectAccess();
            });
    }

    function startRecordingProcess() {
        // Thêm kiểm tra sâu hơn về quyền và trạng thái
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Trình duyệt của bạn không hỗ trợ ghi âm');
            return;
        }

        navigator.permissions.query({ name: 'microphone' })
            .then(async function(permissionStatus) {
                console.log('Permission status:', permissionStatus.state);
                
                if (permissionStatus.state === 'denied') {
                    alert('Bạn đã chặn quyền truy cập microphone. Vui lòng cấp lại quyền trong cài đặt trình duyệt.');
                    showEnableMicInstructions();
                    return;
                }

                try {
                    // Yêu cầu quyền truy cập một cách rõ ràng
                    const stream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: true
                        }
                    });

                    // Nếu có stream, nghĩa là đã được cấp quyền
                    startRecordingWithStream(stream);

                } catch (err) {
                    console.error('Error accessing microphone:', err);
                    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                        alert('Vui lòng làm mới trang và cấp quyền truy cập microphone khi được yêu cầu');
                    } else {
                        alert('Lỗi khi truy cập microphone: ' + err.message);
                    }
                }
            })
            .catch(function(err) {
                console.error('Error checking permission:', err);
                // Thử truy cập trực tiếp nếu không kiểm tra được quyền
                tryDirectAccess();
            });
    }

    function startRecordingWithStream(stream) {
        isRecording = true;
        audioChunks = [];
        mediaRecorder = new MediaRecorder(stream);

        // Hiển thị controls ghi âm và ẩn form chat
        document.getElementById('voice-message-form').style.display = 'block';
        document.getElementById('voice-controls').style.display = 'flex';
        document.querySelector('.custom-text-box').style.display = 'none';
        document.getElementById('audio-preview-container').innerHTML = '';

        mediaRecorder.ondataavailable = function(e) {
            audioChunks.push(e.data);
        };

        mediaRecorder.onstop = function() {
            const audioBlob = new Blob(audioChunks, {
                'type': 'audio/ogg; codecs=opus'
            });
            audioUrl = URL.createObjectURL(audioBlob);
            showAudioPreview(audioUrl);
        };

        mediaRecorder.start();
        startTimer();
    }

    function tryDirectAccess() {
        console.log('Trying direct microphone access...');
        navigator.mediaDevices.getUserMedia({audio: true})
            .then(stream => {
                console.log('Direct access successful');
                startRecordingWithStream(stream);
            })
            .catch(err => {
                console.error('Direct access failed:', err);
                alert('Không thể truy cập microphone. Vui lòng:\n' +
                      '1. Đảm bảo microphone được kết nối\n' +
                      '2. Cho phép quyền trong cài đặt trình duyệt\n' +
                      '3. Tải lại trang sau khi thay đổi cài đặt');
            });
    }

    function handleMicrophoneError(error) {
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            alert('Quyền truy cập microphone bị từ chối. Vui lòng kiểm tra lại cài đặt quyền truy cập trong trình duyệt.');
            showEnableMicInstructions();
        } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            alert('Không tìm thấy thiết bị microphone. Vui lòng kiểm tra kết nối microphone của bạn.');
        } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
            alert('Không thể truy cập microphone. Thiết bị có thể đang được sử dụng bởi ứng dụng khác.');
        } else {
            alert('Lỗi khi truy cập microphone: ' + error.message);
        }
        isRecording = false;
    }

    function showEnableMicInstructions() {
        // Hướng dẫn cách bật quyền microphone trong các trình duyệt phổ biến
        const instructions = `
            Để bật quyền truy cập microphone:
            
            Chrome: 
            1. Nhấp vào biểu tượng khóa/info bên trái URL
            2. Tìm mục "Microphone"
            3. Chọn "Allow"
            
            Firefox:
            1. Nhấp vào biểu tượng khóa bên trái URL
            2. Xóa cài đặt trang web hiện tại
            3. Tải lại trang và cho phép quyền microphone
            
            Safari:
            1. Mở Preferences > Websites
            2. Tìm mục Microphone
            3. Cho phép website này truy cập
            
            Sau khi cấp quyền, vui lòng tải lại trang web.
        `;
        
        alert(instructions);
    }

    function stopRecording() {
        if (!isRecording) return;

        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        isRecording = false;
        stopTimer();
    }

    function cancelRecording() {
        if (!isRecording) return;

        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
        isRecording = false;
        audioChunks = [];
        stopTimer();
        resetRecordingUI();
    }

    function resetRecordingUI() {
        // Hiển thị lại form chat và nút micro
        document.getElementById('voice-controls').style.display = 'none';
        document.getElementById('voice-btn').style.display = 'block';
        document.getElementById('timer').textContent = '00:00';
        document.getElementById('audio-preview-container').innerHTML = '';
        document.querySelector('.custom-text-box').style.display = 'flex';
    }

    function showAudioPreview(audioUrl) {
        const previewHtml = `
        <div class="audio-preview">
            <audio controls>
                <source src="${audioUrl}" type="audio/ogg">
            </audio>
            <div class="audio-controls">
                <button onclick="sendVoiceMessage()" class="send-audio-btn">
                    <i class="fa fa-paper-plane"></i> Gửi
                </button>
                <button onclick="cancelAudioPreview()" class="cancel-audio-btn">
                    <i class="fa fa-times"></i> Hủy
                </button>
            </div>
        </div>
    `;

        document.getElementById('audio-preview-container').innerHTML = previewHtml;
        document.getElementById('voice-controls').style.display = 'none';
        document.getElementById('voice-btn').style.display = 'block';
    }

    function cancelAudioPreview() {
        console.log('Canceling audio preview...');
        document.getElementById('audio-preview-container').innerHTML = '';
        document.querySelector('.custom-text-box').style.display = 'flex';
        if (audioUrl) {
            URL.revokeObjectURL(audioUrl);
            audioUrl = null;
        }
    }

    function startTimer() {
        let seconds = 0;
        const timerDisplay = document.getElementById('timer');

        timerInterval = setInterval(() => {
            seconds++;
            const minutes = Math.floor(seconds / 60);
            const remainingSeconds = seconds % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;

            // Tự động dừng sau 1 phút
            if (seconds >= 60) {
                stopRecording();
            }
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
    }

    function sendVoiceMessage() {
        console.log('Sending voice message...');
        if (!audioUrl) {
            console.log('No audio URL available');
            return;
        }

        fetch(audioUrl)
            .then(res => res.blob())
            .then(audioBlob => {
                const formData = new FormData();
                formData.append('voice_message', audioBlob, 'voice_message.ogg');
                formData.append('receiver_id', currentFriendId);

                $.ajax({
                    url: 'send_voice.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Voice message sent successfully');
                        $('#custom-chat-content').append(response);
                        $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight);
                        cancelAudioPreview();
                        resetRecordingUI();
                    },
                    error: function(xhr, status, error) {
                        console.error('Error sending voice message:', error);
                        alert('Lỗi khi gửi tin nhắn thoại');
                    }
                });
            })
            .catch(error => {
                console.error('Error processing audio:', error);
                alert('Lỗi khi xử lý âm thanh');
            });
    }

    $('#custom-chat-input').keypress(function(e) {
        if (e.which === 13 && !e.shiftKey) {
            sendMessage();
            return false; // Ngăn việc Enter thêm dòng mới
        }
    });
  
    function startVideoCallAsReceiver(callData) {
        // Tạo cửa sổ video call và truy cập camera
        startVideoCall();
        // Thêm logic để nhận SDP offer từ người gọi và thiết lập WebRTC
    }

    // Hàm xử lý click vào ảnh
    function openImageModal(imgSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        modal.style.display = "block";
        modalImg.src = imgSrc;
    }

    // Cập nhật hàm format tin nhắn để thêm sự kiện click cho ảnh
    function formatMessageHTML(message, user_id) {
        // ...existing code...

        if (message.file_url && message.file_url.match(/\.(jpg|jpeg|png|gif)$/i)) {
            html += `<img src="${message.file_url}" 
                     class="message-image" 
                     onclick="openImageModal('${message.file_url}')" 
                     alt="Sent image">`;
        }

        // ...existing code...
    }

    // Đăng ký user ID với socket server khi kết nối
    socket.on('connect', () => {
        socket.emit('register-user', window.currentUserId);
    });
</script>

<!-- Move scripts to end of body, just before </body> -->
<script>
// Initialize Socket.IO first
const socket = io('http://localhost:3000', {
    transports: ['websocket'],
    upgrade: false,
    reconnection: true,
    reconnectionAttempts: 5,
    path: '/socket.io'  // Add explicit path
});

// Debug connection with more details
socket.on('connect', () => {
    console.log('Connected to call server with ID:', socket.id);
    if (window.currentUserId) {
        socket.emit('register-user', window.currentUserId);
    }
});

socket.on('connect_error', (error) => {
    console.error('Socket.IO connection error:', error);
    console.log('Connection state:', socket.connected);
    console.log('Attempting reconnect...');
});

// Set global variables
window.currentUserId = '<?php echo $_SESSION['user_id']; ?>';

// Add library check
window.addEventListener('load', function() {
    if (typeof io === 'undefined') {
        alert('Socket.IO library not loaded');
    }
    if (typeof Peer === 'undefined') {
        alert('PeerJS library not loaded');
    }
    if (typeof SimplePeer === 'undefined') {
        alert('SimplePeer library not loaded');
    }
});

socket.on('connect', () => {
    console.log('Connected to socket server');
    socket.emit('register-user', <?php echo $_SESSION['user_id']; ?>);
});


// // Debug events
socket.onAny((event, ...args) => {
    console.log('Socket event:', event, args);
});

function showCallingDialog() {
    const dialog = document.createElement('div');
    dialog.className = 'calling-dialog';
    dialog.innerHTML = `
        <div class="calling-content">
            <div class="calling-animation"></div>
            <h3>Đang gọi...</h3>
            <button onclick="cancelCall()" class="cancel-call-btn">
                Hủy
            </button>
        </div>
    `;
    document.body.appendChild(dialog);
}

function cancelCall() {
    socket.emit('call-cancelled', {
        to: currentFriendId
    });
    const dialog = document.querySelector('.calling-dialog');
    if (dialog) {
        dialog.remove();
    }
}


// Thêm hàm xử lý thời gian cuộc gọi
let callStartTime = null;
let callDuration = 0;

function startTimer() {
    callStartTime = new Date();
    let seconds = 0;
    const timer = document.getElementById('call-timer');
    window.callTimer = setInterval(() => {
        seconds++;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        timer.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        callDuration = seconds;
    }, 1000);
}

function endCall() {
    console.log('Ending call');
    
    // Calculate call duration
    if (callStartTime) {
        const endTime = new Date();
        const durationInSeconds = Math.floor((endTime - callStartTime) / 1000);
        const minutes = Math.floor(durationInSeconds / 60);
        const seconds = durationInSeconds % 60;
        const durationText = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        // Send call ended message to chat
        $.ajax({
            url: 'send_call_message.php',
            method: 'POST',
            data: {
                receiver_id: currentFriendId,
                call_type: currentCallType,
                duration: durationText,
                status: 'ended'
            }
        });
        
        callStartTime = null;
    }

    // Notify other party
    if (currentFriendId) {
        socket.emit('end-call', {
            to: currentFriendId,
            from: window.currentUserId
        });
    }
    
    cleanupCall();
}

function rejectCall() {
    console.log('Rejecting call from:', currentCallerId);
    
    // Send missed call message
    $.ajax({
        url: 'send_call_message.php',
        method: 'POST',
        data: {
            receiver_id: currentCallerId,
            call_type: currentCallType,
            status: 'missed'
        }
    });

    // Send reject signal to caller
    socket.emit('reject-call', {
        from: window.currentUserId,
        to: currentCallerId
    });

    cleanupCall();
}

function startCallTimer() {
    let seconds = 0;
    const timerDisplay = document.querySelector('.call-timer');
    window.callTimer = setInterval(() => {
        seconds++;
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    }, 1000);
}


// Thêm style vào head
const styleSheet = document.createElement("style");
styleSheet.textContent = callStyles;
document.head.appendChild(styleSheet);

// </script>
<!-- Thêm thư viện SimplePeer -->


<script>window.currentUserId = '<?php echo $_SESSION['user_id']; ?>';</script>


<!-- SIDEBAR RIGHT -->
<div class="fixed-sidebar right">
    <div class="chat-friendz">
        <ul class="chat-users" id="friend-list">
            <?php
            // Update the friend list query
            $sql = "WITH FriendList AS (
                SELECT user_id as my_id, friend_id as friend_id 
                FROM friend 
                WHERE user_id = ? AND status_add = 'accepted'
                UNION 
                SELECT friend_id as my_id, user_id as friend_id
                FROM friend 
                WHERE friend_id = ? AND status_add = 'accepted'
            )
            SELECT DISTINCT
                u.id,
                u.username,
                u.last_activity,
                u.profile_picture,
                (
                    SELECT COUNT(DISTINCT f2.friend_id)
                    FROM friend f2
                    WHERE f2.status_add = 'accepted'
                    AND f2.user_id IN (SELECT friend_id FROM FriendList)
                    AND f2.friend_id IN (SELECT friend_id FROM FriendList)
                    AND f2.friend_id != ?
                ) as mutual_friends_count
            FROM FriendList fl
            JOIN users u ON u.id = fl.friend_id";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iii', $user_id, $user_id, $user_id);
            $result = $stmt->execute();

            if ($result) {
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $friend_id = $row['id'];
                        $mutual_friends_count = $row['mutual_friends_count'];
                        $last_activity = strtotime($row['last_activity']);
                        $current_time = time();
                        
                        // Add default avatar logic
                        $profile_picture = !empty($row['profile_picture']) ? $row['profile_picture'] : 'images/resources/author.jpg';
                        // Status logic
                        if (($current_time - $last_activity) < 300) {
                            $status = 'f-online';
                        } elseif (($current_time - $last_activity) < 900) {
                            $status = 'f-away';
                        } else {
                            $status = 'f-offline';
                        }

                        echo "
                        <li>
                            <a href=\"javascript:void(0);\" onclick=\"openChat($friend_id)\">
                                <div class='author-thmb'>
                                    <img src='$profile_picture' alt='Profile Picture' 
                                         style='width: 32px; height: 32px; object-fit: cover; border-radius: 50%;'>
                                    <span class='status $status'></span>
                                </div>
                            </a>
                            <div class='friend-name'>{$row['username']}</div>
                            <div class='mutual-friends'>Bạn chung: $mutual_friends_count</div>
                        </li>";
                    }
                } else {
                    echo "<p>Bạn chưa có bạn bè nào.</p>";
                }
                $stmt->close();
            } else {
                echo "Lỗi truy vấn: " . $conn->error;
            }
            ?>
        </ul>
    </div>

    <!-- Phần chat box -->
    <div id="custom-chat-box" class="custom-chat-box">
        <div class="custom-chat-head">
            <div class="chat-head-info">
                <span class="status f-online"></span>
                <h6 id="custom-chat-friend-name">Tên bạn bè</h6>
            </div>
            <div class="custom-chat-options">
            <button type="button" class="chat-option-btn" onclick="startCall(currentFriendId, 'video')">
                <i class="fa fa-video-camera"></i>
            </button>
            <button type="button" class="chat-option-btn" onclick="startCall(currentFriendId, 'voice')">
                <i class="fa fa-phone"></i>
            </button>
                <button type="button" class="chat-option-btn" onclick="closeChat()">
                    <i class="ti-close"></i>
                </button>
            </div>
        </div>

        <div id="custom-chat-content" class="custom-chat-content">
            <div class="chat-messages-wrapper">
                <!-- Messages will be loaded here -->
            </div>
        </div>

        <div class="custom-chat-footer">
            <!-- Voice message controls -->
            <div id="voice-message-form" style="display: none;">
                <div id="voice-controls" class="voice-controls">
                    <span id="timer">00:00</span>
                    <button type="button" onclick="stopRecording()" class="stop-record-btn">
                        <i class="fa fa-stop"></i> Dừng
                    </button>
                    <button type="button" onclick="cancelRecording()" class="cancel-record-btn">
                        <i class="fa fa-times"></i> Hủy
                    </button>
                </div>
                <div id="audio-preview-container"></div>
            </div>

            <!-- Preview container -->
            <div id="preview-container" class="preview-container" style="display: none;">
                <div id="preview-content" class="preview-content"></div>
                <div class="preview-actions">
                    <button type="button" onclick="confirmSend()" class="confirm-send-btn">
                        <i class="fa fa-paper-plane"></i> Gửi
                    </button>
                    <button type="button" onclick="cancelPreview()" class="cancel-preview-btn">
                        <i class="fa fa-times"></i> Hủy
                    </button>
                </div>
            </div>

            <!-- Message form -->
            <form class="custom-text-box" onsubmit="sendMessage(); return false;">
                <div class="attachment-options">
                    <button type="button" class="attach-btn" onclick="document.getElementById('image-upload').click();">
                        <i class="fa fa-image"></i>
                    </button>
                    <button type="button" class="attach-btn" onclick="document.getElementById('file-upload').click();">
                        <i class="fa fa-paperclip"></i>
                    </button>
                    <button type="button" id="voice-btn" class="attach-btn" onclick="startRecording()">
                        <i class="fa fa-microphone"></i>
                    </button>
                </div>

                <div class="message-input-wrapper">
                    <textarea id="custom-chat-input" placeholder="Nhập tin nhắn..." rows="1"></textarea>
                    <button type="submit" class="send-btn">
                        <i class="fa fa-paper-plane"></i>
                    </button>
                </div>

                <input type="file" id="image-upload" accept="image/*" onchange="sendImage()" style="display: none;">
                <input type="file" id="file-upload" onchange="sendFile()" style="display: none;">
            </form>
        </div>
    </div>
    <input type="hidden" id="current-username" value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
</div>

<div id="imageModal" class="image-modal" onclick="this.style.display='none'">
    <span class="close-btn">&times;</span>
    <img id="modalImage" src="" alt="Enlarged image">
</div>
<div id="call-window" class="call-window">
    <div class="call-header">
        <h3 id="call-title">Cuộc gọi</h3>
        <span id="call-timer">00:00</span>
    </div>
    <div class="video-container">
        <video id="remote-video" autoplay playsinline></video>
        <video id="local-video" autoplay playsinline muted></video>
    </div>
    <div class="call-status" id="call-status">Đang kết nối...</div>
    <div class="call-controls">
        <button id="mute-btn" onclick="toggleMute()">
            <i class="fa fa-microphone"></i>
        </button>
        <button id="video-btn" onclick="toggleVideo()">
            <i class="fa fa-video-camera"></i>
        </button>
        <button id="end-call-btn" onclick="endCall()">
            <i class="fa fa-phone" style="transform: rotate(135deg);"></i>
        </button>
    </div>
</div>

<!-- Thông báo cuộc gọi đến -->
<div id="call-notification" class="call-notification" style="display: none;">
    <div class="notification-content">
        <div class="caller-info">
            <i class="fa fa-phone pulse"></i>
            <h4 id="caller-name"></h4>
            <p id="call-type-text"></p>
        </div>
        <div class="notification-buttons">
            <button id="accept-call-btn" class="accept-btn">Chấp nhận</button>
            <button onclick="rejectCall()" class="reject-btn">Từ chối</button>
        </div>
    </div>
</div>
<style>
    /* Base styles and typography */
    * {
        font-family: -apple-system, system-ui, BlinkMacSystemFont, "Segoe UI", Roboto, Ubuntu, sans-serif;
    }

    /* Message text styles */
    .my-message p,
    .friend-message p {
        font-size: 15px;
        line-height: 1.4;
        margin: 0;
        font-weight: 400;
        letter-spacing: 0.2px;
    }

    .message-time,
    .message-status {
        font-size: 12px;
        opacity: 0.7;
    }
    /* Message containers */
    .my-message,
    .friend-message {
        max-width: 80%;
        padding: 12px 16px;
        border-radius: 18px;
        margin: 8px 0;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .my-message {
        background: #0084ff;
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 4px;
    }

    .friend-message {
        background: #f0f2f5;
        color: #050505;
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }

    /* Message info styles */
    .message-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        opacity: 0.7;
        margin-top: 4px;
    }

    .my-message .message-info {
        justify-content: flex-end;
        color: rgba(255, 255, 255, 0.9);
    }

    .friend-message .message-info {
        justify-content: flex-start;
      
    }

    /* Chat box structure */
    .custom-chat-box {
        position: fixed;
        bottom: 0;
        right: 20px;
        width: 380px;
        height: 600px;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        z-index: 1000;
        overflow: hidden;
        padding: 16px;
    }

    .custom-chat-box.show {
        display: flex;
    }

    /* Chat header */
    .custom-chat-head {
        background: white;
        border-bottom: 1px solid #e4e6eb;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chat-head-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #custom-chat-friend-name {
        color: #050505;
        font-size: 16px;
        font-weight: 600;
        margin: 0;
    }

    /* Chat content */
    .custom-chat-content {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #ffffff;
    }

    /* Input area */
    .message-input-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 10px;
        background: white;
        border: 1px solid #e4e6eb;
        border-radius: 24px;
        padding: 8px 12px;
        margin-top: 8px;
    }

    #custom-chat-input {
        flex: 1;
        border: none;
        background: none;
        resize: none;
        min-height: 20px;
        padding: 8px;
        font-size: 15px;
        color: #050505;
    }
/* 
    #custom-chat-input::placeholder {
        color: #65676B;
    } */

    /* Buttons */
    .chat-option-btn,
    .attach-btn,
    .send-btn {
        background: none;
        border: none;
        padding: 5px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .attach-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .attach-btn:hover {
        background: #f0f2f5;
    }

    .chat-option-btn:hover {
        background: #f0f2f5;
        border-radius: 50%;
    }

    /* Icons */
    .fa {
        display: inline-block;
        font-size: 20px;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    .send-btn .fa,
    #voice-btn .fa-microphone {
        color: #0084ff;
    }

    /* Voice message controls */
    .voice-controls {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 15px;
        padding: 15px;
        background: white;
        border: 1px solid #e4e6eb;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* File attachments */
    .file-attachment {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 12px;
        margin: 5px 0;
    }

    .friend-message .file-attachment {
        background-color: rgba(0, 0, 0, 0.08);
    }

    .my-message .file-attachment i,
    .my-message .file-attachment a {
        color: white;
    }

    .friend-message .file-attachment a {
        color: #212529;
    }

    /* Image modal */
    .image-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 9999;
        cursor: pointer;
    }

    .image-modal img {
        max-width: 90%;
        max-height: 90vh;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
    }

    /* Custom scrollbar */
    .custom-chat-content::-webkit-scrollbar {
        width: 8px;
    }

    .custom-chat-content::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .custom-chat-content::-webkit-scrollbar-thumb {
        background: #bcc0c4;
        border-radius: 4px;
    }

    /* .custom-chat-content::-webkit-scrollbar-thumb:hover {
        background: #888;
    } */

    /* Điều chỉnh màu text và background cho message */
    .friend-message {
        background: #e4e6eb;
        color: #050505;
    }

    /* .friend-message .message-info {
        color: #65676B;
    } */

    /* Tăng độ tương phản cho input và placeholder */
    #custom-chat-input {
        color: #1c1e21;
    }

    /* #custom-chat-input::placeholder {
        color: #606770;
    } */

    /* Tăng độ tương phản cho buttons */
    .attach-btn {
        color: #1c1e21;
    }

    .attach-btn:hover {
        background: #e4e6eb;
    }

    /* Tăng độ tương phản cho file attachments */
    .friend-message .file-attachment {
        background-color: rgba(0, 0, 0, 0.1);
    }

    .friend-message .file-attachment a {
        color: #1c1e21;
    }

    /* Tăng độ rõ cho borders */
    .custom-chat-head {
        border-bottom: 1px solid #dadde1;
    }

    .message-input-wrapper {
        border: 1px solid #dadde1;
    }

    /* Tăng độ đậm cho text */
    .message-time, 
    .message-status {
        opacity: 0.85;
    }

    /* Đổi màu nền chat content */
    .custom-chat-content {
        background: #f0f2f5;
    }

    /* Tăng shadow cho chat box */
    .custom-chat-box {
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.2);
    }

    /* Thêm styles cho voice controls */
    #voice-message-form {
        width: 100%;
        margin-bottom: 10px;
    }

    .voice-controls {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 15px;
        padding: 15px;
        background: white;
        border: 1px solid #e4e6eb;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    #timer {
        font-family: 'Roboto Mono', monospace;
        font-size: 18px;
        color: #dc3545;
        min-width: 60px;
        text-align: center;
    }

    .stop-record-btn,
    .cancel-record-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .stop-record-btn {
        background: #dc3545;
        color: white;
    }

    .cancel-record-btn {
        background: #6c757d;
        color: white;
    }

    .stop-record-btn:hover {
        background: #c82333;
    }

    .cancel-record-btn:hover {
        background: #5a6268;
    }

    .calling-dialog {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        z-index: 1001;
        text-align: center;
    }

    .calling-animation {
        width: 60px;
        height: 60px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 20px auto;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Add these CSS rules to reduce chat box size */
    .fixed-sidebar.right {
        width: 280px; /* Reduce from default width */
    }

    .custom-chat-box {
        width: 300px; /* Reduce from 380px */
        height: 480px; /* Reduce from 600px */
        right: 290px; /* Position next to friend list */
    }

    .custom-chat-content {
        padding: 10px; /* Reduce padding */
    }

    .message-input-wrapper {
        padding: 5px 8px; /* Reduce padding */
    }

    #custom-chat-input {
        min-height: 16px; /* Reduce height */
        padding: 4px; /* Reduce padding */
        font-size: 14px; /* Reduce font size */
    }

    .attach-btn {
        width: 32px; /* Reduce from 36px */
        height: 32px; /* Reduce from 36px */
    }

    /* Adjust friend list item size */
    .chat-users li {
        padding: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .author-thmb img {
        width: 32px !important; /* Reduce from 40px */
        height: 32px !important; /* Reduce from 40px */
    }

    /* Message layout updates */
    .chat-messages-wrapper {
        display: flex;
        flex-direction: column;
        gap: 8px;
        width: 100%;
        padding: 10px;
    }

    .my-message,
    .friend-message {
        max-width: 80%;
        padding: 8px 12px;
        border-radius: 18px;
        margin: 4px 0;
        word-wrap: break-word;
        position: relative;
        clear: both;
    }

    .my-message {
        float: right;
        background: #0084ff;
        color: white;
        border-bottom-right-radius: 4px;
        margin-left: auto;
    }

    .friend-message {
        float: left;
        background: #e4e6eb;
        color: #050505;
        border-bottom-left-radius: 4px;
        margin-right: auto;
    }

    /* Content area fixes */
    .custom-chat-content {
        display: flex;
        flex-direction: column;
        height: calc(100% - 120px); /* Adjust based on header and footer height */
        overflow-y: auto;
        padding: 10px;
        background: #f0f2f5;
    }

    /* Message info alignment */
    .message-info {
        font-size: 11px;
        margin-top: 2px;
        padding: 0 4px;
    }

    /* Chat container sizing */
    .custom-chat-box {
        width: 320px;
        height: 480px;
        right: 300px;
        bottom: 0;
        padding: 0;
        display: none;
        flex-direction: column;
    }

    /* Footer adjustments */
    .custom-chat-footer {
        padding: 8px;
        background: white;
        border-top: 1px solid #e4e6eb;
    }

    /* Message input area */
    .message-input-wrapper {
        padding: 6px 8px;
    }

    #custom-chat-input {
        min-height: 16px; /* Reduce height */
        padding: 4px; /* Reduce padding */
        font-size: 14px; /* Reduce font size */
    }

    /* Điều chỉnh footer và input area */
    .custom-chat-footer {
        height: auto;
        min-height: 60px;
        padding: 8px;
        background: white;
        border-top: 1px solid #e4e6eb;
    }

    .message-input-wrapper {
        height: auto;
        min-height: 40px;
        padding: 8px 12px;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        background: white;
        border: 1px solid #e4e6eb;
        border-radius: 24px;
        margin-top: 8px;
    }

    #custom-chat-input {
        flex: 1;
        min-height: 24px;
        max-height: 100px;
        padding: 6px;
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
        border: none;
        resize: none;
        background: none;
        overflow-y: auto;
    }

    /* Điều chỉnh nút gửi để căn giữa */
    .send-btn {
        padding: 8px;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Điều chỉnh content area để phù hợp với footer mới */
    .custom-chat-content {
        height: calc(100% - 110px);
        padding: 10px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Điều chỉnh tin nhắn */
    .my-message,
    .friend-message {
        max-width: 85%;
        padding: 6px 10px;
        font-size: 13px;
    }

    /* Điều chỉnh hình ảnh trong tin nhắn */
    .message-image {
        max-width: 150px;
    }

</style>
<script>
    // const socket = io('http://localhost:3000', { transports: ['websocket'], reconnection: true });
    let currentCallType, currentPeer, localStream, pendingSignal = null, isCallActive = false;
socket.on('connect', () => {
    console.log('Socket connected, ID:', socket.id);
    socket.emit('register-user', window.currentUserId);
    console.log('Registered user:', window.currentUserId);
});

socket.on('connect_error', (err) => {
    console.error('Socket connection error:', err.message);
});

function setupPeerEvents(peer, targetId) {
    if (!peer) {
        console.error('No peer provided to setupPeerEvents');
        return;
    }

    // Add connection state tracking
    let isConnecting = true;
    let connectionTimeout = setTimeout(() => {
        if (isConnecting) {
            console.log('Connection timeout');
            cleanupCall();
            alert('Không thể kết nối sau 30 giây');
        }
    }, 30000);

    peer.on('connect', () => {
        console.log('Peer connection established');
        isConnecting = false;
        clearTimeout(connectionTimeout);
        
        // Update UI when connection is established
        document.getElementById('call-status').textContent = 'Đã kết nối';
        startTimer();
    });

    peer.on('stream', stream => {
        console.log('Received remote stream');
        const remoteVideo = document.getElementById('remote-video');
        if (remoteVideo) {
            remoteVideo.srcObject = stream;
            remoteVideo.play().catch(e => console.error('Remote video play error:', e));
        }
    });

    peer.on('signal', signal => {
        console.log('Generated signal for:', targetId);
        socket.emit('webrtc-signal', {
            signal: signal,
            to: targetId,
            from: window.currentUserId
        });
    });

    peer.on('error', err => {
        console.error('Peer connection error:', err);
        clearTimeout(connectionTimeout);
        
        let errorMessage = 'Lỗi kết nối';
        if (err.code === 'ERR_DATA_CHANNEL') {
            errorMessage = 'Lỗi kênh dữ liệu - Vui lòng thử lại';
            cleanupCall();
        } else if (err.code === 'ERR_CONNECTION_FAILURE') {
            errorMessage = 'Không thể kết nối - Kiểm tra kết nối mạng';
            cleanupCall();
        } else if (err.code === 'ERR_WEBRTC_SUPPORT') {
            errorMessage = 'Trình duyệt không hỗ trợ cuộc gọi video';
            cleanupCall();
        }
        
        alert(errorMessage);
    });
}

function acceptCall(callerId, callType) {
    console.log('Accepting call from:', callerId, 'type:', callType);
    
    // Hide notification first
    document.getElementById('call-notification').style.display = 'none';
    
    // Show call window immediately
    const callWindow = document.getElementById('call-window');
    callWindow.style.display = 'block';
    
    document.getElementById('call-title').textContent = 
        callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại';
    document.getElementById('call-status').textContent = 'Đang kết nối...';
    document.getElementById('video-btn').style.display = 
        callType === 'video' ? 'block' : 'none';

    // Set call state
    isCallActive = true;
    currentFriendId = callerId;
    currentCallType = callType;

    // Request media access
    navigator.mediaDevices.getUserMedia({
        video: callType === 'video',
        audio: true
    }).then(stream => {
        localStream = stream;
        
        // Set up local video
        const localVideo = document.getElementById('local-video');
        if (localVideo) {
            localVideo.srcObject = stream;
            localVideo.play().catch(e => console.log('Local video play warning:', e));
        }

        try {
            currentPeer = new SimplePeer({
                initiator: false,
                trickle: false,
                stream: stream,
                config: {
                    iceServers: [
                        { urls: 'stun:stun.l.google.com:19302' },
                        { urls: 'stun:global.stun.twilio.com:3478' }
                    ]
                }
            });

            setupPeerEvents(currentPeer, callerId);

            // Process any pending signals
            if (pendingSignal) {
                console.log('Processing pending signal');
                currentPeer.signal(pendingSignal);
                pendingSignal = null;
            }

        } catch (err) {
            console.error('Error creating peer:', err);
            alert('Lỗi khởi tạo kết nối: ' + err.message);
            cleanupCall();
        }
    }).catch(err => {
        console.error('Media access error:', err);
        alert('Không thể truy cập thiết bị media: ' + err.message);
        cleanupCall();
    });
}

function cleanupCall() {
    console.log('Cleaning up call...');
    
    // Stop media streams
    if (localStream) {
        localStream.getTracks().forEach(track => {
            try {
                track.stop();
            } catch (err) {
                console.error('Error stopping track:', err);
            }
        });
        localStream = null;
    }
    
    // Clean up peer connection
    if (currentPeer) {
        try {
            currentPeer.destroy();
        } catch (err) {
            console.error('Error destroying peer:', err);
        }
        currentPeer = null;
    }
    
    // Reset UI
    document.getElementById('call-notification').style.display = 'none';
    document.getElementById('call-window').style.display = 'none';
    document.getElementById('local-video').srcObject = null;
    document.getElementById('remote-video').srcObject = null;
    document.getElementById('call-timer').textContent = '00:00';
    
    // Reset state
    isCallActive = false;
    currentCallerId = null;
    currentCallType = null;
    pendingSignal = null;
    
    clearInterval(window.callTimer);
}

function endCall() {
    console.log('Ending call with cleanup');
    
    if (currentPeer) {
        try {
            currentPeer.destroy();
        } catch (err) {
            console.error('Error destroying peer:', err);
        }
        currentPeer = null;
    }
    
    if (localStream) {
        localStream.getTracks().forEach(track => {
            try {
                track.stop();
            } catch (err) {
                console.error('Error stopping track:', err);
            }
        });
        localStream = null;
    }
    
    // Reset UI elements
    document.getElementById('local-video').srcObject = null;
    document.getElementById('remote-video').srcObject = null;
    document.getElementById('call-window').style.display = 'none';
    
    clearInterval(window.callTimer);
    document.getElementById('call-timer').textContent = '00:00';
    
    isCallActive = false;
    
    // Notify server about call end
    if (currentFriendId) {
        socket.emit('end-call', {
            to: currentFriendId,
            from: window.currentUserId
        });
        currentFriendId = null;
    }
}

function startCall(friendId, callType) {
    if (isCallActive) {
        alert('Bạn đang trong một cuộc gọi khác');
        return;
    }

    // Show call window first
    const callWindow = document.getElementById('call-window');
    if (!callWindow) {
        console.error('Call window not found');
        alert('Lỗi: Không tìm thấy giao diện cuộc gọi');
        return;
    }

    callWindow.style.display = 'block';
    document.getElementById('call-title').textContent = 
        callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại';
    document.getElementById('call-status').textContent = 'Đang kết nối...';
    document.getElementById('video-btn').style.display = 
        callType === 'video' ? 'block' : 'none';

    // Request media access with error handling
    navigator.mediaDevices.getUserMedia({
        video: callType === 'video',
        audio: true
    }).then(stream => {
        console.log('Got local media stream');
        localStream = stream;
        
        const localVideo = document.getElementById('local-video');
        if (localVideo) {
            localVideo.srcObject = stream;
            localVideo.play().catch(e => console.log('Local video play warning:', e));
        }

        try {
            currentPeer = new SimplePeer({
                initiator: true,
                trickle: false,
                stream: stream
            });
            
            setupPeerEvents(currentPeer, friendId);
            isCallActive = true;
            currentFriendId = friendId;
            currentCallType = callType;

            // Emit call initiation
            socket.emit('initiate-call', {
                to: friendId,
                from: window.currentUserId,
                fromName: document.getElementById('current-username').value,
                type: callType
            });

        } catch (err) {
            console.error('Error creating peer:', err);
            alert('Lỗi khởi tạo kết nối: ' + err.message);
            endCall();
        }
    }).catch(err => {
        console.error('Media access error:', err);
        let errorMessage = 'Không thể truy cập thiết bị media: ';
        
        if (err.name === 'NotAllowedError') {
            errorMessage += 'Vui lòng cho phép truy cập camera/microphone';
        } else if (err.name === 'NotFoundError') {
            errorMessage += 'Không tìm thấy camera/microphone';
        } else {
            errorMessage += err.message;
        }
        
        alert(errorMessage);
        endCall();
    });
}

function rejectCall() {
    console.log('Rejecting call from:', currentCallerId);
    
    // Send missed call message
    $.ajax({
        url: 'send_call_message.php',
        method: 'POST',
        data: {
            receiver_id: currentCallerId,
            call_type: currentCallType,
            status: 'missed'
        }
    });

    // Send reject signal to caller
    socket.emit('reject-call', {
        from: window.currentUserId,
        to: currentCallerId
    });

    cleanupCall();
}

function cleanupCall() {
    // Hide UI elements
    document.getElementById('call-notification').style.display = 'none';
    document.getElementById('call-window').style.display = 'none';
    
    // Stop media streams
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
        localStream = null;
    }
    
    // Clean up peer connection
    if (currentPeer) {
        currentPeer.destroy();
        currentPeer = null;
    }
    
    // Reset call state variables
    currentCallerId = null;
    isCallActive = false;
    currentCallType = null;
    pendingSignal = null;
}

function acceptCall(callerId, callType) {
    console.log('Accepting call from:', callerId, 'type:', callType);
    
    // Set call state
    isCallActive = true;
    currentFriendId = callerId;
    currentCallType = callType;

    // Show and setup call window
    const callWindow = document.getElementById('call-window');
    callWindow.style.display = 'block';
    
    document.getElementById('call-title').textContent = 
        callType === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại';
    document.getElementById('call-status').textContent = 'Đang kết nối...';
    document.getElementById('video-btn').style.display = 
        callType === 'video' ? 'block' : 'none';

    // Request media permissions
    navigator.mediaDevices.getUserMedia({
        video: callType === 'video',
        audio: true
    }).then(stream => {
        console.log('Got local media stream');
        localStream = stream;
        
        // Set up local video
        const localVideo = document.getElementById('local-video');
        localVideo.srcObject = stream;
        localVideo.play().catch(e => console.log('Local video play warning:', e));

        // Create peer connection
        try {
            currentPeer = new SimplePeer({
                initiator: false,
                trickle: false,
                stream: stream
            });

            // Set up peer connection events
            setupPeerEvents(currentPeer, callerId);

            // Process any pending signals
            if (pendingSignal) {
                console.log('Processing pending signal');
                currentPeer.signal(pendingSignal);
                pendingSignal = null;
            }

        } catch (err) {
            console.error('Error creating peer:', err);
            alert('Lỗi kết nối: ' + err.message);
            cleanupCall();
        }
    }).catch(err => {
        console.error('Media access error:', err);
        alert('Không thể truy cập thiết bị media: ' + err.message);
        cleanupCall();
    });
}

// Update socket event handlers
socket.on('call-rejected', (data) => {
    console.log('Call rejected by:', data.from);
    alert(data.message || 'Cuộc gọi đã bị từ chối');
    cleanupCall();
});

socket.on('call-ended', () => {
    console.log('Call ended by other party');
    if (isCallActive) {
        cleanupCall();
    }
});

function endCall() {
    console.log('Ending call');
    
    // Notify other party
    if (currentFriendId) {
        socket.emit('end-call', {
            to: currentFriendId,
            from: window.currentUserId
        });
    }
    
    cleanupCall();
}

socket.on('call-notification', (data) => {
    console.log('Received call notification:', data);
    showCallNotification(data);
});

function showCallNotification(data) {
    console.log('Showing call notification:', data);
    currentCallerId = data.from;
    currentCallType = data.type;
    
    const notification = document.getElementById('call-notification');
    const callerName = document.getElementById('caller-name');
    const callTypeText = document.getElementById('call-type-text');
    
    callerName.textContent = `${data.fromName} đang gọi...`;
    callTypeText.textContent = data.type === 'video' ? 'Cuộc gọi video' : 'Cuộc gọi thoại';
    notification.style.display = 'block';

    // Setup accept button
    document.getElementById('accept-call-btn').onclick = () => {
        notification.style.display = 'none';
        acceptCall(data.from, data.type);
    };
}

socket.on('webrtc-signal', (data) => {
    if (currentPeer) {
        console.log('Processing WebRTC signal:', data);
        currentPeer.signal(data.signal);
    } else {
        console.log('Storing pending signal:', data);
        pendingSignal = data.signal; // Lưu tín hiệu nếu currentPeer chưa sẵn sàng
    }
});

function toggleMute() {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        audioTrack.enabled = !audioTrack.enabled;
        document.getElementById('mute-btn').classList.toggle('muted', !audioTrack.enabled);
    }
}

function toggleVideo() {
    if (localStream && currentCallType === 'video') {
        const videoTrack = localStream.getVideoTracks()[0];
        videoTrack.enabled = !videoTrack.enabled;
        document.getElementById('video-btn').classList.toggle('muted', !videoTrack.enabled);
    }
}

function startTimer() {
    let seconds = 0;
    const timer = document.getElementById('call-timer');
    window.callTimer = setInterval(() => {
        seconds++;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        timer.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }, 1000);
};
</script>
<script>
    // Thêm các hàm xử lý modal
    function openImageModal(imgSrc) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');

        modal.style.display = "block";
        modalImg.src = imgSrc;
    }

    function closeImageModal() {
        document.getElementById('imageModal').style.display = "none";
    }

    // Đóng modal khi nhấn ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeImageModal();
        }
    });

    // Ngăn việc click vào ảnh lan ra ngoài
    document.getElementById('modalImage').onclick = function(event) {
        event.stopPropagation();
    };

    // Thêm hàm để bind lại sự kiện click cho tất cả ảnh sau khi load tin nhắn
    function bindImageClickEvents() {
        document.querySelectorAll('.message-image').forEach(img => {
            img.onclick = function() {
                openImageModal(this.src);
            };
        });
    }

    // Cập nhật hàm loadChatMessages để bind lại sự kiện sau khi load
    function loadChatMessages() {
        $.ajax({
            url: 'load_chat.php',
            method: 'GET',
            data: {
                friend_id: currentFriendId
            },
            success: function(data) {
                $('#custom-chat-content').html(data);
                $('#custom-chat-content').scrollTop($('#custom-chat-content')[0].scrollHeight);
                bindImageClickEvents(); // Thêm dòng này
            },
            error: function(xhr, status, error) {
                console.error("Lỗi khi tải nội dung chat: " + error);
            }
        });
    }
</script>
<script>
    // Cập nhật các event listeners
    document.getElementById('image-upload').onchange = previewImage;
</script>

<style>
    /* Thêm style cho file attachment */
    .file-attachment {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: rgba(0, 0, 0, 0.05);
        border-radius: 8px;
        margin: 5px 0;
    }
    .video-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    }
    #localVideo, #remoteVideo {
    width: 100%;
    max-width: 300px;
    height: auto;
    background: black; /* Để dễ thấy nếu không có stream */
    margin: 10px 0;
    }
    .file-attachment i {
        font-size: 20px;
        color: #0084ff;
    }

    .file-attachment a {
        text-decoration: none;
        color: #050505;
        font-size: 14px;
        word-break: break-all;
    }

    .file-attachment a:hover {
        text-decoration: underline;
    }

   
    .my-message .file-attachment i,
    .my-message .file-attachment a {
        color: white;
    }

    .friend-message .file-attachment {
        background: rgba(0, 0, 0, 0.05);
    }

    .friend-message .file-attachment a {
        color: #050505;
    }

    /* Thêm vào phần CSS hiện có */
    .message-info {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        opacity: 0.7;
        margin-top: 4px;
    }

    .my-message .message-info {
        justify-content: flex-end;
    }

    .friend-message .message-info {
        justify-content: flex-start;
    }

    .message-time,
    .message-status {
        display: inline-block;
    }

    /* .my-message .message-info {
        color: rgba(255, 255, 255, 0.9);
    } */


    /* Thêm styles mới */
    .preview-container {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
    }

    .preview-image-wrapper {
        display: flex;
        justify-content: center;
        margin-bottom: 15px;
    }

    .preview-image-wrapper img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        object-fit: contain;
    }

    .preview-actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .confirm-send-btn,
    .cancel-preview-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 20px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .confirm-send-btn {
        background: #0084ff;
        color: white;
    }

    .cancel-preview-btn {
        background: #6c757d;
        color: white;
    }

    .confirm-send-btn:hover {
        background: #0073e6;
    }

    .cancel-preview-btn:hover {
        background: #5a6268;
    }

    .loading-indicator {
        text-align: center;
        padding: 10px;
        color: #666;
        font-style: italic;
    }

    /* Reset và cập nhật style cho khung chat chính */
    .custom-chat-content {
        display: flex;
        flex-direction: column;
        height: calc(100% - 120px);
        overflow-y: auto;
        padding: 15px;
        background: #f0f2f5;
    }

    /* Container cho tin nhắn */
    .chat-messages-wrapper {
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    /* Style chung cho tin nhắn */
    .message-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 2px 0;
    }

    /* Style cho từng loại tin nhắn */
    .my-message,
    .friend-message {
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 18px;
        word-wrap: break-word;
        position: relative;
        margin: 2px 0;
    }

    .my-message {
        align-self: flex-end;
        background: #0084ff;
        color: white;
        border-bottom-right-radius: 4px;
    }

    .friend-message {
        align-self: flex-start;
        background: #e4e6eb;
        color: #050505;
        border-bottom-left-radius: 4px;
    }

    /* Style cho thông tin tin nhắn */
    .message-info {
        padding: 2px 4px;
        margin-top: 2px;
        font-size: 11px;
        line-height: 1.2;
    }

  
    /* Style cho attachments trong tin nhắn */
    .message-attachments {
        margin-top: 4px;
    }

    .message-image {
        max-width: 200px;
        border-radius: 12px;
        cursor: pointer;
    }

    .file-attachment {
        margin: 4px 0;
        padding: 8px;
        border-radius: 8px;
    }

    .friend-message .file-attachment {
        background: rgba(0, 0, 0, 0.05);
    }

    /* Style cho tin nhắn voice */
    .voice-message {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 12px;
    }

  
    .friend-message .voice-message {
        background: rgba(0, 0, 0, 0.05);
    }

    /* Cập nhật z-index cho chat box để nó hiển thị trên cùng */
    .custom-chat-box {
        position: fixed;
        bottom: 0;
        right: 300px;
        width: 320px;
        height: 480px;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        z-index: 9999; /* Tăng z-index để hiển thị trên các elements khác */
        overflow: hidden;
    }

    /* Điều chỉnh kích thước avatar trong danh sách bạn bè */
    .chat-users li {
        padding: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative; /* Thêm position relative */
    }

    .author-thmb {
        position: relative;
        width: 32px;
        height: 32px;
        min-width: 32px; /* Thêm min-width để tránh bị co lại */
        overflow: hidden; /* Thêm overflow hidden */
    }

    .author-thmb img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Điều chỉnh vị trí status dot */
    .status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 2px solid white;
    }

    /* Điều chỉnh thông tin bạn bè */
    .friend-name {
        font-size: 14px;
        margin-bottom: 2px;
        display: flex;
        flex-direction: column;
        width: 100%;
        gap: 8px;
    }

    /* Style chung cho tin nhắn */
    .message-container {
        display: flex;
        flex-direction: column;
        width: 100%;
        margin: 2px 0;
    }

    /* Style cho từng loại tin nhắn */
    .my-message,
    .friend-message {
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 18px;
        word-wrap: break-word;
        position: relative;
        margin: 2px 0;
    }

    .my-message {
        align-self: flex-end;
        background: #0084ff;
        color: white;
        border-bottom-right-radius: 4px;
    }

    .friend-message {
        align-self: flex-start;
        background: #e4e6eb;
        color: #050505;
        border-bottom-left-radius: 4px;
    }

    /* Style cho thông tin tin nhắn */
    .message-info {
        padding: 2px 4px;
        margin-top: 2px;
        font-size: 11px;
        line-height: 1.2;
    }

  
    /* Style cho attachments trong tin nhắn */
    .message-attachments {
        margin-top: 4px;
    }

    .message-image {
        max-width: 200px;
        border-radius: 12px;
        cursor: pointer;
    }

    .file-attachment {
        margin: 4px 0;
        padding: 8px;
        border-radius: 8px;
    }

    .friend-message .file-attachment {
        background: rgba(0, 0, 0, 0.05);
    }

    /* Style cho tin nhắn voice */
    .voice-message {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px;
        border-radius: 12px;
    }

  
    .friend-message .voice-message {
        background: rgba(0, 0, 0, 0.05);
    }

    /* Cập nhật z-index cho chat box để nó hiển thị trên cùng */
    .custom-chat-box {
        position: fixed;
        bottom: 0;
        right: 300px;
        width: 320px;
        height: 480px;
        background: white;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        z-index: 9999; /* Tăng z-index để hiển thị trên các elements khác */
        overflow: hidden;
    }

    /* Điều chỉnh kích thước avatar trong danh sách bạn bè */
    .chat-users li {
        padding: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative; /* Thêm position relative */
    }

    .author-thmb {
        position: relative;
        width: 32px;
        height: 32px;
        min-width: 32px; /* Thêm min-width để tránh bị co lại */
        overflow: hidden; /* Thêm overflow hidden */
    }

    .author-thmb img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Điều chỉnh vị trí status dot */
    .status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: 2px solid white;
    }

    /* Điều chỉnh thông tin bạn bè */
    .friend-name {
        font-size: 14px;
        margin-bottom: 2px;
        flex: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .mutual-friends {
        font-size: 12px;
        color: #65676B;
    }

    /* Đảm bảo fixed sidebar hiển thị đúng */
    .fixed-sidebar.right {
        position: fixed;
        right: 0;
        top: 0;
        bottom: 0;
        width: 280px;
        z-index: 9998; /* Đặt z-index thấp hơn chat box */
        background: white;
        border-left: 1px solid #e4e6eb;
        overflow-y: auto;
    }

    /* Điều chỉnh độ cao của content area */
    .chat-friendz {
        height: 100%;
        overflow-y: auto;
    }

    /* Thêm style cho scrollbar */
    .chat-friendz::-webkit-scrollbar {
        width: 6px;
    }

    .chat-friendz::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    /* .chat-friendz::-webkit-scrollbar-thumb {
        background: #bcc0c4;
        border-radius: 3px;
    } */


    /* Điều chỉnh sidebar */
    .fixed-sidebar.right {
        position: fixed;
        right: 0;
        top: 60px;
        bottom: 0;
        width: 220px;
        z-index: 998;
        background: white;
        border-left: 1px solid #e4e6eb;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Điều chỉnh chat box */
    .custom-chat-box {
        position: fixed;
        bottom: 0;
        right: 230px;
        width: 260px;
        height: 400px;
        background: white;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        display: none;
        flex-direction: column;
        z-index: 999;
        overflow: hidden;
        padding: 0;
    }

    /* Điều chỉnh header chat */
    .custom-chat-head {
        padding: 8px;
        height: 40px;
        min-height: 40px;
    }

    /* Điều chỉnh content area */
    .custom-chat-content {
        height: calc(100% - 90px);
        overflow-y: auto;
        overflow-x: hidden;
        padding: 8px;
    }

    /* Điều chỉnh footer */
    .custom-chat-footer {
        height: 50px;
        min-height: 50px;
        padding: 5px;
    }

    /* Điều chỉnh input */
    .message-input-wrapper {
        height: 36px;
    }

    #custom-chat-input {
        max-height: 36px;
        font-size: 13px;
    }

    /* Điều chỉnh tin nhắn */
    .my-message,
    .friend-message {
        max-width: 85%;
        padding: 6px 10px;
        font-size: 13px;
    }

    /* Điều chỉnh hình ảnh trong tin nhắn */
    .message-image {
        max-width: 150px;
    }

    /* Điều chỉnh phần input tin nhắn */
    .message-input-wrapper {
        height: auto;
        min-height: 40px;
        max-height: 120px;
        padding: 8px 12px;
        margin: 0;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        background: white;
        border: 1px solid #e4e6eb;
        border-radius: 20px;
    }

    #custom-chat-input {
        flex: 1;
        min-height: 24px;
        max-height: 100px;
        padding: 6px;
        margin: 0;
        font-size: 14px;
        line-height: 1.4;
        border: none;
        resize: none;
        background: none;
        overflow-y: auto;
        word-wrap: break-word;
        white-space: pre-wrap;
    }

    /* Cập nhật chiều cao động cho content area */
    .custom-chat-content {
        flex: 1;
        height: calc(100% - 120px);
        min-height: 200px;
        overflow-y: auto;
        padding: 10px;
        background: #f0f2f5;
    }

    /* Điều chỉnh footer để thích ứng với input */
    .custom-chat-footer {
        height: auto;
        min-height: 60px;
        max-height: 150px;
        padding: 8px;
        background: white;
        border-top: 1px solid #e4e6eb;
    }

    /* Thêm auto-resize cho textarea */
    
</style>
<script>
        $('#custom-chat-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        
        // Giới hạn chiều cao tối đa
        if (this.scrollHeight > 100) {
            this.style.height = '100px';
            this.style.overflowY = 'auto';
        } else {
            this.style.overflowY = 'hidden';
        }
        
        // Điều chỉnh scroll của content area
        adjustContentScroll();
    });

    function adjustContentScroll() {
        const content = document.getElementById('custom-chat-content');
        content.scrollTop = content.scrollHeight;
    }
    </script>

</body>


<style>
   /* Giao diện cuộc gọi */
   .call-window {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 500px;
        max-width: 90%;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10000 ! important;
        overflow: hidden;
        transition: all 0.3s ease;
    }
    .call-header {
        background: #f8f9fa;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
    }
    #call-title {
        margin: 0;
        font-size: 18px;
        color: #333;
    }
    #call-timer {
        font-size: 16px;
        color: #666;
        font-family: monospace;
    }
    .video-container {
        position: relative;
        height: 300px;
        background: #000;
    }
    #remote-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    #local-video {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 120px;
        height: 90px;
        border: 2px solid #fff;
        border-radius: 8px;
        object-fit: cover;
    }
    .call-status {
        padding: 10px;
        text-align: center;
        color: #666;
        font-size: 14px;
        background: #f1f3f5;
    }
    .call-controls {
        display: flex;
        justify-content: center;
        gap: 15px;
        padding: 15px;
        background: #fff;
    }
    .call-controls button {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: none;
        background: #e9ecef;
        color: #333;
        font-size: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .call-controls button:hover {
        background: #dee2e6;
    }
    #end-call-btn {
        background: #dc3545;
        color: #fff;
    }
    #end-call-btn:hover {
        background: #c82333;
    }
    .muted {
        background: #ff6b6b !important;
        color: #fff !important;
    }

    /* Thông báo cuộc gọi */
    .call-notification {
        position: fixed;
        top: 20%;
        left: 50%;
        transform: translateX(-50%);
        width: 320px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        z-index: 10001;
        padding: 20px;
        animation: slideIn 0.3s ease;
    }
    @keyframes slideIn {
        from { top: -100px; opacity: 0; }
        to { top: 20%; opacity: 1; }
    }
    .notification-content {
        text-align: center;
    }
    .caller-info i {
        font-size: 40px;
        color: #28a745;
        margin-bottom: 10px;
    }
    .pulse {
        animation: pulse 1.5s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    #caller-name {
        margin: 0;
        font-size: 20px;
        color: #333;
    }
    #call-type-text {
        margin: 5px 0 15px;
        color: #666;
    }
    .notification-buttons {
        display: flex;
        gap: 10px;
        justify-content: center;
    }
    .notification-buttons button {
        padding: 10px 20px;
        border: none;
        border-radius: 25px;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s;
    }
    .accept-btn {
        background: #28a745;
        color: #fff;
    }
    .accept-btn:hover {
        background: #218838;
    }
    .reject-btn {
        background: #dc3545;
        color: #fff;
    }
    .reject-btn:hover {
        background: #c82333;
    }
</style>

</body>
</html>