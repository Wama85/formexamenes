<?php

session_start();
require_once "conexion.php";

/*
|--------------------------------------------------------------------------
| Validar sesión y rol
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
| Validar examen
|--------------------------------------------------------------------------
*/

$examen_id = isset($_POST['examen_id'])
    ? (int) $_POST['examen_id']
    : 0;

if ($examen_id <= 0) {
    die("Formulario inválido.");
}

/*
|--------------------------------------------------------------------------
| Reiniciar intentos
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    /*
    Eliminar respuestas de los intentos.
    */

    $stmtRespuestas = $conn->prepare("
        DELETE r
        FROM respuestas r
        INNER JOIN intentos i
            ON i.id = r.intento_id
        WHERE i.examen_id = ?
    ");

    $stmtRespuestas->bind_param(
        "i",
        $examen_id
    );

    if (!$stmtRespuestas->execute()) {
        throw new Exception(
            "No se pudieron eliminar las respuestas."
        );
    }

    /*
    Eliminar preguntas asignadas.
    */

    $stmtPreguntas = $conn->prepare("
        DELETE ip
        FROM intento_preguntas ip
        INNER JOIN intentos i
            ON i.id = ip.intento_id
        WHERE i.examen_id = ?
    ");

    $stmtPreguntas->bind_param(
        "i",
        $examen_id
    );

    if (!$stmtPreguntas->execute()) {
        throw new Exception(
            "No se pudieron eliminar las preguntas asignadas."
        );
    }

    /*
    Eliminar intentos.
    */

    $stmtIntentos = $conn->prepare("
        DELETE FROM intentos
        WHERE examen_id = ?
    ");

    $stmtIntentos->bind_param(
        "i",
        $examen_id
    );

    if (!$stmtIntentos->execute()) {
        throw new Exception(
            "No se pudieron eliminar los intentos."
        );
    }

    $conn->commit();

    header(
        "Location: admin_preguntas.php?" .
        "examen_id=" . $examen_id .
        "&reiniciado=1"
    );

    exit;

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "No se pudo reiniciar el Formulario: " .
        htmlspecialchars($error->getMessage())
    );
}