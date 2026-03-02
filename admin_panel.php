<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION["Rol"]) || $_SESSION["Rol"] !== "admin") {
    echo "Acceso denegado.";
    exit();
}

echo "<h1>Bienvenido al Panel Admin, " . htmlspecialchars($_SESSION["NombreUsuario"]) . "</h1>";

// Aprobar o rechazar producto
if (isset($_GET['accion']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $accion = $_GET['accion'];

    if (in_array($accion, ['aprobar', 'rechazar'])) {
        $estado = $accion == 'aprobar' ? 'aprobado' : 'rechazado';
        $stmt = $conn->prepare("UPDATE productos SET estado_aprobacion = ? WHERE IDproducto = ?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
    }
}

// Consulta que une productos con sus imágenes
$sql = "
    SELECT p.IDproducto, p.Nombre, p.Descripcion, p.Stock, p.Precio, p.estado_aprobacion, m.URL
    FROM productos p
    LEFT JOIN productomultimedia pm ON p.IDproducto = pm.IDproducto
    LEFT JOIN multimedia m ON pm.IDmultimedia = m.IDmultimedia
    WHERE p.estado_aprobacion = 'pendiente'
    ORDER BY p.IDproducto DESC
";

$result = $conn->query($sql);

// Agrupar resultados
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

// Mostrar productos
foreach ($productos as $id => $producto) {
    echo "<div>";
    echo "<h2>" . htmlspecialchars($producto['Nombre']) . "</h2>";
    
    foreach ($producto['Imagenes'] as $url) {
        echo "<img src='" . htmlspecialchars($url) . "' width='200' style='margin: 5px;'>";
    }

    echo "<p>" . htmlspecialchars($producto['Descripcion']) . "</p>";
    echo "<p>Stock: " . htmlspecialchars($producto['Stock']) . " | Precio: $" . htmlspecialchars($producto['Precio']) . "</p>";
    echo "<a href='admin_panel.php?accion=aprobar&id={$id}'>✅ Aprobar</a> | ";
    echo "<a href='admin_panel.php?accion=rechazar&id={$id}'>❌ Rechazar</a>";
    echo "</div><hr>";
}
echo '<p><a href="home_admin.php" style="display:inline-block; padding:10px 20px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;">Volver a Home</a></p>';

?>
