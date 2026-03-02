<?php
session_start();
include('conexionCapa.php');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];

// Verificar que se proporcionó un ID de lista
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: perfil.php");
    exit();
}

$lista_id = intval($_GET['id']);

// Obtener información de la lista
$lista_sql = "SELECT * FROM listas_usuario WHERE IDlista = ?";
$lista_stmt = $conn->prepare($lista_sql);
$lista_stmt->bind_param("i", $lista_id);
$lista_stmt->execute();
$lista_result = $lista_stmt->get_result();

if ($lista_result->num_rows === 0) {
    header("Location: perfil.php");
    $lista_stmt->close();
    $conn->close();
    exit();
}

$lista = $lista_result->fetch_assoc();

// Verificar que la lista pertenezca al usuario o sea pública
if ($lista['IDusuario'] != $usuario_id && $lista['privacidad'] == 'privada') {
    header("Location: perfil.php");
    $lista_stmt->close();
    $conn->close();
    exit();
}

// Procesar la eliminación de un producto de la lista
if (isset($_POST['eliminar_producto']) && isset($_POST['producto_id'])) {
    $producto_id = intval($_POST['producto_id']);
    
    $delete_sql = "DELETE FROM productos_lista WHERE IDlista = ? AND IDproducto = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $lista_id, $producto_id);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Redirigir para evitar reenvíos del formulario
    header("Location: detalle_lista.php?id=" . $lista_id);
    exit();
}

// Procesar la eliminación de la lista completa
if (isset($_POST['eliminar_lista']) && $lista['IDusuario'] == $usuario_id) {
    $delete_lista_sql = "DELETE FROM listas_usuario WHERE IDlista = ?";
    $delete_lista_stmt = $conn->prepare($delete_lista_sql);
    $delete_lista_stmt->bind_param("i", $lista_id);
    $delete_lista_stmt->execute();
    $delete_lista_stmt->close();
    
    header("Location: perfil.php");
    exit();
}

// Obtener los productos de la lista
$productos_sql = "SELECT p.*, pl.fecha_agregado, 
                 (SELECT m.URL FROM Multimedia m 
                  INNER JOIN ProductoMultimedia pm ON m.IDmultimedia = pm.IDmultimedia 
                  WHERE pm.IDproducto = p.IDproducto LIMIT 1) as imagen
                 FROM productos p
                 INNER JOIN productos_lista pl ON p.IDproducto = pl.IDproducto
                 WHERE pl.IDlista = ?
                 ORDER BY pl.fecha_agregado DESC";
$productos_stmt = $conn->prepare($productos_sql);
$productos_stmt->bind_param("i", $lista_id);
$productos_stmt->execute();
$productos_result = $productos_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($lista['nombre_lista']); ?> - Click & Go</title>
    <link rel="stylesheet" href="styles-mejorado.css">
    <style>
        /* Estilos para la página de detalles de lista */
 header {
    background: #B2E3D3;
    color: white;
    padding: 10px 20px;
    position: fixed; /* Fijo para mantenerlo al hacer scroll */
    top: 0;
    left: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column; /* Para que el nombre y los enlaces estén en columnas */
    align-items: center; /* Centra horizontalmente los elementos */
}

header h1 {
    margin: 5px 0;
    color: #333;
}
nav ul {
    list-style: none;
    padding: 0;
}

.nav-links {
    display: flex; /* Muestra los enlaces en formato horizontal */
    list-style: none;
    margin: 0;
    padding: 0;
    justify-content: center; /* Centra los enlaces horizontalmente */
}

.nav-links li {
    margin: 0 15px;
}

nav ul li a {
    color: black;
    text-decoration: none;
    font-weight: bold;
    transition: color 0.3s ease;
}

