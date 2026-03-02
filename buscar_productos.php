<?php
header('Content-Type: application/json');
require_once("conexionCapa.php");

// Verificar si se proporcionó un término de búsqueda
if(isset($_GET['q']) && !empty($_GET['q'])) {
    $busqueda = '%' . $conn->real_escape_string($_GET['q']) . '%';
    
    // Consulta para buscar productos
    $sql = "SELECT p.IDproducto, p.Nombre, p.Descripcion, p.Precio, p.Estado, p.Stock, 
            GROUP_CONCAT(m.URL) AS imagenes_urls 
            FROM Productos p
            LEFT JOIN ProductoMultimedia pm ON p.IDproducto = pm.IDproducto
            LEFT JOIN Multimedia m ON pm.IDmultimedia = m.IDmultimedia
            WHERE p.Nombre LIKE ? OR p.Descripcion LIKE ?
            GROUP BY p.IDproducto";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $busqueda, $busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Preparar el array de imágenes
            $imagenes = [];
            
            if(!empty($row["imagenes_urls"])) {
                $imagenes = explode(',', $row["imagenes_urls"]);
            }
            
            // Crear el objeto producto
            $producto = [
                "id" => $row["IDproducto"],
                "nombre" => $row["Nombre"],
                "descripcion" => $row["Descripcion"],
                "precio" => $row["Precio"],
                "estado" => $row["Estado"],
                "stock" => $row["Stock"],
                "imagenes" => $imagenes
            ];
            
            $productos[] = $producto;
        }
    }
    
    echo json_encode($productos);
    $stmt->close();
} else {
    // Si no hay término de búsqueda, devolver todos los productos
    include('obtener_productos.php');
}

$conn->close();
?>