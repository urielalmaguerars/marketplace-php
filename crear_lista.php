<?php
session_start();
include('conexionCapa.php');
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar si se recibieron los datos necesarios
if (!isset($_POST['nombre']) || empty($_POST['nombre'])) {
    echo json_encode(['success' => false, 'error' => 'El nombre de la lista es obligatorio']);
    exit();
}

$nombre = $conn->real_escape_string($_POST['nombre']);
$descripcion = isset($_POST['descripcion']) ? $conn->real_escape_string($_POST['descripcion']) : '';
$privacidad = isset($_POST['privacidad']) && ($_POST['privacidad'] === 'publica' || $_POST['privacidad'] === 'privada') 
              ? $_POST['privacidad'] : 'publica';
$producto_id = isset($_POST['producto_id']) ? intval($_POST['producto_id']) : 0;

// Crear la nueva lista
$insert_sql = "INSERT INTO listas_usuario (IDusuario, nombre_lista, descripcion, privacidad) 
               VALUES (?, ?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("isss", $usuario_id, $nombre, $descripcion, $privacidad);

if ($insert_stmt->execute()) {
    $lista_id = $insert_stmt->insert_id;
    
    // Si se proporcionó un ID de producto, agregarlo a la lista
    if ($producto_id > 0) {
        $insert_product_sql = "INSERT INTO productos_lista (IDlista, IDproducto) VALUES (?, ?)";
        $insert_product_stmt = $conn->prepare($insert_product_sql);
        $insert_product_stmt->bind_param("ii", $lista_id, $producto_id);
        
        if ($insert_product_stmt->execute()) {
            echo json_encode(['success' => true, 'lista_id' => $lista_id]);
        } else {
            echo json_encode([
                'success' => true, 
                'lista_id' => $lista_id, 
                'warning' => 'La lista se creó pero hubo un error al agregar el producto: ' . $conn->error
            ]);
        }
        
        $insert_product_stmt->close();
    } else {
        echo json_encode(['success' => true, 'lista_id' => $lista_id]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error al crear la lista: ' . $conn->error]);
}

$insert_stmt->close();
$conn->close();
?>