<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
//version mejorada
$user_id = $_SESSION['user_id'];
$posts_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $posts_per_page;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    header('Content-Type: application/json');
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_comment':
                $post_id = (int)$_POST['post_id'];
                $content = $_POST['content'];
                if (!empty($content)) {
                    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
                    $stmt->bind_param("iis", $post_id, $user_id, $content);
                    if (!$stmt->execute()) {
                        echo json_encode(["status" => "error", "message" => $stmt->error]);
                    } else {
                        echo json_encode(["status" => "success", "message" => "Comentario añadido con éxito"]);
                    }
                    $stmt->close();
                } else {
                    echo json_encode(["status" => "error", "message" => "El contenido del comentario no puede estar vacío."]);
                }
                break;
            case 'like_post':
                $post_id = (int)$_POST['post_id'];
                $stmt = $conn->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE id=id");
                $stmt->bind_param("ii", $post_id, $user_id);
                if ($stmt->execute()) {
                    echo json_encode(["status" => "liked"]);
                } else {
                    echo json_encode(["status" => "error", "message" => $stmt->error]);
                }
                $stmt->close();
                break;
            case 'unlike_post':
                $post_id = (int)$_POST['post_id'];
                $stmt = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
                $stmt->bind_param("ii", $post_id, $user_id);
                if ($stmt->execute()) {
                    echo json_encode(["status" => "unliked"]);
                } else {
                    echo json_encode(["status" => "error", "message" => $stmt->error]);
                }
                $stmt->close();
                break;
            default:
                echo json_encode(["status" => "error", "message" => "Acción no válida."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No se especificó ninguna acción."]);
    }
    exit();
}

function getPosts($conn, $offset, $limit) {
    $sql = "SELECT posts.*, users.username, users.is_admin, 
            (SELECT COUNT(*) FROM comments WHERE comments.post_id = posts.id) as comment_count,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) as like_count,
            (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = {$_SESSION['user_id']}) as user_liked
            FROM posts 
            JOIN users ON posts.user_id = users.id 
            ORDER BY posts.created_at DESC
            LIMIT $offset, $limit";
    $result = $conn->query($sql);
    return $result;
}

function getComments($conn, $post_id) {
    $stmt = $conn->prepare("SELECT comments.*, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.post_id = ? ORDER BY comments.created_at DESC");
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comments = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $comments;
}

$total_posts = $conn->query("SELECT COUNT(*) as count FROM posts")->fetch_assoc()['count'];
$total_pages = ceil($total_posts / $posts_per_page);
$posts = getPosts($conn, $offset, $posts_per_page);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - Dark Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Posts</h2>
        <form id="postForm" class="mb-4" method="POST" action="posts.php">
            <textarea name="content" placeholder="Escribe tu post aquí" required></textarea>
            <button type="submit">Publicar</button>
        </form>

        <!-- Botón para ver las notificaciones -->
        <button id="notifications-btn">Ver Notificaciones</button>
        <div id="notifications-container"></div>

        <div id="posts" class="posts">
            <?php while ($post = $posts->fetch_assoc()): ?>
                <div class="post <?php echo $post['is_admin'] != '0' ? 'admin-post' : ''; ?>" data-id="<?php echo $post['id']; ?>">
                    <p class="author">
                        <?php 
                        echo htmlspecialchars($post['username']);
                        if ($post['is_admin'] == '1') {
                            echo " (Admin: Piojitoazulado)";
                        } elseif ($post['is_admin'] == '2') {
                            echo " (Admin: matrix)";
                        }
                        ?>
                    </p>
                    <p class="content"><?php echo htmlspecialchars($post['content']); ?></p>
                    <p class="timestamp"><?php echo $post['created_at']; ?></p>
                    <div class="post-actions">
                        <button class="like-btn" data-post-id="<?php echo $post['id']; ?>" data-liked="<?php echo $post['user_liked']; ?>">
                            <?php echo $post['user_liked'] ? 'Unlike' : 'Like'; ?> (<?php echo $post['like_count']; ?>)
                        </button>
                        <button class="comment-btn" data-post-id="<?php echo $post['id']; ?>">
                            Comment (<?php echo $post['comment_count']; ?>)
                        </button>
                        <?php if ($post['user_id'] == $_SESSION['user_id']): ?>
                            <button class="edit-btn" data-post-id="<?php echo $post['id']; ?>">Edit</button>
                            <button class="delete-btn" data-post-id="<?php echo $post['id']; ?>">Delete</button>
                        <?php endif; ?>
                    </div>
                    <div class="comments-section" style="display: none;">
                        <h4>Comments</h4>
                        <div class="comments-list">
                            <?php 
                            $comments = getComments($conn, $post['id']);
                            foreach ($comments as $comment): 
                            ?>
                                <div class="comment">
                                    <p class="comment-author">
                                        <?php 
                                        echo htmlspecialchars($comment['username']);
                                        ?>
                                    </p>
                                    <p class="comment-content"><?php echo htmlspecialchars($comment['content']); ?></p>
                                    <p class="comment-timestamp"><?php echo $comment['created_at']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form class="comment-form" data-post-id="<?php echo $post['id']; ?>">
                            <textarea name="content" placeholder="Write a comment" required></textarea>
                            <button type="submit">Submit Comment</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>" <?php echo $i == $page ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
            <?php endfor; ?>
        </div>
        <p><a href="index.php">Volver al inicio</a></p>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const postsContainer = document.getElementById('posts');
        const postForm = document.getElementById('postForm');
        const notificationsBtn = document.getElementById('notifications-btn');
        const notificationsContainer = document.getElementById('notifications-container');

        // Mostrar notificaciones
        notificationsBtn.addEventListener('click', function() {
            fetch('get_notifications.php')
                .then(response => response.text())
                .then(data => {
                    notificationsContainer.innerHTML = data;
                });
        });

        postForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(postForm);
            formData.append('action', 'create_post');
            fetch('posts.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => location.reload());
        });

        postsContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('like-btn')) {
                const postId = event.target.dataset.postId;
                const liked = event.target.dataset.liked === '1';
                const action = liked ? 'unlike_post' : 'like_post';

                fetch('posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: action,
                        post_id: postId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'liked' || data.status === 'unliked') {
                        event.target.textContent = liked ? `Like (${parseInt(event.target.textContent.match(/\d+/)[0]) - 1})` : `Unlike (${parseInt(event.target.textContent.match(/\d+/)[0]) + 1})`;
                        event.target.dataset.liked = liked ? '0' : '1';
                    }
                })
                .catch(error => console.error('Error:', error));
            }

            if (event.target.classList.contains('comment-btn')) {
                const postId = event.target.dataset.postId;
                const commentsSection = event.target.closest('.post').querySelector('.comments-section');
                commentsSection.style.display = commentsSection.style.display === 'none' ? 'block' : 'none';
            }
        });

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
    });
    </script>
</body>
</html>