<?php
session_start();
include 'conexionCapa.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$mi_id = $_SESSION['usuario_id'];

if (isset($_GET['chat'])) {
    $chat_id = intval($_GET['chat']);

    // Mensajes
    $stmt = $conn->prepare("
        SELECT u.NombreUsuario, m.Mensaje, m.FechaHora 
        FROM msgs m 
        JOIN usuario u ON m.IDusuario = u.IDusuario 
        WHERE m.IDchat = ?
        ORDER BY m.FechaHora ASC
    ");
    $stmt->bind_param("i", $chat_id);
    $stmt->execute();
    $mensajes = $stmt->get_result();
}



    // Obtener datos del producto relacionado al chat
   $stmtProd = $conn->prepare("
    SELECT p.IDproducto, p.Nombre, p.Precio, p.IDusuario AS IDvendedor 
    FROM productos p 
    JOIN chat c ON p.IDproducto = c.IDproducto 
    WHERE c.IDchat = ?
");

    $stmtProd->bind_param("i", $chat_id);
    $stmtProd->execute();
    $producto = $stmtProd->get_result()->fetch_assoc();
    $esVendedor = ($producto && $producto['IDvendedor'] == $mi_id);


// Lista de chats
$chats = $conn->query("
    SELECT c.IDchat, u.NombreUsuario AS Participante
    FROM chat c
    JOIN msgs m ON c.IDchat = m.IDchat
    JOIN usuario u ON m.IDusuario != $mi_id AND u.IDusuario = m.IDusuario
    WHERE c.IDchat IN (
        SELECT IDchat FROM msgs WHERE IDusuario = $mi_id
    )
    GROUP BY c.IDchat
    ORDER BY c.FechaHoraUltimoMensaje DESC
");
?>

<style>
    body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(to right, #ade8f4, #caf0f8);
    display: flex;
    height: 100vh;
}

.chat-wrapper {
    display: flex;
    width: 100%;
    height: 100%;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
}

.chat-sidebar {
    width: 280px;
    background: linear-gradient(to bottom, #0077b6, #00b4d8);
    color: white;
    padding: 20px;
    box-sizing: border-box;
    overflow-y: auto;
    border-right: 2px solid #023e8a;
}

.chat-sidebar h2 {
    margin-top: 0;
    font-size: 1.5em;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
    padding-bottom: 10px;
}

.chat-list {
    list-style: none;
    padding: 0;
    margin-top: 20px;
}

.chat-list a {
    display: block;
    padding: 12px 15px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 8px;
    margin-bottom: 10px;
    text-decoration: none;
    transition: background 0.3s;
}

.chat-list a:hover,
.chat-list a.active {
    background: rgba(255, 255, 255, 0.3);
}

.chat-main {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
    background: #f1f9fb;
}

.chat-header {
    background: #90e0ef;
    padding: 15px 25px;
    border-bottom: 2px solid #0077b6;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.chat-header h3 {
    margin: 0;
    font-size: 1.4em;
    color: #03045e;
}

.producto-titulo {
    font-size: 1em;
    color: #023e8a;
    margin-top: 4px;
}

.chat-messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    background: linear-gradient(to top, #e0f7fa, #ffffff);
}

.mensaje {
    max-width: 60%;
    padding: 12px 18px;
    margin: 10px;
    border-radius: 18px;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    font-size: 0.95em;
    line-height: 1.4em;
    word-wrap: break-word;
}

.mensaje.propio {
    background: #48cae4;
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 0;
}

.mensaje.otro {
    background: #ffffff;
    color: #333;
    margin-right: auto;
    border-bottom-left-radius: 0;
    border: 1px solid #bde0fe;
}

.mensaje strong {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    color: #03045e;
}

.hora {
    display: block;
    font-size: 0.75em;
    margin-top: 5px;
    color: #666;
    text-align: right;
}

.form-mensaje {
    display: flex;
    padding: 15px 20px;
    border-top: 2px solid #90e0ef;
    background: #ffffff;
}

.form-mensaje textarea {
    flex-grow: 1;
    padding: 10px 15px;
    border-radius: 12px;
    border: 1px solid #ccc;
    resize: none;
    font-family: inherit;
    font-size: 1em;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
}

.form-mensaje button {
    margin-left: 10px;
    padding: 10px 20px;
    background-color: #0077b6;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s;
}

.form-mensaje button:hover {
    background-color: #023e8a;
}

.sin-chat {
    color: #555;
    text-align: center;
    margin-top: 50px;
    font-style: italic;
    font-size: 1.2em;
}

</style>

<body>
    <div class="chat-wrapper">
        <!-- BARRA LATERAL -->
        <aside class="chat-sidebar">
            <h2>Mis Chats</h2>
            <ul class="chat-list">
                <?php while ($chat = $chats->fetch_assoc()): ?>
                    <li>
                        <a href="?chat=<?php echo $chat['IDchat']; ?>" class="<?php echo (isset($_GET['chat']) && $_GET['chat'] == $chat['IDchat']) ? 'active' : ''; ?>">
                            <?php echo $chat['Participante']; ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>

 <!-- BOTÓN DE REGRESO -->
<?php
if (isset($_SESSION["rol"]) && $_SESSION["rol"] === "admin") {
    $enlaceHome = "home_admin.php";
} elseif (isset($_SESSION["tipo_privacidad"])) {
    if ($_SESSION["tipo_privacidad"] === "vendedor") {
        $enlaceHome = "home_vendedor.php";
    } elseif ($_SESSION["tipo_privacidad"] === "comprador") {
        $enlaceHome = "home_comprador.php";
    }
}
?>
<a href="<?= $enlaceHome ?>" style="display: block; margin-top: 30px; text-align: center; background: white; color: #0077b6; padding: 10px 15px; border-radius: 10px; font-weight: bold; text-decoration: none;">← Regresar a Home</a>
</aside>

        <!-- PANEL PRINCIPAL DE CHAT -->
        <main class="chat-main">
            <header class="chat-header">
                <h3><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></h3>
                <?php if (isset($_GET['producto'])): ?>
                    <p class="producto-titulo">Producto: <?php echo htmlspecialchars(urldecode($_GET['producto'])); ?></p>
                <?php endif; ?>
            </header>

            <section class="chat-messages">
                <?php if (isset($mensajes)): ?>
                    <?php while ($msg = $mensajes->fetch_assoc()): ?>
                        <div class="mensaje <?php echo ($msg['NombreUsuario'] === $_SESSION['nombre_usuario']) ? 'propio' : 'otro'; ?>">
                            <strong><?php echo $msg['NombreUsuario']; ?></strong>
                            <?php echo $msg['Mensaje']; ?>
                            <span class="hora"><?php echo $msg['FechaHora']; ?></span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="sin-chat">Selecciona un chat para comenzar.</p>
                <?php endif; ?>
            </section>


            <?php if ($esVendedor): ?>
    <div style="padding: 10px 20px;">
        <button onclick="document.getElementById('formCotizacion').style.display='block'" style="background:#00b4d8;color:white;border:none;padding:10px 15px;border-radius:8px;cursor:pointer;">+ Cotización</button>

        <div id="formCotizacion" style="display:none; margin-top:10px; background:#fff; padding:15px; border-radius:10px; border:1px solid #ccc;">
            <form action="enviar_cotizacion.php" method="POST">
                <input type="hidden" name="IDchat" value="<?= $chat_id ?>">
                <input type="hidden" name="IDproducto" value="<?= $producto['IDproducto'] ?>">
                <label><strong>Producto:</strong> <?= htmlspecialchars($producto['Nombre']) ?></label><br>
                <label><strong>Precio actual:</strong> $<?= number_format($producto['Precio'], 2) ?></label><br><br>
                <label><strong>Nuevo precio propuesto:</strong></label><br>
                <input type="number" name="PrecioCotizado" step="0.01" min="1" required><br><br>
                <button type="submit" style="background:#0077b6;color:white;border:none;padding:10px 15px;border-radius:8px;">Enviar cotización</button>
            </form>
        </div>
    </div>
<?php endif; ?>


            <?php if (isset($chat_id)): ?>
                    <form method="POST" action="enviar_mensaje.php" class="form-mensaje">
                    <input type="hidden" name="chat_id" value="<?php echo $chat_id; ?>">
                    <textarea name="mensaje" placeholder="Escribe tu mensaje..." required></textarea>
                    <button type="submit">Enviar</button>
                </form>
            <?php endif; ?>

<?php if (!$esVendedor): ?>
    <?php
    $stmtCot = $conn->prepare("SELECT * FROM cotizaciones WHERE IDchat = ? AND Estado = 'pendiente' ORDER BY Fecha DESC LIMIT 1");
    $stmtCot->bind_param("i", $chat_id);
    $stmtCot->execute();
    $cotizacion = $stmtCot->get_result()->fetch_assoc();
    ?>

    <?php if ($cotizacion): ?>
        <div style="margin: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px;">
            <strong>💬 Cotización del vendedor:</strong><br>
            Precio cotizado: <strong>$<?= number_format($cotizacion['PrecioCotizado'], 2) ?></strong><br><br>
            <form action="responder_cotizacion.php" method="POST">
                <input type="hidden" name="IDcotizacion" value="<?= $cotizacion['IDcotizacion'] ?>">
                <input type="hidden" name="chat" value="<?= $chat_id ?>">
                <button name="respuesta" value="aceptado" style="background: #28a745; color:white; border:none; padding:8px 12px; border-radius:6px;">Aceptar</button>
                <button name="respuesta" value="rechazado" style="background: #dc3545; color:white; border:none; padding:8px 12px; border-radius:6px;">Rechazar</button>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>


        </main>
    </div>
</body>



