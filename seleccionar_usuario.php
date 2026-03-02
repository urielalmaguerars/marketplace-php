<?php
include 'conexionCapa.php';
session_start();

// Obtener todos los usuarios para selección
$stmt = $conn->query("SELECT IDusuario, Nombre FROM usuario ORDER BY Nombre");
$usuarios = [];
while ($row = $stmt->fetch_assoc()) {
    $usuarios[] = $row;
}

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id'])) {
    $usuario_id = (int)$_POST['usuario_id'];
    
    // Verificar que el usuario existe
    $stmt = $conn->prepare("SELECT IDusuario, Nombre FROM usuario WHERE IDusuario = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // Guardar en sesión
        $_SESSION['IDusuario'] = $usuario_id;
        $_SESSION['NombreUsuario'] = $usuario['Nombre'];
        
        // También guardar en cookie para mayor seguridad
        setcookie('usuario_actual_id', $usuario_id, time() + 86400, '/');
        setcookie('usuario_actual_nombre', $usuario['Nombre'], time() + 86400, '/');
        
        // Redirigir a la página de chats
        header('Location: chats.php?login_exitoso=1');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles-mejorado.css">
    <title>Seleccionar Usuario - Click & Go</title>
    <style>
        .seleccion-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #4a89dc;
        }
        
        .instrucciones {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #4a89dc;
        }
        
        .usuario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .usuario-card {
            background-color: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .usuario-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #e8f4ff;
            border-color: #4a89dc;
        }
        
        .usuario-nombre {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .usuario-id {
            color: #777;
            font-size: 14px;
        }
        
        form {
            margin-top: 20px;
        }
        
        button {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #4a89dc;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #3b7dd8;
        }
        
        .selected {
            border: 2px solid #4a89dc;
            background-color: #e8f4ff;
        }
    </style>
</head>
<body>
    <div class="seleccion-container">
        <h1>Seleccionar Usuario</h1>
        
        <div class="instrucciones">
            <p><strong>Importante:</strong> Selecciona el usuario con el que deseas chatear. Esto nos ayudará a asegurarnos de que los mensajes se envíen correctamente.</p>
            <p>Si tienes problemas con los chats, este paso te ayudará a resolverlos.</p>
        </div>
        
        <form method="post" action="">
            <input type="hidden" id="usuario_id" name="usuario_id" value="">
            
            <div class="usuario-grid">
                <?php foreach ($usuarios as $usuario): ?>
                <div class="usuario-card" data-id="<?= $usuario['IDusuario'] ?>" onclick="seleccionarUsuario(<?= $usuario['IDusuario'] ?>, this)">
                    <div class="usuario-nombre"><?= htmlspecialchars($usuario['Nombre']) ?></div>
                    <div class="usuario-id">ID: <?= $usuario['IDusuario'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" id="submit-btn" disabled>Continuar con este usuario</button>
        </form>
    </div>
    
    <script>
        function seleccionarUsuario(id, element) {
            // Quitar selección previa
            document.querySelectorAll('.usuario-card').forEach(card => card.classList.remove('selected'));
            
            // Marcar el seleccionado
            element.classList.add('selected');
            
            // Guardar el ID
            document.getElementById('usuario_id').value = id;
            
            // Habilitar el botón
            document.getElementById('submit-btn').disabled = false;
            
            // Actualizar texto del botón
            const nombreUsuario = element.querySelector('.usuario-nombre').textContent;
            document.getElementById('submit-btn').textContent = `Continuar como ${nombreUsuario}`;
        }
    </script>
</body>
</html>