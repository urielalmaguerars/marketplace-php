<?php
include('conexionCapa.php');
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$idUsuario = $_SESSION['usuario_id'];

// 🟢 Cambiar tipo a vendedor si era comprador
$actualizarRol = $conn->prepare("UPDATE usuario SET TipoPrivacidad = 'vendedor' WHERE IDusuario = ? AND TipoPrivacidad = 'comprador'");
$actualizarRol->bind_param("i", $idUsuario);
$actualizarRol->execute();
$actualizarRol->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = isset($_POST['objetoNombre']) ? $conn->real_escape_string($_POST['objetoNombre']) : '';
    $descripcion = isset($_POST['objetoDescripcion']) ? $conn->real_escape_string($_POST['objetoDescripcion']) : '';
    $precio = isset($_POST['objetoPrecio']) ? floatval($_POST['objetoPrecio']) : 0;
    $nueva_categoria = isset($_POST['nuevaCategoria']) ? trim($conn->real_escape_string($_POST['nuevaCategoria'])) : '';
    $categoria_id = isset($_POST['objetoCategoria']) ? $_POST['objetoCategoria'] : '';
    $stock = 1;
    $estado = 'disponible';
    $estado_aprobacion = 'pendiente';
    $tipo = 'general';

    // 🟢 Insertar nueva categoría si se eligió "otra"
    if ($categoria_id === 'otra' && !empty($nueva_categoria)) {
        $sqlNuevaCat = "INSERT INTO categoria (nombre_categoria, estado, IDusuario) VALUES (?, 'activo', ?)";
        $stmtNuevaCat = $conn->prepare($sqlNuevaCat);
        $stmtNuevaCat->bind_param("si", $nueva_categoria, $idUsuario);

        if ($stmtNuevaCat->execute()) {
            $categoria_id = $stmtNuevaCat->insert_id;
            $stmtNuevaCat->close();
        } else {
            die("Error al guardar la nueva categoría: " . $stmtNuevaCat->error);
        }
    } else {
        $categoria_id = intval($categoria_id);
    }

    if (empty($categoria_id) || !is_numeric($categoria_id)) {
        die("ID de categoría no válido. Verifica que se haya seleccionado o creado correctamente.");
    }

    // 🔵 Insertar producto con categoría (nueva o existente)
    $sql = "INSERT INTO productos (Nombre, Descripcion, Stock, IDusuario, IDcategoria, Estado, Precio, imagen, estado_aprobacion) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ssiiisdss", $nombre, $descripcion, $stock, $idUsuario, $categoria_id, $estado, $precio, $tipo, $estado_aprobacion);

        if ($stmt->execute()) {
            $id_producto = $stmt->insert_id;

            // 🟡 Procesar imágenes con validación
            if (isset($_FILES['objetoImagen']) && !empty($_FILES['objetoImagen']['name'][0])) {
                $extensiones_permitidas = ['jpg', 'jpeg', 'png'];
                $tipos_mime_permitidos = ['image/jpeg', 'image/png'];
                $directorio_destino = "uploads/";

                if (!file_exists($directorio_destino)) {
                    mkdir($directorio_destino, 0777, true);
                }

                $total_archivos = count($_FILES['objetoImagen']['name']);
                for ($i = 0; $i < $total_archivos; $i++) {
                    if (!empty($_FILES['objetoImagen']['name'][$i])) {
                        $nombre_original = $_FILES['objetoImagen']['name'][$i];
                        $tipo_mime = $_FILES['objetoImagen']['type'][$i];
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

                        if (!in_array($extension, $extensiones_permitidas) || !in_array($tipo_mime, $tipos_mime_permitidos)) {
    echo "<script>alert('Archivo no permitido. Solo se aceptan imágenes JPG y PNG.'); window.history.back();</script>";
    exit();
}


                        $nombre_archivo = time() . '_' . $i . '_' . basename($nombre_original);
                        $ruta_archivo = $directorio_destino . $nombre_archivo;

                        if (move_uploaded_file($_FILES['objetoImagen']['tmp_name'][$i], $ruta_archivo)) {
                            $sql_imagen = "INSERT INTO Multimedia (URL) VALUES (?)";
                            $stmt_imagen = $conn->prepare($sql_imagen);
                            if ($stmt_imagen) {
                                $stmt_imagen->bind_param("s", $ruta_archivo);
                                $stmt_imagen->execute();
                                $id_multimedia = $stmt_imagen->insert_id;

                                $sql_relacion = "INSERT INTO ProductoMultimedia (IDproducto, IDmultimedia) VALUES (?, ?)";
                                $stmt_relacion = $conn->prepare($sql_relacion);
                                if ($stmt_relacion) {
                                    $stmt_relacion->bind_param("ii", $id_producto, $id_multimedia);
                                    $stmt_relacion->execute();
                                    $stmt_relacion->close();
                                }

                                $stmt_imagen->close();
                            }
                        }
                    }
                }
            }

            header("Location: home_vendedor.php?status=success&message=Producto agregado correctamente en la categoría " . urlencode($nueva_categoria ?: 'existente'));
            exit();
        } else {
            echo "Error al insertar el producto: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta: " . $conn->error;
    }

    $conn->close();
} else {
    header("Location: Objetos.php");
    exit();
}
?>
