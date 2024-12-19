<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $is_admin = 0;  // Siempre asignamos 0 para usuarios normales

    // Validación de la contraseña (mínimo 8 caracteres)
    if (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Verificar si el nombre de usuario ya existe
        $sql_check = "SELECT id FROM users WHERE username = '$username'";
        $result_check = $conn->query($sql_check);

        if ($result_check->num_rows > 0) {
            $error = "El nombre de usuario ya está en uso.";
        } else {
            // Encriptar la contraseña
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insertar el nuevo usuario
            $sql = "INSERT INTO users (username, password, is_admin) VALUES ('$username', '$password_hashed', '$is_admin')";
            if ($conn->query($sql) === TRUE) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Error: " . $sql . "<br>" . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Dark Forum</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>Registro</h2>
        <?php
        if (isset($error)) {
            echo "<p class='error'>$error</p>";
        }
        ?>
        <form method="post" action="">
            <input type="text" name="username" placeholder="Nombre de usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Registrarse</button>
        </form>
        <p><a href="login.php">¿Ya tienes cuenta? Inicia sesión</a></p>
    </div>
</body>
</html>
