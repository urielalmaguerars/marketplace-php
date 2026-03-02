<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$IDchat = intval($_POST['IDchat']);
$IDproducto = intval($_POST['IDproducto']);
$precio = floatval($_POST['PrecioCotizado']);

// Opcional: validar si el usuario es el vendedor
$stmt = $conn->prepare("SELECT IDusuario FROM productos WHERE IDproducto = ?");
$stmt->bind_param("i", $IDproducto);
$stmt->execute();
$vendedor = $stmt->get_result()->fetch_assoc();

if ($vendedor['IDusuario'] != $_SESSION['usuario_id']) {
    die("No autorizado para cotizar este producto.");
}

// Insertar cotización
$stmt = $conn->prepare("INSERT INTO cotizaciones (IDchat, IDproducto, PrecioCotizado) VALUES (?, ?, ?)");
$stmt->bind_param("iid", $IDchat, $IDproducto, $precio);
$stmt->execute();

header("Location: chats.php?chat=$IDchat");
?>
