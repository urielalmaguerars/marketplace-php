<?php
// Activar mostrado de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once("conexionCapa.php");

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['id']) || !isset($_POST['calificacion'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

// Obtener y validar los datos
$productoId = intval($_POST['id']);
$calificacion = intval($_POST['calificacion']);

// Como no tienes un sistema de usuarios implementado para calificaciones,
// usaremos un ID de usuario fijo (1) como valor predeterminado
// Esto se puede cambiar cuando implementes un sistema de usuarios
$usuarioId = 1; // ID de usuario predeterminado

// Validar los datos
if ($productoId <= 0 || $calificacion < 1 || $calificacion > 5) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    // Verificar si el producto existe
    $checkProduct = "SELECT IDproducto FROM Productos WHERE IDproducto = ?";
    $stmtProduct = $conn->prepare($checkProduct);
    
    if (!$stmtProduct) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    
    $stmtProduct->bind_param("i", $productoId);
    $stmtProduct->execute();
    $resultProduct = $stmtProduct->get_result();
    
    if ($resultProduct->num_rows == 0) {
        throw new Exception("El producto especificado no existe");
    }
    
    $stmtProduct->close();
    
    // Verificar si el usuario existe
    $checkUser = "SELECT IDusuario FROM Usuario WHERE IDusuario = ?";
    $stmtUser = $conn->prepare($checkUser);
    
    if (!$stmtUser) {
        throw new Exception("Error en la preparación de la consulta de usuario: " . $conn->error);
    }
    
    $stmtUser->bind_param("i", $usuarioId);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    
    if ($resultUser->num_rows == 0) {
        throw new Exception("El usuario especificado no existe");
    }
    
    $stmtUser->close();
    
    // Verificar si ya existe una calificación para este producto y usuario
    $checkCalif = "SELECT IDcalif FROM Calificaciones WHERE IDproducto = ? AND IDusuario = ?";
    $stmt = $conn->prepare($checkCalif);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta de calificación: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $productoId, $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Actualizar calificación existente
        $row = $result->fetch_assoc();
        $califId = $row['IDcalif'];
        
        $updateSql = "UPDATE Calificaciones SET Valor = ? WHERE IDcalif = ?";
        $updateStmt = $conn->prepare($updateSql);
        
        if (!$updateStmt) {
            throw new Exception("Error en la preparación de la actualización: " . $conn->error);
        }
        
        $updateStmt->bind_param("ii", $calificacion, $califId);
        $success = $updateStmt->execute();
        $updateStmt->close();
        
        error_log("Calificación actualizada, ID: $califId");
    } else {
        // Insertar nueva calificación
        $insertSql = "INSERT INTO Calificaciones (IDusuario, IDproducto, Valor) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        
        if (!$insertStmt) {
            throw new Exception("Error en la preparación de la inserción: " . $conn->error);
        }
        
        $insertStmt->bind_param("iii", $usuarioId, $productoId, $calificacion);
        $success = $insertStmt->execute();
        $insertId = $conn->insert_id;
        $insertStmt->close();
        
        error_log("Nueva calificación insertada, ID: $insertId");
    }
    
    $stmt->close();
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Error en la operación de la base de datos");
    }
    
} catch (Exception $e) {
    error_log("Error en el proceso de calificación: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>