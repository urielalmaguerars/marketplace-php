<?php
include 'conexionCapa.php';
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['IDusuario'])) {
    // Intentar recuperar desde la cookie
    if (isset($_COOKIE['usuario_actual_id'])) {
        $_SESSION['IDusuario'] = (int)$_COOKIE['usuario_actual_id'];
        $_SESSION['NombreUsuario'] = $_COOKIE['usuario_actual_nombre'] ?? 'Usuario';
    } else {
        echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
        exit;
    }
}

if (!isset($_GET['chat_id']) || !is_numeric($_GET['chat_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de chat no válido']);
    exit;
}

$usuario_id = $_SESSION['IDusuario'];
$chat_id = intval($_GET['chat_id']);

try {
    // Obtener información del usuario actual (para confirmar)
    $stmt = $conn->prepare("SELECT Nombre FROM usuario WHERE IDusuario = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_actual = $result->fetch_assoc();
    
    if (!$usuario_actual) {
        throw new Exception("Error: No se encontró información del usuario actual");
    }
    
    // Actualizar la sesión con el nombre correcto
    $_SESSION['NombreUsuario'] = $usuario_actual['Nombre'];
    setcookie('usuario_actual_nombre', $usuario_actual['Nombre'], time() + 86400, '/');
    
    // Obtener mensajes
    $stmt = $conn->prepare("
        SELECT 
            m.IDmsgs, 
            m.IDusuario, 
            m.Mensaje, 
            m.FechaHora, 
            u.Nombre,
            (m.IDusuario = ?) AS es_propio
        FROM msgs m
        JOIN usuario u ON m.IDusuario = u.IDusuario
        WHERE m.IDchat = ?
        ORDER BY m.FechaHora ASC
    ");
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $usuario_id, $chat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Formatear los mensajes para la respuesta
    $mensajes_formateados = [];
    while ($mensaje = $result->fetch_assoc()) {
        $mensajes_formateados[] = [
            'id' => $mensaje['IDmsgs'],
            'usuario_id' => $mensaje['IDusuario'],
            'mensaje' => $mensaje['Mensaje'],
            'fecha_hora' => date('d/m/Y H:i', strtotime($mensaje['FechaHora'])),
            'nombre_usuario' => $mensaje['Nombre'],
            'es_propio' => (bool)$mensaje['es_propio']
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'mensajes' => $mensajes_formateados,
        'usuario_actual' => [
            'id' => $usuario_id,
            'nombre' => $usuario_actual['Nombre']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error en obtener_mensajes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>