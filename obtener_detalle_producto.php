<?php
header('Content-Type: application/json');
require_once("conexionCapa.php");

// Verificar si se proporcionó un ID
if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Consulta para obtener los detalles del producto
    $sql = "SELECT p.IDproducto, p.Nombre, p.Descripcion, p.Precio, p.Estado, p.Stock, 
            GROUP_CONCAT(m.URL) AS imagenes_urls 
            FROM Productos p
            LEFT JOIN ProductoMultimedia pm ON p.IDproducto = pm.IDproducto
            LEFT JOIN Multimedia m ON pm.IDmultimedia = m.IDmultimedia
            WHERE p.IDproducto = ?
            GROUP BY p.IDproducto";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
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
            "imagenes" => $imagenes,
            "calificacion" => 0 // Puedes implementar una tabla de calificaciones si lo necesitas
        ];
        
        echo json_encode($producto);
    } else {
        // No se encontró el producto
        echo json_encode(null);
    }
    
    $stmt->close();
} else {
    // ID no proporcionado o inválido
    echo json_encode(["error" => "ID de producto no válido"]);
}

$conn->close();
?>
