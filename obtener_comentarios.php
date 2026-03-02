<?php
header('Content-Type: application/json');
include 'conexionCapa.php';

if (isset($_GET['producto_id']) && is_numeric($_GET['producto_id'])) {
    $productoId = intval($_GET['producto_id']);

    $sql = "SELECT c.Texto, c.FechaHora, u.NombreUsuario 
            FROM comentarios c
            JOIN usuario u ON c.IDusuario = u.IDusuario
            WHERE c.IDproducto = ?
            ORDER BY c.FechaHora DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productoId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comentarios = [];

    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }

    echo json_encode($comentarios);
} else {
    echo json_encode(["error" => "ID de producto inválido"]);
}
?>
