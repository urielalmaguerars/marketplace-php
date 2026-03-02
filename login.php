<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$db = "capa_inter";
$port = "3307";

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_errno) {
    die("Fallo la conexión a la base de datos: " . $conn->connect_error);
}

$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombreUsuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['Contraseña'] ?? '';

    if ($nombreUsuario && $contrasena) {
        $sql = "SELECT * FROM usuario WHERE NombreUsuario = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nombreUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows == 1) {
            $fila = $resultado->fetch_assoc();

            echo '<pre>';



            // Aquí asumimos que la contraseña está en texto plano (mejor usar hash)
            if (password_verify($contrasena, $fila['Contraseña'])) {
                $_SESSION['NombreUsuario'] = $fila['NombreUsuario'];
                $_SESSION['IDusuario'] = $fila['IDusuario'];
                $_SESSION['Rol'] = $fila['Rol'];
                $_SESSION['mensaje_login'] = "Inicio de sesión exitoso";

                if ($fila['Rol'] == 'admin') {
                header("Location: admin_panel.php");
                exit;
                    } else {
                // Usuario común, redirige a home
                header("Location: home.php");
                exit;
                }
            } else {
                $error = "Contraseña incorrecta.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
    } else {
        $error = "Por favor, completa todos los campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
</head>
<body>
    <h2>Iniciar sesión</h2>
    <?php if ($error): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post" action="login.php">
        <label for="usuario">Usuario:</label><br>
        <input type="text" name="usuario" id="usuario" required><br><br>

        <label for="password">Contraseña:</label><br>
        <input type="password" name="Contraseña" id="Contraseña" required><br><br>

        <input type="submit" value="Entrar">
    </form>
</body>
</html>
