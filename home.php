<?php
session_start();
?>




<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles-mejorado.css">
    <title>Click & Go</title>
</head>
<body class="home-page">

    <header>
        <h1>Click & Go</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="home.php">Home</a></li>
                <li><a href="Objetos.php">Subir Objeto</a></li>
                <li><a href="chats.php">Mis Chats</a></li>  <!-- Nuevo enlace a chats -->
                <li><a href="perfil.php">Perfil</a></li>
                <li><a href="perfil.php#listas">Mis Listas</a></li>
                <li><a href="login.php">Admin</a></li>
                <li><a href="resumen_ventas.php">Ventas</a></li>
            </ul>
        </nav>
        <button class="cart-icon" onclick="toggleCart()">🛒</button>
        <div class="cart-dropdown" id="cart">
            <p>El carrito está vacío</p>
        </div>
    </header>
        <div class="search-bar">
    <input type="text" id="search" placeholder="Buscar objeto...">
    <button onclick="searchItem()">Buscar</button>
    

<!-- Fragmento relevante modificado de home.php -->

<div class="filtros">
    <div class="catego">
        <label for="categoria">Categoría:</label>
        <select id="categoria" onchange="filtrarPorCategoria()">
            <option value="0">Todas las categorías</option>
        </select>
        <button onclick="mostrarFormularioCategoria()">+ Crear nueva categoría</button>
    </div>

    <div class="orden">
        <label for="ordenar">Ordenar por:</label>
        <select id="ordenar" onchange="ordenarProductos()">
            <option value="predeterminado">Predeterminado</option>
            <option value="precio-mayor">Mayor precio</option>
            <option value="precio-menor">Menor precio</option>
            <option value="nombre-asc">Nombre (A-Z)</option>
            <option value="nombre-desc">Nombre (Z-A)</option>
        </select>
    </div>
</div>

<!-- Formulario oculto para crear nueva categoría -->
<form id="form-nueva-categoria">
  <input type="text" name="nombre" id="nombre-nueva-categoria" placeholder="Nombre de la nueva categoría" required>
  <textarea name="descripcion" id="descripcion-nueva-categoria" placeholder="Descripción (opcional)"></textarea>
  <button type="button" onclick="crearCategoria()">Crear Categoría</button>
</form>


<script>
document.addEventListener('DOMContentLoaded', function () {
    cargarCategorias(); // Nueva llamada para cargar las categorías
    cargarProductos();

    document.getElementById('search').addEventListener('input', function() {
        buscarProductos(this.value);
    });
});

function cargarCategorias() {
    fetch('obtener_categorias.php')
        .then(response => response.json())
        .then(categorias => {
            const select = document.getElementById('categoria');
            select.innerHTML = '<option value="0">Todas las categorías</option>';
            categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.IDcategoria;
                option.textContent = cat.nombre_categoria;
                select.appendChild(option);
            });
        })
        .catch(error => console.error('Error al cargar categorías:', error));
}

function mostrarFormularioCategoria() {
    const form = document.getElementById("form-nueva-categoria");
    form.style.display = form.style.display === "none" ? "block" : "none";
}

function crearCategoria() {
    const nombre = document.getElementById('nombre-nueva-categoria').value.trim();
    const descripcion = document.getElementById('descripcion-nueva-categoria').value.trim();

    if (!nombre) {
        alert('Debes escribir un nombre para la categoría.');
        return;
    }

    fetch('crear_categoria.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nombre=${encodeURIComponent(nombre)}&descripcion=${encodeURIComponent(descripcion)}`
    })
    .then(res => res.json())
.then(data => {
    console.log('Respuesta del servidor:', data);
    if (data.success) {
        alert('Categoría creada correctamente');
    } else {
        alert('Error del servidor: ' + data.error);
    }
})

    .catch(error => {
        console.error('Error al crear categoría:', error);
        alert('Ocurrió un error al enviar la categoría');
    });
}

</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    cargarCategorias(); // Nueva llamada para cargar las categorías
    cargarProductos();

    document.getElementById('search').addEventListener('input', function() {
        buscarProductos(this.value);
    });
});


</script>

    </div>
