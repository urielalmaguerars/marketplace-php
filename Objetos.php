<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Venta de Objetos en Línea</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="objetos">
    <header>
        <h1>Click & Go</h1>
        <nav>
            <ul class="nav-links">
                <li><a href="home_vendedor.php">Home</a></li>
                <li><a href="Objetos.php">Subir Objeto</a></li>
                <li><a href="chats.php">Mis Chats</a></li> 
                <li><a href="perfil_vendedor.php">Perfil</a></li>
                <li><a href="perfil.php#listas">Mis Listas</a></li>
            </ul>
        </nav>
    </header>
    
    <section id="subir" class="contenedor">
        <h2>Subir un Objeto</h2>
        <form id="uploadForm" class="formulario" action="insertar.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="objetoNombre" id="objetoNombre" placeholder="Nombre del Objeto" required>
            <textarea name="objetoDescripcion" id="objetoDescripcion" placeholder="Descripción" required></textarea>
            </select>


            <select name="objetoCategoria" required>
    <option value="">-- Selecciona una categoría --</option>
    <?php
    include('conexionCapa.php');
    $res = $conn->query("SELECT IDcategoria, nombre_categoria FROM categoria WHERE estado = 'activo' ORDER BY nombre_categoria ASC");
    while ($cat = $res->fetch_assoc()) {
        echo "<option value='{$cat['IDcategoria']}'>" . htmlspecialchars($cat['nombre_categoria']) . "</option>";
    }
    ?>
</select>

            <input type="number" name="objetoPrecio" id="objetoPrecio" placeholder="Precio">
            <input type="file" name="objetoImagen[]" id="objetoImagen" accept="image/.jpg,.jpeg,.png*" multiple required>
            <button type="submit">Publicar</button>
        </form>
    </section>

    <script>
    // Esperar a que el documento esté cargado
    document.addEventListener('DOMContentLoaded', function() {
        // Obtener el formulario
        const form = document.getElementById('uploadForm');
        
        // Agregar evento de envío al formulario
        form.addEventListener('submit', function(event) {
            // Evitamos que se envíe directamente si queremos procesarlo con JS
            // Si prefieres usar el PHP directamente, elimina este comentario y
            // las siguientes dos líneas:
            //event.preventDefault();
            //procesarFormulario();
        });
        
        // Esta función solo se usaría si decides procesar con JS 
        // antes de enviar al servidor
        function procesarFormulario() {
            // Obtener valores del formulario
            let nombre = document.getElementById('objetoNombre').value;
            let descripcion = document.getElementById('objetoDescripcion').value;
            let precio = document.getElementById('objetoPrecio').value;
            let categoria = document.getElementById('objetoCategoria').value;
            let imagenesInput = document.getElementById('objetoImagen').files;

            // Verificar si hay imágenes seleccionadas
            if (imagenesInput.length === 0) {
                alert('Por favor, selecciona al menos una imagen para tu objeto.');
                return;
            }

            // Mostrar un indicador de carga
            const botonSubmit = form.querySelector('button[type="submit"]');
            const textoOriginal = botonSubmit.textContent;
            botonSubmit.textContent = 'Procesando...';
            botonSubmit.disabled = true;

            // Aquí podrías hacer validaciones adicionales antes de enviar el formulario
            
            // Finalmente, enviar el formulario al servidor
            form.submit();
        }
    });
    </script>
    <script>
function mostrarCampoNuevaCategoria(valor) {
    const nuevaCategoriaDiv = document.getElementById('nuevaCategoriaDiv');
    if (valor === 'otra') {
        nuevaCategoriaDiv.style.display = 'block';
        document.getElementById('nuevaCategoria').required = true;
    } else {
        nuevaCategoriaDiv.style.display = 'none';
        document.getElementById('nuevaCategoria').required = false;
    }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    fetch('obtener_categorias.php')
        .then(response => response.json())
        .then(categorias => {
            const select = document.getElementById('categoria');
            categorias.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.IDcategoria;
                option.textContent = cat.nombre_categoria;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error al cargar categorías:', error);
        });
});
</script>

</body>
</html>