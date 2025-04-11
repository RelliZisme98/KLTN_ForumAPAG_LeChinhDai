// class CallHandler {
//     constructor() {
//         this.peerConnection = null;
//         this.localStream = null;
//         this.remoteStream = null;
//         this.ws = new WebSocket('wss://' + window.location.hostname + ':8080');
//         this.currentCallId = null;
        
//         this.ws.onmessage = this.handleWebSocketMessage.bind(this);
//     }

//     async startVideoCall(recipientId) {
//         try {
//             // Get local stream
//             this.localStream = await navigator.mediaDevices.getUserMedia({
//                 video: true,
//                 audio: true
//             });
            
//             // Show call UI
//             this.showCallUI(true);
//             document.getElementById('localVideo').srcObject = this.localStream;

//             // Create peer connection
//             this.setupPeerConnection();

//             // Send call offer
//             this.currentCallId = Date.now().toString();
//             this.ws.send(JSON.stringify({
//                 type: 'call-offer',
//                 recipientId: recipientId,
//                 callId: this.currentCallId
//             }));

//         } catch (err) {
//             console.error('Error starting video call:', err);
//             alert('Could not start video call: ' + err.message);
//         }
//     }

//     async startVoiceCall(recipientId) {
//         try {
//             // Get local stream (audio only)
//             this.localStream = await navigator.mediaDevices.getUserMedia({
//                 video: false,
//                 audio: true
//             });
            
//             // Show call UI (audio only)
//             this.showCallUI(false);

//             // Create peer connection
//             this.setupPeerConnection();

//             // Send call offer
//             this.currentCallId = Date.now().toString();
//             this.ws.send(JSON.stringify({
//                 type: 'voice-call-offer',
//                 recipientId: recipientId,
//                 callId: this.currentCallId
//             }));

//         } catch (err) {
//             console.error('Error starting voice call:', err);
//             alert('Could not start voice call: ' + err.message);
//         }
//     }

//     setupPeerConnection() {
//         this.peerConnection = new RTCPeerConnection({
//             iceServers: [
//                 { urls: 'stun:stun.l.google.com:19302' },
//                 { urls: 'stun:stun1.l.google.com:19302' }
//             ]
//         });

//         // Add local stream
//         this.localStream.getTracks().forEach(track => {
//             this.peerConnection.addTrack(track, this.localStream);
//         });

//         // Handle remote stream
//         this.peerConnection.ontrack = (event) => {
//             this.remoteStream = event.streams[0];
//             document.getElementById('remoteVideo').srcObject = this.remoteStream;
//         };

//         // Handle ICE candidates
//         this.peerConnection.onicecandidate = (event) => {
//             if (event.candidate) {
//                 this.ws.send(JSON.stringify({
//                     type: 'ice-candidate',
//                     candidate: event.candidate,
//                     callId: this.currentCallId
//                 }));
//             }
//         };
//     }

//     async handleWebSocketMessage(event) {
//         const data = JSON.parse(event.data);
        
//         switch(data.type) {
//             case 'call-offer':
//                 await this.handleCallOffer(data);
//                 break;
//             case 'call-answer':
//                 await this.handleCallAnswer(data);
//                 break;
//             case 'ice-candidate':
//                 await this.handleIceCandidate(data);
//                 break;
//             case 'call-rejected':
//                 this.handleCallRejected(data);
//                 break;
//             case 'end-call':
//                 this.endCall();
//                 break;
//         }
//     }

//     async handleCallOffer(data) {
//         if (confirm('Incoming call. Accept?')) {
//             try {
//                 this.currentCallId = data.callId;
//                 const stream = await navigator.mediaDevices.getUserMedia({
//                     video: data.type === 'call-offer',
//                     audio: true
//                 });
//                 this.localStream = stream;
//                 this.showCallUI(data.type === 'call-offer');
//                 document.getElementById('localVideo').srcObject = stream;
                
//                 this.setupPeerConnection();
                
//                 const answer = await this.peerConnection.createAnswer();
//                 await this.peerConnection.setLocalDescription(answer);
                
//                 this.ws.send(JSON.stringify({
//                     type: 'call-answer',
//                     answer: answer,
//                     callId: this.currentCallId
//                 }));
//             } catch (err) {
//                 console.error('Error accepting call:', err);
//                 alert('Could not accept call: ' + err.message);
//             }
//         } else {
//             this.ws.send(JSON.stringify({
//                 type: 'call-rejected',
//                 callId: data.callId
//             }));
//         }
//     }

//     showCallUI(isVideo) {
//         const callContainer = document.createElement('div');
//         callContainer.id = 'call-container';
//         callContainer.innerHTML = `
//             <div class="call-header">
//                 <span>On Call</span>
//                 <button onclick="callHandler.endCall()">End Call</button>
//             </div>
//             <video id="remoteVideo" ${isVideo ? '' : 'hidden'} autoplay></video>
//             <video id="localVideo" ${isVideo ? '' : 'hidden'} autoplay muted></video>
//             <div class="call-controls">
//                 <button onclick="callHandler.toggleMute()">Mute</button>
//                 ${isVideo ? '<button onclick="callHandler.toggleVideo()">Turn off video</button>' : ''}
//             </div>
//         `;
//         document.body.appendChild(callContainer);
//     }

//     endCall() {
//         if (this.peerConnection) {
//             this.peerConnection.close();
//             this.peerConnection = null;
//         }
//         if (this.localStream) {
//             this.localStream.getTracks().forEach(track => track.stop());
//         }
//         const callContainer = document.getElementById('call-container');
//         if (callContainer) {
//             callContainer.remove();
//         }
//         this.ws.send(JSON.stringify({
//             type: 'end-call',
//             callId: this.currentCallId
//         }));
//     }
// }

// const callHandler = new CallHandler();