</div>
    </div>

    <h1>Artículos en Venta</h1>
    <div class="container" id="store"></div>

    <!-- Lightbox -->
    <div id="lightbox" class="lightbox" onclick="closeLightbox()">
        <img id="lightboxImage" src="" alt="Imagen Ampliada">
    </div>

    <!-- Modal de detalles -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="cerrar-modal" onclick="closeModal()">&times;</span>
            <div id="detalles-objeto"></div>
        </div>
    </div>

    <script>

        document.addEventListener('DOMContentLoaded', function () {
    cargarProductos();

    // Evento para búsqueda en tiempo real
    document.getElementById('search').addEventListener('input', function() {
        buscarProductos(this.value);
    });
});



















function cargarProductos() {
    fetch('obtener_productos.php')
        .then(response => response.json())
        .then(data => {
            console.log("Productos cargados:", data); // Para depuración
            
            // Verificar si los productos tienen imágenes
            data.forEach((producto, index) => {
                console.log(`Producto ${index}:`, producto.nombre);
                console.log(`Imágenes:`, producto.imagenes);
            });
            
            mostrarProductos(data);
            
            // Verificar que los carruseles se hayan creado
            setTimeout(() => {
                document.querySelectorAll('.carousel-images').forEach((carrusel, i) => {
                    console.log(`Carrusel ${i} creado:`, carrusel.children.length, "imágenes");
                });
            }, 500);
        })
        .catch(error => {
            console.error('Error al cargar productos:', error);
            document.getElementById("store").innerHTML = 
                '<p class="error">Error al cargar productos. Por favor, intenta más tarde.</p>';
        });
}

// Inicializar el array de posiciones del carrusel
let posicionesCarrusel = [];





