<?php
header('Content-Type: application/json');
session_start();
require_once("conexionCapa.php");

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuario no autenticado']);
    exit();
}

$IDusuario = $_SESSION['usuario_id'];
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

if ($nombre === '') {
    echo json_encode(['success' => false, 'error' => 'Nombre de categoría vacío']);
    exit();
}

$sql = "INSERT INTO categoria (IDusuario, nombre_categoria, descripcion_categoria, estado) VALUES (?, ?, ?, 'activo')";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en prepare(): ' . $conn->error
    ]);
    exit();
}

$stmt->bind_param("iss", $IDusuario, $nombre, $descripcion);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'IDcategoria' => $stmt->insert_id]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
