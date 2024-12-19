document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.comment-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const content = form.querySelector('textarea').value;
            const postId = form.getAttribute('data-post-id');

            if (content) {
                fetch('posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=create_comment&post_id=${postId}&content=${encodeURIComponent(content)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        location.reload();
                    } else {
                        console.error(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error: ", error);
                });
            }
        });
    });

    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const postId = button.getAttribute('data-post-id');
            const liked = button.getAttribute('data-liked') === '1';
            const action = liked ? 'unlike_post' : 'like_post';

            fetch('posts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&post_id=${postId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'liked' || data.status === 'unliked') {
                    button.textContent = liked ? `Like (${parseInt(button.textContent.match(/\d+/)[0]) - 1})` : `Unlike (${parseInt(button.textContent.match(/\d+/)[0]) + 1})`;
                    button.setAttribute('data-liked', liked ? '0' : '1');
                } else {
                    console.error(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });
});