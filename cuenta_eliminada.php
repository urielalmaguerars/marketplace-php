<?php
// Asegurarse de que no hay sesión activa
session_start();
session_destroy();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuenta Eliminada - Click & Go</title>
    <link rel="stylesheet" href="styles-mejorado.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        
        .container {
            max-width: 600px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        p {
            color: #555;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cuenta Eliminada</h1>
        <p>Tu cuenta ha sido eliminada correctamente. Todos tus datos personales han sido borrados de nuestro sistema.</p>
        <p>Lamentamos que hayas decidido dejarnos. Esperamos volver a verte pronto.</p>
        <a href="index.php" class="btn">Volver al inicio</a>
    </div>
</body>
</html>