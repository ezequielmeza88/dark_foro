<?php
session_start();  // Asegúrate de iniciar la sesión
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // Redirige si el usuario no ha iniciado sesión
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id']; // ID del usuario logueado

// Usar consultas preparadas para prevenir inyección SQL
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND seen = 0 ORDER BY timestamp DESC LIMIT 5");
$stmt->bind_param("i", $userId);  // "i" indica que el parámetro es un entero
$stmt->execute();
$result = $stmt->get_result();

while ($notification = $result->fetch_assoc()) {
    echo "<div class='notification' id='notification-" . $notification['id'] . "' onclick='markAsRead(" . $notification['id'] . ")'>";
    echo "<p>" . $notification['message'] . "</p>";
    echo "<span class='timestamp'>" . $notification['timestamp'] . "</span>";
    echo "</div>";
}

$stmt->close();
?>
