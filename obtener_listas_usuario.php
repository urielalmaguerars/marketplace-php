<?php
session_start();
include('conexionCapa.php');
header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener las listas del usuario
$sql = "SELECT IDlista, nombre_lista, descripcion, privacidad FROM listas_usuario WHERE IDusuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$listas = [];
while ($row = $result->fetch_assoc()) {
    $listas[] = $row;
}

echo json_encode($listas);
$stmt->close();
$conn->close();
?>