<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];
$destino_id = intval($_POST['usuario_destino']);

// 1. Ver si ya existe chat entre ambos (busca mensajes donde ambos estén presentes)
$stmt = $conn->prepare("
    SELECT c.IDchat 
    FROM chat c 
    JOIN msgs m1 ON c.IDchat = m1.IDchat 
    JOIN msgs m2 ON c.IDchat = m2.IDchat 
    WHERE m1.IDusuario = ? AND m2.IDusuario = ?
    GROUP BY c.IDchat
");
$stmt->bind_param("ii", $mi_id, $destino_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Ya existe chat
    $chat_id = $row['IDchat'];
} else {
    // No existe, crear chat y mensaje de inicio
    $conn->query("INSERT INTO chat () VALUES ()");
    $chat_id = $conn->insert_id;

    $stmt = $conn->prepare("INSERT INTO msgs (IDusuario, IDchat, Mensaje) VALUES (?, ?, ?)");
    $mensaje_inicial = "¡Hola!";
    $stmt->bind_param("iis", $mi_id, $chat_id, $mensaje_inicial);
    $stmt->execute();

    // También insertar un mensaje vacío del otro usuario para asociarlo
    $mensaje_vacio = "[conversación iniciada]";
    $stmt2 = $conn->prepare("INSERT INTO msgs (IDusuario, IDchat, Mensaje) VALUES (?, ?, ?)");
    $stmt2->bind_param("iis", $destino_id, $chat_id, $mensaje_vacio);
    $stmt2->execute();
}

header("Location: chats.php?chat=$chat_id");
