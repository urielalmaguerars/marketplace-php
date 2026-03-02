<?php
// Iniciar sesión
session_start();


// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    // Redirigir al inicio de sesión si no ha iniciado sesión
    header("Location: index.php");
    exit();
}

// Incluir archivo de conexión a la base de datos
include("conexionCapa.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener información del usuario
$usuario_id = $_SESSION['usuario_id'];
$stmt = $conn->prepare("SELECT IDusuario, Nombre, Apellidos, NombreUsuario, Correo, FechaNacimiento, Genero, Avatar FROM usuario WHERE IDusuario = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    echo "<script>alert('Usuario no encontrado'); window.location.href = 'index.php';</script>";
    exit();
}

// Procesar el formulario cuando se envía
if (isset($_POST['guardar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $apellidos = trim($_POST['apellidos']);
    $correo = trim($_POST['correo']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];

    // Validación de correo
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('El correo electrónico no es válido.'); window.history.back();</script>";
        exit();
    }

    // Validación de fecha de nacimiento
    $fecha_actual = date('Y-m-d');
    if (!strtotime($fecha_nacimiento) || $fecha_nacimiento >= $fecha_actual) {
        echo "<script>alert('Fecha de nacimiento inválida.'); window.history.back();</script>";
        exit();
    }

    // Procesar el avatar si se ha subido uno nuevo
    $avatar_data = $usuario['Avatar']; // Mantener el avatar actual por defecto

    if (isset($_FILES['avatar']) && $_FILES['avatar']['size'] > 0) {
        $archivo = $_FILES['avatar'];
        $tipo_mime = mime_content_type($archivo['tmp_name']);
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
        $mimes_permitidos = ['image/jpeg', 'image/png'];

        if (!in_array($extension, $extensiones_permitidas) || !in_array($tipo_mime, $mimes_permitidos)) {
            echo "<script>alert('Archivo no permitido. Solo se aceptan imágenes JPG y PNG.'); window.history.back();</script>";
            exit();
        }

        $imagen_temporal = $archivo['tmp_name'];
        $avatar_data = file_get_contents($imagen_temporal);
    }

    // Actualizar la información del usuario en la base de datos
    $update_stmt = $conn->prepare("UPDATE usuario SET Nombre = ?, Apellidos = ?, Correo = ?, FechaNacimiento = ?, Genero = ?, Avatar = ? WHERE IDusuario = ?");
    $update_stmt->bind_param("ssssssi", $nombre, $apellidos, $correo, $fecha_nacimiento, $genero, $avatar_data, $usuario_id);

    if ($update_stmt->execute()) {
        $success_message = "✅ Perfil actualizado correctamente.";
        $usuario['Nombre'] = $nombre;
        $usuario['Apellidos'] = $apellidos;
        $usuario['Correo'] = $correo;
        $usuario['FechaNacimiento'] = $fecha_nacimiento;
        $usuario['Genero'] = $genero;
        $usuario['Avatar'] = $avatar_data;
    } else {
        $error_message = "Error al actualizar el perfil: " . $conn->error;
    }

    $update_stmt->close();
}

// Función para mostrar el avatar
function mostrarAvatar($avatar_blob) {
    if (!empty($avatar_blob)) {
        return 'data:image/jpeg;base64,' . base64_encode($avatar_blob);
    } else {
        return 'uploads/avatars/default.png';
    }
}

if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $nueva_password = $_POST['nueva_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    // Para debug - guardar en un log
    error_log("Intento de cambio de contraseña para usuario ID: " . $usuario_id);
    
    // Verificar que la nueva contraseña y la confirmación coincidan
    if ($nueva_password !== $confirmar_password) {
        $password_error = "Las contraseñas no coinciden.";
        error_log("Error: Las contraseñas no coinciden");
    } else {
        // Verificar la contraseña actual
        $check_stmt = $conn->prepare("SELECT Contraseña FROM usuario WHERE IDusuario = ?");
        if (!$check_stmt) {
            $password_error = "Error en la preparación de la consulta: " . $conn->error;
            error_log("Error en la preparación de la consulta: " . $conn->error);
        } else {
            $check_stmt->bind_param("i", $usuario_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $password_error = "No se encontró el usuario.";
                error_log("Error: No se encontró el usuario ID: " . $usuario_id);
            } else {
                $usuario_check = $check_result->fetch_assoc();
                $hashed_password_from_db = $usuario_check['Contraseña'];
                
                // Log para depuración
                error_log("Contraseña encriptada de la BD: " . substr($hashed_password_from_db, 0, 10) . "...");
                
                if (password_verify($password_actual, $hashed_password_from_db)) {
                    // La contraseña actual es correcta, actualizar con la nueva
                    $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
                    
                    // Log para depuración
                    error_log("Nueva contraseña encriptada: " . substr($hashed_password, 0, 10) . "...");
                    
                    $update_pass_stmt = $conn->prepare("UPDATE usuario SET Contraseña = ? WHERE IDusuario = ?");
                    
                    if (!$update_pass_stmt) {
                        $password_error = "Error en la preparación de la actualización: " . $conn->error;
                        error_log("Error en la preparación de la actualización: " . $conn->error);
                    } else {
                        $update_pass_stmt->bind_param("si", $hashed_password, $usuario_id);
                        
                        if ($update_pass_stmt->execute()) {
                            $password_success = "Contraseña actualizada correctamente.";
                            error_log("Contraseña actualizada correctamente para usuario ID: " . $usuario_id);
                        } else {
                            $password_error = "Error al actualizar la contraseña: " . $update_pass_stmt->error;
                            error_log("Error al actualizar la contraseña: " . $update_pass_stmt->error);
                        }
                        
                        $update_pass_stmt->close();
                    }
                } else {
                    $password_error = "La contraseña actual es incorrecta.";
                    error_log("Error: La contraseña actual es incorrecta para usuario ID: " . $usuario_id);
                }
                
                $check_stmt->close();
            }
        }
    }
}

// Agregar el procesamiento del cierre de sesión
if (isset($_POST['cerrar_sesion'])) {
    // Destruir todas las variables de sesión
    session_unset();
    session_destroy();
    
    // Redirigir al index.php
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
    <link rel="stylesheet" href="styles-mejorado.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        header {
    background: #B2E3D3;
    color: white;
    padding: 10px 20px;
    position: fixed; /* Fijo para mantenerlo al hacer scroll */
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column; /* Para que el nombre y los enlaces estén en columnas */
    align-items: center; /* Centra horizontalmente los elementos */
}

header h1 {
    margin: 5px 0;
    color: #333;
}
nav ul {
    list-style: none;
    padding: 0;
}

.nav-links {
    display: flex; /* Muestra los enlaces en formato horizontal */
    list-style: none;
    margin: 0;
    padding: 0;
    justify-content: center; /* Centra los enlaces horizontalmente */
}

.nav-links li {
    margin: 0 15px;
}

nav ul li a {
    color: black;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

nav ul li a:hover {
    color: #0077B6;
}
        
        .perfil-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        
        .perfil-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .perfil-sidebar {
            text-align: center;
        }
        
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        
        #avatarPreview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e1e1e1;
        }
        
        .avatar-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background-color: #007bff;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
        }
        
        #avatarInput {
            display: none;
        }
        
        .perfil-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .buttons {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button[type="submit"] {
            background-color: #007bff;
            color: white;
        }
        
        button[type="submit"]:hover {
            background-color: #0069d9;
        }
        
        .cancel-btn {
            background-color: #f8f9fa;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .cancel-btn:hover {
            background-color: #e2e6ea;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .nav-links {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
            padding: 0;
            list-style: none;
        }
        
        .nav-links li {
            margin: 0 10px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: black;
            font-weight: bold;
            transition: color 0.3s;
        }
        
        .nav-links a:hover {
            color: black;
        }
        
        .perfil-info {
            margin-bottom: 20px;
        }
        
        .perfil-info p {
            margin: 5px 0;
        }

        .password-requirements {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }
        
        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 3px;
            background-color: #eee;
            position: relative;
        }
        
        .password-strength-meter {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .meter-weak {
            background-color: #dc3545;
            width: 25%;
        }
        
        .meter-medium {
            background-color: #ffc107;
            width: 50%;
        }
        
        .meter-strong {
            background-color: #28a745;
            width: 100%;
        }

        
        /* Responsive */
        @media (max-width: 768px) {
            .perfil-grid {
                grid-template-columns: 1fr;
            }
            
            .perfil-form {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: 1;
            }
            
            .buttons {
                grid-column: 1;
                flex-direction: column;
                gap: 10px;
            }
            
            button {
                width: 100%;
            }
        }
        /* Estilos para el botón de eliminar cuenta */
.delete-account-btn {
    background-color: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.delete-account-btn:hover {
    background-color: #c82333;
}

.warning-text {
    color: #dc3545;
    font-weight: bold;
    margin-bottom: 15px;
}

/* Estilos para el modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 10% auto;
    padding: 30px;
    border: 1px solid #888;
    width: 500px;
    max-width: 90%;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.modal-buttons {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
}

.confirm-delete-btn {
    background-color: #dc3545;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.confirm-delete-btn:hover {
    background-color: #c82333;
}

.modal-content ul {
    text-align: left;
    margin: 15px 0;
}


/* Estilos para la distribución de dos columnas */
.two-column-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.column-left, .column-right {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.column-right {
    display: flex;
    flex-direction: column;
}

.delete-account-container {
    margin-top: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-grow: 1;
}

.password-form .form-group {
    margin-bottom: 20px;
}

.password-form .form-button {
    margin-top: 30px;
    display: flex;
    justify-content: center;
}

.password-form button {
    width: 100%;
    max-width: 250px;
}

/* Estilos para el botón de cerrar sesión */
.logout-btn {
    background-color: #6c757d;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
    display: block;
    margin: 30px auto 10px;
    width: 200px;
    text-align: center;
}

.logout-btn:hover {
    background-color: #5a6268;
}

/* Responsive para dispositivos móviles */
@media (max-width: 768px) {
    .two-column-layout {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .column-left, .column-right {
        padding: 15px;
    }
    
    .delete-account-container {
        margin-top: 20px;
    }
    
    .logout-btn {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <header>
        <h1>Click & Go</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="home_admin.php">Home</a></li>
                <li><a href="perfil_admin.php">Perfil</a></li>
            </ul>
        </nav>
    </header>

    <div class="perfil-container">
        <h1>Editar Perfil</h1>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="perfil-grid">
            <div class="perfil-sidebar">
                <div class="avatar-container">
                    <img id="avatarPreview" src="<?php echo mostrarAvatar($usuario['Avatar']); ?>" alt="Avatar">
                    <label for="avatarInput" class="avatar-overlay">
                        <span>+</span>
                    </label>
                </div>
                <div class="perfil-info">
                    <p><strong><?php echo htmlspecialchars($usuario['NombreUsuario']); ?></strong></p>
                    <p>Miembro desde: <?php echo date('d/m/Y', strtotime($usuario['FechaRegistro'] ?? 'now')); ?></p>
                </div>
            </div>
            
            <form class="perfil-form" method="POST" enctype="multipart/form-data">
                <input type="file" id="avatarInput" name="avatar" accept="image/*" onchange="previewAvatar(event)">
                
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['Nombre']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="apellidos">Apellidos:</label>
                    <input type="text" id="apellidos" name="apellidos" value="<?php echo htmlspecialchars($usuario['Apellidos']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="correo">Correo electrónico:</label>
                    <input type="email" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['Correo']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de nacimiento:</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo $usuario['FechaNacimiento']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="genero">Género:</label>
                    <select id="genero" name="genero">
                        <option value="masculino" <?php echo $usuario['Genero'] == 'masculino' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="femenino" <?php echo $usuario['Genero'] == 'femenino' ? 'selected' : ''; ?>>Femenino</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="username">Nombre de usuario:</label>
                    <input type="text" id="username" value="<?php echo htmlspecialchars($usuario['NombreUsuario']); ?>" disabled>
                    <small>El nombre de usuario no se puede cambiar</small>
                </div>
                
                <div class="form-group">
                    <label for="role">Rol:</label>
                    <small>El rol no se puede cambiar</small>
                </div>
                
                <div class="form-group full-width">
                    <div class="buttons">
                        <button type="button" class="cancel-btn" onclick="window.location.href='home.php'">Cancelar</button>
                        <button type="submit" name="guardar_perfil">Guardar cambios</button>
                    </div>
                </div>

                 <!-- Primero las secciones de cambio de contraseña y eliminar cuenta en dos columnas -->
<div class="form-group full-width" style="margin-top: 30px;">
    <div class="two-column-layout">
        <!-- Columna izquierda: Cambiar contraseña -->
        <div class="column-left">
            <h2>Cambiar Contraseña</h2>
            
            <?php if (isset($password_success)): ?>
                <div class="alert alert-success"><?php echo $password_success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($password_error)): ?>
                <div class="alert alert-danger"><?php echo $password_error; ?></div>
            <?php endif; ?>
            
            <form class="password-form" method="POST">
                <div class="form-group">
                    <label for="password_actual">Contraseña actual:</label>
                    <input type="password" id="password_actual" name="password_actual">
                </div>
                
                <div class="form-group">
                    <label for="nueva_password">Nueva contraseña:</label>
                    <input type="password" id="nueva_password" name="nueva_password" minlength="8">
                    <div class="password-strength">
                        <div id="passwordStrengthMeter" class="password-strength-meter"></div>
                    </div>
                    <div class="password-requirements">
                        <small>La contraseña debe cumplir con lo siguiente:</small>
                        <ul>
                            <li id="length">Al menos 8 caracteres</li>
                            <li id="uppercase">Al menos una letra mayúscula</li>
                            <li id="lowercase">Al menos una letra minúscula</li>
                            <li id="number">Al menos un número</li>
                        </ul>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_password">Confirmar nueva contraseña:</label>
                    <input type="password" id="confirmar_password" name="confirmar_password">
                    <div id="passwordMatchMessage" style="margin-top: 5px;"></div>
                </div>
                
                <div class="form-button">
                    <button type="submit" name="cambiar_password" id="btnCambiarPassword">Cambiar contraseña</button>
                </div>
            </form>
        </div>
        
        <!-- Columna derecha: Eliminar cuenta -->
        <div class="column-right">
            <h2>Eliminar cuenta</h2>
            <p class="warning-text">¡Atención! Esta acción eliminará permanentemente tu cuenta y todos tus datos asociados. Esta acción no se puede deshacer.</p>
            <div class="delete-account-container">
                <button type="button" class="delete-account-btn" onclick="mostrarConfirmacionEliminarCuenta()">Eliminar mi cuenta</button>
            </div>
        </div>
    </div>
</div>

<!-- Después, la sección de Mis Listas -->
<div class="form-group full-width listas-section">
    <h2>Mis Listas</h2>
    
    <div class="listas-container" id="listas-container">
        <?php
        // Obtener las listas del usuario
        $sql_listas = "SELECT l.*, 
                      (SELECT COUNT(*) FROM productos_lista WHERE IDlista = l.IDlista) AS cantidad_productos 
                       FROM listas_usuario l 
                       WHERE l.IDusuario = ? 
                       ORDER BY l.fecha_creacion DESC";
        $stmt_listas = $conn->prepare($sql_listas);
        $stmt_listas->bind_param("i", $usuario_id);
        $stmt_listas->execute();
        $result_listas = $stmt_listas->get_result();
        
        if ($result_listas->num_rows > 0) {
            while ($lista = $result_listas->fetch_assoc()) {
                echo '<div class="lista-card" onclick="verDetallesLista(' . $lista['IDlista'] . ')">';
                echo '<h3>' . htmlspecialchars($lista['nombre_lista']) . '</h3>';
                echo '<p class="lista-descripcion">' . htmlspecialchars($lista['descripcion']) . '</p>';
                echo '<div class="lista-meta">';
                echo '<span class="lista-cantidad">' . $lista['cantidad_productos'] . ' productos</span>';
                echo '<span class="lista-privacidad">' . ($lista['privacidad'] == 'publica' ? 'Pública' : 'Privada') . '</span>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p class="sin-listas">No has creado ninguna lista todavía.</p>';
            echo '<div class="create-list-button-container">';
            echo '</div>';
        }
        
        $stmt_listas->close();
        ?>
    </div>
</div>

<!-- Botón para cerrar sesión -->
<form method="POST" class="logout-form">
    <button type="submit" name="cerrar_sesion" class="logout-btn">Cerrar Sesión</button>
</form>

<!-- Modal de confirmación para eliminar cuenta (añadir al final del body) -->
<div id="modal-eliminar-cuenta" class="modal">
    <div class="modal-content">
        <span class="close" onclick="cerrarModal()">&times;</span>
        <h2>¿Estás seguro?</h2>
        <p>Esta acción eliminará permanentemente tu cuenta y todos los datos asociados:</p>
        <ul>
            <li>Tu perfil e información personal</li>
            <li>Todas tus listas de productos</li>
            <li>Tus comentarios y valoraciones</li>
            <li>Productos que hayas publicado</li>
        </ul>
        <p><strong>Esta acción NO puede deshacerse.</strong></p>
        <div class="modal-buttons">
            <button type="button" class="cancel-btn" onclick="cerrarModal()">Cancelar</button>
            <button type="button" class="confirm-delete-btn" onclick="eliminarCuenta()">Sí, eliminar mi cuenta</button>
        </div>
    </div>
</div>


</div>
        </div>
</form>
        </div>
        </div>

    <script>
        function previewAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function cambiarTab(tabId) {
            // Ocultar todas las pestañas y remover clase active
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.perfil-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Activar el botón de la pestaña
            const tabBtns = document.querySelectorAll('.perfil-tab');
            tabBtns.forEach((btn, index) => {
                if (index === 0 && tabId === 'perfil' || index === 1 && tabId === 'seguridad') {
                    btn.classList.add('active');
                }
            });
        }
        
        function checkPasswordStrength() {
            const password = document.getElementById('nueva_password').value;
            const meter = document.getElementById('passwordStrengthMeter');
            
            // Validar requisitos
            const hasLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            
            // Actualizar lista de requisitos
            document.getElementById('length').style.color = hasLength ? 'green' : '';
            document.getElementById('uppercase').style.color = hasUpperCase ? 'green' : '';
            document.getElementById('lowercase').style.color = hasLowerCase ? 'green' : '';
            document.getElementById('number').style.color = hasNumber ? 'green' : '';
            
            // Calcular fortaleza
            let strength = 0;
            if (hasLength) strength++;
            if (hasUpperCase) strength++;
            if (hasLowerCase) strength++;
            if (hasNumber) strength++;
            
            // Actualizar medidor
            meter.className = 'password-strength-meter';
            
            if (password.length === 0) {
                meter.style.width = '0';
            } else if (strength <= 2) {
                meter.classList.add('meter-weak');
            } else if (strength === 3) {
                meter.classList.add('meter-medium');
            } else {
                meter.classList.add('meter-strong');
            }
            
            checkPasswordMatch();
        }
        
        // Asegúrate de reemplazar la función checkPasswordStrength y checkPasswordMatch
function checkPasswordStrength() {
    const password = document.getElementById('nueva_password').value;
    const meter = document.getElementById('passwordStrengthMeter');
    
    // Validar requisitos
    const hasLength = password.length >= 8;
    const hasUpperCase = /[A-Z]/.test(password);
    const hasLowerCase = /[a-z]/.test(password);
    const hasNumber = /[0-9]/.test(password);
    
    // Actualizar lista de requisitos
    document.getElementById('length').style.color = hasLength ? 'green' : '';
    document.getElementById('uppercase').style.color = hasUpperCase ? 'green' : '';
    document.getElementById('lowercase').style.color = hasLowerCase ? 'green' : '';
    document.getElementById('number').style.color = hasNumber ? 'green' : '';
    
    // Calcular fortaleza
    let strength = 0;
    if (hasLength) strength++;
    if (hasUpperCase) strength++;
    if (hasLowerCase) strength++;
    if (hasNumber) strength++;
    
    // Actualizar medidor
    meter.className = 'password-strength-meter';
    
    if (password.length === 0) {
        meter.style.width = '0';
    } else if (strength <= 2) {
        meter.classList.add('meter-weak');
    } else if (strength === 3) {
        meter.classList.add('meter-medium');
    } else {
        meter.classList.add('meter-strong');
    }
    
    // Verificar si coinciden las contraseñas
    checkPasswordMatch();
}

function checkPasswordMatch() {
    const password = document.getElementById('nueva_password').value;
    const confirmPassword = document.getElementById('confirmar_password').value;
    const message = document.getElementById('passwordMatchMessage');
    
    if (confirmPassword.length === 0) {
        message.innerHTML = '';
        return;
    }
    
    if (password === confirmPassword) {
        message.innerHTML = '✓ Las contraseñas coinciden';
        message.style.color = 'green';
    } else {
        message.innerHTML = '✗ Las contraseñas no coinciden';
        message.style.color = 'red';
    }
}

// Agregar estos event listeners una vez que el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Vincular eventos a los campos de contraseña
    document.getElementById('nueva_password').addEventListener('input', checkPasswordStrength);
    document.getElementById('confirmar_password').addEventListener('input', checkPasswordMatch);
    
    // Manejar el envío del formulario de contraseña
    document.querySelector('.password-form').addEventListener('submit', function(event) {
        const password = document.getElementById('nueva_password').value;
        const confirmPassword = document.getElementById('confirmar_password').value;
        
        if (password !== confirmPassword) {
            event.preventDefault();
            alert('Las contraseñas no coinciden. Por favor, verifica.');
            return false;
        }
        
        // Validar fortaleza mínima
        const hasLength = password.length >= 8;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        
        if (!hasLength || !hasUpperCase || !hasLowerCase || !hasNumber) {
            event.preventDefault();
            alert('La contraseña no cumple con los requisitos mínimos de seguridad.');
            return false;
        }
        
        return true;
        
    });
});

// Funciones para el modal de eliminar cuenta
function mostrarConfirmacionEliminarCuenta() {
    document.getElementById('modal-eliminar-cuenta').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('modal-eliminar-cuenta').style.display = 'none';
}

function eliminarCuenta() {
    // Redirigir a la página de eliminación de cuenta
    window.location.href = 'eliminar_cuenta.php';
}

// Cerrar el modal si el usuario hace clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('modal-eliminar-cuenta');
    if (event.target == modal) {
        cerrarModal();
    }
}
// Funciones para el manejo de listas en el perfil
function mostrarFormularioNuevaLista() {
    document.getElementById('form-nueva-lista-perfil').style.display = 'block';
}

function ocultarFormularioNuevaLista() {
    document.getElementById('form-nueva-lista-perfil').style.display = 'none';
}

function verDetallesLista(listaId) {
    window.location.href = 'detalle_lista.php?id=' + listaId;
}
    </script>
</body>
</html>