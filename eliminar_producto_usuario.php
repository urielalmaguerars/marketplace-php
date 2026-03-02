<?php
// Desactivar la visualización de errores (enviarlos a logs)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Forzar encabezado JSON
header('Content-Type: application/json');

try {
    // Incluir conexión
    include 'conexionCapa.php';
    
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    session_start();
    
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("No hay sesión activa");
    }
    
    $userId = $_SESSION['usuario_id'];
    
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido. Esta acción requiere una solicitud POST.");
    }
    
    // Obtener ID del producto a eliminar
    $producto_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($producto_id <= 0) {
        throw new Exception("ID de producto inválido");
    }
    
    // Verificar que el producto pertenece al usuario
    $stmt = $conn->prepare("SELECT IDusuario FROM productos WHERE IDproducto = ?");
    if (!$stmt) {
        throw new Exception("Error al preparar la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Producto no encontrado");
    }
    
    $producto = $result->fetch_assoc();
    
    // Verificar propietario
    if ($producto['IDusuario'] != $userId) {
        throw new Exception("No tienes permiso para eliminar este producto");
    }
    
    $stmt->close();
    
    // Iniciar transacción para garantizar que todas las operaciones se completen o ninguna
    $conn->begin_transaction();
    
    try {
        // 1. Eliminar relaciones en ProductoMultimedia
        $stmt = $conn->prepare("DELETE FROM ProductoMultimedia WHERE IDproducto = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar eliminación de ProductoMultimedia: " . $conn->error);
        }
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $stmt->close();
        
        // 2. Eliminar el producto
        $stmt = $conn->prepare("DELETE FROM productos WHERE IDproducto = ? AND IDusuario = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar eliminación de producto: " . $conn->error);
        }
        $stmt->bind_param("ii", $producto_id, $userId);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("Error al eliminar el producto: " . $stmt->error);
        }
        
        $rowsAffected = $stmt->affected_rows;
        $stmt->close();
        
        if ($rowsAffected <= 0) {
            throw new Exception("No se pudo eliminar el producto");
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Enviar respuesta de éxito
        echo json_encode([
            'success' => true,
            'message' => 'Producto eliminado con éxito'
        ]);
        
    } catch (Exception $e) {
        // Revertir la transacción si algo falla
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    // Devolver error como JSON
    echo json_encode([
        'success' => false,
        'error' => true,
        'message' => $e->getMessage()
    ]);
}

// Cerrar la conexión
if (isset($conn)) {
    $conn->close();
}
?>