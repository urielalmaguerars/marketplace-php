<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$IDcotizacion = intval($_POST['IDcotizacion']);
$respuesta = $_POST['respuesta'];
$chat_id = intval($_POST['chat']);

if (!in_array($respuesta, ['aceptado', 'rechazado'])) {
    die("Respuesta inválida.");
}

$stmt = $conn->prepare("UPDATE cotizaciones SET Estado = ? WHERE IDcotizacion = ?");
$stmt->bind_param("si", $respuesta, $IDcotizacion);
$stmt->execute();

if ($respuesta === 'aceptado') {
    $cotData = $conn->query("SELECT IDproducto FROM cotizaciones WHERE IDcotizacion = $IDcotizacion")->fetch_assoc();
    $idUsuario = $_SESSION['usuario_id'];
    $idProducto = $cotData['IDproducto'];
    $conn->query("INSERT INTO carrito (IDusuario, IDproducto) VALUES ($idUsuario, $idProducto)");
}

header("Location: chats.php?chat=$chat_id");
?>
