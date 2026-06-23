<?php

$env = parse_ini_file(".env");

$host = $env["DB_HOST"];
$user = $env["DB_USER"];
$pass = $env["DB_PASS"];
$db   = $env["DB_NAME"];

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>