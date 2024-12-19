<?php
include 'db_connect.php';

function markAsRead($notificationId) {
    global $conn;
    $stmt = $conn->prepare("UPDATE notifications SET seen = 1 WHERE id = ?");
    $stmt->bind_param("i", $notificationId);
    $stmt->execute();
    $stmt->close();
}
?>
