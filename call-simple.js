// // Check if required libraries are loaded
// if (typeof SimplePeer === 'undefined') {
//     console.error('SimplePeer library not loaded!');
//     alert('Required libraries are not loaded properly. Please check your internet connection and refresh the page.');
// }

// // Update socket connection with retry logic
// const socket = io('http://localhost:3000', {
//     reconnection: true,
//     reconnectionAttempts: 5,
//     reconnectionDelay: 1000,
//     timeout: 10000,
//     transports: ['websocket', 'polling']
// });

// // Add better socket error handling
// socket.on('connect', () => {
//     console.log('Connected to signaling server');
// });

// socket.on('connect_error', (error) => {
//     console.error('Socket connection error:', error);
//     if (!document.getElementById('socket-error-alert')) {
//         const alert = document.createElement('div');
//         alert.id = 'socket-error-alert';
//         alert.style.cssText = `
//             position: fixed;
//             top: 20px;
//             right: 20px;
//             background: #ff4444;
//             color: white;
//             padding: 10px 20px;
//             border-radius: 5px;
//             z-index: 9999;
//         `;
//         alert.innerHTML = 'Unable to connect to call server. Video calls may not work.';
//         document.body.appendChild(alert);
//     }
// });

// socket.on('disconnect', () => {
//     console.log('Disconnected from signaling server');
// });

// class SimpleCallHandler {
//     constructor() {
//         this.peer = null;
//         this.localStream = null;
//         this.callWindow = null;
//         this.currentUserId = window.currentUserId; // Lấy ID người dùng hiện tại
//         this.callingDialog = null;
//         this.getUserInfo();
//     }

//     getUserInfo() {
//         const usernameElement = document.getElementById('current-username');
//         this.currentUsername = usernameElement ? usernameElement.value : 'Unknown User';
//     }

//     async checkMediaPermissions(isVideo) {
//         try {
//             // First check if we're on HTTPS or localhost
//             if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
//                 throw new Error('Media access requires HTTPS or localhost');
//             }

//             // Check device availability
//             const devices = await navigator.mediaDevices.enumerateDevices();
//             const hasAudio = devices.some(device => device.kind === 'audioinput');
//             const hasVideo = devices.some(device => device.kind === 'videoinput');

//             if (isVideo && !hasVideo) {
//                 throw new Error('No camera detected');
//             }
//             if (!hasAudio) {
//                 throw new Error('No microphone detected');
//             }

//             // Try to get permissions
//             const stream = await navigator.mediaDevices.getUserMedia({
//                 video: isVideo ? { width: 640, height: 480 } : false,
//                 audio: {
//                     echoCancellation: true,
//                     noiseSuppression: true,
//                     autoGainControl: true
//                 }
//             });

//             // Stop the test stream
//             stream.getTracks().forEach(track => track.stop());
//             return true;

//         } catch (err) {
//             console.error('Permission check failed:', err);
//             let message = 'Could not access ';
//             message += isVideo ? 'camera and microphone' : 'microphone';
//             message += '\n\nPlease:\n';
//             message += '1. Check that your device is connected\n';
//             message += '2. Click the lock/info icon in the address bar\n';
//             message += '3. Allow access to camera/microphone\n';
//             message += '4. Refresh the page\n\n';
//             message += `Error: ${err.message}`;
            
//             alert(message);
//             return false;
//         }
//     }

//     async startCall(recipientId, isVideo = true) {
//         try {
//             console.log('Starting call to:', recipientId);
            
//             // Check permissions first
//             const hasPermissions = await this.checkMediaPermissions(isVideo);
//             if (!hasPermissions) {
//                 return;
//             }

//             // Show calling dialog first
//             this.showCallingDialog();

//             // Use this.currentUsername instead of trying to get it from DOM
//             socket.emit('initiate-call', {
//                 from: this.currentUserId,
//                 fromName: this.currentUsername,
//                 to: recipientId,
//                 type: isVideo ? 'video' : 'voice'
//             });

//             // Get media stream with specific constraints
//             this.localStream = await navigator.mediaDevices.getUserMedia({
//                 video: isVideo ? {
//                     width: { ideal: 1280 },
//                     height: { ideal: 720 },
//                     facingMode: 'user'
//                 } : false,
//                 audio: {
//                     echoCancellation: true,
//                     noiseSuppression: true,
//                     autoGainControl: true,
//                     sampleRate: 48000
//                 }
//             });

//             // Show call window
//             this.showCallUI(isVideo);

//             // Initialize peer with ICE servers
//             this.peer = new SimplePeer({
//                 initiator: true,
//                 stream: this.localStream,
//                 trickle: false,
//                 config: {
//                     iceServers: [
//                         { urls: 'stun:stun.l.google.com:19302' },
//                         { urls: 'stun:global.stun.twilio.com:3478' }
//                     ]
//                 }
//             });

//             // Add error handling for peer connection
//             this.peer.on('error', err => {
//                 console.error('Peer connection error:', err);
//                 alert('Connection error. Please try again.');
//                 this.endCall();
//             });

//             // Handle peer events
//             this.peer.on('signal', data => {
//                 socket.emit('call-user', {
//                     userToCall: recipientId,
//                     signalData: data,
//                     type: isVideo ? 'video' : 'voice'
//                 });
//             });

