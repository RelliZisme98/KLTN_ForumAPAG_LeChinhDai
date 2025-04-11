const express = require('express');
const app = express();
const server = require('http').createServer(app);
const io = require('socket.io')(server, {
    cors: { origin: "*" }
});

const PORT = 3000;
const users = new Map();

app.get('/', (req, res) => res.send('Simple Call Server'));

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    socket.on('register-user', (userId) => {
        users.set(userId, socket.id);
        // console.log('User registered:', userId, socket.id);
    });

    socket.on('initiate-call', (data) => {
        console.log('Call initiated:', data);
        const recipientSocket = users.get(data.to);
        if (recipientSocket) {
            console.log('Sending call-notification to:', recipientSocket);
            io.to(recipientSocket).emit('call-notification', {
                from: data.from,
                fromName: data.fromName,
                type: data.type,
                callId: Date.now()
            });
        } else {
            console.log('Recipient not online:', data.to);
            socket.emit('call-failed', { message: 'Người dùng không trực tuyến' });
        }
    });

    socket.on('accept-call', (data) => {
        const callerSocket = users.get(data.from);
        if (callerSocket) {
            console.log('Call accepted, notifying:', callerSocket, socket.id);
            io.to(callerSocket).emit('call-accepted', data);
            socket.emit('call-accepted', data);
        } else {
            console.error('Caller not found:', data.from);
        }
    });

    socket.on('webrtc-signal', (data) => {
        const targetSocket = users.get(data.to);
        if (targetSocket) {
            console.log('Forwarding WebRTC signal to:', targetSocket, data);
            io.to(targetSocket).emit('webrtc-signal', data);
        } else {
            console.error('Target not found for WebRTC signal:', data.to);
            socket.emit('webrtc-error', {
                code: 'PEER_NOT_FOUND',
                message: 'Người dùng không còn kết nối'
            });
        }
    });

    socket.on('end-call', (data) => {
        console.log('Received end-call:', data);
        const targetSocket = users.get(data.to);
        if (targetSocket) {
            console.log('Sending call-ended to:', targetSocket);
            io.to(targetSocket).emit('call-ended');
        }
        // Gửi thông báo cả cho người gọi
        socket.emit('call-ended');
    });

    socket.on('reject-call', (data) => {
        console.log('Received reject-call:', data); 
        const callerSocket = users.get(data.to);
        if (callerSocket) {
            console.log('Sending call-rejected to caller:', callerSocket);
            io.to(callerSocket).emit('call-rejected', {
                from: data.from,
                message: 'Cuộc gọi đã bị từ chối'
            });
        } else {
            console.error('Caller not found:', data.to);
        }
    });

    socket.on('error', (error) => {
        console.error('Socket error:', error);
        // Notify relevant peers about the error
        if (socket.currentCall) {
            const peerId = socket.currentCall.peerId;
            const peerSocket = users.get(peerId);
            if (peerSocket) {
                io.to(peerSocket).emit('call-error', {
                    code: 'PEER_ERROR',
                    message: 'Lỗi kết nối với người dùng'
                });
            }
        }
    });

    socket.on('disconnect', () => {
        for (let [userId, socketId] of users.entries()) {
            if (socketId === socket.id) {
                users.delete(userId);
                break;
            }
        }
        console.log('Client disconnected:', socket.id);

        // Notify any active call partners
        if (socket.currentCall) {
            const peerId = socket.currentCall.peerId;
            const peerSocket = users.get(peerId);
            if (peerSocket) {
                io.to(peerSocket).emit('peer-disconnected', {
                    message: 'Người dùng đã ngắt kết nối'
                });
            }
        }
    });
});

server.listen(PORT, () => {
    console.log(`Call server running on port ${PORT}`);
});