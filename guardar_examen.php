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
$estado = $_POST['estado'];

$activo = $estado == 'publicado' ? 1 : 0;



$stmt = $conn->prepare("
    INSERT INTO examenes(
        titulo,
        descripcion,
        tiempo_minutos,
        cantidad_preguntas,
        activo,
        estado
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssiiis",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $cantidad_preguntas,
    $activo,
    $estado
);

$stmt->execute();

header("Location: admin_examen.php");
exit;
?>