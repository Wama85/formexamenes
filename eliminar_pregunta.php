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

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($id <= 0 || $examen_id <= 0) {
    die("Pregunta inválida.");
}

/*
|--------------------------------------------------------------------------
| Obtener imagen de la pregunta
|--------------------------------------------------------------------------
*/

$stmtPregunta = $conn->prepare("
    SELECT
        imagen_tipo,
        imagen
    FROM preguntas
    WHERE id = ?
      AND examen_id = ?
    LIMIT 1
");

$stmtPregunta->bind_param(
    "ii",
    $id,
    $examen_id
);

$stmtPregunta->execute();

$resultadoPregunta =
    $stmtPregunta->get_result();

$pregunta =
    $resultadoPregunta->fetch_assoc();

if (!$pregunta) {
    die("Pregunta no encontrada.");
}

$imagenTipo =
    $pregunta['imagen_tipo'] ?? null;

$imagen =
    $pregunta['imagen'] ?? null;

/*
|--------------------------------------------------------------------------
| Eliminar dentro de una transacción
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    Eliminar opciones
    */

    $stmtOpciones = $conn->prepare("
        DELETE FROM opciones
        WHERE pregunta_id = ?
    ");

    $stmtOpciones->bind_param(
        "i",
        $id
    );

    if (!$stmtOpciones->execute()) {
        throw new Exception(
            "No se pudieron eliminar las opciones."
        );
    }

    /*
    Eliminar relaciones con intentos
    */

    $stmtIntentoPreguntas = $conn->prepare("
        DELETE FROM intento_preguntas
        WHERE pregunta_id = ?
    ");

    $stmtIntentoPreguntas->bind_param(
        "i",
        $id
    );

    if (!$stmtIntentoPreguntas->execute()) {
        throw new Exception(
            "No se pudieron eliminar las referencias de la pregunta."
        );
    }

    /*
    Eliminar respuestas
    */

    $stmtRespuestas = $conn->prepare("
        DELETE FROM respuestas
        WHERE pregunta_id = ?
    ");

    $stmtRespuestas->bind_param(
        "i",
        $id
    );

    if (!$stmtRespuestas->execute()) {
        throw new Exception(
            "No se pudieron eliminar las respuestas."
        );
    }

    /*
    Eliminar pregunta
    */

    $stmtEliminar = $conn->prepare("
        DELETE FROM preguntas
        WHERE id = ?
          AND examen_id = ?
    ");

    $stmtEliminar->bind_param(
        "ii",
        $id,
        $examen_id
    );

    if (!$stmtEliminar->execute()) {
        throw new Exception(
            "No se pudo eliminar la pregunta."
        );
    }

    $conn->commit();

    /*
    Eliminar archivo físico únicamente
    después de confirmar la BD
    */

    if (
        $imagenTipo === 'archivo' &&
        !empty($imagen)
    ) {

        $ruta =
            __DIR__ . '/' . $imagen;

        $carpetaPermitida =
            realpath(
                __DIR__ .
                '/uploads/preguntas'
            );

        if (
            is_file($ruta) &&
            $carpetaPermitida !== false &&
            realpath(dirname($ruta)) ===
            $carpetaPermitida
        ) {

            @unlink($ruta);
        }
    }

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "No se pudo eliminar la pregunta: " .
        htmlspecialchars(
            $error->getMessage()
        )
    );
}

header(
    "Location: admin_preguntas.php?examen_id=" .
    $examen_id
);

exit;
?>