// Función para filtrar productos por categoría
function filtrarPorCategoria() {
    // Mostrar un indicador de carga
    const storeContainer = document.getElementById("store");
    storeContainer.innerHTML = '<p class="cargando">Cargando productos...</p>';
    
    // Obtener el valor del selector de categoría
    const categoriaId = document.getElementById('categoria').value;
    console.log('Filtrando productos por categoría:', categoriaId); // Para depuración
    
    // Obtener también el valor de ordenamiento actual para mantenerlo
    const ordenarPor = document.getElementById('ordenar').value;
    
    // Realizar la petición al servidor con el criterio de filtrado y ordenamiento
    fetch(`obtener_productos.php?categoria=${categoriaId}&orden=${ordenarPor}`)
        .then(response => {
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`Error en la respuesta del servidor: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Productos filtrados recibidos:', data.length); // Para depuración
            // Mostrar los productos filtrados
            mostrarProductos(data);
        })
        .catch(error => {
            console.error('Error al filtrar productos:', error);
            storeContainer.innerHTML = '<p class="error">Error al cargar productos. Por favor, intenta más tarde.</p>';
        });
}











// Función para mostrar productos con carrusel
function mostrarProductos(productos) {
    let storeContainer = document.getElementById("store");
    storeContainer.innerHTML = '';
    
    // Inicializar posicionesCarrusel con ceros para cada producto
    posicionesCarrusel = Array(productos.length).fill(0);

    if (productos.length === 0) {
        storeContainer.innerHTML = '<p class="sin-productos">No hay productos disponibles</p>';
        return;
    }

    productos.forEach((producto, index) => {
        let itemDiv = document.createElement("div");
        itemDiv.classList.add("item");
        

        let imagesHTML = '';
        let dotsHTML = '';
        
        // Verificar si hay imágenes disponibles
        const imagenes = producto.imagenes || [];
        
        if (imagenes.length > 0) {
            imagenes.forEach((img, i) => {
                imagesHTML += `<img src="${img}" alt="${producto.nombre}" onclick="openLightbox('${img}')">`;
                dotsHTML += `<button onclick="irACarrusel(${index}, ${i})" id="dot-${index}-${i}" class="${i === 0 ? 'active' : ''}"></button>`;
            });
        } else {
            // Imagen por defecto si no hay imágenes
            imagesHTML = `<img src="uploads/no-image.png" alt="Sin imagen disponible">`;
        }

        itemDiv.innerHTML = `
            <div class="carousel-container">
                <div class="carousel-images" id="carousel-${index}">
                    ${imagesHTML}
                </div>
                ${imagenes.length > 1 ? `
                <div class="carousel-arrows">
                    <button class="carousel-arrow" onclick="moverCarrusel(${index}, -1)">❮</button>
                    <button class="carousel-arrow" onclick="moverCarrusel(${index}, 1)">❯</button>
                </div>
                <div class="carousel-dots" id="dots-${index}">${dotsHTML}</div>
                ` : ''}
            </div>
            <h2>${producto.nombre}</h2>
            <p class="descripcion">${producto.descripcion}</p>
            <p class="precio"><strong>Precio:</strong> $${producto.precio}</p>
            <p class="stock"><strong>Stock:</strong> ${producto.stock}</p>
            <div class="item-buttons">
                <button class="btn-agregar" onclick="addToCart('${producto.nombre}', ${producto.precio}, ${producto.id})">
                    Agregar al Carrito
                </button>
                <button class="btn-ver" onclick="verDetalles(${producto.id})">Ver detalles</button>
                
        <button class="btn-lista" onclick="agregarALista(${producto.id})">+Lista</button>

            </div>`;
        storeContainer.appendChild(itemDiv);
    });

    // Inicializar posiciones del carrusel
    console.log("Productos cargados:", productos.length);
    console.log("Posiciones del carrusel inicializadas:", posicionesCarrusel);
}









function verDetalles(productoId) {
    const modal = document.getElementById('modal');
    const detallesContainer = document.getElementById('detalles-objeto');
    detallesContainer.innerHTML = '<p class="cargando">Cargando detalles del producto...</p>';
    modal.style.display = 'flex';

    fetch(`obtener_detalle_producto.php?id=${productoId}`)
        .then(response => response.json())
        .then(producto => {
            if (!producto || producto.error) {
                detallesContainer.innerHTML = '<p class="error">No se pudo cargar el detalle del producto</p>';
                return;
            }

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

            let estrellas = producto.calificacion > 0 ? '⭐'.repeat(producto.calificacion) : 'Sin calificación';

            detallesContainer.innerHTML = `
                <h2>${producto.nombre}</h2>
                <div class="detalles-flex">
                    <div class="detalles-imagenes">${imagenesHTML}</div>
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
                        <button class="btn-chat" onclick="iniciarChatConVendedor(${producto.id}, '${encodeURIComponent(producto.nombre)}')">💬 Chatear con Vendedor</button>
                    </div>
                </div>
            `;

            // Comentarios
            fetch(`obtener_comentarios.php?producto_id=${productoId}`)
                .then(res => res.json())
                .then(comentarios => {
                    const comentariosHTML = comentarios.map(com => `
                        <div class="comentario">
                            <strong>${com.NombreUsuario}</strong> <span>(${com.FechaHora})</span>
                            <p>${com.Texto}</p>
                        </div>
                    `).join('');

                    const comentariosSection = `
                        <div class="comentarios">
                            <h3>Comentarios</h3>
                            ${comentariosHTML || '<p>No hay comentarios aún.</p>'}
                            <form id="form-comentario">
                                <textarea id="comentario-texto" placeholder="Escribe tu comentario..." required></textarea>
                                <button type="submit">Enviar</button>
                            </form>
                        </div>
                    `;

                    detallesContainer.innerHTML += comentariosSection;

                    document.getElementById('form-comentario').addEventListener('submit', function(e) {
                        e.preventDefault();
                        const texto = document.getElementById('comentario-texto').value.trim();
                        if (!texto) return alert('Escribe algo antes de enviar.');

                        fetch('agregar_comentario.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `IDproducto=${productoId}&comentario=${encodeURIComponent(texto)}`
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) verDetalles(productoId);
                            else alert('Error: ' + data.error);
                        });
                    });
                });

            if (producto.imagenes.length > 1) {
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
            detallesContainer.innerHTML = '<p class="error">Ocurrió un error al cargar los detalles del producto</p>';
        });
}













// Mejorar la función moverCarrusel
function moverCarrusel(index, direccion) {
    let carrusel = document.getElementById(`carousel-${index}`);
    if (!carrusel) return;
    
    let imagenes = carrusel.querySelectorAll('img');
    let total = imagenes.length;
    
    if (total <= 1) return;
    
    // Actualizar la posición
    posicionesCarrusel[index] = (posicionesCarrusel[index] || 0) + direccion;
    
    // Navegación circular
    if (posicionesCarrusel[index] < 0) posicionesCarrusel[index] = total - 1;
    if (posicionesCarrusel[index] >= total) posicionesCarrusel[index] = 0;
    
    // Mover el carrusel
    carrusel.style.transform = `translateX(-${posicionesCarrusel[index] * 100}%)`;
    
    // Actualizar los puntos indicadores
    actualizarDots(index, posicionesCarrusel[index]);
}













// Función para ir a una imagen específica del carrusel
function irACarrusel(productoIndex, imagenIndex) {
    posicionesCarrusel[productoIndex] = imagenIndex;
    
    let carrusel = document.getElementById(`carousel-${productoIndex}`);
    if (!carrusel) return;
    
    // Mover el carrusel a la posición seleccionada
    carrusel.style.transform = `translateX(-${imagenIndex * 100}%)`;
    
    // Actualizar los puntos indicadores
    actualizarDots(productoIndex, imagenIndex);
}












// Funciones del carrito
let cart = [];



// Cargar carrito desde servidor (productos cotizados aceptados)
fetch('obtener_carrito_usuario.php')
    .then(response => response.json())
    .then(data => {
        cart = data;
        updateCart();
    })
    .catch(error => {
        console.error('Error al cargar el carrito del servidor:', error);
    });





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













// Función para actualizar los puntos indicadores del carrusel
function actualizarDots(productoIndex, imagenIndex) {
    const dotsContainer = document.getElementById(`dots-${productoIndex}`);
    if (!dotsContainer) return;
    
    // Quitar la clase 'active' de todos los puntos
    const dots = dotsContainer.querySelectorAll('button');
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Añadir la clase 'active' al punto seleccionado
    const activeDot = document.getElementById(`dot-${productoIndex}-${imagenIndex}`);
    if (activeDot) activeDot.classList.add('active');
}











function closeModal() {
    const modal = document.getElementById('modal');
    modal.style.display = 'none';
    
    // Limpia el contenido del modal si quieres
    document.getElementById('detalles-objeto').innerHTML = '';
}









//Funcion para dirigir el boton de Pagar
function checkout() {
    let cartData = encodeURIComponent(JSON.stringify(cart));
    window.location.href = `compra.php?cart=${cartData}`;
}














function toggleCart() {
    const cartContainer = document.getElementById("cart");
    if (cartContainer) {
        cartContainer.classList.toggle("active");
    }
}















function buscarProductos(query) {
    fetch(`buscar_productos.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            mostrarProductos(data);
        })
        .catch(error => {
            console.error('Error al buscar productos:', error);
        });
}














