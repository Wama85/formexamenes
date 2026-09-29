<?php

declare(strict_types=1);

class IntentoController
{
    public function reiniciar(string $id): void
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
        | Reiniciar intentos
        |--------------------------------------------------------------------------
        */

        $conn->begin_transaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Eliminar respuestas
            |--------------------------------------------------------------------------
            */

            $stmtRespuestas = $conn->prepare("
                DELETE r
                FROM respuestas r
                INNER JOIN intentos i
                    ON i.id = r.intento_id
                WHERE i.examen_id = ?
            ");

            if (!$stmtRespuestas) {
                throw new Exception(
                    'No se pudo preparar la eliminación de respuestas.'
                );
            }

            $stmtRespuestas->bind_param(
                'i',
                $examenId
            );

            if (!$stmtRespuestas->execute()) {
                throw new Exception(
                    'No se pudieron eliminar las respuestas.'
                );
            }

            $stmtRespuestas->close();


            /*
            |--------------------------------------------------------------------------
            | Eliminar preguntas asignadas
            |--------------------------------------------------------------------------
            */

            $stmtPreguntas = $conn->prepare("
                DELETE ip
                FROM intento_preguntas ip
                INNER JOIN intentos i
                    ON i.id = ip.intento_id
                WHERE i.examen_id = ?
            ");

            if (!$stmtPreguntas) {
                throw new Exception(
                    'No se pudo preparar la eliminación de preguntas asignadas.'
                );
            }

            $stmtPreguntas->bind_param(
                'i',
                $examenId
            );

            if (!$stmtPreguntas->execute()) {
                throw new Exception(
                    'No se pudieron eliminar las preguntas asignadas.'
                );
            }

            $stmtPreguntas->close();


            /*
            |--------------------------------------------------------------------------
            | Eliminar intentos
            |--------------------------------------------------------------------------
            */

            $stmtIntentos = $conn->prepare("
                DELETE FROM intentos
                WHERE examen_id = ?
            ");

            if (!$stmtIntentos) {
                throw new Exception(
                    'No se pudo preparar la eliminación de intentos.'
                );
            }

            $stmtIntentos->bind_param(
                'i',
                $examenId
            );

            if (!$stmtIntentos->execute()) {
                throw new Exception(
                    'No se pudieron eliminar los intentos.'
                );
            }

            $stmtIntentos->close();


            /*
            |--------------------------------------------------------------------------
            | Confirmar transacción
            |--------------------------------------------------------------------------
            */

            $conn->commit();


            /*
            |--------------------------------------------------------------------------
            | Volver al listado de preguntas
            |--------------------------------------------------------------------------
            */

            header(
                'Location: ' .
                BASE_URL .
                '/formularios/' .
                $examenId .
                '/preguntas?reiniciado=1'
            );

            exit;

        } catch (Throwable $error) {

            $conn->rollback();

            http_response_code(500);

            die(
                'No se pudo reiniciar el formulario: ' .
                htmlspecialchars(
                    $error->getMessage(),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
    }
}