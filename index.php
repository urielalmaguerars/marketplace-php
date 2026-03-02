<?php 
ini_set('display_errors', 1); 
error_reporting(E_ALL);

// Iniciar sesión 
session_start();

// Conexión a la base de datos 
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db = "capa_inter"; 
$port = "3307"; 
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error); 
}

// Función para mostrar el avatar 
function mostrarAvatar($avatar_blob) {
    if (!empty($avatar_blob)) {
        return 'data:image/jpeg;base64,' . base64_encode($avatar_blob);
    } else {
        return 'uploads/avatars/default.png';
    } 
} 
?>

<!DOCTYPE html> 
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <title>Inicio de Sesión</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
            background-image: url(Fondo_2.jpg);
            background-position: center center;
            background-repeat: no-repeat;
            background-size: cover;
            margin: 0;
        }
        
        .container {
            background: #E0E0E0;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .avatar {
            display: block;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin: 10px auto;
        }
        
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        label {
            align-self: flex-start;
            margin-left: 15px;
            margin-top: 10px;
            font-weight: bold;
            color: #555;
        }
        
        input, select {
            width: 90%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 25px;
        }
        
        button {
            width: 80%;
            padding: 8px;
            margin: 15px 0;
            border: 1px solid;
            background-color: #97dfea;
            border-radius: 25px;
            color: #0077B6;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background-color: #7fd1e0;
        }
        
        p {
            margin-top: 15px;
            color: #555;
        }
        
        a {
            color: #0077B6;
            text-decoration: none;
        }
        
        a:hover {
            text-decoration: underline;
        }
    </style>
</head> 
<body>
    <div class="container">
        <h2>LOGIN</h2>
        <form action="" method="POST">
            <label for="username">Nombre de Usuario:</label>
            <input type="text" name="username" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" name="password" required>
            
            <button type="submit" name="submit">Iniciar Sesión</button>
        </form>
        <p>¿No tienes una cuenta? <a href="registro.php">Regístrate aquí</a></p>
    </div>
    
    <script>
        // Para mostrar un avatar predeterminado
        document.addEventListener("DOMContentLoaded", function() {
            const avatarImg = document.getElementById("avatarPreview");
            avatarImg.src = 'uploads/avatars/default.png';
        });
    </script>
</body> 
</html>

<?php
if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT IDusuario, NombreUsuario, Contraseña, Avatar, TipoPrivacidad, Rol FROM usuario WHERE NombreUsuario = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $hashedPassword = $usuario['Contraseña'];
        
        if (password_verify($password, $hashedPassword)) {
            $rol = $usuario['Rol'];
            $tipo = $usuario['TipoPrivacidad'];

            $_SESSION['usuario_id'] = $usuario['IDusuario'];
            $_SESSION['nombre_usuario'] = $usuario['NombreUsuario'];
            $_SESSION['tipo_privacidad'] = $tipo;
            $_SESSION['rol'] = $rol;

            if ($rol === 'super') {
        echo "<script>alert('Bienvenido superadministrador'); window.location.href = 'asignar_admin.php';</script>";
    } elseif ($rol === 'admin') {
        echo "<script>alert('Bienvenido administrador'); window.location.href = 'login.php';</script>";
    } elseif ($tipo === 'vendedor') {
        echo "<script>alert('Bienvenido vendedor'); window.location.href = 'home_vendedor.php';</script>";
    } elseif ($tipo === 'comprador') {
        echo "<script>alert('Bienvenido comprador'); window.location.href = 'home_comprador.php';</script>";
    } elseif ($tipo === 'ambos') {
        echo "<script>alert('Bienvenido'); window.location.href = 'home.php';</script>";
    } else {
        echo "<script>alert('Tipo de usuario no válido.');</script>";
    }
}
    }

    
    $stmt->close();
}

?>