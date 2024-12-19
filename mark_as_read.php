<?php
include 'db_connect.php';

if (isset($_GET['id'])) {
    $notificationId = $_GET['id'];

    // Actualizar el estado de la notificación a "leída"
    $stmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
}
?>
