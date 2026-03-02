<?php
header('Content-Type: application/json');
require_once("conexionCapa.php");

// Obtener el parámetro de ordenamiento
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'predeterminado';

// Obtener el parámetro de categoría
$categoria = isset($_GET['categoria']) ? intval($_GET['categoria']) : 0; // 0 significa todas las categorías

// Construir la base de la consulta SQL
$sql = "SELECT p.IDproducto, p.Nombre, p.Descripcion, p.Precio, p.Estado, p.Stock, p.IDcategoria, 
        c.nombre_categoria,
        GROUP_CONCAT(m.URL) AS imagenes_urls
        FROM Productos p
        LEFT JOIN ProductoMultimedia pm ON p.IDproducto = pm.IDproducto
        LEFT JOIN Multimedia m ON pm.IDmultimedia = m.IDmultimedia
        LEFT JOIN categoria c ON p.IDcategoria = c.IDcategoria
        WHERE p.estado_aprobacion = 'aprobado'";

// Añadir filtro por categoría si se especifica
if ($categoria > 0) {
    $sql .= " AND p.IDcategoria = " . $categoria;
}

// Agrupar por producto
$sql .= " GROUP BY p.IDproducto";

// Añadir la cláusula ORDER BY según el criterio seleccionado
switch ($orden) {
    case 'precio-mayor':
        $sql .= " ORDER BY p.Precio DESC";
        break;
    case 'precio-menor':
        $sql .= " ORDER BY p.Precio ASC";
        break;
    case 'nombre-asc':
        $sql .= " ORDER BY p.Nombre ASC";
        break;
    case 'nombre-desc':
        $sql .= " ORDER BY p.Nombre DESC";
        break;
    default:
        $sql .= " ORDER BY p.IDproducto DESC"; // Por defecto, los más recientes primero
}

// Para depuración - guardar en un log
error_log("Ordenando productos por: " . $orden);
error_log("Filtrando por categoría: " . $categoria);
error_log("Consulta SQL: " . $sql);

// Ejecutar la consulta
$result = $conn->query($sql);

$productos = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Preparar el array de imágenes
        $imagenes = [];
        
        // Añadir imágenes si existen
        if (!empty($row["imagenes_urls"])) {
            $imagenes = explode(',', $row["imagenes_urls"]);
        }
        
        // Asegurarse de que el precio sea numérico para ordenamiento correcto
        $precio = is_numeric($row["Precio"]) ? (float)$row["Precio"] : 0;
        
        // Crear el objeto producto
        $producto = [
            "id" => $row["IDproducto"],
            "nombre" => $row["Nombre"],
            "descripcion" => $row["Descripcion"],
            "precio" => $precio,
            "estado" => $row["Estado"],
            "stock" => $row["Stock"],
            "categoria_id" => $row["IDcategoria"],
            "categoria_nombre" => $row["nombre_categoria"],
            "imagenes" => $imagenes
        ];
        
        $productos[] = $producto;
    }
} else {
    // Registrar el error en caso de que la consulta falle
    if (!$result) {
        error_log("Error en la consulta: " . $conn->error);
    }
}

// Devolver los productos en formato JSON
echo json_encode($productos);

// Cerrar la conexión
$conn->close();
?>