//             this.peer.on('stream', stream => {
//                 document.getElementById('remoteVideo').srcObject = stream;
//             });

//         } catch (err) {
//             this.hideCallingDialog();
//             console.error('Error starting call:', err);
//             this.handleCallError(err);
//         }
//     }

//     handleCallError(error) {
//         let message = 'Could not start call.\n\n';
        
//         if (error.name === 'NotAllowedError') {
//             message += 'Camera/Microphone access was denied.\n';
//             message += 'Please check your browser permissions.';
//         } else if (error.name === 'NotFoundError') {
//             message += 'No camera/microphone found.\n';
//             message += 'Please check your device connections.';
//         } else if (error.name === 'NotReadableError') {
//             message += 'Camera/Microphone is already in use.\n';
//             message += 'Please close other applications that might be using it.';
//         } else {
//             message += `Error: ${error.message}`;
//         }
        
//         alert(message);
//         this.endCall();
//     }

//     showCallUI(isVideo) {
//         const container = document.createElement('div');
//         container.className = 'call-container';
//         container.innerHTML = `
//             <div class="call-header">
//                 <span>Call in progress</span>
//                 <button onclick="callHandler.endCall()">End Call</button>
//             </div>
//             <div class="videos">
//                 <video id="remoteVideo" autoplay></video>
//                 <video id="localVideo" autoplay muted></video>
//             </div>
//         `;
//         document.body.appendChild(container);
//         this.callWindow = container;
        
//         document.getElementById('localVideo').srcObject = this.localStream;
//     }

//     showCallingDialog() {
//         if (this.callingDialog) {
//             return;
//         }

//         this.callingDialog = document.createElement('div');
//         this.callingDialog.className = 'calling-dialog';
//         this.callingDialog.innerHTML = `
//             <div class="calling-content">
//                 <h3>Đang gọi...</h3>
//                 <div class="calling-animation"></div>
//                 <button onclick="callHandler.cancelCall()" class="cancel-call-btn">Hủy</button>
//             </div>
//         `;
//         document.body.appendChild(this.callingDialog);
//     }

//     hideCallingDialog() {
//         if (this.callingDialog) {
//             this.callingDialog.remove();
//             this.callingDialog = null;
//         }
//     }

//     cancelCall() {
//         socket.emit('call-cancelled', {
//             to: currentFriendId
//         });
//         this.endCall();
//     }

//     endCall() {
//         this.hideCallingDialog();
//         if (this.localStream) {
//             this.localStream.getTracks().forEach(track => track.stop());
//         }
//         if (this.peer) {
//             this.peer.destroy();
//         }
//         if (this.callWindow) {
//             this.callWindow.remove();
//             this.callWindow = null;
//         }
//         socket.emit('end-call');
//     }

//     showIncomingCallDialog(data) {
//         const dialog = document.createElement('div');
//         dialog.className = 'incoming-call-dialog';
//         dialog.innerHTML = `
//             <div class="call-content">
//                 <h3>${data.fromName} đang gọi ${data.type === 'video' ? 'video' : 'voice'}</h3>
//                 <div class="call-buttons">
//                     <button onclick="callHandler.acceptCall('${data.from}', '${data.type}')" class="accept-btn">
//                         <i class="fa fa-phone"></i> Chấp nhận
//                     </button>
//                     <button onclick="callHandler.rejectCall('${data.from}')" class="reject-btn">
//                         <i class="fa fa-phone-slash"></i> Từ chối
//                     </button>
//                 </div>
//             </div>
//         `;
//         document.body.appendChild(dialog);
        
//         // Auto close after 30 seconds
//         setTimeout(() => {
//             if (document.body.contains(dialog)) {
//                 dialog.remove();
//                 this.rejectCall(data.from);
//             }
//         }, 30000);
//     }
// }

// const callHandler = new SimpleCallHandler();

// // Socket event handlers
// socket.on('call-made', async data => {
//     if (confirm(`Incoming ${data.type} call. Accept?`)) {
//         try {
//             const stream = await navigator.mediaDevices.getUserMedia({
//                 video: data.type === 'video',
//                 audio: true
//             });
            
//             callHandler.localStream = stream;
//             callHandler.showCallUI(data.type === 'video');
            
//             callHandler.peer = new SimplePeer({
//                 initiator: false,
//                 stream: stream,
//                 trickle: false
//             });
            
//             callHandler.peer.signal(data.signal);
//         } catch (err) {
//             console.error('Error accepting call:', err);
//             socket.emit('call-rejected');
//         }
//     } else {
//         socket.emit('call-rejected');
//     }
// });

// // Thêm socket event listeners
// socket.on('incoming-call', (data) => {
//     console.log('Incoming call from:', data);
//     callHandler.showIncomingCallDialog(data);
// });

// socket.on('call-cancelled', () => {
//     callHandler.hideCallingDialog();
// });

// socket.on('call-rejected', () => {
//     callHandler.hideCallingDialog();
//     alert('Cuộc gọi bị từ chối');
// });

// socket.on('call-ended', () => {
//     callHandler.endCall();
// });
