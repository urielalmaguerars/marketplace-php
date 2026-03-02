<?php
session_start();
include 'conexionCapa.php';

echo "<h1>Información de la sesión actual</h1>";

if(isset($_SESSION['IDusuario'])) {
    $usuario_id = $_SESSION['IDusuario'];
    echo "<p>Usuario logueado: ID = $usuario_id</p>";
    
    // Obtener información del usuario
    $stmt = $conn->prepare("SELECT * FROM usuario WHERE IDusuario = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        echo "<pre>";
        print_r($usuario);
        echo "</pre>";
    } else {
        echo "<p>Error: No se encontró información para este usuario en la base de datos.</p>";
    }
} else {
    echo "<p>No hay sesión activa. Necesitas iniciar sesión.</p>";
}

echo "<h2>Datos completos de la sesión:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Todas las cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<p><a href='chats.php'>Volver a Chats</a></p>";
?>