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

$examen_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($examen_id <= 0) {
    die("Formulario inválido.");
}

/*
|--------------------------------------------------------------------------
| Obtener imágenes locales del formulario
|--------------------------------------------------------------------------
*/

$stmtImagenes = $conn->prepare("
    SELECT
        imagen_tipo,
        imagen
    FROM preguntas
    WHERE examen_id = ?
      AND imagen_tipo = 'archivo'
      AND imagen IS NOT NULL
      AND imagen <> ''
");

$stmtImagenes->bind_param(
    "i",
    $examen_id
);

$stmtImagenes->execute();

$resultadoImagenes =
    $stmtImagenes->get_result();

$imagenesEliminar = [];

while (
    $filaImagen =
    $resultadoImagenes->fetch_assoc()
) {

    $imagenesEliminar[] =
        $filaImagen['imagen'];
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
    | Eliminar respuestas
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE r
        FROM respuestas r
        INNER JOIN intentos i
            ON i.id = r.intento_id
        WHERE i.examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar las respuestas."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar intento_preguntas
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE ip
        FROM intento_preguntas ip
        INNER JOIN intentos i
            ON i.id = ip.intento_id
        WHERE i.examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar las preguntas asignadas."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar eventos de seguridad
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE es
        FROM eventos_seguridad es
        INNER JOIN intentos i
            ON i.id = es.intento_id
        WHERE i.examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar los eventos de seguridad."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar intentos
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM intentos
        WHERE examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar los intentos."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar opciones
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE o
        FROM opciones o
        INNER JOIN preguntas p
            ON p.id = o.pregunta_id
        WHERE p.examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar las opciones."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar preguntas
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM preguntas
        WHERE examen_id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudieron eliminar las preguntas."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar formulario
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        DELETE FROM examenes
        WHERE id = ?
    ");

    $stmt->bind_param(
        "i",
        $examen_id
    );

    if (!$stmt->execute()) {
        throw new Exception(
            "No se pudo eliminar el formulario."
        );
    }

    if ($stmt->affected_rows !== 1) {
        throw new Exception(
            "El formulario no existe."
        );
    }

    $conn->commit();

    /*
    |--------------------------------------------------------------------------
    | Eliminar archivos físicos después del commit
    |--------------------------------------------------------------------------
    */

    $carpetaPermitida =
        realpath(
            __DIR__ .
            '/uploads/preguntas'
        );

    if ($carpetaPermitida !== false) {

        foreach (
            $imagenesEliminar
            as $imagen
        ) {

            $ruta =
                __DIR__ . '/' . $imagen;

            if (
                is_file($ruta) &&
                realpath(dirname($ruta)) ===
                $carpetaPermitida
            ) {

                @unlink($ruta);
            }
        }
    }

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "No se pudo eliminar el formulario: " .
        htmlspecialchars(
            $error->getMessage()
        )
    );
}

header(
    "Location: admin_examen.php?eliminado=1"
);

exit;
?>