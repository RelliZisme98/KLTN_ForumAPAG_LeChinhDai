function submitComment(event, postId) {
    event.preventDefault();
    const form = event.target;
    const commentText = form.querySelector('textarea[name="comment"]').value;
    
    if (!commentText.trim()) return;

    fetch('index.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=comment&post_id=${postId}&comment=${encodeURIComponent(commentText)}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload(); // Reload to show new comment
        }
    });
}
