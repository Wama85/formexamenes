<?php

session_start();
require_once "conexion.php";

/*
|--------------------------------------------------------------------------
| Validar sesión
|--------------------------------------------------------------------------
*/

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

/*
|--------------------------------------------------------------------------
| Validar examen
|--------------------------------------------------------------------------
*/

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($examen_id <= 0) {
    die("Examen inválido.");
}

/*
|--------------------------------------------------------------------------
| Obtener datos del examen
|--------------------------------------------------------------------------
*/

$stmtExamen = $conn->prepare("
    SELECT titulo
    FROM examenes
    WHERE id = ?
    LIMIT 1
");

$stmtExamen->bind_param(
    "i",
    $examen_id
);

$stmtExamen->execute();

$resultadoExamen = $stmtExamen->get_result();
$examen = $resultadoExamen->fetch_assoc();

if (!$examen) {
    die("Examen no encontrado.");
}

/*
|--------------------------------------------------------------------------
| Preparar nombre del archivo
|--------------------------------------------------------------------------
*/

$nombreExamen = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $examen['titulo']
);

$nombreArchivo =
    "notas_" .
    $nombreExamen .
    ".csv";

/*
|--------------------------------------------------------------------------
| Encabezados CSV
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: text/csv; charset=utf-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $nombreArchivo .
    '"'
);

$salida = fopen(
    'php://output',
    'w'
);

/*
Agregar BOM para que Excel reconozca correctamente
acentos y caracteres especiales.
*/

fprintf(
    $salida,
    chr(0xEF) .
    chr(0xBB) .
    chr(0xBF)
);

fputcsv(
    $salida,
    [
        'correo',
        'nota'
    ]
);

/*
|--------------------------------------------------------------------------
| Obtener notas del examen seleccionado
|--------------------------------------------------------------------------
*/

$stmtNotas = $conn->prepare("
    SELECT
        u.correo,
        i.nota
    FROM intentos i
    INNER JOIN usuarios u
        ON u.id = i.usuario_id
    WHERE i.finalizado = 1
      AND i.examen_id = ?
    ORDER BY u.correo ASC
");

$stmtNotas->bind_param(
    "i",
    $examen_id
);

$stmtNotas->execute();

$resultadoNotas =
    $stmtNotas->get_result();

while (
    $fila = $resultadoNotas->fetch_assoc()
) {

    fputcsv(
        $salida,
        [
            $fila['correo'],
            $fila['nota']
        ]
    );
}

fclose($salida);
exit;