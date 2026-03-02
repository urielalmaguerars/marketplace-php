<?php
include 'conexionCapa.php'; // tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['IDproducto'];
    $accion = $_POST['accion'];

    if ($accion == 'aprobar') {
        $estado = 'aprobado';
    } elseif ($accion == 'rechazar') {
        $estado = 'rechazado';
    } else {
        exit("Acción no válida");
    }

    $stmt = $conn->prepare("UPDATE productos SET estado_aprobacion = ? WHERE IDproducto = ?");
    $stmt->bind_param("si", $estado, $id);
    $stmt->execute();

    header("Location: panel_admin.php");
    exit();
}
?>