nav ul li a:hover {
    color: #0077B6;
}

        .lista-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .lista-acciones {
            display: flex;
            gap: 15px;
        }
        
        .lista-info {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .lista-descripcion {
            color: #666;
            margin-top: 10px;
        }
        
        .lista-meta {
            display: flex;
            gap: 20px;
            color: #888;
            font-size: 0.9em;
            margin-top: 15px;
        }
        
        .productos-lista {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .producto-card {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .producto-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .producto-imagen {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .producto-info {
            padding: 15px;
        }
        
        .producto-nombre {
            font-size: 1.1em;
            margin: 0 0 10px 0;
        }
        
        .producto-precio {
            font-weight: bold;
            color: #333;
        }
        
        .producto-acciones {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }
        
        .btn-eliminar-producto {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-ver-producto {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-eliminar-lista {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-volver {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .sin-productos {
            text-align: center;
            color: #666;
            padding: 30px;
            grid-column: 1 / -1;
        }
        
        /* Estilos para el modal de detalles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            overflow-y: auto;
        }
        
        .modal-content {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 20px auto;
        }
        
        .cerrar-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            cursor: pointer;
        }
        
        .detalles-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .detalles-imagenes {
            flex: 1;
            min-width: 300px;
        }
        
        .detalles-info {
            flex: 1;
            min-width: 300px;
        }
        
        .carrusel-detalle {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 5px;
        }
        
        .carrusel-imagenes {
            display: flex;
            transition: transform 0.5s ease-in-out;
            width: 100%;
        }
        
        .carrusel-imagenes img {
            width: 100%;
            flex-shrink: 0;
            object-fit: cover;
        }
        
        .carrusel-controles {
            display: flex;
            justify-content: space-between;
            position: absolute;
            top: 50%;
            width: 100%;
            transform: translateY(-50%);
        }
        
        .carrusel-controles button {
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .imagen-detalle {
            width: 100%;
            border-radius: 5px;
        }
        
        .cargando {
            text-align: center;
            padding: 20px;
            color: #666;
        }

        .cart-icon {
    position: fixed; /* Posiciona el carrito en la esquina superior derecha */
    top: 15px;
    right: 20px;
    font-size: 24px;
    background: none;
    border: none;
    cursor: pointer;
    z-index: 1001;
}

.cart-dropdown {
    display: none;
    position: absolute;
    top: 50px;
    right: 20px;
    background: white;
    border: 1px solid #ccc;
    padding: 15px;
    border-radius: 10px;
    width: 250px;
    z-index: 999;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    text-align: left;
}

.cart-dropdown.active {
    display: block;
}

.cart-dropdown p {
    color: black;
    margin: 5px 0;
}

.checkout-btn {
    width: 100%;
    background-color: #0077B6;
    color: white;
    margin-top: 10px;
}
    </style>
</head>
<body>
    <header>
        <h1>Click & Go</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="Objetos.php">Subir Objeto</a></li>
                <li><a href="compra.php">Pagar Objetos</a></li>
                <li><a href="Objetos.php">Solicitar Información</a></li>
                <li><a href="perfil.php">Perfil</a></li>
                <li><a href="perfil.php#listas">Mis Listas</a></li>
                <li><a href="home.php">Home</a></li>
            </ul>
        </nav>
        <button class="cart-icon" onclick="toggleCart()">🛒</button>
        <div class="cart-dropdown" id="cart">
            <p>El carrito está vacío</p>
        </div>
    </header>

    <div class="container" style="margin-top: 100px; padding: 20px;">
        <div class="lista-header">
            <h1><?php echo htmlspecialchars($lista['nombre_lista']); ?></h1>
            
            <div class="lista-acciones">
                <a href="perfil_comprador.php" class="btn-volver">Volver al Perfil</a>
                
                <?php if ($lista['IDusuario'] == $usuario_id): ?>
                <form method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta lista? Esta acción no se puede deshacer.');">
                    <button type="submit" name="eliminar_lista" class="btn-eliminar-lista">Eliminar Lista</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="lista-info">
            <p class="lista-descripcion"><?php echo htmlspecialchars($lista['descripcion']); ?></p>
            
            <div class="lista-meta">
                <span>Creada: <?php echo date('d/m/Y', strtotime($lista['fecha_creacion'])); ?></span>
                <span>Privacidad: <?php echo $lista['privacidad'] == 'publica' ? 'Pública' : 'Privada'; ?></span>
                <span>Productos: <?php echo $productos_result->num_rows; ?></span>
            </div>
        </div>
        
        <div class="productos-lista">
            <?php
            if ($productos_result->num_rows > 0) {
                while ($producto = $productos_result->fetch_assoc()) {
                    // Determinar la imagen a mostrar
                    $imagen_url = !empty($producto['imagen']) ? $producto['imagen'] : 'uploads/no-image.png';
                    
                    echo '<div class="producto-card">';
                    echo '<img src="' . $imagen_url . '" alt="' . htmlspecialchars($producto['Nombre']) . '" class="producto-imagen">';
                    echo '<div class="producto-info">';
                    echo '<h3 class="producto-nombre">' . htmlspecialchars($producto['Nombre']) . '</h3>';
                    echo '<p class="producto-precio">$' . number_format($producto['Precio'], 2) . '</p>';
                    
                    // Solo mostrar opciones de eliminar si el usuario es el propietario de la lista
                    if ($lista['IDusuario'] == $usuario_id) {
                        echo '<div class="producto-acciones">';
                        echo '<form method="POST">';
                        echo '<input type="hidden" name="producto_id" value="' . $producto['IDproducto'] . '">';
                        echo '<button type="submit" name="eliminar_producto" class="btn-eliminar-producto">Eliminar</button>';
                        echo '</form>';
                        echo '<button class="btn-ver-producto" onclick="verDetalles(' . $producto['IDproducto'] . ')">Ver detalles</button>';
                        echo '</div>';
                    } else {
                        echo '<div class="producto-acciones">';
                        echo '<button class="btn-ver-producto" onclick="verDetalles(' . $producto['IDproducto'] . ')">Ver detalles</button>';
                        echo '</div>';
                    }
                    
                    echo '</div>'; // .producto-info
                    echo '</div>'; // .producto-card
                }
            } else {
                echo '<p class="sin-productos">No hay productos en esta lista todavía.</p>';
            }
            ?>
        </div>
    </div>
    
    <!-- Modal de detalles (similar al de home.php) -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="cerrar-modal" onclick="closeModal()">&times;</span>
            <div id="detalles-objeto"></div>
        </div>
    </div>
    
    <script>
        // Función para ver detalles de un producto en un modal
        function verDetalles(productoId) {
            // Mostrar el modal con indicador de carga
            const modal = document.getElementById('modal');
            const detallesContainer = document.getElementById('detalles-objeto');
            detallesContainer.innerHTML = '<p class="cargando">Cargando detalles del producto...</p>';
            modal.style.display = 'flex';
            
            // Obtener el producto por ID
            fetch(`obtener_detalle_producto.php?id=${productoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta: ${response.status}`);
                    }
                    return response.json();
                })
                .then(producto => {
                    if (!producto || producto.error) {
                        console.error("Error al cargar producto:", producto);
                        detallesContainer.innerHTML = '<p class="error">No se pudo cargar el detalle del producto</p>';
                        return;
                    }
                    
                    console.log("Producto recibido:", producto);
                    console.log("Calificación recibida:", producto.calificacion);
                    
                    // Generar carrusel de imágenes si hay múltiples
                    let imagenesHTML = '';
                    if (Array.isArray(producto.imagenes) && producto.imagenes.length > 0) {
                        imagenesHTML = `
                            <div class="carrusel-detalle">
                                <div class="carrusel-imagenes">
                                    ${producto.imagenes.map(img => `<img src="${img}" alt="${producto.nombre}">`).join('')}
                                </div>
                                ${producto.imagenes.length > 1 ? `
                                <div class="carrusel-controles">
                                    <button id="prev-img">❮</button>
                                    <button id="next-img">❯</button>
                                </div>` : ''}
                            </div>
                        `;
                    } else {
                        imagenesHTML = `<img src="uploads/no-image.png" alt="Sin imagen disponible" class="imagen-detalle">`;
                    }
                    
                    // Preparar las estrellas para mostrar la calificación
                    let estrellas = '';
                    if (producto.calificacion && producto.calificacion > 0) {
                        estrellas = '⭐'.repeat(producto.calificacion);
                    } else {
                        estrellas = 'Sin calificación';
                    }
                    
                    detallesContainer.innerHTML = `
                        <h2>${producto.nombre}</h2>
                        <div class="detalles-flex">
                            <div class="detalles-imagenes">
                                ${imagenesHTML}
                            </div>
                            <div class="detalles-info">
                                <p><strong>Descripción:</strong> ${producto.descripcion}</p>
                                <p><strong>Precio:</strong> $${producto.precio}</p>
                                <p><strong>Stock:</strong> ${producto.stock}</p>
                                <p><strong>Estado:</strong> ${producto.estado}</p>
                                <p><strong>Calificación:</strong> <span class="calificacion-valor">${estrellas}</span></p>
                                <div class="rating-detalle">
                                    <label>Calificar este producto:</label>
                                    <select onchange="guardarCalificacion(${producto.id}, this.value)">
                                        <option value="">Seleccionar</option>
                                        <option value="1">⭐</option>
                                        <option value="2">⭐⭐</option>
                                        <option value="3">⭐⭐⭐</option>
                                        <option value="4">⭐⭐⭐⭐</option>
                                        <option value="5">⭐⭐⭐⭐⭐</option>
                                    </select>
                                </div>
                                <button class="btn-agregar" onclick="addToCart('${producto.nombre}', ${producto.precio}, ${producto.id})">
                                    Agregar al Carrito
                                </button>
                            </div>
                        </div>
                    `;
                    
                    // Configurar controles del carrusel si existen
                    if (Array.isArray(producto.imagenes) && producto.imagenes.length > 1) {
                        let currentImageIndex = 0;
                        const carrusel = detallesContainer.querySelector('.carrusel-imagenes');
                        
                        document.getElementById('prev-img').addEventListener('click', function() {
                            currentImageIndex = (currentImageIndex > 0) ? currentImageIndex - 1 : producto.imagenes.length - 1;
                            carrusel.style.transform = `translateX(-${currentImageIndex * 100}%)`;
                        });
                        
                        document.getElementById('next-img').addEventListener('click', function() {
                            currentImageIndex = (currentImageIndex < producto.imagenes.length - 1) ? currentImageIndex + 1 : 0;
                            carrusel.style.transform = `translateX(-${currentImageIndex * 100}%)`;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error al cargar el detalle del producto:', error);
                    detallesContainer.innerHTML = '<p class="error">Ocurrió un error al cargar los detalles del producto</p>';
                });
        }
        
        // Función para cerrar el modal
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }
        






        // Funciones del carrito
let cart = [];

function addToCart(name, price, id) {
    // Verificar si el producto ya está en el carrito
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    
    updateCart();
    // Mostrar notificación de agregado al carrito
    alert(`"${name}" agregado al carrito`);
}







function updateCart() {
    let cartContainer = document.getElementById("cart");
    if (!cartContainer) return;
    
    if (cart.length === 0) {
        cartContainer.innerHTML = "<p>El carrito está vacío</p>";
    } else {
        let total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        cartContainer.innerHTML = cart.map(item => 
            `<p>${item.name} - $${item.price} x ${item.quantity} = $${(item.price * item.quantity).toFixed(2)}</p>`
        ).join('');
        
        cartContainer.innerHTML += `<p><strong>Total: $${total.toFixed(2)}</strong></p>`;
        cartContainer.innerHTML += '<button class="checkout-btn" onclick="checkout()">Pagar</button>';
    }
}






function toggleCart() {
    const cartContainer = document.getElementById("cart");
    if (cartContainer) {
        cartContainer.classList.toggle("active");
    }
}





//Funcion para dirigir el boton de Pagar
function checkout() {
    let cartData = encodeURIComponent(JSON.stringify(cart));
    window.location.href = `compra.php?cart=${cartData}`;
}







        
        // Función para guardar calificación
        function guardarCalificacion(productoId, calificacion) {
            if (!calificacion) return;
            
            console.log(`Guardando calificación ${calificacion} para el producto ${productoId}`);
            
            fetch('guardar_calificacion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productoId}&calificacion=${calificacion}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Respuesta del servidor:', data);
                
                if (data.success) {
                    // Actualizar la visualización en el modal
                    const calificacionElement = document.querySelector('.calificacion-valor');
                    if (calificacionElement) {
                        calificacionElement.innerHTML = '⭐'.repeat(calificacion);
                    }
                    
                    alert('Calificación guardada con éxito');
                } else {
                    alert('Error al guardar calificación: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error en la petición:', error);
                alert('Error al guardar la calificación');
            });
        }
    </script>
</body>
</html>