<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'error' => 'No has iniciado sesión.']);
    exit;
}

$IDusuario = $_SESSION['usuario_id'];
$IDproducto = intval($_POST['IDproducto'] ?? 0);
$comentario = trim($_POST['comentario'] ?? '');

if ($comentario === '') {
    echo json_encode(['success' => false, 'error' => 'Comentario vacío']);
    exit;
}

$sql = "INSERT INTO comentarios (IDusuario, IDproducto, Texto) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iis", $IDusuario, $IDproducto, $comentario);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar el comentario']);
}
?>
