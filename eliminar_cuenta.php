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

// Obtener ID del usuario
$usuario_id = $_SESSION['usuario_id'];

// Comenzar una transacción
$conn->begin_transaction();

try {
    // Aquí deberías eliminar todos los registros relacionados con el usuario en otras tablas
    // Por ejemplo: listas_usuario, productos_lista, comentarios, etc.
    
    // 1. Eliminar registros de las listas del usuario y productos asociados
    $stmt_productos_listas = $conn->prepare("DELETE pl FROM productos_lista pl 
                                            INNER JOIN listas_usuario lu ON pl.IDlista = lu.IDlista 
                                            WHERE lu.IDusuario = ?");
    $stmt_productos_listas->bind_param("i", $usuario_id);
    $stmt_productos_listas->execute();
    $stmt_productos_listas->close();
    
    // 2. Eliminar las listas del usuario
    $stmt_listas = $conn->prepare("DELETE FROM listas_usuario WHERE IDusuario = ?");
    $stmt_listas->bind_param("i", $usuario_id);
    $stmt_listas->execute();
    $stmt_listas->close();
    
    // 3. Si existen otras tablas relacionadas como comentarios, valoraciones, etc., eliminarlas aquí
     $stmt_comentarios = $conn->prepare("DELETE FROM comentarios WHERE IDusuario = ?");
     $stmt_comentarios->bind_param("i", $usuario_id);
     $stmt_comentarios->execute();
     $stmt_comentarios->close();


    // 4. Eliminar las listas de productos
    $stmt_listas = $conn->prepare("DELETE FROM productos WHERE IDusuario = ?");
    $stmt_listas->bind_param("i", $usuario_id);
    $stmt_listas->execute();
    $stmt_listas->close();


    // 5. Eliminar las lista de calificaciones 
    $stmt_listas = $conn->prepare("DELETE FROM calificaciones WHERE IDusuario = ?");
    $stmt_listas->bind_param("i", $usuario_id);
    $stmt_listas->execute();
    $stmt_listas->close();

    
    // 6. Finalmente, eliminar el usuario
    $stmt_usuario = $conn->prepare("DELETE FROM usuario WHERE IDusuario = ?");
    $stmt_usuario->bind_param("i", $usuario_id);
    $stmt_usuario->execute();
    $stmt_usuario->close();
    
    // Si todo sale bien, confirmar la transacción
    $conn->commit();
    
    // Cerrar la sesión
    session_destroy();
    
    // Redirigir a una página de confirmación
    header("Location: cuenta_eliminada.php");
    exit();
    
} catch (Exception $e) {
    // Si hay algún error, revertir la transacción
    $conn->rollback();
    
    // Mostrar mensaje de error
    echo "<script>
        alert('Error al eliminar la cuenta: " . $e->getMessage() . "');
        window.location.href = 'perfil.php';
    </script>";
    exit();
}
?>