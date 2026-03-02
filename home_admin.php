<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION["Rol"]) || $_SESSION["Rol"] !== "admin") {
    echo "Acceso denegado.";
    exit();
}

echo "<h1>Bienvenido Admin, " . htmlspecialchars($_SESSION["nombre_usuario"]) . "</h1>";

// Consulta de productos aprobados
$sql = "
    SELECT p.IDproducto, p.Nombre, p.Descripcion, p.Stock, p.Precio, p.estado_aprobacion, m.URL
    FROM productos p
    LEFT JOIN productomultimedia pm ON p.IDproducto = pm.IDproducto
    LEFT JOIN multimedia m ON pm.IDmultimedia = m.IDmultimedia
    WHERE p.estado_aprobacion = 'aprobado'
    ORDER BY p.IDproducto DESC
";

$result = $conn->query($sql);

// Agrupar resultados por producto
$productos = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['IDproducto'];
    
    if (!isset($productos[$id])) {
        $productos[$id] = [
            'Nombre' => $row['Nombre'],
            'Descripcion' => $row['Descripcion'],
            'Stock' => $row['Stock'],
            'Precio' => $row['Precio'],
            'Imagenes' => []
        ];
    }

    if (!empty($row['URL'])) {
        $productos[$id]['Imagenes'][] = $row['URL'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos Aprobados - Panel Admin</title>
    <link rel="stylesheet" href="styles-mejorado.css">
</head>
<body class="home-page">
    <header>
        <h1>Click & Go</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="perfil_admin.php">Perfil</a></li>
                <li><a href="admin_panel.php">Volver al Panel</a></li>
            </ul>
        </nav>
    </header>

    <div class="productos-aprobados" style="padding: 20px;">
        <h2>Productos Aprobados</h2>
        <?php
        if (empty($productos)) {
            echo "<p>No hay productos aprobados aún.</p>";
        } else {
            foreach ($productos as $id => $producto) {
                echo "<div style='border: 1px solid #ccc; border-radius: 10px; padding: 10px; margin-bottom: 20px; background: #f9f9f9;'>";
                echo "<h3>" . htmlspecialchars($producto['Nombre']) . "</h3>";
                
                foreach ($producto['Imagenes'] as $url) {
                    echo "<img src='" . htmlspecialchars($url) . "' width='200' style='margin: 5px; border-radius: 10px;'>";
                }

                echo "<p>" . htmlspecialchars($producto['Descripcion']) . "</p>";
                echo "<p><strong>Stock:</strong> " . htmlspecialchars($producto['Stock']) . "</p>";
                echo "<p><strong>Precio:</strong> $" . htmlspecialchars($producto['Precio']) . "</p>";
                echo "</div>";
            }
        }
        ?>
    </div>
</body>
</html>
