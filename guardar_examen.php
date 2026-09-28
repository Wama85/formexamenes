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


/*
|--------------------------------------------------------------------------
| DATOS GENERALES
|--------------------------------------------------------------------------
*/

$titulo = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

$cantidad_preguntas =
    (int)($_POST['cantidad_preguntas'] ?? 1);

$cantidad_intentos =
    (int)($_POST['cantidad_intentos'] ?? 1);

$estado =
    $_POST['estado'] ?? 'borrador';

$tipo_tiempo =
    $_POST['tipo_tiempo'] ?? 'individual';

$criterio_nota =
    $_POST['criterio_nota'] ?? 'mejor_nota';

/*
|--------------------------------------------------------------------------
| VALIDACIONES
|--------------------------------------------------------------------------
*/

if ($titulo === '') {
    die("Debe ingresar un título.");
}

if ($cantidad_preguntas < 1) {
    $cantidad_preguntas = 1;
}

if ($cantidad_intentos < 1) {
    $cantidad_intentos = 1;
}

if (
    $tipo_tiempo !== 'individual' &&
    $tipo_tiempo !== 'limite'
) {
    $tipo_tiempo = 'individual';
}
if (
    $criterio_nota !== 'mejor_nota' &&
    $criterio_nota !== 'ultimo_intento'
) {
    $criterio_nota = 'mejor_nota';
}

/*
|--------------------------------------------------------------------------
| CONTROL DEL TIEMPO
|--------------------------------------------------------------------------
*/

$tiempo_minutos = 0;
$fecha_hora_limite = null;


/*
|--------------------------------------------------------------------------
| TIEMPO INDIVIDUAL
|--------------------------------------------------------------------------
*/

if ($tipo_tiempo === 'individual') {

    $tiempo_minutos =
        (int)($_POST['tiempo_minutos'] ?? 0);

    if ($tiempo_minutos < 1) {
        die(
            "El tiempo individual debe ser de al menos 1 minuto."
        );
    }

    $fecha_hora_limite = null;
}


/*
|--------------------------------------------------------------------------
| HORA LÍMITE
|--------------------------------------------------------------------------
*/

if ($tipo_tiempo === 'limite') {

    $fechaRecibida =
        trim($_POST['fecha_hora_limite'] ?? '');

    if ($fechaRecibida === '') {
        die(
            "Debe seleccionar una fecha y hora límite."
        );
    }

    /*
     * datetime-local normalmente llega:
     * 2026-09-27T20:15
     */

    $fechaObjeto =
        DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $fechaRecibida
        );

    if (!$fechaObjeto) {
        die(
            "La fecha y hora límite no son válidas."
        );
    }

    /*
     * Convertimos al formato DATETIME de MySQL
     */

    $fecha_hora_limite =
        $fechaObjeto->format(
            'Y-m-d H:i:s'
        );

    /*
     * En modo límite no utilizaremos
     * tiempo_minutos para calcular el cronómetro.
     */

    $tiempo_minutos = 0;
}


/*
|--------------------------------------------------------------------------
| ESTADO DEL FORMULARIO
|--------------------------------------------------------------------------
*/

$activo =
    $estado === 'publicado'
        ? 1
        : 0;


/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare("
    INSERT INTO examenes(
        titulo,
        descripcion,
        tiempo_minutos,
        cantidad_preguntas,
        cantidad_intentos,
        activo,
        estado,
        tipo_tiempo,
        fecha_hora_limite,
        criterio_nota
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die(
        "Error al preparar la consulta: " .
        $conn->error
    );
}


$stmt->bind_param(
    "ssiiiissss",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $cantidad_preguntas,
    $cantidad_intentos,
    $activo,
    $estado,
    $tipo_tiempo,
    $fecha_hora_limite,
    $criterio_nota
);


if (!$stmt->execute()) {

    die(
        "Error al guardar el formulario: " .
        $stmt->error
    );
}


$stmt->close();


header(
    "Location: admin_examen.php"
);

exit;

?>