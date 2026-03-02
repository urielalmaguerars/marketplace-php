<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$chat_id = intval($_POST['chat_id']);
$mensaje = trim($_POST['mensaje']);
$id_usuario = $_SESSION['usuario_id'];

if (!empty($mensaje)) {
    $stmt = $conn->prepare("INSERT INTO msgs (IDusuario, IDchat, Mensaje) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $id_usuario, $chat_id, $mensaje);
    $stmt->execute();

    $conn->query("UPDATE chat SET FechaHoraUltimoMensaje = CURRENT_TIMESTAMP WHERE IDchat = $chat_id");
}

header("Location: chats.php?chat=$chat_id");
