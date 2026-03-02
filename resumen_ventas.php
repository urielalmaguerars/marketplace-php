<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = $_SESSION['usuario_id'];

// Obtener nombre del usuario
$stmtUser = $conn->prepare("SELECT NombreUsuario FROM usuario WHERE IDusuario = ?");
$stmtUser->bind_param("i", $idUsuario);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$usuario = $resultUser->fetch_assoc();
$nombreUsuario = $usuario['NombreUsuario'];
$stmtUser->close();

// Ejecutar procedimiento almacenado
$stmt = $conn->prepare("CALL VerVentasYProductos(?)");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();

// Primer conjunto: productos vendidos
$productosVendidos = $stmt->get_result();

// Mover al segundo conjunto de resultados
$stmt->next_result();
$productosRealizados = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen de Ventas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
        }

        .usuario-info {
            text-align: right;
            font-weight: bold;
            margin-bottom: 20px;
        }
        h2 {
            color: #333;
        }
        table {
            width: 100%;
            margin-bottom: 40px;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 0 5px #ccc;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            background-color: #e6e6e6;
        }

        .back-home {
            display: block;
            margin: 20px auto;
            width: 200px;
            text-align: center;
            background: white;
            color: #0077b6;
            padding: 10px 15px;
            border-radius: 10px;
            font-weight: bold;
            text-decoration: none;
        }
    </style>
</head>
<body>

<a href="home_vendedor.php" class="back-home">← Regresar a Home</a>

<div class="usuario-info">👤 Bienvenido: <?php echo htmlspecialchars($nombreUsuario); ?></div>

<h2>📦 Productos Vendidos</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Precio Original</th>
        <th>Precio de Venta</th>
        <th>Fecha de Venta</th>
        <th>Comprador</th>
    </tr>
    <?php if ($productosVendidos && $productosVendidos->num_rows > 0): ?>
        <?php while ($row = $productosVendidos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['IDproducto']; ?></td>
                <td><?php echo htmlspecialchars($row['NombreProducto']); ?></td>
                <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                <td>$<?php echo number_format($row['PrecioVenta'], 2); ?></td>
                <td><?php echo $row['FechaVenta']; ?></td>
                <td><?php echo htmlspecialchars($row['Comprador']); ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="6">No hay productos vendidos aún.</td></tr>
    <?php endif; ?>
</table>

<h2>📝 Productos Publicados (No Vendidos)</h2>
<table>
    <tr>
        <th>ID</th>
        <th>Nombre</th>
        <th>Precio</th>
        <th>Fecha de Subida</th>
    </tr>
    <?php if ($productosRealizados && $productosRealizados->num_rows > 0): ?>
        <?php while ($row = $productosRealizados->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['IDproducto']; ?></td>
                <td><?php echo htmlspecialchars($row['NombreProducto']); ?></td>
                <td>$<?php echo number_format($row['Precio'], 2); ?></td>
                <td><?php echo $row['fecha_creacion']; ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">No hay productos realizados sin venta.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>
