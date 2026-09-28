<?php

require_once "conexion.php";

echo "Zona horaria PHP: ";
echo date_default_timezone_get();

echo "<br><br>";

echo "Hora PHP: ";
echo date("Y-m-d H:i:s");

echo "<br><br>";

echo "Hora MySQL: ";

$resultado = $conn->query("
    SELECT NOW() AS hora_mysql
");

$fila = $resultado->fetch_assoc();

echo $fila['hora_mysql'];

?>