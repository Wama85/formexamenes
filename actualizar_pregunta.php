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
| Recibir y validar datos principales
|--------------------------------------------------------------------------
*/

$id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

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

$metodo = isset($_POST['metodo_correccion'])
    ? trim($_POST['metodo_correccion'])
    : 'manual';

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

if ($id <= 0 || $examen_id <= 0) {
    die("Datos de la pregunta inválidos.");
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
| Verificar que la pregunta pertenezca al examen
|--------------------------------------------------------------------------
*/

$stmtVerificar = $conn->prepare("
    SELECT id
    FROM preguntas
    WHERE id = ?
      AND examen_id = ?
    LIMIT 1
");

$stmtVerificar->bind_param(
    "ii",
    $id,
    $examen_id
);

$stmtVerificar->execute();

$resultadoVerificar = $stmtVerificar->get_result();

if (!$resultadoVerificar->fetch_assoc()) {
    die("Pregunta no encontrada.");
}

/*
|--------------------------------------------------------------------------
| Configurar corrección según el tipo
|--------------------------------------------------------------------------
*/

$esPreguntaSeleccion = (
    $tipo === 'opcion_multiple' ||
    $tipo === 'seleccion_multiple'
);

if ($esPreguntaSeleccion) {

    $respuesta_correcta = '';
    $metodo = 'manual';

} else {

    if (!in_array($metodo, $metodosPermitidos, true)) {
        $metodo = 'manual';
    }

    if (
        $metodo !== 'manual' &&
        $respuesta_correcta === ''
    ) {
        die(
            "Debe escribir una respuesta esperada para el método seleccionado."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Preparar las opciones
|--------------------------------------------------------------------------
*/

$opcionesProcesadas = [];
$indicesCorrectos = [];

if ($esPreguntaSeleccion) {

    $opciones = $_POST['opcion'] ?? [];
    $opcionesIds = $_POST['opcion_id'] ?? [];

    if (
        !is_array($opciones) ||
        !is_array($opcionesIds)
    ) {
        die("Las opciones recibidas no son válidas.");
    }

    foreach ($opciones as $indice => $texto) {

        $opcionId = isset($opcionesIds[$indice])
            ? (int) $opcionesIds[$indice]
            : 0;

        $opcionesProcesadas[] = [
            'indice' => (int) $indice,
            'id' => $opcionId,
            'texto' => trim((string) $texto)
        ];
    }

    $opcionesConTexto = array_filter(
        $opcionesProcesadas,
        function ($opcion) {
            return $opcion['texto'] !== '';
        }
    );

    if (count($opcionesConTexto) < 2) {
        die("Debe escribir por lo menos dos opciones.");
    }

    /*
    |--------------------------------------------------------------------------
    | Respuesta correcta para selección única
    |--------------------------------------------------------------------------
    */

    if ($tipo === 'opcion_multiple') {

        if (!isset($_POST['correcta'])) {
            die("Debe seleccionar una respuesta correcta.");
        }

        $indicesCorrectos = [
            (int) $_POST['correcta']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Respuestas correctas para selección múltiple
    |--------------------------------------------------------------------------
    */

    if ($tipo === 'seleccion_multiple') {

        $correctasRecibidas = $_POST['correctas'] ?? [];

        if (!is_array($correctasRecibidas)) {
            die("Las respuestas correctas no son válidas.");
        }

        foreach ($correctasRecibidas as $indiceCorrecto) {
            $indicesCorrectos[] = (int) $indiceCorrecto;
        }

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
    |--------------------------------------------------------------------------
    | Verificar que las opciones marcadas tengan texto
    |--------------------------------------------------------------------------
    */

    $indicesConTexto = [];

    foreach ($opcionesConTexto as $opcion) {
        $indicesConTexto[] = $opcion['indice'];
    }

    foreach ($indicesCorrectos as $indiceCorrecto) {

        if (
            !in_array(
                $indiceCorrecto,
                $indicesConTexto,
                true
            )
        ) {
            die(
                "Una opción marcada como correcta está vacía o no existe."
            );
        }
    }

    /*
    En selección múltiple debe existir al menos una opción incorrecta.
    */

    if (
        $tipo === 'seleccion_multiple' &&
        count($indicesCorrectos) === count($opcionesConTexto)
    ) {
        die(
            "En selección múltiple debe existir por lo menos una opción incorrecta."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Actualizar pregunta y opciones
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | Actualizar la pregunta
    |--------------------------------------------------------------------------
    */

    $stmtPregunta = $conn->prepare("
        UPDATE preguntas
        SET
            tipo = ?,
            pregunta = ?,
            respuesta_correcta = ?,
            puntaje = ?,
            metodo_correccion = ?
        WHERE id = ?
          AND examen_id = ?
    ");

    $stmtPregunta->bind_param(
        "sssdsii",
        $tipo,
        $pregunta,
        $respuesta_correcta,
        $puntaje,
        $metodo,
        $id,
        $examen_id
    );

    if (!$stmtPregunta->execute()) {
        throw new Exception(
            "No se pudo actualizar la pregunta."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Si ya no es pregunta de selección, eliminar sus opciones anteriores
    |--------------------------------------------------------------------------
    */

    if (!$esPreguntaSeleccion) {

        $stmtEliminarOpciones = $conn->prepare("
            DELETE FROM opciones
            WHERE pregunta_id = ?
        ");

        $stmtEliminarOpciones->bind_param(
            "i",
            $id
        );

        if (!$stmtEliminarOpciones->execute()) {
            throw new Exception(
                "No se pudieron eliminar las opciones anteriores."
            );
        }

    } else {

        /*
        |--------------------------------------------------------------------------
        | Obtener los IDs de opciones que realmente pertenecen a la pregunta
        |--------------------------------------------------------------------------
        */

        $stmtOpcionesActuales = $conn->prepare("
            SELECT id
            FROM opciones
            WHERE pregunta_id = ?
        ");

        $stmtOpcionesActuales->bind_param(
            "i",
            $id
        );

        $stmtOpcionesActuales->execute();

        $resultadoOpcionesActuales =
            $stmtOpcionesActuales->get_result();

        $idsOpcionesActuales = [];

        while (
            $filaOpcion =
            $resultadoOpcionesActuales->fetch_assoc()
        ) {
            $idsOpcionesActuales[] =
                (int) $filaOpcion['id'];
        }

        /*
        |--------------------------------------------------------------------------
        | Preparar consultas para actualizar, insertar y eliminar
        |--------------------------------------------------------------------------
        */

        $stmtActualizarOpcion = $conn->prepare("
            UPDATE opciones
            SET
                opcion_texto = ?,
                es_correcta = ?
            WHERE id = ?
              AND pregunta_id = ?
        ");

        $stmtInsertarOpcion = $conn->prepare("
            INSERT INTO opciones (
                pregunta_id,
                opcion_texto,
                es_correcta
            )
            VALUES (?, ?, ?)
        ");

        $stmtEliminarOpcion = $conn->prepare("
            DELETE FROM opciones
            WHERE id = ?
              AND pregunta_id = ?
        ");

        foreach ($opcionesProcesadas as $opcion) {

            $indice = $opcion['indice'];
            $opcionId = $opcion['id'];
            $texto = $opcion['texto'];

            $esCorrecta = in_array(
                $indice,
                $indicesCorrectos,
                true
            ) ? 1 : 0;

            /*
            Si la opción ya existe y quedó vacía, eliminarla.
            */

            if (
                $opcionId > 0 &&
                $texto === ''
            ) {

                if (
                    in_array(
                        $opcionId,
                        $idsOpcionesActuales,
                        true
                    )
                ) {

                    $stmtEliminarOpcion->bind_param(
                        "ii",
                        $opcionId,
                        $id
                    );

                    if (!$stmtEliminarOpcion->execute()) {
                        throw new Exception(
                            "No se pudo eliminar una opción vacía."
                        );
                    }
                }

                continue;
            }

            /*
            Si no existe y está vacía, no hacer nada.
            */

            if (
                $opcionId <= 0 &&
                $texto === ''
            ) {
                continue;
            }

            /*
            Actualizar opción existente.
            */

            if ($opcionId > 0) {

                if (
                    !in_array(
                        $opcionId,
                        $idsOpcionesActuales,
                        true
                    )
                ) {
                    throw new Exception(
                        "Una opción no pertenece a esta pregunta."
                    );
                }

                $stmtActualizarOpcion->bind_param(
                    "siii",
                    $texto,
                    $esCorrecta,
                    $opcionId,
                    $id
                );

                if (!$stmtActualizarOpcion->execute()) {
                    throw new Exception(
                        "No se pudo actualizar una opción."
                    );
                }

            } else {

                /*
                Insertar una opción nueva.
                */

                $stmtInsertarOpcion->bind_param(
                    "isi",
                    $id,
                    $texto,
                    $esCorrecta
                );

                if (!$stmtInsertarOpcion->execute()) {
                    throw new Exception(
                        "No se pudo agregar una opción."
                    );
                }
            }
        }
    }

    $conn->commit();

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "Ocurrió un error al actualizar la pregunta: " .
        htmlspecialchars($error->getMessage())
    );
}

/*
|--------------------------------------------------------------------------
| Volver al listado
|--------------------------------------------------------------------------
*/

header(
    "Location: admin_preguntas.php?examen_id=" .
    $examen_id
);

exit;
?>