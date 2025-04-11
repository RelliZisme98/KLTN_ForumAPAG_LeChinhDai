let socket;
try {
    socket = io('http://localhost:3000', {
        transports: ['websocket'],
        upgrade: false,
        reconnection: true,
        reconnectionAttempts: 5,
        timeout: 10000
    });

    socket.on('connect', () => {
        console.log('Connected to Socket.IO server');
        if (window.currentUserId) {
            socket.emit('register', window.currentUserId);
        }
    });

    socket.on('connect_error', (error) => {
        console.error('Socket.IO connection error:', error);
        // Show error message but don't block page load 
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'position:fixed;top:10px;right:10px;background:red;color:white;padding:10px;border-radius:5px;';
        errorDiv.textContent = 'Chat server connection error. Some features may not work.';
        document.body.appendChild(errorDiv);
    });

    window.socket = socket;
} catch (err) {
    console.error('Socket initialization error:', err);
}
