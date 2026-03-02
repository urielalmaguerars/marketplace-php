<?php
session_start();
include 'conexionCapa.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar'])) {
    $id_producto = intval($_POST['IDproducto']);
    $nombre = trim($_POST['Nombre']);
    $descripcion = trim($_POST['Descripcion']);
    $stock = intval($_POST['Stock']);
    $precio = floatval($_POST['Precio']);
    $estado = $_POST['Estado'];

    $stmt = $conn->prepare("UPDATE productos SET Nombre=?, Descripcion=?, Stock=?, Precio=?, Estado=? WHERE IDproducto=? AND IDusuario=?");
    $stmt->bind_param("ssidsii", $nombre, $descripcion, $stock, $precio, $estado, $id_producto, $usuario_id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Producto actualizado correctamente'); window.location.href='mis_productos.php';</script>";
    exit();
}

// Obtener productos del vendedor
$stmt = $conn->prepare("SELECT * FROM productos WHERE IDusuario = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Productos</title>
    <link rel="stylesheet" href="styles.css">
</head>
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

<body>
    <h1>Mis Productos</h1>
<a href="home_vendedor.php" class="back-home">← Regresar a Home</a>

    <?php while ($producto = $resultado->fetch_assoc()): ?>
        <form method="POST" style="border:1px solid #ccc; padding:10px; margin-bottom:20px;">
            <input type="hidden" name="IDproducto" value="<?= $producto['IDproducto'] ?>">
            <p><strong>ID:</strong> <?= $producto['IDproducto'] ?></p>

            <label>Nombre:</label><br>
            <input type="text" name="Nombre" value="<?= htmlspecialchars($producto['Nombre']) ?>" required><br>

            <label>Descripción:</label><br>
            <textarea name="Descripcion" required><?= htmlspecialchars($producto['Descripcion']) ?></textarea><br>

            <label>Stock:</label><br>
            <input type="number" name="Stock" value="<?= $producto['Stock'] ?>" min="0"><br>

            <label>Precio:</label><br>
            <input type="number" step="0.01" name="Precio" value="<?= $producto['Precio'] ?>" required><br>

            <label>Estado:</label><br>
            <select name="Estado">
                <option value="disponible" <?= $producto['Estado'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                <option value="agotado" <?= $producto['Estado'] == 'agotado' ? 'selected' : '' ?>>Agotado</option>
            </select><br><br>

            <button type="submit" name="actualizar">Actualizar</button>
        </form>
    <?php endwhile; ?>

</body>
</html>
