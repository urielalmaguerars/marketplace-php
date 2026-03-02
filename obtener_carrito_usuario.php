<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([]);
    exit();
}

$idUsuario = $_SESSION['usuario_id'];

$sql = "SELECT c.IDproducto, p.Nombre, c.PrecioCotizado
        FROM carrito c
        JOIN productos p ON c.IDproducto = p.IDproducto
        WHERE c.IDusuario = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();

$result = $stmt->get_result();
$items = [];

while ($row = $result->fetch_assoc()) {
    $items[] = [
        "id" => $row["IDproducto"],
        "name" => $row["Nombre"],
        "price" => floatval($row["PrecioCotizado"]),
        "quantity" => 1
    ];
}

echo json_encode($items);
