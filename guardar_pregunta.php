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
| Datos principales
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

$imagen_tipo = isset($_POST['imagen_tipo'])
    ? trim($_POST['imagen_tipo'])
    : '';

$imagen = null;

/*
|--------------------------------------------------------------------------
| Validaciones
|--------------------------------------------------------------------------
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
    die("Formulario inválido.");
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
| Verificar formulario
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

$resultadoExamen =
    $stmtExamen->get_result();

if (!$resultadoExamen->fetch_assoc()) {
    die("El formulario no existe.");
}

/*
|--------------------------------------------------------------------------
| Procesar imagen
|--------------------------------------------------------------------------
*/

if ($imagen_tipo === 'url') {

    $imagen_url = isset($_POST['imagen_url'])
        ? trim($_POST['imagen_url'])
        : '';

    if ($imagen_url === '') {
        die("Debe ingresar la URL de la imagen.");
    }

    if (!filter_var($imagen_url, FILTER_VALIDATE_URL)) {
        die("La URL de la imagen no es válida.");
    }

    $imagen = $imagen_url;

} elseif ($imagen_tipo === 'archivo') {

    if (
        !isset($_FILES['imagen_archivo']) ||
        $_FILES['imagen_archivo']['error'] !== UPLOAD_ERR_OK
    ) {
        die("Debe seleccionar una imagen válida.");
    }

    $archivo = $_FILES['imagen_archivo'];

    /*
    Máximo 5 MB.
    */

    if ($archivo['size'] > 5 * 1024 * 1024) {
        die("La imagen no puede superar los 5 MB.");
    }

    /*
    Validar MIME real.
    */

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file(
        $archivo['tmp_name']
    );

    $extensionesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!isset($extensionesPermitidas[$mime])) {
        die("Formato de imagen no permitido.");
    }

    $extension =
        $extensionesPermitidas[$mime];

    /*
    Crear carpeta si no existe.
    */

    $carpetaFisica =
        __DIR__ . '/uploads/preguntas';

    if (!is_dir($carpetaFisica)) {

        if (
            !mkdir(
                $carpetaFisica,
                0755,
                true
            )
        ) {
            die("No se pudo crear la carpeta de imágenes.");
        }
    }

    /*
    Nombre único.
    */

    $nombreArchivo =
        'pregunta_' .
        $examen_id .
        '_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;

    $rutaFisica =
        $carpetaFisica .
        '/' .
        $nombreArchivo;

    if (
        !move_uploaded_file(
            $archivo['tmp_name'],
            $rutaFisica
        )
    ) {
        die("No se pudo guardar la imagen.");
    }

    /*
    Guardamos ruta relativa para usarla en HTML.
    */

    $imagen =
        'uploads/preguntas/' .
        $nombreArchivo;

} else {

    $imagen_tipo = null;
    $imagen = null;
}

/*
|--------------------------------------------------------------------------
| Configurar corrección
|--------------------------------------------------------------------------
*/

if (
    $tipo === 'opcion_multiple' ||
    $tipo === 'seleccion_multiple'
) {

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
| Preparar opciones
|--------------------------------------------------------------------------
*/

$opcionesValidas = [];
$indicesCorrectos = [];

if (
    $tipo === 'opcion_multiple' ||
    $tipo === 'seleccion_multiple'
) {

    $opcionesRecibidas =
        $_POST['opcion'] ?? [];

    if (!is_array($opcionesRecibidas)) {
        die("Las opciones recibidas no son válidas.");
    }

    foreach (
        $opcionesRecibidas as $indice => $textoOpcion
    ) {

        $textoOpcion =
            trim((string) $textoOpcion);

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

        $indicesCorrectos = [
            (int) $_POST['correcta']
        ];
    }

    /*
    Selección múltiple.
    */

    if ($tipo === 'seleccion_multiple') {

        $correctasRecibidas =
            $_POST['correctas'] ?? [];

        if (!is_array($correctasRecibidas)) {
            die("Las respuestas correctas no son válidas.");
        }

        foreach (
            $correctasRecibidas as $indiceCorrecto
        ) {

            $indicesCorrectos[] =
                (int) $indiceCorrecto;
        }

        $indicesCorrectos =
            array_values(
                array_unique(
                    $indicesCorrectos
                )
            );

        if (count($indicesCorrectos) < 2) {
            die(
                "Debe seleccionar por lo menos dos respuestas correctas."
            );
        }
    }

    /*
    Verificar índices válidos.
    */

    $indicesOpcionesValidas =
        array_column(
            $opcionesValidas,
            'indice_original'
        );

    foreach (
        $indicesCorrectos as $indiceCorrecto
    ) {

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

    if (
        $tipo === 'seleccion_multiple' &&
        count($indicesCorrectos) ===
        count($opcionesValidas)
    ) {
        die(
            "En selección múltiple debe existir por lo menos una opción incorrecta."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Guardar pregunta
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    $stmtPregunta = $conn->prepare("
        INSERT INTO preguntas (
            examen_id,
            tipo,
            pregunta,
            respuesta_correcta,
            puntaje,
            metodo_correccion,
            imagen_tipo,
            imagen
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtPregunta->bind_param(
        "isssdsss",
        $examen_id,
        $tipo,
        $pregunta,
        $respuesta_correcta,
        $puntaje,
        $metodo_correccion,
        $imagen_tipo,
        $imagen
    );

    if (!$stmtPregunta->execute()) {

        throw new Exception(
            "No se pudo guardar la pregunta."
        );
    }

    $pregunta_id =
        (int) $stmtPregunta->insert_id;

    /*
    Guardar opciones.
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

        foreach (
            $opcionesValidas as $opcion
        ) {

            $indiceOriginal =
                (int) $opcion['indice_original'];

            $textoOpcion =
                $opcion['texto'];

            $esCorrecta =
                in_array(
                    $indiceOriginal,
                    $indicesCorrectos,
                    true
                )
                ? 1
                : 0;

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

    /*
    Si se subió una imagen local pero falló la BD,
    eliminarla para no dejar archivos huérfanos.
    */

    if (
        $imagen_tipo === 'archivo' &&
        $imagen
    ) {

        $archivoEliminar =
            __DIR__ . '/' . $imagen;

        if (is_file($archivoEliminar)) {
            unlink($archivoEliminar);
        }
    }

    die(
        "Ocurrió un error al guardar la pregunta: " .
        htmlspecialchars(
            $error->getMessage()
        )
    );
}

/*
|--------------------------------------------------------------------------
| Volver
|--------------------------------------------------------------------------
*/

header(
    "Location: admin_preguntas.php?examen_id=" .
    $examen_id
);

exit;
?>