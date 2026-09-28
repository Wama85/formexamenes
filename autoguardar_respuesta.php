<?php

session_start();
require_once "conexion.php";

header('Content-Type: application/json; charset=utf-8');


/*
|--------------------------------------------------------------------------
| VALIDAR SESIÓN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario_id'])) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión no válida.'
    ]);

    exit;
}

$usuario_id = (int)$_SESSION['usuario_id'];


/*
|--------------------------------------------------------------------------
| RECIBIR DATOS
|--------------------------------------------------------------------------
*/

$intento_id =
    isset($_POST['intento_id'])
        ? (int)$_POST['intento_id']
        : 0;

$pregunta_id =
    isset($_POST['pregunta_id'])
        ? (int)$_POST['pregunta_id']
        : 0;

$respuesta =
    $_POST['respuesta'] ?? '';


if ($intento_id <= 0 || $pregunta_id <= 0) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Datos inválidos.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| VERIFICAR INTENTO
|--------------------------------------------------------------------------
|
| Debe:
| - pertenecer al estudiante;
| - estar abierto;
| - contener realmente esa pregunta.
|
*/

$stmt = $conn->prepare("
    SELECT i.id
    FROM intentos i
    INNER JOIN intento_preguntas ip
        ON ip.intento_id = i.id
    WHERE i.id = ?
      AND i.usuario_id = ?
      AND i.finalizado = 0
      AND ip.pregunta_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "iii",
    $intento_id,
    $usuario_id,
    $pregunta_id
);

$stmt->execute();

$resultado = $stmt->get_result();

if (!$resultado->fetch_assoc()) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'Intento o pregunta no válidos.'
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| NORMALIZAR RESPUESTA
|--------------------------------------------------------------------------
|
| Las preguntas de selección múltiple pueden llegar como arreglo.
| Las guardamos temporalmente como JSON.
|
*/

if (is_array($respuesta)) {

    $respuesta = array_values(
        array_unique(
            array_map(
                'intval',
                $respuesta
            )
        )
    );

    $respuesta = json_encode(
        $respuesta,
        JSON_UNESCAPED_UNICODE
    );

} else {

    $respuesta =
        trim((string)$respuesta);
}


/*
|--------------------------------------------------------------------------
| AUTOGUARDAR
|--------------------------------------------------------------------------
|
| correcta, puntaje_obtenido y observacion quedan sin calcular.
| La corrección definitiva se realizará al finalizar.
|
*/

$correcta = null;
$puntaje_obtenido = null;

$observacion =
    "Respuesta autoguardada. Pendiente de finalizar.";


$stmtGuardar = $conn->prepare("
    INSERT INTO respuestas (
        intento_id,
        pregunta_id,
        respuesta,
        correcta,
        puntaje_obtenido,
        observacion
    )
    VALUES (?, ?, ?, ?, ?, ?)

    ON DUPLICATE KEY UPDATE

        respuesta = VALUES(respuesta),
        correcta = NULL,
        puntaje_obtenido = NULL,
        observacion = VALUES(observacion)
");


$stmtGuardar->bind_param(
    "iisids",
    $intento_id,
    $pregunta_id,
    $respuesta,
    $correcta,
    $puntaje_obtenido,
    $observacion
);


if (!$stmtGuardar->execute()) {

    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se pudo autoguardar.'
    ]);

    exit;
}


echo json_encode([
    'ok' => true
]);

exit;
?>