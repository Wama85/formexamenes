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
| Validar datos recibidos
|--------------------------------------------------------------------------
*/

$examen_id = isset($_POST['examen_id'])
    ? (int) $_POST['examen_id']
    : 0;

$tipo = isset($_POST['tipo'])
    ? trim($_POST['tipo'])
    : '';

$pregunta = isset($_POST['pregunta'])
    ? trim($_POST['pregunta'])
    : '';

$puntaje = isset($_POST['puntaje'])
    ? (float) $_POST['puntaje']
    : 0;

$respuesta_correcta = isset($_POST['respuesta_correcta'])
    ? trim($_POST['respuesta_correcta'])
    : '';

$metodo_correccion = isset($_POST['metodo_correccion'])
    ? trim($_POST['metodo_correccion'])
    : 'manual';

/*
Tipos permitidos.
Se conserva opcion_multiple para no romper preguntas anteriores.
*/

$tiposPermitidos = [
    'opcion_multiple',
    'seleccion_multiple',
    'respuesta_texto',
    'codigo'
];

$metodosPermitidos = [
    'manual',
    'palabras_clave',
    'ollama'
];

if ($examen_id <= 0) {
    die("Examen inválido.");
}

if (!in_array($tipo, $tiposPermitidos, true)) {
    die("Tipo de pregunta inválido.");
}

if ($pregunta === '') {
    die("Debe escribir la pregunta.");
}

if ($puntaje <= 0) {
    die("El puntaje debe ser mayor que cero.");
}

/*
|--------------------------------------------------------------------------
| Verificar que el examen exista
|--------------------------------------------------------------------------
*/

$stmtExamen = $conn->prepare("
    SELECT id
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

if (!$resultadoExamen->fetch_assoc()) {
    die("El examen no existe.");
}

/*
|--------------------------------------------------------------------------
| Configurar corrección según el tipo
|--------------------------------------------------------------------------
*/

if (
    $tipo === 'opcion_multiple' ||
    $tipo === 'seleccion_multiple'
) {

    /*
    Las respuestas correctas se guardan en la tabla opciones.
    */

    $metodo_correccion = 'manual';
    $respuesta_correcta = '';

} else {

    if (
        !in_array(
            $metodo_correccion,
            $metodosPermitidos,
            true
        )
    ) {
        $metodo_correccion = 'manual';
    }

    /*
    Si se usan palabras clave u Ollama,
    debe existir una respuesta esperada.
    */

    if (
        $metodo_correccion !== 'manual' &&
        $respuesta_correcta === ''
    ) {
        die(
            "Debe escribir una respuesta esperada para este método de corrección."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Preparar opciones para preguntas de selección
|--------------------------------------------------------------------------
*/

$opcionesValidas = [];
$indicesCorrectos = [];

if (
    $tipo === 'opcion_multiple' ||
    $tipo === 'seleccion_multiple'
) {

    $opcionesRecibidas = $_POST['opcion'] ?? [];

    if (!is_array($opcionesRecibidas)) {
        die("Las opciones recibidas no son válidas.");
    }

    /*
    Guardamos el índice original para identificar correctamente
    cuáles fueron marcadas por el docente.
    */

    foreach ($opcionesRecibidas as $indice => $textoOpcion) {

        $textoOpcion = trim((string) $textoOpcion);

        if ($textoOpcion === '') {
            continue;
        }

        $opcionesValidas[] = [
            'indice_original' => (int) $indice,
            'texto' => $textoOpcion
        ];
    }

    if (count($opcionesValidas) < 2) {
        die("Debe escribir por lo menos dos opciones.");
    }

    /*
    Selección única.
    */

    if ($tipo === 'opcion_multiple') {

        if (!isset($_POST['correcta'])) {
            die("Debe seleccionar una respuesta correcta.");
        }

        $indiceCorrecto = (int) $_POST['correcta'];

        $indicesCorrectos = [$indiceCorrecto];
    }

    /*
    Selección múltiple.
    */

    if ($tipo === 'seleccion_multiple') {

        $correctasRecibidas = $_POST['correctas'] ?? [];

        if (!is_array($correctasRecibidas)) {
            die("Las respuestas correctas no son válidas.");
        }

        foreach ($correctasRecibidas as $indiceCorrecto) {
            $indicesCorrectos[] = (int) $indiceCorrecto;
        }

        /*
        Eliminar índices repetidos.
        */

        $indicesCorrectos = array_values(
            array_unique($indicesCorrectos)
        );

        if (count($indicesCorrectos) < 2) {
            die(
                "Debe seleccionar por lo menos dos respuestas correctas."
            );
        }
    }

    /*
    Verificar que cada índice correcto corresponda
    a una opción que tiene texto.
    */

    $indicesOpcionesValidas = array_column(
        $opcionesValidas,
        'indice_original'
    );

    foreach ($indicesCorrectos as $indiceCorrecto) {

        if (
            !in_array(
                $indiceCorrecto,
                $indicesOpcionesValidas,
                true
            )
        ) {
            die(
                "Una opción marcada como correcta está vacía o no existe."
            );
        }
    }

    /*
    En selección múltiple no tiene sentido que todas las opciones
    sean correctas, porque el estudiante solo tendría que marcar todo.
    */

    if (
        $tipo === 'seleccion_multiple' &&
        count($indicesCorrectos) === count($opcionesValidas)
    ) {
        die(
            "En selección múltiple debe existir por lo menos una opción incorrecta."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Guardar pregunta y opciones dentro de una transacción
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    Guardar pregunta.
    */

    $stmtPregunta = $conn->prepare("
        INSERT INTO preguntas (
            examen_id,
            tipo,
            pregunta,
            respuesta_correcta,
            puntaje,
            metodo_correccion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmtPregunta->bind_param(
        "isssds",
        $examen_id,
        $tipo,
        $pregunta,
        $respuesta_correcta,
        $puntaje,
        $metodo_correccion
    );

    if (!$stmtPregunta->execute()) {
        throw new Exception(
            "No se pudo guardar la pregunta."
        );
    }

    $pregunta_id = (int) $stmtPregunta->insert_id;

    /*
    Guardar las opciones.
    */

    if (
        $tipo === 'opcion_multiple' ||
        $tipo === 'seleccion_multiple'
    ) {

        $stmtOpcion = $conn->prepare("
            INSERT INTO opciones (
                pregunta_id,
                opcion_texto,
                es_correcta
            )
            VALUES (?, ?, ?)
        ");

        foreach ($opcionesValidas as $opcion) {

            $indiceOriginal =
                (int) $opcion['indice_original'];

            $textoOpcion =
                $opcion['texto'];

            $esCorrecta = in_array(
                $indiceOriginal,
                $indicesCorrectos,
                true
            ) ? 1 : 0;

            $stmtOpcion->bind_param(
                "isi",
                $pregunta_id,
                $textoOpcion,
                $esCorrecta
            );

            if (!$stmtOpcion->execute()) {
                throw new Exception(
                    "No se pudieron guardar las opciones."
                );
            }
        }
    }

    $conn->commit();

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "Ocurrió un error al guardar la pregunta: " .
        htmlspecialchars($error->getMessage())
    );
}

/*
|--------------------------------------------------------------------------
| Volver al listado de preguntas
|--------------------------------------------------------------------------
*/

header(
    "Location: admin_preguntas.php?examen_id=" .
    $examen_id
);

exit;
?>