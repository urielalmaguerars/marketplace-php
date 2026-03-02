<?php
include('conexionCapa.php');

// Array para almacenar mensajes de error
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación para el nombre
    $nombre = trim($_POST['nombre']);
    if (empty($nombre)) {
        $errores['nombre'] = "El nombre es obligatorio.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/u", $nombre)) {
        $errores['nombre'] = "El nombre debe contener solo letras y tener entre 2 y 50 caracteres.";
    }
    
    // Validación para apellidos
    $apellidos = trim($_POST['apellidos']);
    if (empty($apellidos)) {
        $errores['apellidos'] = "Los apellidos son obligatorios.";
    } elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/u", $apellidos)) {
        $errores['apellidos'] = "Los apellidos deben contener solo letras y tener entre 2 y 50 caracteres.";
    }
    
    // Validación para nombre de usuario
    $username = trim($_POST['username']);
    if (empty($username)) {
        $errores['username'] = "El nombre de usuario es obligatorio.";
    } elseif (!preg_match("/^[a-zA-Z0-9_]{4,20}$/", $username)) {
        $errores['username'] = "El nombre de usuario debe tener entre 4 y 20 caracteres y solo puede contener letras, números y guiones bajos.";
    } else {
        // Verificar si el nombre de usuario ya existe en la base de datos
        $stmt = $conn->prepare("SELECT NombreUsuario FROM Usuario WHERE NombreUsuario = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errores['username'] = "Este nombre de usuario ya está en uso.";
        }
        $stmt->close();
    }
    
    // Validación para correo electrónico
    $correo = trim($_POST['correo']);
    if (empty($correo)) {
        $errores['correo'] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores['correo'] = "Por favor, introduce un correo electrónico válido.";
    } else {
        // Verificar si el correo ya existe en la base de datos
        $stmt = $conn->prepare("SELECT Correo FROM Usuario WHERE Correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errores['correo'] = "Este correo electrónico ya está registrado.";
        }
        $stmt->close();
    }
    
    // Validación para contraseña
    $password = $_POST['password'];
    if (empty($password)) {
        $errores['password'] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 8) {
        $errores['password'] = "La contraseña debe tener al menos 8 caracteres.";
    } elseif (!preg_match("/[A-Z]/", $password)) {
        $errores['password'] = "La contraseña debe contener al menos una letra mayúscula.";
    } elseif (!preg_match("/[a-z]/", $password)) {
        $errores['password'] = "La contraseña debe contener al menos una letra minúscula.";
    } elseif (!preg_match("/[0-9]/", $password)) {
        $errores['password'] = "La contraseña debe contener al menos un número.";
    } elseif (!preg_match("/[\W_]/", $password)) {
        $errores['password'] = "La contraseña debe contener al menos un carácter especial.";
    }
    
    // Confirmar contraseña
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $errores['confirm_password'] = "Las contraseñas no coinciden.";
    }
    
    // Validación para fecha de nacimiento
    $fechaNacimiento = $_POST['fechaNacimiento'];
    if (empty($fechaNacimiento)) {
        $errores['fechaNacimiento'] = "La fecha de nacimiento es obligatoria.";
    } else {
        // Verificar que la fecha no sea futura
        $fecha = new DateTime($fechaNacimiento);
        $hoy = new DateTime();
        $edad = $hoy->diff($fecha)->y;
        
        if ($fecha > $hoy) {
            $errores['fechaNacimiento'] = "La fecha de nacimiento no puede ser en el futuro.";
        } elseif ($edad < 13) {
            $errores['fechaNacimiento'] = "Debes tener al menos 13 años para registrarte.";
        }
    }
    
    // Validación para género
    $genero = $_POST['genero'];
    if (empty($genero)) {
        $errores['genero'] = "El género es obligatorio.";
    } elseif (!in_array($genero, ['masculino', 'femenino', 'otro'])) {
        $errores['genero'] = "Por favor, selecciona un género válido.";
    }

    $tipoPrivacidad = $_POST['tipo_privacidad'] ?? '';

if (empty($tipoPrivacidad) || !in_array($tipoPrivacidad, ['vendedor', 'comprador', 'ambos'])) {
    $errores['tipo_privacidad'] = "Por favor, selecciona si deseas ser vendedor, comprador.";
}

    
    // Si no hay errores, proceder con el registro
    if (empty($errores)) {
        // Hashear la contraseña
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Puedes manejar el avatar con un sistema de subida, por ahora lo dejamos NULL
        $IDmultimedia = null;
        
        // Preparar y ejecutar la consulta
        $sql = "INSERT INTO Usuario 
    (Nombre, Contraseña, Apellidos, NombreUsuario, Correo, FechaNacimiento, Genero, TipoPrivacidad, IDmultimedia) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Error en prepare: " . $conn->error);
        }
        