// Mejora de la función ordenarProductos()
function ordenarProductos() {
    // Mostrar un indicador de carga
    const storeContainer = document.getElementById("store");
    storeContainer.innerHTML = '<p class="cargando">Ordenando productos...</p>';
    
    // Obtener el valor del selector de ordenamiento
    const ordenarPor = document.getElementById('ordenar').value;
    
    // Obtener también el valor de categoría actual para mantenerlo
    const categoriaId = document.getElementById('categoria').value;
    
    console.log('Ordenando productos por:', ordenarPor, 'en categoría:', categoriaId); // Para depuración
    
    // Realizar la petición al servidor con el criterio de ordenamiento y filtrado
    fetch(`obtener_productos.php?orden=${ordenarPor}&categoria=${categoriaId}`)
        .then(response => {
            // Verificar si la respuesta es exitosa
            if (!response.ok) {
                throw new Error(`Error en la respuesta del servidor: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Productos ordenados recibidos:', data.length); // Para depuración
            // Mostrar los productos ordenados
            mostrarProductos(data);
        })
        .catch(error => {
            console.error('Error al ordenar productos:', error);
            storeContainer.innerHTML = '<p class="error">Error al ordenar productos. Por favor, intenta más tarde.</p>';
        });
}














function guardarCalificacion(productoId, calificacion) {
    if (!calificacion) return;
    
    console.log(`Guardando calificación ${calificacion} para el producto ${productoId}`);
    
    // Mostrar indicador visual mientras se guarda
    const calificacionDisplay = document.getElementById(`calificacion-${productoId}`);
    if (calificacionDisplay) {
        calificacionDisplay.innerHTML = '<span class="guardando">Guardando...</span>';
    }
    
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
            // Actualizar la visualización en la lista de productos
            if (calificacionDisplay) {
                calificacionDisplay.innerHTML = '⭐'.repeat(calificacion);
            }
            
            // Si el modal de detalles está abierto, actualizar también allí
            const modal = document.getElementById('modal');
            if (modal && modal.style.display === 'flex') {
                const detallesCalificacion = document.querySelector('#detalles-objeto .calificacion-valor');
                if (detallesCalificacion) {
                    detallesCalificacion.innerHTML = '⭐'.repeat(calificacion);
                }
            }
            
            console.log('Calificación guardada con éxito');
        } else {
            console.error('Error al guardar calificación:', data.error);
            
            if (calificacionDisplay) {
                calificacionDisplay.innerHTML = '<span class="error">Error al guardar</span>';
                
                // Restaurar el estado después de un tiempo
                setTimeout(() => {
                    calificacionDisplay.innerHTML = '';
                }, 3000);
            }
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        
        if (calificacionDisplay) {
            calificacionDisplay.innerHTML = '<span class="error">Error de conexión</span>';
            
            // Restaurar el estado después de un tiempo
            setTimeout(() => {
                calificacionDisplay.innerHTML = '';
            }, 3000);
        }
    });
}





// Función para agregar un producto a una lista
function agregarALista(productoId) {
    // Comprobar si el usuario está logueado
    fetch('verificar_sesion_ajax.php')
        .then(response => response.json())
        .then(data => {
            if (data.logueado) {
                // Mostrar modal para seleccionar o crear lista
                mostrarModalListas(productoId);
            } else {
                alert('Debes iniciar sesión para agregar productos a tus listas');
                window.location.href = 'index.php';
            }
        })
        .catch(error => {
            console.error('Error al verificar sesión:', error);
        });
}





// Función para mostrar el modal de selección de listas
function mostrarModalListas(productoId) {
    // Primero obtener las listas del usuario
    fetch('obtener_listas_usuario.php')
        .then(response => response.json())
        .then(listas => {
            // Crear el HTML del modal
            let modalHTML = `
                <div id="modal-listas" class="modal">
                    <div class="modal-content">
                        <span class="cerrar-modal" onclick="cerrarModalListas()">&times;</span>
                        <h3>Agregar a una lista</h3>
                        
                        <div class="listas-existentes">
                            <h4>Mis listas</h4>
                            ${listas.length > 0 ? `
                                <ul class="lista-seleccion">
                                    ${listas.map(lista => `
                                        <li>
                                            <button onclick="agregarProductoALista(${productoId}, ${lista.IDlista})">
                                                ${lista.nombre_lista}
                                            </button>
                                        </li>
                                    `).join('')}
                                </ul>
                            ` : '<p>No tienes listas creadas</p>'}
                        </div>
                        
                        <div class="crear-lista">
                            <h4>Crear nueva lista</h4>
                            <form id="form-nueva-lista">
                                <input type="text" id="nombre-lista" placeholder="Nombre de la lista" required>
                                <textarea id="descripcion-lista" placeholder="Descripción (opcional)"></textarea>
                                <select id="privacidad-lista">
                                    <option value="publica">Pública</option>
                                    <option value="privada">Privada</option>
                                </select>
                                <input type="hidden" id="producto-id" value="${productoId}">
                                <button type="button" onclick="crearNuevaLista(${productoId})">Crear y agregar</button>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar el modal al DOM
            const modalDiv = document.createElement('div');
            modalDiv.innerHTML = modalHTML;
            document.body.appendChild(modalDiv);
            
            // Mostrar el modal
            document.getElementById('modal-listas').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error al obtener listas:', error);
        });
}

// Función para cerrar el modal de listas
function cerrarModalListas() {
    const modal = document.getElementById('modal-listas');
    if (modal) {
        modal.style.display = 'none';
        modal.remove();
    }
}

// Función para agregar un producto a una lista existente
function agregarProductoALista(productoId, listaId) {
    fetch('agregar_producto_lista.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `producto_id=${productoId}&lista_id=${listaId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Producto agregado a la lista con éxito');
            cerrarModalListas();
        } else {
            alert('Error al agregar producto: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al procesar la solicitud');
    });
}

// Función para crear una nueva lista y agregar el producto
function crearNuevaLista(productoId) {
    const nombreLista = document.getElementById('nombre-lista').value;
    const descripcionLista = document.getElementById('descripcion-lista').value;
    const privacidadLista = document.getElementById('privacidad-lista').value;
    
    if (!nombreLista) {
        alert('Por favor ingresa un nombre para la lista');
        return;
    }
    
    fetch('crear_lista.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `nombre=${nombreLista}&descripcion=${descripcionLista}&privacidad=${privacidadLista}&producto_id=${productoId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Lista creada y producto agregado con éxito');
            cerrarModalListas();
        } else {
            alert('Error al crear lista: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al procesar la solicitud');
    });
}



function iniciarChatConVendedor(idProducto, nombreProducto) {
    window.location.href = `iniciar_chat_objeto.php?id_producto=${idProducto}&nombre_producto=${nombreProducto}`;
}



    </script>

</body>
</html>