<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';
include 'notifications.php';

// Función para obtener el número de notificaciones no leídas
function getUnreadNotificationsCount($userId, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND seen = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['unread_count'];
}

// Función para marcar una notificación como leída
if (isset($_POST['notification_id'])) {
    $notification_id = (int)$_POST['notification_id'];
    $stmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE id = ?");
    $stmt->bind_param("i", $notification_id);
    $stmt->execute();
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dark Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenido al Dark Forum</h1>
        <p>Este es un lugar para debatir temas interesantes y conectar con otros usuarios. ¡Explora, participa y comparte!</p>

        <?php
        if (isset($_SESSION['user_id'])) {
            $username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
            $userId = $_SESSION['user_id'];

            // Obtener el número de notificaciones no leídas
            $unreadNotificationsCount = getUnreadNotificationsCount($userId, $conn);
            
            echo "<p>Hola, $username! <a href='logout.php'>Cerrar sesión</a></p>";
            echo "<a href='posts.php'>Ver posts</a>";

            // Mostrar el contador de notificaciones no leídas si hay alguna
            if ($unreadNotificationsCount > 0) {
                echo "<div id='notification-count' class='notification-count'>$unreadNotificationsCount</div>";
            }
        } else {
            echo "<p><a href='login.php'>Iniciar sesión</a> o <a href='register.php'>Registrarse</a></p>";
        }
        ?>

        <!-- Sección para las notificaciones -->
        <div id="notifications" class="notifications">
            <?php
            if (isset($_SESSION['user_id'])) {
                $query = "SELECT * FROM notifications WHERE user_id = $userId AND seen = 0 ORDER BY timestamp DESC LIMIT 5";
                $result = $conn->query($query);

                while ($notification = $result->fetch_assoc()) {
                    echo "<div class='notification' id='notification-" . $notification['id'] . "' onclick='markAsRead(" . $notification['id'] . ")'>";
                    echo "<p>" . $notification['message'] . "</p>";
                    echo "<span class='timestamp'>" . $notification['timestamp'] . "</span>";
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>

    <script>
        // Función para marcar una notificación como leída
        function markAsRead(notificationId) {
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => {
                document.getElementById('notification-' + notificationId).style.backgroundColor = '#f0f0f0'; // Cambiar color para indicar que está leída
            })
            .catch(error => console.error('Error:', error));
        }
    </script>

    <script src="main.js"></script>
</body>
</html>
