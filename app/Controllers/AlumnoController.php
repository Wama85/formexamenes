<?php

declare(strict_types=1);

class AlumnoController
{
    public function resolver(string $id): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validar sesión
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

        /*
        |--------------------------------------------------------------------------
        | Validar rol
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_SESSION['rol']) ||
            $_SESSION['rol'] !== 'alumno'
        ) {
            http_response_code(403);
            die('Acceso denegado.');
        }

        $usuarioId =
            (int)$_SESSION['usuario_id'];


        /*
        |--------------------------------------------------------------------------
        | Validar formulario
        |--------------------------------------------------------------------------
        */

        $examenId = (int)$id;

        if ($examenId <= 0) {
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
        | Obtener formulario publicado
        |--------------------------------------------------------------------------
        */

        $stmtExamen = $conn->prepare("
            SELECT *
            FROM examenes
            WHERE id = ?
              AND estado = 'publicado'
            LIMIT 1
        ");

        if (!$stmtExamen) {
            die(
                'No se pudo preparar la consulta del formulario.'
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
            die(
                'El formulario no existe o ya no está disponible.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Cantidad de intentos
        |--------------------------------------------------------------------------
        */

        $cantidadIntentos =
            (int)$examen['cantidad_intentos'];

        if ($cantidadIntentos < 1) {
            $cantidadIntentos = 1;
        }


        /*
        |--------------------------------------------------------------------------
        | Tipo de tiempo
        |--------------------------------------------------------------------------
        */

        $tipoTiempo =
            $examen['tipo_tiempo']
            ?? 'individual';

        $fechaHoraLimite =
            $examen['fecha_hora_limite']
            ?? null;


        /*
        |--------------------------------------------------------------------------
        | Verificar hora límite global
        |--------------------------------------------------------------------------
        */

        if ($tipoTiempo === 'limite') {

            if (empty($fechaHoraLimite)) {
                die(
                    'Este formulario no tiene configurada ' .
                    'correctamente la fecha y hora límite.'
                );
            }

            $limiteTimestamp =
                strtotime($fechaHoraLimite);

            if ($limiteTimestamp === false) {
                die(
                    'La fecha y hora límite del formulario ' .
                    'no es válida.'
                );
            }

            if (time() >= $limiteTimestamp) {
                die(
                    'El tiempo de este formulario ya terminó. ' .
                    'Ya no puede iniciar ni continuar un intento.'
                );
            }
        }

       /*
|--------------------------------------------------------------------------
| Buscar intento abierto
|--------------------------------------------------------------------------
*/

$stmtIntentoAbierto = $conn->prepare("
    SELECT
        id,
        fecha_inicio,
        cambios_pestana,
        numero_intento
    FROM intentos
    WHERE usuario_id = ?
      AND examen_id = ?
      AND finalizado = 0
    ORDER BY id DESC
    LIMIT 1
");

if (!$stmtIntentoAbierto) {
    die(
        'No se pudo preparar la consulta del intento.'
    );
}

$stmtIntentoAbierto->bind_param(
    'ii',
    $usuarioId,
    $examenId
);

$stmtIntentoAbierto->execute();

$resultadoIntentoAbierto =
    $stmtIntentoAbierto->get_result();

$intentoAbierto =
    $resultadoIntentoAbierto->fetch_assoc();

$stmtIntentoAbierto->close();


/*
|--------------------------------------------------------------------------
| Continuar intento existente
|--------------------------------------------------------------------------
*/

if ($intentoAbierto) {

    $intentoId =
        (int)$intentoAbierto['id'];

    $fechaInicio =
        $intentoAbierto['fecha_inicio'];

    $cambiosGuardados =
        isset($intentoAbierto['cambios_pestana'])
        ? (int)$intentoAbierto['cambios_pestana']
        : 0;

    $numeroIntento =
        (int)$intentoAbierto['numero_intento'];

} else {

    /*
    |--------------------------------------------------------------------------
    | Buscar último número de intento utilizado
    |--------------------------------------------------------------------------
    */

    $stmtCantidadIntentos = $conn->prepare("
        SELECT
            COALESCE(
                MAX(numero_intento),
                0
            ) AS ultimo_intento
        FROM intentos
        WHERE usuario_id = ?
          AND examen_id = ?
    ");

    if (!$stmtCantidadIntentos) {
        die(
            'No se pudo consultar la cantidad de intentos.'
        );
    }

    $stmtCantidadIntentos->bind_param(
        'ii',
        $usuarioId,
        $examenId
    );

    $stmtCantidadIntentos->execute();

    $resultadoCantidadIntentos =
        $stmtCantidadIntentos->get_result();

    $datosIntentos =
        $resultadoCantidadIntentos->fetch_assoc();

    $stmtCantidadIntentos->close();

    $ultimoIntento =
        (int)$datosIntentos['ultimo_intento'];


    /*
    |--------------------------------------------------------------------------
    | Verificar límite de intentos
    |--------------------------------------------------------------------------
    */

    if (
        $ultimoIntento >=
        $cantidadIntentos
    ) {
        die(
            'Ya utilizó los ' .
            $cantidadIntentos .
            ' intentos permitidos para este formulario. ' .
            'Ahora solo puede consultar su nota.'
        );
    }

    $numeroIntento =
        $ultimoIntento + 1;


    /*
    |--------------------------------------------------------------------------
    | Crear nuevo intento
    |--------------------------------------------------------------------------
    */

    $ip =
        $_SERVER['REMOTE_ADDR']
        ?? '';

    $navegador =
        $_SERVER['HTTP_USER_AGENT']
        ?? '';

    $stmtCrearIntento = $conn->prepare("
        INSERT INTO intentos (
            usuario_id,
            examen_id,
            fecha_inicio,
            ip_publica,
            navegador,
            finalizado,
            numero_intento
        )
        VALUES (
            ?,
            ?,
            NOW(),
            ?,
            ?,
            0,
            ?
        )
    ");

    if (!$stmtCrearIntento) {
        die(
            'No se pudo preparar la creación del intento.'
        );
    }

    $stmtCrearIntento->bind_param(
        'iissi',
        $usuarioId,
        $examenId,
        $ip,
        $navegador,
        $numeroIntento
    );

    if (!$stmtCrearIntento->execute()) {
        die(
            'No se pudo crear el intento.'
        );
    }

    $intentoId =
        (int)$stmtCrearIntento->insert_id;

    $stmtCrearIntento->close();

    $fechaInicio =
        date('Y-m-d H:i:s');

    $cambiosGuardados = 0;
}


/*
|--------------------------------------------------------------------------
| Verificar preguntas asignadas al intento
|--------------------------------------------------------------------------
*/

$stmtCantidadAsignadas = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM intento_preguntas
    WHERE intento_id = ?
");

if (!$stmtCantidadAsignadas) {
    die(
        'No se pudo consultar las preguntas asignadas.'
    );
}

$stmtCantidadAsignadas->bind_param(
    'i',
    $intentoId
);

$stmtCantidadAsignadas->execute();

$resultadoCantidadAsignadas =
    $stmtCantidadAsignadas->get_result();

$datosCantidadAsignadas =
    $resultadoCantidadAsignadas->fetch_assoc();

$stmtCantidadAsignadas->close();

$totalAsignadas =
    (int)$datosCantidadAsignadas['total'];


/*
|--------------------------------------------------------------------------
| Asignar preguntas aleatorias una sola vez
|--------------------------------------------------------------------------
*/

if ($totalAsignadas === 0) {

    $cantidadPreguntas =
        isset($examen['cantidad_preguntas'])
        ? (int)$examen['cantidad_preguntas']
        : 8;

    if ($cantidadPreguntas < 1) {
        $cantidadPreguntas = 8;
    }


    /*
    |--------------------------------------------------------------------------
    | Verificar preguntas disponibles
    |--------------------------------------------------------------------------
    */

    $stmtTotalPreguntas = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM preguntas
        WHERE examen_id = ?
    ");

    if (!$stmtTotalPreguntas) {
        die(
            'No se pudo consultar las preguntas disponibles.'
        );
    }

    $stmtTotalPreguntas->bind_param(
        'i',
        $examenId
    );

    $stmtTotalPreguntas->execute();

    $resultadoTotalPreguntas =
        $stmtTotalPreguntas->get_result();

    $datosTotalPreguntas =
        $resultadoTotalPreguntas->fetch_assoc();

    $stmtTotalPreguntas->close();

    $totalDisponibles =
        (int)$datosTotalPreguntas['total'];


    /*
    |--------------------------------------------------------------------------
    | Formulario sin preguntas
    |--------------------------------------------------------------------------
    */

    if ($totalDisponibles === 0) {

        $stmtEliminarIntento = $conn->prepare("
            DELETE FROM intentos
            WHERE id = ?
              AND usuario_id = ?
              AND finalizado = 0
        ");

        if ($stmtEliminarIntento) {

            $stmtEliminarIntento->bind_param(
                'ii',
                $intentoId,
                $usuarioId
            );

            $stmtEliminarIntento->execute();

            $stmtEliminarIntento->close();
        }

        die(
            'Este formulario todavía no tiene preguntas.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | No solicitar más preguntas de las disponibles
    |--------------------------------------------------------------------------
    */

    if (
        $cantidadPreguntas >
        $totalDisponibles
    ) {
        $cantidadPreguntas =
            $totalDisponibles;
    }


    /*
    |--------------------------------------------------------------------------
    | Seleccionar preguntas aleatorias
    |--------------------------------------------------------------------------
    */

    $stmtPreguntasAleatorias = $conn->prepare("
        SELECT id
        FROM preguntas
        WHERE examen_id = ?
        ORDER BY RAND()
        LIMIT ?
    ");

    if (!$stmtPreguntasAleatorias) {
        die(
            'No se pudo seleccionar las preguntas.'
        );
    }

    $stmtPreguntasAleatorias->bind_param(
        'ii',
        $examenId,
        $cantidadPreguntas
    );

    $stmtPreguntasAleatorias->execute();

    $resultadoPreguntasAleatorias =
        $stmtPreguntasAleatorias->get_result();


    /*
    |--------------------------------------------------------------------------
    | Guardar preguntas asignadas
    |--------------------------------------------------------------------------
    */

    $stmtGuardarPregunta = $conn->prepare("
        INSERT INTO intento_preguntas (
            intento_id,
            pregunta_id,
            orden
        )
        VALUES (?, ?, ?)
    ");

    if (!$stmtGuardarPregunta) {
        die(
            'No se pudo preparar la asignación de preguntas.'
        );
    }

    $orden = 1;

    while (
        $filaPregunta =
        $resultadoPreguntasAleatorias->fetch_assoc()
    ) {

        $preguntaId =
            (int)$filaPregunta['id'];

        $stmtGuardarPregunta->bind_param(
            'iii',
            $intentoId,
            $preguntaId,
            $orden
        );

        if (!$stmtGuardarPregunta->execute()) {
            die(
                'No se pudieron asignar las preguntas del formulario.'
            );
        }

        $orden++;
    }

    $stmtGuardarPregunta->close();
    $stmtPreguntasAleatorias->close();
}


/*
|--------------------------------------------------------------------------
| Cargar preguntas asignadas al intento
|--------------------------------------------------------------------------
*/

$stmtPreguntas = $conn->prepare("
    SELECT
        p.*,
        ip.orden
    FROM intento_preguntas ip
    INNER JOIN preguntas p
        ON p.id = ip.pregunta_id
    WHERE ip.intento_id = ?
    ORDER BY ip.orden ASC
");

if (!$stmtPreguntas) {
    die(
        'No se pudieron preparar las preguntas del intento.'
    );
}

$stmtPreguntas->bind_param(
    'i',
    $intentoId
);

$stmtPreguntas->execute();

$resultadoPreguntas =
    $stmtPreguntas->get_result();


/*
|--------------------------------------------------------------------------
| Guardar preguntas en un array
|--------------------------------------------------------------------------
*/

$preguntas = [];

while (
    $filaPregunta =
    $resultadoPreguntas->fetch_assoc()
) {
    $preguntas[] =
        $filaPregunta;
}

$stmtPreguntas->close();

if (empty($preguntas)) {
    die(
        'No existen preguntas asignadas a este intento.'
    );
}


/*
|--------------------------------------------------------------------------
| Recuperar respuestas autoguardadas
|--------------------------------------------------------------------------
*/

$respuestasGuardadas = [];

$stmtRespuestasGuardadas = $conn->prepare("
    SELECT
        pregunta_id,
        respuesta
    FROM respuestas
    WHERE intento_id = ?
");

if (!$stmtRespuestasGuardadas) {
    die(
        'No se pudieron recuperar las respuestas guardadas.'
    );
}

$stmtRespuestasGuardadas->bind_param(
    'i',
    $intentoId
);

$stmtRespuestasGuardadas->execute();

$resultadoRespuestasGuardadas =
    $stmtRespuestasGuardadas->get_result();

while (
    $filaRespuesta =
    $resultadoRespuestasGuardadas->fetch_assoc()
) {

    $preguntaIdGuardada =
        (int)$filaRespuesta['pregunta_id'];

    $respuestasGuardadas[
        $preguntaIdGuardada
    ] = $filaRespuesta['respuesta'];
}

$stmtRespuestasGuardadas->close();


/*
|--------------------------------------------------------------------------
| Calcular tiempo restante
|--------------------------------------------------------------------------
*/

if ($tipoTiempo === 'limite') {

    /*
    |--------------------------------------------------------------------------
    | Hora límite global
    |--------------------------------------------------------------------------
    */

    $limiteTimestamp =
        strtotime($fechaHoraLimite);

    $tiempoRestante =
        $limiteTimestamp - time();

} else {

    /*
    |--------------------------------------------------------------------------
    | Tiempo individual
    |--------------------------------------------------------------------------
    */

    $tiempoMinutos =
        (int)$examen['tiempo_minutos'];

    if ($tiempoMinutos < 1) {
        $tiempoMinutos = 1;
    }

    $tiempoTotalSegundos =
        $tiempoMinutos * 60;

    $inicioTimestamp =
        strtotime($fechaInicio);

    if ($inicioTimestamp === false) {
        $inicioTimestamp = time();
    }

    $segundosTranscurridos =
        time() - $inicioTimestamp;

    $tiempoRestante =
        $tiempoTotalSegundos -
        $segundosTranscurridos;
}


/*
|--------------------------------------------------------------------------
| Evitar tiempo negativo
|--------------------------------------------------------------------------
*/

if ($tiempoRestante < 0) {
    $tiempoRestante = 0;
}


/*
|--------------------------------------------------------------------------
| Cargar vista
|--------------------------------------------------------------------------
*/

require BASE_PATH .
    '/views/alumno/resolver.php';
    }

public function autoguardar(): void
{
    /*
    |--------------------------------------------------------------------------
    | Respuesta JSON
    |--------------------------------------------------------------------------
    */

    header(
        'Content-Type: application/json; charset=utf-8'
    );


    /*
    |--------------------------------------------------------------------------
    | Validar sesión
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Sesión no válida.'
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Validar rol
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_SESSION['rol']) ||
        $_SESSION['rol'] !== 'alumno'
    ) {

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Acceso no autorizado.'
        ]);

        exit;
    }

    $usuarioId =
        (int)$_SESSION['usuario_id'];


    /*
    |--------------------------------------------------------------------------
    | Recibir datos
    |--------------------------------------------------------------------------
    */

    $intentoId =
        isset($_POST['intento_id'])
        ? (int)$_POST['intento_id']
        : 0;

    $preguntaId =
        isset($_POST['pregunta_id'])
        ? (int)$_POST['pregunta_id']
        : 0;

    $respuesta =
        $_POST['respuesta']
        ?? '';


    if (
        $intentoId <= 0 ||
        $preguntaId <= 0
    ) {

        echo json_encode([
            'ok' => false,
            'mensaje' => 'Datos inválidos.'
        ]);

        exit;
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
    | Verificar intento y pregunta
    |--------------------------------------------------------------------------
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

    if (!$stmt) {

        echo json_encode([
            'ok' => false,
            'mensaje' =>
                'No se pudo validar el intento.'
        ]);

        exit;
    }

    $stmt->bind_param(
        'iii',
        $intentoId,
        $usuarioId,
        $preguntaId
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    if (!$resultado->fetch_assoc()) {

        $stmt->close();

        echo json_encode([
            'ok' => false,
            'mensaje' =>
                'Intento o pregunta no válidos.'
        ]);

        exit;
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Normalizar respuesta
    |--------------------------------------------------------------------------
    */

    if (is_array($respuesta)) {

        $respuesta =
            array_values(
                array_unique(
                    array_map(
                        'intval',
                        $respuesta
                    )
                )
            );

        $respuesta =
            json_encode(
                $respuesta,
                JSON_UNESCAPED_UNICODE
            );

    } else {

        $respuesta =
            trim(
                (string)$respuesta
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Autoguardar
    |--------------------------------------------------------------------------
    */

    $correcta = null;

    $puntajeObtenido = null;

    $observacion =
        'Respuesta autoguardada. ' .
        'Pendiente de finalizar.';


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

    if (!$stmtGuardar) {

        echo json_encode([
            'ok' => false,
            'mensaje' =>
                'No se pudo preparar el autoguardado.'
        ]);

        exit;
    }

    $stmtGuardar->bind_param(
        'iisids',
        $intentoId,
        $preguntaId,
        $respuesta,
        $correcta,
        $puntajeObtenido,
        $observacion
    );


    if (!$stmtGuardar->execute()) {

        $stmtGuardar->close();

        echo json_encode([
            'ok' => false,
            'mensaje' =>
                'No se pudo autoguardar.'
        ]);

        exit;
    }

    $stmtGuardar->close();


    /*
    |--------------------------------------------------------------------------
    | Respuesta correcta
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        'ok' => true
    ]);

    exit;
}
public function finalizar(): void
{
    /*
    |--------------------------------------------------------------------------
    | Validar sesión
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
        !isset($_SESSION['rol']) ||
        $_SESSION['rol'] !== 'alumno'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }

    $usuarioId =
        (int)$_SESSION['usuario_id'];


    /*
    |--------------------------------------------------------------------------
    | Validar intento recibido
    |--------------------------------------------------------------------------
    */

    $intentoId =
        isset($_POST['intento_id'])
            ? (int)$_POST['intento_id']
            : 0;

    if ($intentoId <= 0) {

        die('Intento inválido.');
    }


    /*
    |--------------------------------------------------------------------------
    | Cambios de pantalla
    |--------------------------------------------------------------------------
    */

    $cambiosPestana =
        isset($_POST['cambios_pestana'])
            ? (int)$_POST['cambios_pestana']
            : 0;

    if ($cambiosPestana < 0) {

        $cambiosPestana = 0;
    }

    if ($cambiosPestana > 2) {

        $cambiosPestana = 2;
    }


    /*
    |--------------------------------------------------------------------------
    | Dependencias
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';

    require_once BASE_PATH .
        '/ollama.php';


    /*
    |--------------------------------------------------------------------------
    | Iniciar transacción
    |--------------------------------------------------------------------------
    */

    $conn->begin_transaction();

    try {

        /*
        |--------------------------------------------------------------------------
        | Verificar intento
        |--------------------------------------------------------------------------
        */

        $stmtVerificarIntento =
            $conn->prepare("
                SELECT
                    i.id,
                    i.examen_id,
                    i.finalizado,
                    i.fecha_inicio,
                    e.tipo_tiempo,
                    e.tiempo_minutos,
                    e.fecha_hora_limite
                FROM intentos i

                INNER JOIN examenes e
                    ON e.id = i.examen_id

                WHERE i.id = ?
                  AND i.usuario_id = ?

                LIMIT 1
                FOR UPDATE
            ");

        if (!$stmtVerificarIntento) {

            throw new Exception(
                'No se pudo verificar el intento.'
            );
        }

        $stmtVerificarIntento->bind_param(
            'ii',
            $intentoId,
            $usuarioId
        );

        $stmtVerificarIntento->execute();

        $resultadoIntento =
            $stmtVerificarIntento
                ->get_result();

        $intento =
            $resultadoIntento
                ->fetch_assoc();

        $stmtVerificarIntento->close();


        if (!$intento) {

            throw new Exception(
                'No tiene permiso para finalizar este intento.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Evitar doble finalización
        |--------------------------------------------------------------------------
        */

        if (
            (int)$intento['finalizado']
            === 1
        ) {

            $conn->rollback();

            header(
                'Location: ' .
                BASE_URL .
                '/formularios/disponibles'
            );

            exit;
        }


        $examenId =
            (int)$intento['examen_id'];


       /*
|--------------------------------------------------------------------------
| Verificar tiempo del formulario
|--------------------------------------------------------------------------
|
| Esta validación se realiza también en el servidor.
|
*/

$tipoTiempo =
    $intento['tipo_tiempo']
    ?? 'individual';

$fueraDeTiempo = false;


/*
|--------------------------------------------------------------------------
| Hora límite global
|--------------------------------------------------------------------------
*/

if ($tipoTiempo === 'limite') {

    $fechaHoraLimite =
        $intento['fecha_hora_limite']
        ?? null;

    if (empty($fechaHoraLimite)) {

        throw new Exception(
            'El formulario no tiene configurada correctamente ' .
            'la hora límite.'
        );
    }

    $limiteTimestamp =
        strtotime(
            $fechaHoraLimite
        );

    if ($limiteTimestamp === false) {

        throw new Exception(
            'La hora límite del formulario no es válida.'
        );
    }

    if (
        time() >=
        $limiteTimestamp
    ) {

        $fueraDeTiempo = true;
    }
}


/*
|--------------------------------------------------------------------------
| Tiempo individual
|--------------------------------------------------------------------------
*/

else {

    $tiempoMinutos =
        (int)(
            $intento['tiempo_minutos']
            ?? 0
        );

    if ($tiempoMinutos < 1) {

        $tiempoMinutos = 1;
    }

    $inicioTimestamp =
        strtotime(
            $intento['fecha_inicio']
        );

    if ($inicioTimestamp === false) {

        throw new Exception(
            'No se pudo determinar la hora de inicio del intento.'
        );
    }

    $finTimestamp =
        $inicioTimestamp +
        ($tiempoMinutos * 60);

    if (
        time() >=
        $finTimestamp
    ) {

        $fueraDeTiempo = true;
    }
}


/*
|--------------------------------------------------------------------------
| Recuperar respuestas autoguardadas
|--------------------------------------------------------------------------
|
| Si una respuesta no llegó mediante POST,
| recuperamos la versión autoguardada.
|
*/

$stmtAutoguardadas =
    $conn->prepare("
        SELECT
            r.pregunta_id,
            r.respuesta,
            p.tipo
        FROM respuestas r

        INNER JOIN preguntas p
            ON p.id = r.pregunta_id

        INNER JOIN intento_preguntas ip
            ON ip.intento_id = r.intento_id
           AND ip.pregunta_id = r.pregunta_id

        WHERE r.intento_id = ?
    ");

if (!$stmtAutoguardadas) {

    throw new Exception(
        'No se pudieron recuperar las respuestas autoguardadas.'
    );
}

$stmtAutoguardadas->bind_param(
    'i',
    $intentoId
);

$stmtAutoguardadas->execute();

$resultadoAutoguardadas =
    $stmtAutoguardadas
        ->get_result();


while (
    $respuestaGuardada =
    $resultadoAutoguardadas
        ->fetch_assoc()
) {

    $preguntaGuardadaId =
        (int)$respuestaGuardada[
            'pregunta_id'
        ];

    $campoGuardado =
        'pregunta_' .
        $preguntaGuardadaId;


    /*
    |--------------------------------------------------------------------------
    | El POST tiene prioridad
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_POST[$campoGuardado]
        )
    ) {
        continue;
    }


    $valorGuardado =
        $respuestaGuardada[
            'respuesta'
        ] ?? '';

    $tipoPreguntaGuardada =
        $respuestaGuardada[
            'tipo'
        ] ?? '';


    /*
    |--------------------------------------------------------------------------
    | Selección múltiple
    |--------------------------------------------------------------------------
    */

    if (
        $tipoPreguntaGuardada ===
        'seleccion_multiple'
    ) {

        $selecciones =
            json_decode(
                $valorGuardado,
                true
            );

        if (
            is_array(
                $selecciones
            )
        ) {

            $selecciones =
                array_values(
                    array_filter(
                        array_map(
                            'intval',
                            $selecciones
                        ),
                        function ($id) {
                            return $id > 0;
                        }
                    )
                );

            if (
                count(
                    $selecciones
                ) > 0
            ) {

                $_POST[
                    $campoGuardado
                ] = $selecciones;
            }
        }

        continue;
    }


    /*
    |--------------------------------------------------------------------------
    | Texto, código y selección única
    |--------------------------------------------------------------------------
    */

    if (
        trim(
            (string)$valorGuardado
        ) !== ''
    ) {

        $_POST[
            $campoGuardado
        ] = $valorGuardado;
    }
}

$stmtAutoguardadas->close();


/*
|--------------------------------------------------------------------------
| Eliminar respuestas autoguardadas
|--------------------------------------------------------------------------
|
| Ya recuperamos su contenido en $_POST.
| Ahora las eliminamos para guardar las respuestas
| corregidas definitivamente.
|
*/

$stmtEliminarRespuestas =
    $conn->prepare("
        DELETE FROM respuestas
        WHERE intento_id = ?
    ");

if (!$stmtEliminarRespuestas) {

    throw new Exception(
        'No se pudieron preparar las respuestas para la corrección.'
    );
}

$stmtEliminarRespuestas->bind_param(
    'i',
    $intentoId
);

$stmtEliminarRespuestas->execute();

$stmtEliminarRespuestas->close();


/*
|--------------------------------------------------------------------------
| Obtener preguntas asignadas
|--------------------------------------------------------------------------
*/

$stmtPreguntas =
    $conn->prepare("
        SELECT
            p.*
        FROM intento_preguntas ip

        INNER JOIN preguntas p
            ON p.id = ip.pregunta_id

        WHERE ip.intento_id = ?
          AND p.examen_id = ?

        ORDER BY ip.orden ASC
    ");

if (!$stmtPreguntas) {

    throw new Exception(
        'No se pudieron obtener las preguntas del intento.'
    );
}

$stmtPreguntas->bind_param(
    'ii',
    $intentoId,
    $examenId
);

$stmtPreguntas->execute();

$resultadoPreguntas =
    $stmtPreguntas->get_result();

if (
    $resultadoPreguntas->num_rows === 0
) {

    throw new Exception(
        'El intento no tiene preguntas asignadas.'
    );
}


/*
|--------------------------------------------------------------------------
| Preparar inserción de respuestas definitivas
|--------------------------------------------------------------------------
*/

$stmtGuardarRespuesta =
    $conn->prepare("
        INSERT INTO respuestas (
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido,
            observacion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

if (!$stmtGuardarRespuesta) {

    throw new Exception(
        'No se pudo preparar el guardado de las respuestas.'
    );
}


/*
|--------------------------------------------------------------------------
| Servicio de corrección
|--------------------------------------------------------------------------
*/

$correccionService =
    new CorreccionService();


/*
|--------------------------------------------------------------------------
| Variables de calificación
|--------------------------------------------------------------------------
*/

$nota = 0.0;

$puntajeTotal = 0.0;


/*
|--------------------------------------------------------------------------
| Recorrer preguntas
|--------------------------------------------------------------------------
*/

while (
    $pregunta =
    $resultadoPreguntas->fetch_assoc()
) {

    $preguntaId =
        (int)$pregunta['id'];

    $puntaje =
        (float)$pregunta['puntaje'];

    if ($puntaje < 0) {
        $puntaje = 0;
    }

    $puntajeTotal +=
        $puntaje;

    $campo =
        'pregunta_' .
        $preguntaId;

    $respuestaRecibida =
        $_POST[$campo]
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | Detectar pregunta sin responder
    |--------------------------------------------------------------------------
    */

    $sinResponder = false;

    if (
        $respuestaRecibida === null
    ) {

        $sinResponder = true;

    } elseif (
        is_array(
            $respuestaRecibida
        )
    ) {

        $respuestaRecibida =
            array_values(
                array_filter(
                    $respuestaRecibida,
                    function ($valor) {
                        return (int)$valor > 0;
                    }
                )
            );

        if (
            count(
                $respuestaRecibida
            ) === 0
        ) {

            $sinResponder = true;
        }

    } elseif (
        trim(
            (string)$respuestaRecibida
        ) === ''
    ) {

        $sinResponder = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Sin responder
    |--------------------------------------------------------------------------
    */

    if ($sinResponder) {

        $respuestaAlumno = '';

        $correcta = 0;

        $puntajeObtenido = 0.0;

        $observacion =
            'Pregunta sin responder.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | Código
        |--------------------------------------------------------------------------
        */

        if (
            $pregunta['tipo'] ===
            'codigo'
        ) {

            $resultadoCorreccion =
                $correccionService
                    ->corregirCodigo(
                        $pregunta,
                        (string)$respuestaRecibida
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Respuesta escrita
        |--------------------------------------------------------------------------
        */

        elseif (
            $pregunta['tipo'] ===
            'respuesta_texto'
        ) {

            $resultadoCorreccion =
                $correccionService
                    ->corregirRespuestaTexto(
                        $pregunta,
                        (string)$respuestaRecibida
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Selección múltiple
        |--------------------------------------------------------------------------
        */

        elseif (
            $pregunta['tipo'] ===
            'seleccion_multiple'
        ) {

            $resultadoCorreccion =
                $correccionService
                    ->corregirSeleccionMultiple(
                        $conn,
                        $pregunta,
                        (array)$respuestaRecibida
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Selección única
        |--------------------------------------------------------------------------
        */

        else {

            $resultadoCorreccion =
                $correccionService
                    ->corregirSeleccionUnica(
                        $conn,
                        $pregunta,
                        (int)$respuestaRecibida
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Resultado de la corrección
        |--------------------------------------------------------------------------
        */

        $respuestaAlumno =
            (string)$resultadoCorreccion[
                'respuesta'
            ];

        $correcta =
            (int)$resultadoCorreccion[
                'correcta'
            ];

        $puntajeObtenido =
            (float)$resultadoCorreccion[
                'puntaje'
            ];

        $observacion =
            (string)$resultadoCorreccion[
                'observacion'
            ];

        $nota +=
            $puntajeObtenido;
    }


    /*
    |--------------------------------------------------------------------------
    | Guardar respuesta corregida
    |--------------------------------------------------------------------------
    */

    $stmtGuardarRespuesta->bind_param(
        'iisids',
        $intentoId,
        $preguntaId,
        $respuestaAlumno,
        $correcta,
        $puntajeObtenido,
        $observacion
    );

    if (
        !$stmtGuardarRespuesta
            ->execute()
    ) {

        throw new Exception(
            'No se pudo guardar una de las respuestas.'
        );
    }
}

$stmtPreguntas->close();

$stmtGuardarRespuesta->close();


/*
|--------------------------------------------------------------------------
| Calcular nota sobre 100
|--------------------------------------------------------------------------
*/

$notaFinal = 0.0;

if ($puntajeTotal > 0) {

    $notaFinal =
        ($nota * 100) /
        $puntajeTotal;
}

if ($notaFinal > 100) {

    $notaFinal = 100;
}

if ($notaFinal < 0) {

    $notaFinal = 0;
}


/*
|--------------------------------------------------------------------------
| Finalizar intento
|--------------------------------------------------------------------------
*/

$stmtFinalizar =
    $conn->prepare("
        UPDATE intentos
        SET
            fecha_fin = NOW(),
            nota = ?,
            finalizado = 1,
            cambios_pestana = ?
        WHERE id = ?
          AND usuario_id = ?
          AND finalizado = 0
    ");

if (!$stmtFinalizar) {

    throw new Exception(
        'No se pudo preparar la finalización del intento.'
    );
}

$stmtFinalizar->bind_param(
    'diii',
    $notaFinal,
    $cambiosPestana,
    $intentoId,
    $usuarioId
);

if (!$stmtFinalizar->execute()) {

    throw new Exception(
        'No se pudo finalizar el intento.'
    );
}

if (
    $stmtFinalizar->affected_rows !== 1
) {

    throw new Exception(
        'No se pudo finalizar el intento.'
    );
}

$stmtFinalizar->close();


/*
|--------------------------------------------------------------------------
| Confirmar cambios
|--------------------------------------------------------------------------
*/

$conn->commit();


/*
|--------------------------------------------------------------------------
| Estado final
|--------------------------------------------------------------------------
*/

$estado =
    $notaFinal >= 51
        ? 'APROBADO'
        : 'REPROBADO';


/*
|--------------------------------------------------------------------------
| Vista del resultado
|--------------------------------------------------------------------------
*/

require BASE_PATH .
    '/views/alumno/resultado.php';

    } catch (Throwable $error) {

        $conn->rollback();

        die(
            'Ocurrió un error al finalizar el Formulario: ' .
            htmlspecialchars(
                $error->getMessage()
            )
        );
    }
}
public function disponibles(): void
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
        ($_SESSION['rol'] ?? '') !==
        'alumno'
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
    | Formularios publicados
    |--------------------------------------------------------------------------
    */

   $usuarioId =
    (int)$_SESSION['usuario_id'];

$stmt =
    $conn->prepare("
        SELECT
            e.id,
            e.titulo,
            e.descripcion,
            e.cantidad_preguntas,
            e.cantidad_intentos,
            e.tipo_tiempo,
            e.tiempo_minutos,
            e.fecha_hora_limite,
            e.mostrar_preguntas_antes,
            e.mostrar_respuestas,

(
    SELECT COALESCE(
        MAX(i.numero_intento),
        0
    )
    FROM intentos i
    WHERE i.usuario_id = ?
      AND i.examen_id = e.id
) AS intentos_utilizados

        FROM examenes e

        WHERE e.estado = 'publicado'
          AND e.activo = 1

        ORDER BY e.id DESC
    ");

    if (!$stmt) {

        http_response_code(500);

        die(
            'No se pudieron obtener los formularios disponibles.'
        );
    }
$stmt->bind_param(
    'i',
    $usuarioId
);
    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $formularios = [];

    while (
        $fila =
        $resultado->fetch_assoc()
    ) {

        $formularios[] =
            $fila;
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/alumno/disponibles.php';
}
public function misRespuestas(string $id): void
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
        ($_SESSION['rol'] ?? '') !==
        'alumno'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar formulario
    |--------------------------------------------------------------------------
    */

    $examenId = (int)$id;

    if ($examenId <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
    }

    $usuarioId =
        (int)$_SESSION['usuario_id'];


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
                titulo,
                mostrar_respuestas
            FROM examenes
            WHERE id = ?
            LIMIT 1
        ");

    if (!$stmtExamen) {

        http_response_code(500);

        die(
            'No se pudo obtener el formulario.'
        );
    }

    $stmtExamen->bind_param(
        'i',
        $examenId
    );

    $stmtExamen->execute();

    $examen =
        $stmtExamen
            ->get_result()
            ->fetch_assoc();

    $stmtExamen->close();


    if (!$examen) {

        http_response_code(404);

        die('Formulario no encontrado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Verificar permiso del docente
    |--------------------------------------------------------------------------
    */

    if (
        (int)$examen['mostrar_respuestas']
        !== 1
    ) {

        http_response_code(403);

        die(
            'El docente todavía no habilitó la revisión de respuestas.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener último intento finalizado
    |--------------------------------------------------------------------------
    */

    $stmtIntento =
        $conn->prepare("
            SELECT
                id,
                nota,
                numero_intento,
                fecha_fin
            FROM intentos
            WHERE usuario_id = ?
              AND examen_id = ?
              AND finalizado = 1
            ORDER BY id DESC
            LIMIT 1
        ");

    if (!$stmtIntento) {

        http_response_code(500);

        die(
            'No se pudo obtener el intento.'
        );
    }

    $stmtIntento->bind_param(
        'ii',
        $usuarioId,
        $examenId
    );

    $stmtIntento->execute();

    $intento =
        $stmtIntento
            ->get_result()
            ->fetch_assoc();

    $stmtIntento->close();


    if (!$intento) {

        http_response_code(404);

        die(
            'Todavía no finalizaste este formulario.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener respuestas
    |--------------------------------------------------------------------------
    */

    $stmtRespuestas =
        $conn->prepare("
            SELECT
                p.pregunta,
                p.tipo,
                r.respuesta,
                r.correcta,
                r.puntaje_obtenido,
                r.observacion
            FROM respuestas r

            INNER JOIN preguntas p
                ON p.id = r.pregunta_id

            WHERE r.intento_id = ?

            ORDER BY r.id ASC
        ");

    if (!$stmtRespuestas) {

        http_response_code(500);

        die(
            'No se pudieron obtener las respuestas.'
        );
    }

    $stmtRespuestas->bind_param(
        'i',
        $intento['id']
    );

    $stmtRespuestas->execute();

    $resultadoRespuestas =
        $stmtRespuestas->get_result();

    $respuestas = [];

    while (
        $respuesta =
        $resultadoRespuestas->fetch_assoc()
    ) {

        $respuestas[] =
            $respuesta;
    }

    $stmtRespuestas->close();


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/alumno/mis-respuestas.php';
}
public function logout(): void
{
    $_SESSION = [];

    if (
        ini_get('session.use_cookies')
    ) {

        $params =
            session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header(
        'Location: ' .
        BASE_URL .
        '/login'
    );

    exit;
}
public function login(): void
{
    header(
        'Location: ' .
        BASE_URL .
        '/login.php'
    );

    exit;
}

}