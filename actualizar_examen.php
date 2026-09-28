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


/*
|--------------------------------------------------------------------------
| DATOS DEL FORMULARIO
|--------------------------------------------------------------------------
*/

$id = isset($_POST['id'])
    ? (int)$_POST['id']
    : 0;

$titulo = isset($_POST['titulo'])
    ? trim($_POST['titulo'])
    : '';

$descripcion = isset($_POST['descripcion'])
    ? trim($_POST['descripcion'])
    : '';

$cantidad_preguntas =
    isset($_POST['cantidad_preguntas'])
        ? (int)$_POST['cantidad_preguntas']
        : 1;

$cantidad_intentos =
    isset($_POST['cantidad_intentos'])
        ? (int)$_POST['cantidad_intentos']
        : 1;

$estado =
    isset($_POST['estado'])
        ? $_POST['estado']
        : 'borrador';

$tipo_tiempo =
    isset($_POST['tipo_tiempo'])
        ? $_POST['tipo_tiempo']
        : 'individual';
$criterio_nota =
    isset($_POST['criterio_nota'])
        ? $_POST['criterio_nota']
        : 'mejor_nota';

$mostrar_respuestas =
    isset($_POST['mostrar_respuestas'])
        ? 1
        : 0;

$mostrar_preguntas_antes =
    isset($_POST['mostrar_preguntas_antes'])
        ? 1
        : 0;


/*
|--------------------------------------------------------------------------
| VALIDACIONES GENERALES
|--------------------------------------------------------------------------
*/

if ($id <= 0) {
    die("Formulario inválido.");
}

if ($titulo === '') {
    die("Debe escribir un título.");
}

if ($cantidad_preguntas < 1) {
    $cantidad_preguntas = 1;
}

if ($cantidad_intentos < 1) {
    $cantidad_intentos = 1;
}


/*
|--------------------------------------------------------------------------
| VALIDAR ESTADO
|--------------------------------------------------------------------------
*/

$estadosPermitidos = [
    'borrador',
    'publicado',
    'cerrado'
];

if (
    !in_array(
        $estado,
        $estadosPermitidos,
        true
    )
) {
    $estado = 'borrador';
}


/*
|--------------------------------------------------------------------------
| VALIDAR TIPO DE TIEMPO
|--------------------------------------------------------------------------
*/

$tiposTiempoPermitidos = [
    'individual',
    'limite'
];

if (
    !in_array(
        $tipo_tiempo,
        $tiposTiempoPermitidos,
        true
    )
) {
    $tipo_tiempo = 'individual';
}

/*
|--------------------------------------------------------------------------
| VALIDAR CRITERIO DE NOTA
|--------------------------------------------------------------------------
*/

$criteriosNotaPermitidos = [
    'mejor_nota',
    'ultimo_intento'
];

if (
    !in_array(
        $criterio_nota,
        $criteriosNotaPermitidos,
        true
    )
) {
    $criterio_nota = 'mejor_nota';
}
/*
|--------------------------------------------------------------------------
| CONTROL DE TIEMPO
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
        isset($_POST['tiempo_minutos'])
            ? (int)$_POST['tiempo_minutos']
            : 0;

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
        isset($_POST['fecha_hora_limite'])
            ? trim($_POST['fecha_hora_limite'])
            : '';

    if ($fechaRecibida === '') {
        die(
            "Debe seleccionar una fecha y hora límite."
        );
    }

    /*
     * datetime-local:
     * 2026-09-27T20:30
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

    $fecha_hora_limite =
        $fechaObjeto->format(
            'Y-m-d H:i:s'
        );

    /*
     * En este modo el cronómetro no utilizará
     * tiempo_minutos.
     */

    $tiempo_minutos = 0;
}


/*
|--------------------------------------------------------------------------
| ACTIVO
|--------------------------------------------------------------------------
*/

$activo =
    $estado === 'publicado'
        ? 1
        : 0;


/*
|--------------------------------------------------------------------------
| ACTUALIZAR FORMULARIO
|--------------------------------------------------------------------------
*/

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
        mostrar_preguntas_antes = ?,
        tipo_tiempo = ?,
        fecha_hora_limite = ?,
        criterio_nota = ?
    WHERE id = ?
");

if (!$stmt) {

    die(
        "Error al preparar la consulta: " .
        $conn->error
    );
}


/*
|--------------------------------------------------------------------------
| TIPOS
|--------------------------------------------------------------------------
|
| s titulo
| s descripcion
| i tiempo_minutos
| i cantidad_preguntas
| i cantidad_intentos
| i activo
| s estado
| i mostrar_respuestas
| i mostrar_preguntas_antes
| s tipo_tiempo
| s fecha_hora_limite
| s criterio_nota
| i id
|
*/

$stmt->bind_param(
    "ssiiiisiisssi",
    $titulo,
    $descripcion,
    $tiempo_minutos,
    $cantidad_preguntas,
    $cantidad_intentos,
    $activo,
    $estado,
    $mostrar_respuestas,
    $mostrar_preguntas_antes,
    $tipo_tiempo,
    $fecha_hora_limite,
    $criterio_nota,
    $id
);


if (!$stmt->execute()) {

    die(
        "No se pudo actualizar el formulario: " .
        $stmt->error
    );
}


$stmt->close();


header(
    "Location: admin_examen.php"
);

exit;

?>