<?php

declare(strict_types=1);

class ResultadoController
{
    public function index(string $id): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validar sesión y rol
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
            $_SESSION['rol'] !== 'docente'
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
    SELECT
        id,
        titulo,
        estado,
        mostrar_respuestas,
        criterio_nota
    FROM examenes
    WHERE id = ?
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
    http_response_code(404);

    die(
        'El formulario no existe.'
    );
}


        /*
        |--------------------------------------------------------------------------
        | Criterio de nota
        |--------------------------------------------------------------------------
        */

        $criterioNota =
            $examen['criterio_nota']
            ?? 'mejor_nota';


        /*
        |--------------------------------------------------------------------------
        | Último intento
        |--------------------------------------------------------------------------
        */

        if ($criterioNota === 'ultimo_intento') {

            $stmtResultados = $conn->prepare("
                SELECT
                    i.id,
                    i.usuario_id,
                    i.numero_intento,
                    u.nombre,
                    u.correo,
                    i.nota,
                    i.fecha_inicio,
                    i.fecha_fin,
                    i.cambios_pestana,

                    (
                        SELECT COUNT(*)
                        FROM intentos ic
                        WHERE ic.usuario_id = i.usuario_id
                          AND ic.examen_id = i.examen_id
                          AND ic.finalizado = 1
                    ) AS total_intentos

                FROM intentos i

                INNER JOIN usuarios u
                    ON u.id = i.usuario_id

                WHERE i.finalizado = 1
                  AND i.examen_id = ?

                  AND i.numero_intento = (
                        SELECT MAX(i2.numero_intento)
                        FROM intentos i2
                        WHERE i2.usuario_id = i.usuario_id
                          AND i2.examen_id = i.examen_id
                          AND i2.finalizado = 1
                  )

                ORDER BY u.nombre ASC
            ");

        } else {

            /*
            |--------------------------------------------------------------------------
            | Mejor nota
            |--------------------------------------------------------------------------
            */

            $stmtResultados = $conn->prepare("
                SELECT
                    i.id,
                    i.usuario_id,
                    i.numero_intento,
                    u.nombre,
                    u.correo,
                    i.nota,
                    i.fecha_inicio,
                    i.fecha_fin,
                    i.cambios_pestana,

                    (
                        SELECT COUNT(*)
                        FROM intentos ic
                        WHERE ic.usuario_id = i.usuario_id
                          AND ic.examen_id = i.examen_id
                          AND ic.finalizado = 1
                    ) AS total_intentos

                FROM intentos i

                INNER JOIN usuarios u
                    ON u.id = i.usuario_id

                WHERE i.finalizado = 1
                  AND i.examen_id = ?

                  AND i.id = (
                        SELECT i2.id
                        FROM intentos i2
                        WHERE i2.usuario_id = i.usuario_id
                          AND i2.examen_id = i.examen_id
                          AND i2.finalizado = 1

                        ORDER BY
                            i2.nota DESC,
                            i2.numero_intento DESC,
                            i2.id DESC

                        LIMIT 1
                  )

                ORDER BY u.nombre ASC
            ");
        }

        if (!$stmtResultados) {
            die(
                'No se pudo preparar la consulta de resultados.'
            );
        }

        $stmtResultados->bind_param(
            'i',
            $examenId
        );

        $stmtResultados->execute();

        $resultadoConsulta =
            $stmtResultados->get_result();

        $resultados = [];

        while (
            $fila =
            $resultadoConsulta->fetch_assoc()
        ) {
            $resultados[] = $fila;
        }

        $stmtResultados->close();


        /*
        |--------------------------------------------------------------------------
        | Vista
        |--------------------------------------------------------------------------
        */

        require BASE_PATH .
            '/views/resultados/index.php';
    }
    public function detalle(string $id): void
{
    /*
    |--------------------------------------------------------------------------
    | Validar sesión y rol
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
        $_SESSION['rol'] !== 'docente'
    ) {
        http_response_code(403);
        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar intento
    |--------------------------------------------------------------------------
    */

    $intentoId = (int)$id;

    if ($intentoId <= 0) {
        die('Intento inválido.');
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
    | Obtener información del intento
    |--------------------------------------------------------------------------
    */

    $stmtIntento = $conn->prepare("
        SELECT
            i.id,
            i.examen_id,
            i.numero_intento,
            i.nota,
            u.nombre,
            u.correo,
            e.titulo
        FROM intentos i
        INNER JOIN usuarios u
            ON u.id = i.usuario_id
        INNER JOIN examenes e
            ON e.id = i.examen_id
        WHERE i.id = ?
        LIMIT 1
    ");

    if (!$stmtIntento) {
        die(
            'No se pudo preparar la consulta del intento.'
        );
    }

    $stmtIntento->bind_param(
        'i',
        $intentoId
    );

    $stmtIntento->execute();

    $resultadoIntento =
        $stmtIntento->get_result();

    $intento =
        $resultadoIntento->fetch_assoc();

    $stmtIntento->close();

    if (!$intento) {
        http_response_code(404);
        die('El intento no existe.');
    }


    /*
    |--------------------------------------------------------------------------
    | Obtener respuestas
    |--------------------------------------------------------------------------
    */

    $stmtRespuestas = $conn->prepare("
        SELECT
            p.id AS pregunta_id,
            p.pregunta,
            r.respuesta,
            r.correcta,
            r.puntaje_obtenido
        FROM respuestas r
        INNER JOIN preguntas p
            ON p.id = r.pregunta_id
        WHERE r.intento_id = ?
        ORDER BY r.id ASC
    ");

    if (!$stmtRespuestas) {
        die(
            'No se pudo preparar la consulta de respuestas.'
        );
    }

    $stmtRespuestas->bind_param(
        'i',
        $intentoId
    );

    $stmtRespuestas->execute();

    $resultadoConsulta =
        $stmtRespuestas->get_result();

    $respuestas = [];

    while (
        $fila =
        $resultadoConsulta->fetch_assoc()
    ) {
        $respuestas[] = $fila;
    }

    $stmtRespuestas->close();


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/resultados/detalle.php';
}
}