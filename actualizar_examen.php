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

$id = (int)$_POST['id'];
$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];
$tiempo_minutos = (int)$_POST['tiempo_minutos'];
$estado = $_POST['estado'];
$mostrar_respuestas = isset($_POST['mostrar_respuestas']) ? 1 : 0;

$activo = $estado == 'publicado' ? 1 : 0;

$stmt = $conn->prepare("
    UPDATE examenes
    SET
        titulo = ?,
        descripcion = ?,
        tiempo_minutos = ?,
        activo = ?,
        estado = ?,
        mostrar_respuestas = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssiisii",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $activo,
    $estado,
    $mostrar_respuestas,
    $id
);

$stmt->execute();

header("Location: admin_examen.php");
exit;
?>