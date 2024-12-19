<?php
session_start();

// Eliminar las variables específicas de sesión
session_unset();

// Destruir la sesión completamente
session_destroy();

// Redirigir a la página de inicio
header("Location: index.php");
exit();
?>
