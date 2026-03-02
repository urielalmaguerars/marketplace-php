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
if (!isset($_POST['producto_id']) || !isset($_POST['lista_id'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit();
}

$producto_id = intval($_POST['producto_id']);
$lista_id = intval($_POST['lista_id']);

// Verificar que la lista pertenezca al usuario
$check_sql = "SELECT IDusuario FROM listas_usuario WHERE IDlista = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $lista_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Lista no encontrada']);
    $check_stmt->close();
    $conn->close();
    exit();
}

$lista_info = $check_result->fetch_assoc();
if ($lista_info['IDusuario'] != $usuario_id) {
    echo json_encode(['success' => false, 'error' => 'No tienes permiso para modificar esta lista']);
    $check_stmt->close();
    $conn->close();
    exit();
}

// Verificar si el producto ya está en la lista
$check_product_sql = "SELECT IDproducto_lista FROM productos_lista WHERE IDlista = ? AND IDproducto = ?";
$check_product_stmt = $conn->prepare($check_product_sql);
$check_product_stmt->bind_param("ii", $lista_id, $producto_id);
$check_product_stmt->execute();
$check_product_result = $check_product_stmt->get_result();

if ($check_product_result->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'El producto ya está en esta lista']);
    $check_product_stmt->close();
    $check_stmt->close();
    $conn->close();
    exit();
}

// Agregar el producto a la lista
$insert_sql = "INSERT INTO productos_lista (IDlista, IDproducto) VALUES (?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("ii", $lista_id, $producto_id);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al agregar el producto a la lista: ' . $conn->error]);
}

$insert_stmt->close();
$check_product_stmt->close();
$check_stmt->close();
$conn->close();
?>