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

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=notas_examen.csv');

$salida = fopen('php://output', 'w');

fputcsv($salida, ['correo', 'nota']);

$resultado = $conn->query("
    SELECT 
        u.correo,
        i.nota
    FROM intentos i
    INNER JOIN usuarios u
    ON i.usuario_id = u.id
    WHERE i.finalizado = 1
    ORDER BY u.correo ASC
");

while ($fila = $resultado->fetch_assoc()) {
    fputcsv($salida, [
        $fila['correo'],
        $fila['nota']
    ]);
}

fclose($salida);
exit;
?>