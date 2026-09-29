<?php

declare(strict_types=1);

class PreguntaController
{
/*
|--------------------------------------------------------------------------
| Preguntas del formulario
|--------------------------------------------------------------------------
*/

public function preguntas(string $id): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar formulario
    |--------------------------------------------------------------------------
    */

    $idFormulario = (int)$id;

    if ($idFormulario <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Obtener formulario
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT *
        FROM examenes
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {

        http_response_code(500);

        die(
            'Error al preparar la consulta: ' .
            $conn->error
        );
    }

    $stmt->bind_param(
        'i',
        $idFormulario
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $examen =
        $resultado->fetch_assoc();

    $stmt->close();


    if (!$examen) {

        http_response_code(404);

        die('Formulario no encontrado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener preguntas
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT *
        FROM preguntas
        WHERE examen_id = ?
        ORDER BY id ASC
    ");

    if (!$stmt) {

        http_response_code(500);

        die(
            'Error al obtener las preguntas: ' .
            $conn->error
        );
    }

    $stmt->bind_param(
        'i',
        $idFormulario
    );

    $stmt->execute();

    $resultadoPreguntas =
        $stmt->get_result();

    $preguntas = [];

    while (
        $pregunta =
            $resultadoPreguntas->fetch_assoc()
    ) {

        $preguntas[] = $pregunta;
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/preguntas/index.php';
}

/*
|--------------------------------------------------------------------------
| Nueva pregunta
|--------------------------------------------------------------------------
*/

public function nuevaPregunta(string $id): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar formulario
    |--------------------------------------------------------------------------
    */

    $idFormulario = (int)$id;

    if ($idFormulario <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Obtener formulario
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT
            id,
            titulo
        FROM examenes
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {

        http_response_code(500);

        die(
            'Error al preparar la consulta: ' .
            $conn->error
        );
    }


    $stmt->bind_param(
        'i',
        $idFormulario
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $examen =
        $resultado->fetch_assoc();

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Verificar formulario
    |--------------------------------------------------------------------------
    */

    if (!$examen) {

        http_response_code(404);

        die('Formulario no encontrado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/preguntas/nueva.php';
}
/*
|--------------------------------------------------------------------------
| Guardar pregunta
|--------------------------------------------------------------------------
*/

public function guardarPregunta(string $id): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Datos principales
    |--------------------------------------------------------------------------
    */

    $examenId = (int)$id;

    $tipo = isset($_POST['tipo'])
        ? trim((string)$_POST['tipo'])
        : '';

    $pregunta = isset($_POST['pregunta'])
        ? trim((string)$_POST['pregunta'])
        : '';

    $puntaje = isset($_POST['puntaje'])
        ? (float)$_POST['puntaje']
        : 0;

    $respuestaCorrecta =
        isset($_POST['respuesta_correcta'])
            ? trim(
                (string)$_POST[
                    'respuesta_correcta'
                ]
            )
            : '';

    $metodoCorreccion =
        isset($_POST['metodo_correccion'])
            ? trim(
                (string)$_POST[
                    'metodo_correccion'
                ]
            )
            : 'manual';

    $imagenTipo =
        isset($_POST['imagen_tipo'])
            ? trim(
                (string)$_POST[
                    'imagen_tipo'
                ]
            )
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


    if ($examenId <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
    }


    if (
        !in_array(
            $tipo,
            $tiposPermitidos,
            true
        )
    ) {

        die('Tipo de pregunta inválido.');
    }


    if ($pregunta === '') {

        die('Debe escribir la pregunta.');
    }


    if ($puntaje <= 0) {

        die(
            'El puntaje debe ser mayor que cero.'
        );
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

    if (!$stmtExamen) {

        die(
            'No se pudo verificar el formulario.'
        );
    }


    $stmtExamen->bind_param(
        'i',
        $examenId
    );

    $stmtExamen->execute();

    $resultadoExamen =
        $stmtExamen->get_result();

    if (
        !$resultadoExamen->fetch_assoc()
    ) {

        $stmtExamen->close();

        die('El formulario no existe.');
    }

    $stmtExamen->close();


    /*
    |--------------------------------------------------------------------------
    | Procesar imagen
    |--------------------------------------------------------------------------
    */

    if ($imagenTipo === 'url') {

        $imagenUrl =
            isset($_POST['imagen_url'])
                ? trim(
                    (string)$_POST[
                        'imagen_url'
                    ]
                )
                : '';


        if ($imagenUrl === '') {

            die(
                'Debe ingresar la URL de la imagen.'
            );
        }


        if (
            !filter_var(
                $imagenUrl,
                FILTER_VALIDATE_URL
            )
        ) {

            die(
                'La URL de la imagen no es válida.'
            );
        }


        $imagen = $imagenUrl;

    } elseif (
        $imagenTipo === 'archivo'
    ) {

        if (
            !isset(
                $_FILES['imagen_archivo']
            ) ||
            $_FILES['imagen_archivo']['error']
                !== UPLOAD_ERR_OK
        ) {

            die(
                'Debe seleccionar una imagen válida.'
            );
        }


        $archivo =
            $_FILES['imagen_archivo'];


        /*
        |--------------------------------------------------------------------------
        | Máximo 5 MB
        |--------------------------------------------------------------------------
        */

        if (
            $archivo['size'] >
            5 * 1024 * 1024
        ) {

            die(
                'La imagen no puede superar los 5 MB.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MIME real
        |--------------------------------------------------------------------------
        */

        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

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
                $extensionesPermitidas[
                    $mime
                ]
            )
        ) {

            die(
                'Formato de imagen no permitido.'
            );
        }


        $extension =
            $extensionesPermitidas[
                $mime
            ];


        /*
        |--------------------------------------------------------------------------
        | Carpeta de imágenes
        |--------------------------------------------------------------------------
        |
        | Conservamos la misma carpeta utilizada por el sistema anterior:
        |
        | formExamenes/uploads/preguntas/
        |
        */

        $carpetaFisica =
            BASE_PATH .
            '/uploads/preguntas';


        if (
            !is_dir(
                $carpetaFisica
            )
        ) {

            if (
                !mkdir(
                    $carpetaFisica,
                    0755,
                    true
                )
            ) {

                die(
                    'No se pudo crear la carpeta de imágenes.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Nombre único
        |--------------------------------------------------------------------------
        */

        $nombreArchivo =
            'pregunta_' .
            $examenId .
            '_' .
            bin2hex(
                random_bytes(8)
            ) .
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

            die(
                'No se pudo guardar la imagen.'
            );
        }


        $imagen =
            'uploads/preguntas/' .
            $nombreArchivo;

    } else {

        $imagenTipo = null;
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

        $metodoCorreccion =
            'manual';

        $respuestaCorrecta = '';

    } else {

        if (
            !in_array(
                $metodoCorreccion,
                $metodosPermitidos,
                true
            )
        ) {

            $metodoCorreccion =
                'manual';
        }


        if (
            $metodoCorreccion !==
                'manual' &&
            $respuestaCorrecta === ''
        ) {

            die(
                'Debe escribir una respuesta esperada para este método de corrección.'
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


        if (
            !is_array(
                $opcionesRecibidas
            )
        ) {

            die(
                'Las opciones recibidas no son válidas.'
            );
        }


        foreach (
            $opcionesRecibidas
            as $indice => $textoOpcion
        ) {

            $textoOpcion =
                trim(
                    (string)$textoOpcion
                );


            if (
                $textoOpcion === ''
            ) {

                continue;
            }


            $opcionesValidas[] = [
                'indice_original' =>
                    (int)$indice,

                'texto' =>
                    $textoOpcion
            ];
        }


        if (
            count(
                $opcionesValidas
            ) < 2
        ) {

            die(
                'Debe escribir por lo menos dos opciones.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Selección única
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
            'opcion_multiple'
        ) {

            if (
                !isset(
                    $_POST['correcta']
                )
            ) {

                die(
                    'Debe seleccionar una respuesta correcta.'
                );
            }


            $indicesCorrectos = [
                (int)$_POST['correcta']
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Selección múltiple
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
            'seleccion_multiple'
        ) {

            $correctasRecibidas =
                $_POST['correctas']
                ?? [];


            if (
                !is_array(
                    $correctasRecibidas
                )
            ) {

                die(
                    'Las respuestas correctas no son válidas.'
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
                count(
                    $indicesCorrectos
                ) < 2
            ) {

                die(
                    'Debe seleccionar por lo menos dos respuestas correctas.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Verificar índices
        |--------------------------------------------------------------------------
        */

        $indicesOpcionesValidas =
            array_column(
                $opcionesValidas,
                'indice_original'
            );


        foreach (
            $indicesCorrectos
            as $indiceCorrecto
        ) {

            if (
                !in_array(
                    $indiceCorrecto,
                    $indicesOpcionesValidas,
                    true
                )
            ) {

                die(
                    'Una opción marcada como correcta está vacía o no existe.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Selección múltiple necesita al menos una incorrecta
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
                'seleccion_multiple' &&
            count(
                $indicesCorrectos
            ) ===
            count(
                $opcionesValidas
            )
        ) {

            die(
                'En selección múltiple debe existir por lo menos una opción incorrecta.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();


    try {

        /*
        |--------------------------------------------------------------------------
        | Pregunta
        |--------------------------------------------------------------------------
        */

        $stmtPregunta =
            $conn->prepare("
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


        if (!$stmtPregunta) {

            throw new Exception(
                'No se pudo preparar la pregunta.'
            );
        }


        $stmtPregunta->bind_param(
            'isssdsss',
            $examenId,
            $tipo,
            $pregunta,
            $respuestaCorrecta,
            $puntaje,
            $metodoCorreccion,
            $imagenTipo,
            $imagen
        );


        if (
            !$stmtPregunta->execute()
        ) {

            throw new Exception(
                'No se pudo guardar la pregunta.'
            );
        }


        $preguntaId =
            (int)$stmtPregunta
                ->insert_id;

        $stmtPregunta->close();


        /*
        |--------------------------------------------------------------------------
        | Opciones
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
                'opcion_multiple' ||
            $tipo ===
                'seleccion_multiple'
        ) {

            $stmtOpcion =
                $conn->prepare("
                    INSERT INTO opciones (
                        pregunta_id,
                        opcion_texto,
                        es_correcta
                    )
                    VALUES (?, ?, ?)
                ");


            if (!$stmtOpcion) {

                throw new Exception(
                    'No se pudieron preparar las opciones.'
                );
            }


            foreach (
                $opcionesValidas
                as $opcion
            ) {

                $indiceOriginal =
                    (int)$opcion[
                        'indice_original'
                    ];

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
                    'isi',
                    $preguntaId,
                    $textoOpcion,
                    $esCorrecta
                );


                if (
                    !$stmtOpcion
                        ->execute()
                ) {

                    throw new Exception(
                        'No se pudieron guardar las opciones.'
                    );
                }
            }


            $stmtOpcion->close();
        }


        $conn->commit();


    } catch (Throwable $error) {

        $conn->rollback();


        /*
        |--------------------------------------------------------------------------
        | Eliminar imagen si falló la BD
        |--------------------------------------------------------------------------
        */

        if (
            $imagenTipo ===
                'archivo' &&
            $imagen
        ) {

            $archivoEliminar =
                BASE_PATH .
                '/' .
                $imagen;


            if (
                is_file(
                    $archivoEliminar
                )
            ) {

                unlink(
                    $archivoEliminar
                );
            }
        }


        die(
            'Ocurrió un error al guardar la pregunta: ' .
            htmlspecialchars(
                $error->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Volver al listado
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/formularios/' .
        $examenId .
        '/preguntas'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Editar pregunta
|--------------------------------------------------------------------------
*/

public function editarPregunta(
    string $id,
    string $preguntaId
): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar parámetros
    |--------------------------------------------------------------------------
    */

    $examenId =
        (int)$id;

    $idPregunta =
        (int)$preguntaId;


    if (
        $examenId <= 0 ||
        $idPregunta <= 0
    ) {

        http_response_code(400);

        die(
            'Datos de la pregunta inválidos.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Obtener formulario
    |--------------------------------------------------------------------------
    */

    $stmtExamen =
        $conn->prepare("
            SELECT
                id,
                titulo
            FROM examenes
            WHERE id = ?
            LIMIT 1
        ");


    if (!$stmtExamen) {

        http_response_code(500);

        die(
            'No se pudo consultar el formulario.'
        );
    }


    $stmtExamen->bind_param(
        'i',
        $examenId
    );

    $stmtExamen->execute();

    $resultadoExamen =
        $stmtExamen->get_result();

    $examen =
        $resultadoExamen->fetch_assoc();

    $stmtExamen->close();


    if (!$examen) {

        http_response_code(404);

        die(
            'Formulario no encontrado.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener pregunta
    |--------------------------------------------------------------------------
    */

    $stmtPregunta =
        $conn->prepare("
            SELECT *
            FROM preguntas
            WHERE id = ?
              AND examen_id = ?
            LIMIT 1
        ");


    if (!$stmtPregunta) {

        http_response_code(500);

        die(
            'No se pudo consultar la pregunta.'
        );
    }


    $stmtPregunta->bind_param(
        'ii',
        $idPregunta,
        $examenId
    );

    $stmtPregunta->execute();

    $resultadoPregunta =
        $stmtPregunta->get_result();

    $pregunta =
        $resultadoPregunta->fetch_assoc();

    $stmtPregunta->close();


    if (!$pregunta) {

        http_response_code(404);

        die(
            'Pregunta no encontrada.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener opciones
    |--------------------------------------------------------------------------
    */

    $stmtOpciones =
        $conn->prepare("
            SELECT
                id,
                opcion_texto,
                es_correcta
            FROM opciones
            WHERE pregunta_id = ?
            ORDER BY id ASC
        ");


    if (!$stmtOpciones) {

        http_response_code(500);

        die(
            'No se pudieron consultar las opciones.'
        );
    }


    $stmtOpciones->bind_param(
        'i',
        $idPregunta
    );

    $stmtOpciones->execute();

    $resultadoOpciones =
        $stmtOpciones->get_result();

    $listaOpciones = [];


    while (
        $opcion =
            $resultadoOpciones->fetch_assoc()
    ) {

        $listaOpciones[] =
            $opcion;
    }


    $stmtOpciones->close();


    /*
    |--------------------------------------------------------------------------
    | Opciones iniciales
    |--------------------------------------------------------------------------
    */

    if (
        (
            $pregunta['tipo'] ===
                'opcion_multiple' ||

            $pregunta['tipo'] ===
                'seleccion_multiple'
        ) &&
        count($listaOpciones) === 0
    ) {

        for ($i = 0; $i < 4; $i++) {

            $listaOpciones[] = [
                'id' => 0,
                'opcion_texto' => '',
                'es_correcta' => 0
            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Imagen actual
    |--------------------------------------------------------------------------
    */

    $imagenTipoActual =
        $pregunta['imagen_tipo']
        ?? '';

    $imagenActual =
        $pregunta['imagen']
        ?? '';


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/preguntas/editar.php';
}

/*
|--------------------------------------------------------------------------
| Actualizar pregunta
|--------------------------------------------------------------------------
*/

public function actualizarPregunta(
    string $id,
    string $preguntaId
): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Datos
    |--------------------------------------------------------------------------
    */

    $examenId =
        (int)$id;

    $idPregunta =
        (int)$preguntaId;

    $tipo =
        isset($_POST['tipo'])
            ? trim((string)$_POST['tipo'])
            : '';

    $textoPregunta =
        isset($_POST['pregunta'])
            ? trim((string)$_POST['pregunta'])
            : '';

    $puntaje =
        isset($_POST['puntaje'])
            ? (float)$_POST['puntaje']
            : 0;

    $respuestaCorrecta =
        isset($_POST['respuesta_correcta'])
            ? trim(
                (string)$_POST[
                    'respuesta_correcta'
                ]
            )
            : '';

    $metodo =
        isset($_POST['metodo_correccion'])
            ? trim(
                (string)$_POST[
                    'metodo_correccion'
                ]
            )
            : 'manual';

    $accionImagen =
        isset($_POST['accion_imagen'])
            ? trim(
                (string)$_POST[
                    'accion_imagen'
                ]
            )
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
    | Validaciones
    |--------------------------------------------------------------------------
    */

    if (
        $examenId <= 0 ||
        $idPregunta <= 0
    ) {

        die(
            'Datos de la pregunta inválidos.'
        );
    }


    if (
        !in_array(
            $tipo,
            $tiposPermitidos,
            true
        )
    ) {

        die(
            'Tipo de pregunta inválido.'
        );
    }


    if ($textoPregunta === '') {

        die(
            'Debe escribir la pregunta.'
        );
    }


    if ($puntaje <= 0) {

        die(
            'El puntaje debe ser mayor que cero.'
        );
    }


    if (
        !in_array(
            $accionImagen,
            $accionesImagenPermitidas,
            true
        )
    ) {

        die(
            'Acción de imagen inválida.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener pregunta actual
    |--------------------------------------------------------------------------
    */

    $stmtVerificar =
        $conn->prepare("
            SELECT
                id,
                imagen_tipo,
                imagen
            FROM preguntas
            WHERE id = ?
              AND examen_id = ?
            LIMIT 1
        ");


    if (!$stmtVerificar) {

        die(
            'No se pudo verificar la pregunta.'
        );
    }


    $stmtVerificar->bind_param(
        'ii',
        $idPregunta,
        $examenId
    );

    $stmtVerificar->execute();

    $resultadoVerificar =
        $stmtVerificar->get_result();

    $preguntaActual =
        $resultadoVerificar->fetch_assoc();

    $stmtVerificar->close();


    if (!$preguntaActual) {

        http_response_code(404);

        die(
            'Pregunta no encontrada.'
        );
    }


    $imagenTipoAnterior =
        $preguntaActual['imagen_tipo']
        ?? null;

    $imagenAnterior =
        $preguntaActual['imagen']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Preparar imagen
    |--------------------------------------------------------------------------
    */

    $imagenTipoNueva =
        $imagenTipoAnterior;

    $imagenNueva =
        $imagenAnterior;

    $archivoNuevoFisico =
        null;

    $eliminarArchivoAnterior =
        false;


    /*
    |--------------------------------------------------------------------------
    | Mantener imagen
    |--------------------------------------------------------------------------
    */

    if (
        $accionImagen ===
        'mantener'
    ) {

        $imagenTipoNueva =
            $imagenTipoAnterior;

        $imagenNueva =
            $imagenAnterior;
    }


    /*
    |--------------------------------------------------------------------------
    | Eliminar imagen
    |--------------------------------------------------------------------------
    */

    if (
        $accionImagen === 'eliminar' ||
        $accionImagen === 'sin_imagen'
    ) {

        if (
            $imagenTipoAnterior ===
                'archivo' &&
            !empty($imagenAnterior)
        ) {

            $eliminarArchivoAnterior =
                true;
        }


        $imagenTipoNueva = null;
        $imagenNueva = null;
    }


    /*
    |--------------------------------------------------------------------------
    | Nueva imagen desde URL
    |--------------------------------------------------------------------------
    */

    if (
        $accionImagen === 'url'
    ) {

        $imagenUrl =
            isset($_POST['imagen_url'])
                ? trim(
                    (string)$_POST[
                        'imagen_url'
                    ]
                )
                : '';


        if ($imagenUrl === '') {

            die(
                'Debe ingresar la URL de la imagen.'
            );
        }


        if (
            !filter_var(
                $imagenUrl,
                FILTER_VALIDATE_URL
            )
        ) {

            die(
                'La URL de la imagen no es válida.'
            );
        }


        if (
            $imagenTipoAnterior ===
                'archivo' &&
            !empty($imagenAnterior)
        ) {

            $eliminarArchivoAnterior =
                true;
        }


        $imagenTipoNueva = 'url';
        $imagenNueva = $imagenUrl;
    }


    /*
    |--------------------------------------------------------------------------
    | Subir nueva imagen
    |--------------------------------------------------------------------------
    */

    if (
        $accionImagen === 'archivo'
    ) {

        if (
            !isset(
                $_FILES['imagen_archivo']
            ) ||
            $_FILES['imagen_archivo']['error']
                !== UPLOAD_ERR_OK
        ) {

            die(
                'Debe seleccionar una imagen válida.'
            );
        }


        $archivo =
            $_FILES['imagen_archivo'];


        /*
        |--------------------------------------------------------------------------
        | Máximo 5 MB
        |--------------------------------------------------------------------------
        */

        if (
            $archivo['size'] >
            5 * 1024 * 1024
        ) {

            die(
                'La imagen no puede superar los 5 MB.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | MIME real
        |--------------------------------------------------------------------------
        */

        $finfo =
            new finfo(
                FILEINFO_MIME_TYPE
            );

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
                $extensionesPermitidas[
                    $mime
                ]
            )
        ) {

            die(
                'Formato de imagen no permitido.'
            );
        }


        $extension =
            $extensionesPermitidas[
                $mime
            ];


        /*
        |--------------------------------------------------------------------------
        | Carpeta
        |--------------------------------------------------------------------------
        */

        $carpetaFisica =
            BASE_PATH .
            '/uploads/preguntas';


        if (
            !is_dir(
                $carpetaFisica
            )
        ) {

            if (
                !mkdir(
                    $carpetaFisica,
                    0755,
                    true
                )
            ) {

                die(
                    'No se pudo crear la carpeta de imágenes.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Nombre único
        |--------------------------------------------------------------------------
        */

        $nombreArchivo =
            'pregunta_' .
            $examenId .
            '_' .
            bin2hex(
                random_bytes(8)
            ) .
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
                'No se pudo guardar la nueva imagen.'
            );
        }


        $imagenTipoNueva =
            'archivo';

        $imagenNueva =
            'uploads/preguntas/' .
            $nombreArchivo;


        /*
        |--------------------------------------------------------------------------
        | Eliminar anterior después del commit
        |--------------------------------------------------------------------------
        */

        if (
            $imagenTipoAnterior ===
                'archivo' &&
            !empty($imagenAnterior)
        ) {

            $eliminarArchivoAnterior =
                true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Configurar corrección
    |--------------------------------------------------------------------------
    */

    $esPreguntaSeleccion =
        (
            $tipo ===
                'opcion_multiple' ||

            $tipo ===
                'seleccion_multiple'
        );


    if ($esPreguntaSeleccion) {

        $respuestaCorrecta = '';
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
            $respuestaCorrecta === ''
        ) {

            die(
                'Debe escribir una respuesta esperada para el método seleccionado.'
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
            $_POST['opcion']
            ?? [];

        $opcionesIds =
            $_POST['opcion_id']
            ?? [];


        if (
            !is_array($opciones) ||
            !is_array($opcionesIds)
        ) {

            die(
                'Las opciones recibidas no son válidas.'
            );
        }


        foreach (
            $opciones
            as $indice => $texto
        ) {

            $opcionId =
                isset(
                    $opcionesIds[
                        $indice
                    ]
                )
                    ? (int)$opcionesIds[
                        $indice
                    ]
                    : 0;


            $opcionesProcesadas[] = [
                'indice' =>
                    (int)$indice,

                'id' =>
                    $opcionId,

                'texto' =>
                    trim(
                        (string)$texto
                    )
            ];
        }


        $opcionesConTexto =
            array_filter(
                $opcionesProcesadas,
                function ($opcion) {

                    return
                        $opcion['texto']
                        !== '';
                }
            );


        if (
            count(
                $opcionesConTexto
            ) < 2
        ) {

            die(
                'Debe escribir por lo menos dos opciones.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Selección única
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
            'opcion_multiple'
        ) {

            if (
                !isset(
                    $_POST['correcta']
                )
            ) {

                die(
                    'Debe seleccionar una respuesta correcta.'
                );
            }


            $indicesCorrectos = [
                (int)$_POST['correcta']
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Selección múltiple
        |--------------------------------------------------------------------------
        */

        if (
            $tipo ===
            'seleccion_multiple'
        ) {

            $correctasRecibidas =
                $_POST['correctas']
                ?? [];


            if (
                !is_array(
                    $correctasRecibidas
                )
            ) {

                die(
                    'Las respuestas correctas no son válidas.'
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
                count(
                    $indicesCorrectos
                ) < 2
            ) {

                die(
                    'Debe seleccionar por lo menos dos respuestas correctas.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Verificar índices correctos
        |--------------------------------------------------------------------------
        */

        $indicesConTexto = [];


        foreach (
            $opcionesConTexto
            as $opcion
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
                    'Una opción marcada como correcta está vacía o no existe.'
                );
            }
        }


        if (
            $tipo ===
                'seleccion_multiple' &&
            count(
                $indicesCorrectos
            ) ===
            count(
                $opcionesConTexto
            )
        ) {

            die(
                'En selección múltiple debe existir por lo menos una opción incorrecta.'
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
        |--------------------------------------------------------------------------
        | Actualizar pregunta
        |--------------------------------------------------------------------------
        */

        $stmtPregunta =
            $conn->prepare("
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


        if (!$stmtPregunta) {

            throw new Exception(
                'No se pudo preparar la actualización.'
            );
        }


        $stmtPregunta->bind_param(
            'sssdsssii',
            $tipo,
            $textoPregunta,
            $respuestaCorrecta,
            $puntaje,
            $metodo,
            $imagenTipoNueva,
            $imagenNueva,
            $idPregunta,
            $examenId
        );


        if (
            !$stmtPregunta->execute()
        ) {

            throw new Exception(
                'No se pudo actualizar la pregunta.'
            );
        }


        $stmtPregunta->close();


        /*
        |--------------------------------------------------------------------------
        | Ya no es pregunta de selección
        |--------------------------------------------------------------------------
        */

        if (!$esPreguntaSeleccion) {

            $stmtEliminarOpciones =
                $conn->prepare("
                    DELETE FROM opciones
                    WHERE pregunta_id = ?
                ");


            if (!$stmtEliminarOpciones) {

                throw new Exception(
                    'No se pudieron preparar las opciones.'
                );
            }


            $stmtEliminarOpciones
                ->bind_param(
                    'i',
                    $idPregunta
                );


            if (
                !$stmtEliminarOpciones
                    ->execute()
            ) {

                throw new Exception(
                    'No se pudieron eliminar las opciones anteriores.'
                );
            }


            $stmtEliminarOpciones
                ->close();

        } else {

            /*
            |--------------------------------------------------------------------------
            | Opciones actuales
            |--------------------------------------------------------------------------
            */

            $stmtOpcionesActuales =
                $conn->prepare("
                    SELECT id
                    FROM opciones
                    WHERE pregunta_id = ?
                ");


            if (!$stmtOpcionesActuales) {

                throw new Exception(
                    'No se pudieron consultar las opciones actuales.'
                );
            }


            $stmtOpcionesActuales
                ->bind_param(
                    'i',
                    $idPregunta
                );

            $stmtOpcionesActuales
                ->execute();


            $resultadoOpcionesActuales =
                $stmtOpcionesActuales
                    ->get_result();


            $idsOpcionesActuales = [];


            while (
                $filaOpcion =
                    $resultadoOpcionesActuales
                        ->fetch_assoc()
            ) {

                $idsOpcionesActuales[] =
                    (int)$filaOpcion['id'];
            }


            $stmtOpcionesActuales
                ->close();


            /*
            |--------------------------------------------------------------------------
            | Preparar consultas
            |--------------------------------------------------------------------------
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


            if (
                !$stmtActualizarOpcion ||
                !$stmtInsertarOpcion ||
                !$stmtEliminarOpcion
            ) {

                throw new Exception(
                    'No se pudieron preparar las operaciones de opciones.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Opciones recibidas
            |--------------------------------------------------------------------------
            */

            $idsRecibidos = [];


            foreach (
                $opcionesProcesadas
                as $opcion
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
                |--------------------------------------------------------------------------
                | Opción existente
                |--------------------------------------------------------------------------
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
                            'Una opción no pertenece a esta pregunta.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Existente vacía: eliminar
                    |--------------------------------------------------------------------------
                    */

                    if ($texto === '') {

                        $stmtEliminarOpcion
                            ->bind_param(
                                'ii',
                                $opcionId,
                                $idPregunta
                            );


                        if (
                            !$stmtEliminarOpcion
                                ->execute()
                        ) {

                            throw new Exception(
                                'No se pudo eliminar una opción vacía.'
                            );
                        }


                        continue;
                    }


                    $idsRecibidos[] =
                        $opcionId;


                    $stmtActualizarOpcion
                        ->bind_param(
                            'siii',
                            $texto,
                            $esCorrecta,
                            $opcionId,
                            $idPregunta
                        );


                    if (
                        !$stmtActualizarOpcion
                            ->execute()
                    ) {

                        throw new Exception(
                            'No se pudo actualizar una opción.'
                        );
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Nueva opción
                    |--------------------------------------------------------------------------
                    */

                    if ($texto === '') {

                        continue;
                    }


                    $stmtInsertarOpcion
                        ->bind_param(
                            'isi',
                            $idPregunta,
                            $texto,
                            $esCorrecta
                        );


                    if (
                        !$stmtInsertarOpcion
                            ->execute()
                    ) {

                        throw new Exception(
                            'No se pudo agregar una opción.'
                        );
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Eliminar opciones quitadas desde la interfaz
            |--------------------------------------------------------------------------
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
                            'ii',
                            $idActual,
                            $idPregunta
                        );


                    if (
                        !$stmtEliminarOpcion
                            ->execute()
                    ) {

                        throw new Exception(
                            'No se pudo eliminar una opción.'
                        );
                    }
                }
            }


            $stmtActualizarOpcion
                ->close();

            $stmtInsertarOpcion
                ->close();

            $stmtEliminarOpcion
                ->close();
        }


        /*
        |--------------------------------------------------------------------------
        | Confirmar
        |--------------------------------------------------------------------------
        */

        $conn->commit();


        /*
        |--------------------------------------------------------------------------
        | Eliminar archivo anterior SOLO después del commit
        |--------------------------------------------------------------------------
        */

        if (
            $eliminarArchivoAnterior &&
            $imagenTipoAnterior ===
                'archivo' &&
            !empty($imagenAnterior)
        ) {

            $rutaAnterior =
                BASE_PATH .
                '/' .
                $imagenAnterior;


            $carpetaPermitida =
                realpath(
                    BASE_PATH .
                    '/uploads/preguntas'
                );

            $carpetaArchivo =
                realpath(
                    dirname(
                        $rutaAnterior
                    )
                );


            if (
                is_file(
                    $rutaAnterior
                ) &&
                $carpetaPermitida !== false &&
                $carpetaArchivo ===
                    $carpetaPermitida
            ) {

                @unlink(
                    $rutaAnterior
                );
            }
        }


    } catch (Throwable $error) {

        $conn->rollback();


        /*
        |--------------------------------------------------------------------------
        | Si falló BD, eliminar nueva imagen
        |--------------------------------------------------------------------------
        */

        if (
            $archivoNuevoFisico !==
                null &&
            is_file(
                $archivoNuevoFisico
            )
        ) {

            @unlink(
                $archivoNuevoFisico
            );
        }


        die(
            'Ocurrió un error al actualizar la pregunta: ' .
            htmlspecialchars(
                $error->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Volver al listado
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/formularios/' .
        $examenId .
        '/preguntas'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Eliminar pregunta
|--------------------------------------------------------------------------
*/

public function eliminarPregunta(
    string $id,
    string $preguntaId
): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Parámetros
    |--------------------------------------------------------------------------
    */

    $examenId =
        (int)$id;

    $idPregunta =
        (int)$preguntaId;


    if (
        $examenId <= 0 ||
        $idPregunta <= 0
    ) {

        http_response_code(400);

        die(
            'Pregunta inválida.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Obtener pregunta e imagen
    |--------------------------------------------------------------------------
    */

    $stmtPregunta =
        $conn->prepare("
            SELECT
                imagen_tipo,
                imagen
            FROM preguntas
            WHERE id = ?
              AND examen_id = ?
            LIMIT 1
        ");


    if (!$stmtPregunta) {

        http_response_code(500);

        die(
            'No se pudo consultar la pregunta.'
        );
    }


    $stmtPregunta->bind_param(
        'ii',
        $idPregunta,
        $examenId
    );

    $stmtPregunta->execute();

    $resultadoPregunta =
        $stmtPregunta->get_result();

    $pregunta =
        $resultadoPregunta->fetch_assoc();

    $stmtPregunta->close();


    if (!$pregunta) {

        http_response_code(404);

        die(
            'Pregunta no encontrada.'
        );
    }


    $imagenTipo =
        $pregunta['imagen_tipo']
        ?? null;

    $imagen =
        $pregunta['imagen']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Transacción
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();


    try {

        /*
        |--------------------------------------------------------------------------
        | Eliminar opciones
        |--------------------------------------------------------------------------
        */

        $stmtOpciones =
            $conn->prepare("
                DELETE FROM opciones
                WHERE pregunta_id = ?
            ");


        if (!$stmtOpciones) {

            throw new Exception(
                'No se pudo preparar la eliminación de opciones.'
            );
        }


        $stmtOpciones->bind_param(
            'i',
            $idPregunta
        );


        if (
            !$stmtOpciones->execute()
        ) {

            throw new Exception(
                'No se pudieron eliminar las opciones.'
            );
        }


        $stmtOpciones->close();


        /*
        |--------------------------------------------------------------------------
        | Eliminar relaciones con intentos
        |--------------------------------------------------------------------------
        */

        $stmtIntentoPreguntas =
            $conn->prepare("
                DELETE FROM intento_preguntas
                WHERE pregunta_id = ?
            ");


        if (!$stmtIntentoPreguntas) {

            throw new Exception(
                'No se pudo preparar la eliminación de referencias.'
            );
        }


        $stmtIntentoPreguntas
            ->bind_param(
                'i',
                $idPregunta
            );


        if (
            !$stmtIntentoPreguntas
                ->execute()
        ) {

            throw new Exception(
                'No se pudieron eliminar las referencias de la pregunta.'
            );
        }


        $stmtIntentoPreguntas
            ->close();


        /*
        |--------------------------------------------------------------------------
        | Eliminar respuestas
        |--------------------------------------------------------------------------
        */

        $stmtRespuestas =
            $conn->prepare("
                DELETE FROM respuestas
                WHERE pregunta_id = ?
            ");


        if (!$stmtRespuestas) {

            throw new Exception(
                'No se pudo preparar la eliminación de respuestas.'
            );
        }


        $stmtRespuestas->bind_param(
            'i',
            $idPregunta
        );


        if (
            !$stmtRespuestas->execute()
        ) {

            throw new Exception(
                'No se pudieron eliminar las respuestas.'
            );
        }


        $stmtRespuestas->close();


        /*
        |--------------------------------------------------------------------------
        | Eliminar pregunta
        |--------------------------------------------------------------------------
        */

        $stmtEliminar =
            $conn->prepare("
                DELETE FROM preguntas
                WHERE id = ?
                  AND examen_id = ?
            ");


        if (!$stmtEliminar) {

            throw new Exception(
                'No se pudo preparar la eliminación de la pregunta.'
            );
        }


        $stmtEliminar->bind_param(
            'ii',
            $idPregunta,
            $examenId
        );


        if (
            !$stmtEliminar->execute()
        ) {

            throw new Exception(
                'No se pudo eliminar la pregunta.'
            );
        }


        $stmtEliminar->close();


        /*
        |--------------------------------------------------------------------------
        | Confirmar BD
        |--------------------------------------------------------------------------
        */

        $conn->commit();


        /*
        |--------------------------------------------------------------------------
        | Eliminar imagen física después del commit
        |--------------------------------------------------------------------------
        */

        if (
            $imagenTipo === 'archivo' &&
            !empty($imagen)
        ) {

            $ruta =
                BASE_PATH .
                '/' .
                ltrim(
                    $imagen,
                    '/'
                );


            $carpetaPermitida =
                realpath(
                    BASE_PATH .
                    '/uploads/preguntas'
                );

            $carpetaArchivo =
                realpath(
                    dirname($ruta)
                );


            if (
                is_file($ruta) &&
                $carpetaPermitida !== false &&
                $carpetaArchivo ===
                    $carpetaPermitida
            ) {

                @unlink($ruta);
            }
        }


    } catch (Throwable $error) {

        $conn->rollback();


        die(
            'No se pudo eliminar la pregunta: ' .
            htmlspecialchars(
                $error->getMessage(),
                ENT_QUOTES,
                'UTF-8'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Volver al listado
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/formularios/' .
        $examenId .
        '/preguntas'
    );

    exit;
}
}
