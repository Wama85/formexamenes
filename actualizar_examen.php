<?php

session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'docente'
) {
    die("Acceso denegado.");
}

$id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

$titulo = isset($_POST['titulo'])
    ? trim($_POST['titulo'])
    : '';

$descripcion = isset($_POST['descripcion'])
    ? trim($_POST['descripcion'])
    : '';

$tiempo_minutos = isset($_POST['tiempo_minutos'])
    ? (int) $_POST['tiempo_minutos']
    : 1;

$cantidad_preguntas = isset($_POST['cantidad_preguntas'])
    ? (int) $_POST['cantidad_preguntas']
    : 1;

$cantidad_intentos = isset($_POST['cantidad_intentos'])
    ? (int) $_POST['cantidad_intentos']
    : 1;

$estado = isset($_POST['estado'])
    ? $_POST['estado']
    : 'borrador';

$mostrar_respuestas =
    isset($_POST['mostrar_respuestas'])
    ? 1
    : 0;

$mostrar_preguntas_antes =
    isset($_POST['mostrar_preguntas_antes'])
    ? 1
    : 0;

if ($id <= 0) {
    die("Formulario inválido.");
}

if ($titulo === '') {
    die("Debe escribir un título.");
}

if ($tiempo_minutos < 1) {
    $tiempo_minutos = 1;
}

if ($cantidad_preguntas < 1) {
    $cantidad_preguntas = 1;
}

if ($cantidad_intentos < 1) {
    $cantidad_intentos = 1;
}

$estadosPermitidos = [
    'borrador',
    'publicado',
    'cerrado'
];

if (!in_array($estado, $estadosPermitidos, true)) {
    $estado = 'borrador';
}

$activo = $estado === 'publicado'
    ? 1
    : 0;

$stmt = $conn->prepare("
    UPDATE examenes
    SET
        titulo = ?,
        descripcion = ?,
        tiempo_minutos = ?,
        cantidad_preguntas = ?,
        cantidad_intentos = ?,
        activo = ?,
        estado = ?,
        mostrar_respuestas = ?,
        mostrar_preguntas_antes = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssiiiisiii",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $cantidad_preguntas,
    $cantidad_intentos,
    $activo,
    $estado,
    $mostrar_respuestas,
    $mostrar_preguntas_antes,
    $id
);

if (!$stmt->execute()) {
    die("No se pudo actualizar el formulario.");
}

header("Location: admin_examen.php");
exit;
?>