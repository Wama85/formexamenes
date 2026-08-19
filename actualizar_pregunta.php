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
| Recibir datos
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

$accion_imagen = isset($_POST['accion_imagen'])
    ? trim($_POST['accion_imagen'])
    : 'mantener';

/*
|--------------------------------------------------------------------------
| Valores permitidos
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

$accionesImagenPermitidas = [
    'mantener',
    'eliminar',
    'sin_imagen',
    'archivo',
    'url'
];

/*
|--------------------------------------------------------------------------
| Validaciones principales
|--------------------------------------------------------------------------
*/

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

if (
    !in_array(
        $accion_imagen,
        $accionesImagenPermitidas,
        true
    )
) {
    die("Acción de imagen inválida.");
}

/*
|--------------------------------------------------------------------------
| Obtener pregunta actual
|--------------------------------------------------------------------------
*/

$stmtVerificar = $conn->prepare("
    SELECT
        id,
        imagen_tipo,
        imagen
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

$resultadoVerificar =
    $stmtVerificar->get_result();

$preguntaActual =
    $resultadoVerificar->fetch_assoc();

if (!$preguntaActual) {
    die("Pregunta no encontrada.");
}

$imagenTipoAnterior =
    $preguntaActual['imagen_tipo'] ?? null;

$imagenAnterior =
    $preguntaActual['imagen'] ?? null;

/*
|--------------------------------------------------------------------------
| Preparar imagen
|--------------------------------------------------------------------------
*/

$imagenTipoNueva =
    $imagenTipoAnterior;

$imagenNueva =
    $imagenAnterior;

$archivoNuevoFisico = null;

$eliminarArchivoAnterior = false;

/*
|--------------------------------------------------------------------------
| Mantener imagen
|--------------------------------------------------------------------------
*/

if ($accion_imagen === 'mantener') {

    $imagenTipoNueva =
        $imagenTipoAnterior;

    $imagenNueva =
        $imagenAnterior;
}

/*
|--------------------------------------------------------------------------
| Sin imagen / eliminar
|--------------------------------------------------------------------------
*/

if (
    $accion_imagen === 'eliminar' ||
    $accion_imagen === 'sin_imagen'
) {

    if (
        $imagenTipoAnterior === 'archivo' &&
        !empty($imagenAnterior)
    ) {
        $eliminarArchivoAnterior = true;
    }

    $imagenTipoNueva = null;
    $imagenNueva = null;
}

/*
|--------------------------------------------------------------------------
| Nueva imagen desde URL
|--------------------------------------------------------------------------
*/

if ($accion_imagen === 'url') {

    $imagenUrl = isset($_POST['imagen_url'])
        ? trim($_POST['imagen_url'])
        : '';

    if ($imagenUrl === '') {
        die("Debe ingresar la URL de la imagen.");
    }

    if (
        !filter_var(
            $imagenUrl,
            FILTER_VALIDATE_URL
        )
    ) {
        die("La URL de la imagen no es válida.");
    }

    if (
        $imagenTipoAnterior === 'archivo' &&
        !empty($imagenAnterior)
    ) {
        $eliminarArchivoAnterior = true;
    }

    $imagenTipoNueva = 'url';
    $imagenNueva = $imagenUrl;
}

/*
|--------------------------------------------------------------------------
| Subir nueva imagen
|--------------------------------------------------------------------------
*/

if ($accion_imagen === 'archivo') {

    if (
        !isset($_FILES['imagen_archivo']) ||
        $_FILES['imagen_archivo']['error'] !== UPLOAD_ERR_OK
    ) {
        die("Debe seleccionar una imagen válida.");
    }

    $archivo =
        $_FILES['imagen_archivo'];

    /*
    Máximo 5 MB
    */

    if (
        $archivo['size'] >
        5 * 1024 * 1024
    ) {
        die(
            "La imagen no puede superar los 5 MB."
        );
    }

    /*
    Validar tipo real
    */

    $finfo =
        new finfo(FILEINFO_MIME_TYPE);

    $mime =
        $finfo->file(
            $archivo['tmp_name']
        );

    $extensionesPermitidas = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (
        !isset(
            $extensionesPermitidas[$mime]
        )
    ) {
        die(
            "Formato de imagen no permitido."
        );
    }

    $extension =
        $extensionesPermitidas[$mime];

    /*
    Carpeta
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
            die(
                "No se pudo crear la carpeta de imágenes."
            );
        }
    }

    /*
    Nombre único
    */

    $nombreArchivo =
        'pregunta_' .
        $examen_id .
        '_' .
        bin2hex(random_bytes(8)) .
        '.' .
        $extension;

    $archivoNuevoFisico =
        $carpetaFisica .
        '/' .
        $nombreArchivo;

    if (
        !move_uploaded_file(
            $archivo['tmp_name'],
            $archivoNuevoFisico
        )
    ) {
        die(
            "No se pudo guardar la nueva imagen."
        );
    }

    $imagenTipoNueva =
        'archivo';

    $imagenNueva =
        'uploads/preguntas/' .
        $nombreArchivo;

    /*
    Si había otra imagen local,
    se eliminará después del commit.
    */

    if (
        $imagenTipoAnterior === 'archivo' &&
        !empty($imagenAnterior)
    ) {
        $eliminarArchivoAnterior = true;
    }
}

/*
|--------------------------------------------------------------------------
| Configurar corrección
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

    if (
        !in_array(
            $metodo,
            $metodosPermitidos,
            true
        )
    ) {
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
| Preparar opciones
|--------------------------------------------------------------------------
*/

$opcionesProcesadas = [];
$indicesCorrectos = [];

if ($esPreguntaSeleccion) {

    $opciones =
        $_POST['opcion'] ?? [];

    $opcionesIds =
        $_POST['opcion_id'] ?? [];

    if (
        !is_array($opciones) ||
        !is_array($opcionesIds)
    ) {
        die(
            "Las opciones recibidas no son válidas."
        );
    }

    foreach (
        $opciones as $indice => $texto
    ) {

        $opcionId =
            isset($opcionesIds[$indice])
            ? (int)$opcionesIds[$indice]
            : 0;

        $opcionesProcesadas[] = [
            'indice' => (int)$indice,
            'id' => $opcionId,
            'texto' => trim((string)$texto)
        ];
    }

    $opcionesConTexto =
        array_filter(
            $opcionesProcesadas,
            function ($opcion) {

                return
                    $opcion['texto'] !== '';
            }
        );

    if (
        count($opcionesConTexto) < 2
    ) {
        die(
            "Debe escribir por lo menos dos opciones."
        );
    }

    /*
    Selección única
    */

    if ($tipo === 'opcion_multiple') {

        if (
            !isset($_POST['correcta'])
        ) {
            die(
                "Debe seleccionar una respuesta correcta."
            );
        }

        $indicesCorrectos = [
            (int)$_POST['correcta']
        ];
    }

    /*
    Selección múltiple
    */

    if ($tipo === 'seleccion_multiple') {

        $correctasRecibidas =
            $_POST['correctas'] ?? [];

        if (
            !is_array(
                $correctasRecibidas
            )
        ) {
            die(
                "Las respuestas correctas no son válidas."
            );
        }

        foreach (
            $correctasRecibidas
            as $indiceCorrecto
        ) {

            $indicesCorrectos[] =
                (int)$indiceCorrecto;
        }

        $indicesCorrectos =
            array_values(
                array_unique(
                    $indicesCorrectos
                )
            );

        if (
            count($indicesCorrectos) < 2
        ) {
            die(
                "Debe seleccionar por lo menos dos respuestas correctas."
            );
        }
    }

    /*
    Verificar índices
    */

    $indicesConTexto = [];

    foreach (
        $opcionesConTexto as $opcion
    ) {

        $indicesConTexto[] =
            $opcion['indice'];
    }

    foreach (
        $indicesCorrectos
        as $indiceCorrecto
    ) {

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

    if (
        $tipo === 'seleccion_multiple' &&
        count($indicesCorrectos) ===
        count($opcionesConTexto)
    ) {
        die(
            "En selección múltiple debe existir por lo menos una opción incorrecta."
        );
    }
}

/*
|--------------------------------------------------------------------------
| Transacción
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    Actualizar pregunta
    */

    $stmtPregunta = $conn->prepare("
        UPDATE preguntas
        SET
            tipo = ?,
            pregunta = ?,
            respuesta_correcta = ?,
            puntaje = ?,
            metodo_correccion = ?,
            imagen_tipo = ?,
            imagen = ?
        WHERE id = ?
          AND examen_id = ?
    ");

    $stmtPregunta->bind_param(
        "sssdsssii",
        $tipo,
        $pregunta,
        $respuesta_correcta,
        $puntaje,
        $metodo,
        $imagenTipoNueva,
        $imagenNueva,
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
    | Si ya no es selección
    |--------------------------------------------------------------------------
    */

    if (!$esPreguntaSeleccion) {

        $stmtEliminarOpciones =
            $conn->prepare("
                DELETE FROM opciones
                WHERE pregunta_id = ?
            ");

        $stmtEliminarOpciones->bind_param(
            "i",
            $id
        );

        if (
            !$stmtEliminarOpciones->execute()
        ) {
            throw new Exception(
                "No se pudieron eliminar las opciones anteriores."
            );
        }

    } else {

        /*
        Opciones actuales
        */

        $stmtOpcionesActuales =
            $conn->prepare("
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
            $resultadoOpcionesActuales
                ->fetch_assoc()
        ) {

            $idsOpcionesActuales[] =
                (int)$filaOpcion['id'];
        }

        /*
        Preparar consultas
        */

        $stmtActualizarOpcion =
            $conn->prepare("
                UPDATE opciones
                SET
                    opcion_texto = ?,
                    es_correcta = ?
                WHERE id = ?
                  AND pregunta_id = ?
            ");

        $stmtInsertarOpcion =
            $conn->prepare("
                INSERT INTO opciones (
                    pregunta_id,
                    opcion_texto,
                    es_correcta
                )
                VALUES (?, ?, ?)
            ");

        $stmtEliminarOpcion =
            $conn->prepare("
                DELETE FROM opciones
                WHERE id = ?
                  AND pregunta_id = ?
            ");

        /*
        IDs recibidos que siguen presentes.
        Esto permite detectar opciones que fueron
        eliminadas con el botón Eliminar.
        */

        $idsRecibidos = [];

        foreach (
            $opcionesProcesadas as $opcion
        ) {

            $indice =
                $opcion['indice'];

            $opcionId =
                $opcion['id'];

            $texto =
                $opcion['texto'];

            $esCorrecta =
                in_array(
                    $indice,
                    $indicesCorrectos,
                    true
                )
                ? 1
                : 0;

            /*
            Opción existente
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

                /*
                Si está vacía, eliminar
                */

                if ($texto === '') {

                    $stmtEliminarOpcion
                        ->bind_param(
                            "ii",
                            $opcionId,
                            $id
                        );

                    if (
                        !$stmtEliminarOpcion
                            ->execute()
                    ) {
                        throw new Exception(
                            "No se pudo eliminar una opción vacía."
                        );
                    }

                    continue;
                }

                $idsRecibidos[] =
                    $opcionId;

                $stmtActualizarOpcion
                    ->bind_param(
                        "siii",
                        $texto,
                        $esCorrecta,
                        $opcionId,
                        $id
                    );

                if (
                    !$stmtActualizarOpcion
                        ->execute()
                ) {
                    throw new Exception(
                        "No se pudo actualizar una opción."
                    );
                }

            } else {

                /*
                Nueva opción
                */

                if ($texto === '') {
                    continue;
                }

                $stmtInsertarOpcion
                    ->bind_param(
                        "isi",
                        $id,
                        $texto,
                        $esCorrecta
                    );

                if (
                    !$stmtInsertarOpcion
                        ->execute()
                ) {
                    throw new Exception(
                        "No se pudo agregar una opción."
                    );
                }
            }
        }

        /*
        Eliminar opciones que existían en BD
        pero fueron quitadas desde la interfaz.
        */

        foreach (
            $idsOpcionesActuales
            as $idActual
        ) {

            if (
                !in_array(
                    $idActual,
                    $idsRecibidos,
                    true
                )
            ) {

                $stmtEliminarOpcion
                    ->bind_param(
                        "ii",
                        $idActual,
                        $id
                    );

                if (
                    !$stmtEliminarOpcion
                        ->execute()
                ) {
                    throw new Exception(
                        "No se pudo eliminar una opción."
                    );
                }
            }
        }
    }

    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | Eliminar archivo anterior SOLO después del commit
    |--------------------------------------------------------------------------
    */

    if (
        $eliminarArchivoAnterior &&
        $imagenTipoAnterior === 'archivo' &&
        !empty($imagenAnterior)
    ) {

        $rutaAnterior =
            __DIR__ . '/' .
            $imagenAnterior;

        if (
            is_file($rutaAnterior) &&
            realpath(dirname($rutaAnterior)) ===
            realpath(__DIR__ . '/uploads/preguntas')
        ) {
            @unlink($rutaAnterior);
        }
    }

} catch (Throwable $error) {

    $conn->rollback();

    /*
    Si acabamos de subir una imagen nueva
    pero falló la BD, eliminar la nueva.
    */

    if (
        $archivoNuevoFisico !== null &&
        is_file($archivoNuevoFisico)
    ) {
        @unlink($archivoNuevoFisico);
    }

    die(
        "Ocurrió un error al actualizar la pregunta: " .
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