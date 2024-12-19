<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dark_forum";

// Habilitar reporte de errores detallados
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4"); // Asegurar codificación UTF-8
} catch (mysqli_sql_exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
