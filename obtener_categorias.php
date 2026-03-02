<?php
header('Content-Type: application/json');
require_once("conexionCapa.php");

$sql = "SELECT IDcategoria, nombre_categoria FROM categoria WHERE estado = 'activo' ORDER BY nombre_categoria ASC";
$result = $conn->query($sql);

$categorias = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}

echo json_encode($categorias);
$conn->close();
?>
