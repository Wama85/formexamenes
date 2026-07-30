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

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

$valor = isset($_GET['valor'])
    ? (int) $_GET['valor']
    : -1;

if ($examen_id <= 0) {
    die("Examen inválido.");
}

if ($valor !== 0 && $valor !== 1) {
    die("Valor inválido.");
}

$stmtExamen = $conn->prepare("
    SELECT id
    FROM examenes
    WHERE id = ?
      AND estado = 'publicado'
    LIMIT 1
");

$stmtExamen->bind_param(
    "i",
    $examen_id
);

$stmtExamen->execute();

$resultadoExamen =
    $stmtExamen->get_result();

if (!$resultadoExamen->fetch_assoc()) {
    die("El examen no existe o ya no está publicado.");
}

$stmt = $conn->prepare("
    UPDATE examenes
    SET mostrar_respuestas = ?
    WHERE id = ?
      AND estado = 'publicado'
");

$stmt->bind_param(
    "ii",
    $valor,
    $examen_id
);

if (!$stmt->execute()) {
    die("No se pudo actualizar la visibilidad.");
}

header(
    "Location: resultados.php?examen_id=" .
    $examen_id
);

exit;