$stmt->bind_param("ssssssssi", $nombre, $password_hash, $apellidos, $username, $correo, $fechaNacimiento, $genero, $tipoPrivacidad, $IDmultimedia);
        
        if ($stmt->execute()) {
            // Mensaje de éxito con una alerta JavaScript
            echo "<script>alert('Usuario registrado exitosamente.'); window.location.href = 'index.php';</script>";
            exit();
        } else {
            // Mensaje de error general
            $errores['general'] = "Error al registrar el usuario: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>



<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro</title>
    <link rel="stylesheet" href="styles.css"> <!-- Asegúrate de que el archivo CSS esté correctamente vinculado -->
</head>
<body class="Registro">
    <div class="FormsReg">
        <h2>Registro</h2>
        
        <?php if (!empty($errores['general'])): ?>
            <div class="error-message"><?php echo $errores['general']; ?></div>
        <?php endif; ?>
        
        <form id="registration-form" action="registro.php" method="POST" enctype="multipart/form-data" novalidate>
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>" 
                       class="<?php echo isset($errores['nombre']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['nombre'])): ?>
                    <span class="error-message"><?php echo $errores['nombre']; ?></span>
                <?php endif; ?>
                <small class="requirements"><!--Solo letras, entre 2 y 50 caracteres.--></small>
            </div>

            <div class="form-group">
                <label for="apellidos">Apellidos:</label>
                <input type="text" id="apellidos" name="apellidos" value="<?php echo isset($apellidos) ? htmlspecialchars($apellidos) : ''; ?>"
                       class="<?php echo isset($errores['apellidos']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['apellidos'])): ?>
                    <span class="error-message"><?php echo $errores['apellidos']; ?></span>
                <?php endif; ?>
                <small class="requirements"><!--Solo letras, entre 2 y 50 caracteres.--></small>
            </div>

            <div class="form-group">
                <label for="username">Nombre de Usuario:</label>
                <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"
                       class="<?php echo isset($errores['username']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['username'])): ?>
                    <span class="error-message"><?php echo $errores['username']; ?></span>
                <?php endif; ?>
                <small class="requirements"><!--Letras, números y guiones bajos, entre 4 y 20 caracteres.--></small>
            </div>

            <div class="form-group">
                <label for="correo">Correo:</label>
                <input type="email" id="correo" name="correo" value="<?php echo isset($correo) ? htmlspecialchars($correo) : ''; ?>"
                       class="<?php echo isset($errores['correo']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['correo'])): ?>
                    <span class="error-message"><?php echo $errores['correo']; ?></span>
                <?php endif; ?>
                <small class="requirements"><!--Formato válido: ejemplo@dominio.com--></small>
            </div>

            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" 
                       class="<?php echo isset($errores['password']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['password'])): ?>
                    <span class="error-message"><?php echo $errores['password']; ?></span>
                <?php endif; ?>
                <div class="strength-meter">
                    <div id="strength-bar"></div>
                </div>
                <small class="requirements"><!--Al menos 8 caracteres, una mayúscula, una minúscula, un número y un carácter especial.--></small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <input type="password" id="confirm_password" name="confirm_password" 
                       class="<?php echo isset($errores['confirm_password']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['confirm_password'])): ?>
                    <span class="error-message"><?php echo $errores['confirm_password']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="fechaNacimiento">Fecha de Nacimiento:</label>
                <input type="date" id="fechaNacimiento" name="fechaNacimiento" 
                       value="<?php echo isset($fechaNacimiento) ? $fechaNacimiento : ''; ?>"
                       class="<?php echo isset($errores['fechaNacimiento']) ? 'invalid-input' : ''; ?>">
                <?php if (isset($errores['fechaNacimiento'])): ?>
                    <span class="error-message"><?php echo $errores['fechaNacimiento']; ?></span>
                <?php endif; ?>
                <small class="requirements"><!--Debes tener al menos 18 años para registrarte.--></small>
            </div>

            <div class="form-group">
                <label for="genero">Género:</label>
                <select id="genero" name="genero" class="<?php echo isset($errores['genero']) ? 'invalid-input' : ''; ?>">
                    <option value="" disabled selected>Selecciona un género</option>
                    <option value="masculino" <?php echo (isset($genero) && $genero === 'masculino') ? 'selected' : ''; ?>>Masculino</option>
                    <option value="femenino" <?php echo (isset($genero) && $genero === 'femenino') ? 'selected' : ''; ?>>Femenino</option>
                </select>
                <?php if (isset($errores['genero'])): ?>
                    <span class="error-message"><?php echo $errores['genero']; ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
    <label for="tipo_privacidad">Tipo de Cuenta:</label>
    <select id="tipo_privacidad" name="tipo_privacidad" class="<?php echo isset($errores['tipo_privacidad']) ? 'invalid-input' : ''; ?>">
        <option value="" disabled selected>Selecciona una opción</option>
        <option value="comprador" <?php echo (isset($tipoPrivacidad) && $tipoPrivacidad === 'comprador') ? 'selected' : ''; ?>>Comprador</option>
        <option value="vendedor" <?php echo (isset($tipoPrivacidad) && $tipoPrivacidad === 'vendedor') ? 'selected' : ''; ?>>Vendedor</option>
    </select>
    <?php if (isset($errores['tipo_privacidad'])): ?>
        <span class="error-message"><?php echo $errores['tipo_privacidad']; ?></span>
    <?php endif; ?>
</div>


            <button type="submit" name="submit">Registrar</button>
        </form>
    </div>

    <script>
        // Función para validar el formulario en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registration-form');
            const nombreInput = document.getElementById('nombre');
            const apellidosInput = document.getElementById('apellidos');
            const usernameInput = document.getElementById('username');
            const correoInput = document.getElementById('correo');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const fechaNacimientoInput = document.getElementById('fechaNacimiento');
            const generoInput = document.getElementById('genero');
            const strengthBar = document.getElementById('strength-bar');
            
            // Establecer la fecha máxima (hoy)
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            const maxDateValue = `${yyyy}-${mm}-${dd}`;
            fechaNacimientoInput.setAttribute('max', maxDateValue);
            
            // Establecer una fecha mínima (por ejemplo, 120 años atrás)
            const minYear = yyyy - 120;
            const minDateValue = `${minYear}-${mm}-${dd}`;
            fechaNacimientoInput.setAttribute('min', minDateValue);
            
            // Validación para el Nombre
            nombreInput.addEventListener('input', function() {
                const value = this.value.trim();
                const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/u;
                
                if (value === '') {
                    showError(this, 'El nombre es obligatorio.');
                } else if (!regex.test(value)) {
                    showError(this, 'El nombre debe contener solo letras y tener entre 2 y 50 caracteres.');
                } else {
                    clearError(this);
                }
            });
            
            // Validación para Apellidos
            apellidosInput.addEventListener('input', function() {
                const value = this.value.trim();
                const regex = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]{2,50}$/u;
                
                if (value === '') {
                    showError(this, 'Los apellidos son obligatorios.');
                } else if (!regex.test(value)) {
                    showError(this, 'Los apellidos deben contener solo letras y tener entre 2 y 50 caracteres.');
                } else {
                    clearError(this);
                }
            });
            
            // Validación para Nombre de Usuario
            usernameInput.addEventListener('input', function() {
                const value = this.value.trim();
                const regex = /^[a-zA-Z0-9_]{4,20}$/;
                
                if (value === '') {
                    showError(this, 'El nombre de usuario es obligatorio.');
                } else if (!regex.test(value)) {
                    showError(this, 'El nombre de usuario debe tener entre 4 y 20 caracteres y solo puede contener letras, números y guiones bajos.');
                } else {
                    clearError(this);
                }
            });
            
            // Validación para Correo Electrónico
            correoInput.addEventListener('input', function() {
                const value = this.value.trim();
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (value === '') {
                    showError(this, 'El correo electrónico es obligatorio.');
                } else if (!regex.test(value)) {
                    showError(this, 'Por favor, introduce un correo electrónico válido, ejemplo@dominio.com.');
                } else {
                    clearError(this);
                }
            });
            
            // Validación para Contraseña
            passwordInput.addEventListener('input', function() {
                const value = this.value;
                let strength = 0;
                let feedback = '';
                
                // Verificar longitud
                if (value.length < 8) {
                    feedback = 'La contraseña debe tener al menos 8 caracteres.';
                } else {
                    strength += 1;
                }
                
                // Verificar mayúsculas
                if (!/[A-Z]/.test(value)) {
                    if (feedback === '') {
                        feedback = 'La contraseña debe contener al menos una letra mayúscula.';
                    }
                } else {
                    strength += 1;
                }
                
                // Verificar minúsculas
                if (!/[a-z]/.test(value)) {
                    if (feedback === '') {
                        feedback = 'La contraseña debe contener al menos una letra minúscula.';
                    }
                } else {
                    strength += 1;
                }
                
                // Verificar números
                if (!/[0-9]/.test(value)) {
                    if (feedback === '') {
                        feedback = 'La contraseña debe contener al menos un número.';
                    }
                } else {
                    strength += 1;
                }
                
                // Verificar caracteres especiales
                if (!/[\W_]/.test(value)) {
                    if (feedback === '') {
                        feedback = 'La contraseña debe contener al menos un carácter especial.';
                    }
                } else {
                    strength += 1;
                }
                
                // Actualizar barra de seguridad
                strengthBar.className = '';
                if (strength < 3) {
                    strengthBar.classList.add('weak');
                } else if (strength < 5) {
                    strengthBar.classList.add('medium');
                } else {
                    strengthBar.classList.add('strong');
                }
                
                if (feedback !== '') {
                    showError(this, feedback);
                } else {
                    clearError(this);
                }
                
                // Validar confirmación de contraseña si ya está escrita
                if (confirmPasswordInput.value !== '') {
                    validatePasswordConfirmation();
                }
            });
            
            // Validación para Confirmar Contraseña
            confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);
            
            function validatePasswordConfirmation() {
                if (confirmPasswordInput.value === '') {
                    showError(confirmPasswordInput, 'Por favor, confirma tu contraseña.');
                } else if (confirmPasswordInput.value !== passwordInput.value) {
                    showError(confirmPasswordInput, 'Las contraseñas no coinciden.');
                } else {
                    clearError(confirmPasswordInput);
                }
            }
            
            // Validación para Fecha de Nacimiento
            fechaNacimientoInput.addEventListener('change', function() {
                const value = this.value;
                
                if (value === '') {
                    showError(this, 'La fecha de nacimiento es obligatoria.');
                    return;
                }
                
                const fechaNacimiento = new Date(value);
                const hoy = new Date();
                
                if (fechaNacimiento > hoy) {
                    showError(this, 'La fecha de nacimiento no puede ser en el futuro.');
                    return;
                }
                
                // Calcular edad
                const edad = calculateAge(fechaNacimiento);
                
                if (edad < 18) {
                    showError(this, 'Debes tener al menos 18 años para registrarte.');
                } else {
                    clearError(this);
                }
            });
            
            // Validación para Género
            generoInput.addEventListener('change', function() {
                if (this.value === '') {
                    showError(this, 'Por favor, selecciona un género.');
                } else {
                    clearError(this);
                }
            });
            
            // Mostrar mensaje de error
            function showError(input, message) {
                input.classList.add('invalid-input');
                
                // Buscar mensaje de error existente
                let errorElement = input.nextElementSibling;
                if (!errorElement || !errorElement.classList.contains('error-message')) {
                    errorElement = document.createElement('span');
                    errorElement.classList.add('error-message');
                    input.parentNode.insertBefore(errorElement, input.nextElementSibling);
                }
                
                errorElement.textContent = message;
            }
            
            // Eliminar mensaje de error
            function clearError(input) {
                input.classList.remove('invalid-input');
                
                // Buscar y eliminar mensaje de error
                let errorElement = input.nextElementSibling;
                if (errorElement && errorElement.classList.contains('error-message')) {
                    errorElement.textContent = '';
                }
            }
            
            // Calcular edad
            function calculateAge(birthDate) {
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                return age;
            }
            
            // Validar formulario antes de enviar
            form.addEventListener('submit', function(event) {
                let hasError = false;
                
                // Comprobar cada campo
                if (nombreInput.value.trim() === '') {
                    showError(nombreInput, 'El nombre es obligatorio.');
                    hasError = true;
                }
                
                if (apellidosInput.value.trim() === '') {
                    showError(apellidosInput, 'Los apellidos son obligatorios.');
                    hasError = true;
                }
                
                if (usernameInput.value.trim() === '') {
                    showError(usernameInput, 'El nombre de usuario es obligatorio.');
                    hasError = true;
                }
                
                if (correoInput.value.trim() === '') {
                    showError(correoInput, 'El correo electrónico es obligatorio.');
                    hasError = true;
                }
                
                if (passwordInput.value === '') {
                    showError(passwordInput, 'La contraseña es obligatoria.');
                    hasError = true;
                }
                
                if (confirmPasswordInput.value === '') {
                    showError(confirmPasswordInput, 'Por favor, confirma tu contraseña.');
                    hasError = true;
                } else if (confirmPasswordInput.value !== passwordInput.value) {
                    showError(confirmPasswordInput, 'Las contraseñas no coinciden.');
                    hasError = true;
                }
                
                if (fechaNacimientoInput.value === '') {
                    showError(fechaNacimientoInput, 'La fecha de nacimiento es obligatoria.');
                    hasError = true;
                }
                
                if (generoInput.value === '' || generoInput.value === null) {
                    showError(generoInput, 'Por favor, selecciona un género.');
                    hasError = true;
                }
                
                if (hasError) {
                    event.preventDefault();
                    // Scroll al primer error
                    const firstError = document.querySelector('.invalid-input');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        });
    </script>
</body>
</html>

