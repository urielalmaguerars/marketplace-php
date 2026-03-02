<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$comprador_id = $_SESSION['usuario_id'];
$id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : 0;
$nombre_producto = isset($_GET['nombre_producto']) ? urldecode($_GET['nombre_producto']) : '';

if ($id_producto <= 0) {
    die("Producto inválido");
}

// 1. Obtener el ID del vendedor
$stmt = $conn->prepare("SELECT IDusuario FROM Productos WHERE IDproducto = ?");
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$stmt->bind_result($vendedor_id);
$stmt->fetch();
$stmt->close();

if (!$vendedor_id || $vendedor_id == $comprador_id) {
    die("No puedes chatear contigo mismo o el producto no existe.");
}

// 2. Buscar si ya hay un chat entre comprador y vendedor
$stmt = $conn->prepare("
    SELECT c.IDchat 
    FROM chat c 
    JOIN msgs m1 ON c.IDchat = m1.IDchat 
    JOIN msgs m2 ON c.IDchat = m2.IDchat 
    WHERE m1.IDusuario = ? AND m2.IDusuario = ?
    GROUP BY c.IDchat
");
$stmt->bind_param("ii", $comprador_id, $vendedor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $chat_id = $row['IDchat'];
} else {
    // Crear chat y mensajes iniciales
    $conn->query("INSERT INTO chat () VALUES ()");
    $chat_id = $conn->insert_id;

    $mensaje_inicial = "Hola, estoy interesado en tu producto \"$nombre_producto\".";
    $stmt = $conn->prepare("INSERT INTO msgs (IDusuario, IDchat, Mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $comprador_id, $chat_id, $mensaje_inicial);
    $stmt->execute();

    // Añadir un mensaje vacío del vendedor para establecer relación
    $msg_vacio = "[conversación iniciada]";
    $stmt2 = $conn->prepare("INSERT INTO msgs (IDusuario, IDchat, Mensaje) VALUES (?, ?, ?)");
    $stmt2->bind_param("iis", $vendedor_id, $chat_id, $msg_vacio);
    $stmt2->execute();
}

header("Location: chats.php?chat=$chat_id&producto=" . urlencode($nombre_producto));
exit();
