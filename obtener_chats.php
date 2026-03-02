<?php
include('conexionCapa.php');
session_start();

if (!isset($_SESSION['IDusuario'])) {
    echo json_encode([]);
    exit;
}

$idUsuario = $_SESSION['IDusuario'];

$stmt = $conn->prepare("CALL SP_ObtenerChatsUsuario(?)");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = [
        'id' => $row['IDchat'],
        'nombre_otro_usuario' => $row['nombre_otro_usuario']
    ];
}

echo json_encode($chats);