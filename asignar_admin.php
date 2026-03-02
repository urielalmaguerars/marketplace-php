<?php
session_start();
include 'conexionCapa.php';

// Verificar que el usuario es admin
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "super") {
    echo "Acceso denegado.";
    exit();
}

// Procesar formulario
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['IDusuario'])) {
    $IDusuario = intval($_POST['IDusuario']);
    
    // Actualizar el rol del usuario
    $stmt = $conn->prepare("UPDATE usuario SET Rol = 'admin' WHERE IDusuario = ?");
    $stmt->bind_param("i", $IDusuario);

    if ($stmt->execute()) {
        $mensaje = "✅ Usuario actualizado a administrador correctamente.";
    } else {
        $mensaje = "❌ Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
}

// Obtener lista de usuarios normales
$result = $conn->query("SELECT IDusuario, NombreUsuario, Rol FROM usuario WHERE Rol != 'super' ORDER BY NombreUsuario ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignar Administrador</title>
    <link rel="stylesheet" href="styles-mejorado.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background-color: #f4f4f4;
        }
        h1 {
            color: #0077b6;
        }
        form {
            margin-top: 20px;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        select, button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .mensaje {
            margin-top: 20px;
            font-weight: bold;
            color: green;
        }
    </style>
</head>
<body>
    <h1>Asignar Rol de Administrador</h1>

    <?php if (!empty($mensaje)): ?>
        <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="IDusuario">Selecciona un usuario:</label>
        <select name="IDusuario" required>
            <option value="" disabled selected>-- Elige un usuario --</option>
            <?php while ($usuario = $result->fetch_assoc()): ?>
                <option value="<?= $usuario['IDusuario'] ?>">
                    <?= htmlspecialchars($usuario['NombreUsuario']) ?> (<?= $usuario['Rol'] ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit">Convertir en Admin</button>
    </form>

    <br><a href="index.php">← Volver a iniciar sesion</a>
</body>
</html>
