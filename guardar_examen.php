<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}

$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];
$tiempo_minutos = (int)$_POST['tiempo_minutos'];
$cantidad_preguntas = (int)$_POST['cantidad_preguntas'];
$cantidad_intentos = (int)$_POST['cantidad_intentos'];

if ($cantidad_intentos < 1) {
    $cantidad_intentos = 1;
}
$estado = $_POST['estado'];

$activo = $estado == 'publicado' ? 1 : 0;



$stmt = $conn->prepare("
    INSERT INTO examenes(
        titulo,
        descripcion,
        tiempo_minutos,
        cantidad_preguntas,
        cantidad_intentos,
        activo,
        estado
    )
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssiiiis",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $cantidad_preguntas,
    $cantidad_intentos,
    $activo,
    $estado
);

$stmt->execute();

header("Location: admin_examen.php");
exit;
?>