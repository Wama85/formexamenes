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

$valor = isset($_GET['valor']) ? (int)$_GET['valor'] : 0;

if ($valor != 0 && $valor != 1) {
    $valor = 0;
}

$stmt = $conn->prepare("
    UPDATE examenes
    SET mostrar_respuestas = ?
    WHERE estado = 'publicado'
");

$stmt->bind_param("i", $valor);
$stmt->execute();

header("Location: resultados.php");
exit;
?>