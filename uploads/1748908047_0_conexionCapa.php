<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "prueba_1";
    $port = "3307";
    $conn = new mysqli($host, $user, $pass, $db, $port);
    //$conn = mysqli_connect($host, $user, $pass, $bd, $port); //CONEXION CON LA COMPU DE URIEL

    if($conn->connect_errno){
        echo "Failed to connect DB" . $conexion->connect_errno;
        
    }else echo "Conexion exitosa DB";

?>