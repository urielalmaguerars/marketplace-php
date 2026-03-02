<?php
session_start();
header('Content-Type: application/json');

// Verificar si el usuario está logueado
$response = ['logueado' => false];

if (isset($_SESSION['usuario_id']) || isset($_SESSION['IDusuario'])) {
    // Compatibilidad con ambas variantes
    $response['logueado'] = true;
    $response['usuario_id'] = $_SESSION['usuario_id'] ?? $_SESSION['IDusuario'];
}

echo json_encode($response);
?>
