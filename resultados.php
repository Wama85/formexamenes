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
| Obtener examen seleccionado
|--------------------------------------------------------------------------
*/

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

/*
|--------------------------------------------------------------------------
| Obtener únicamente exámenes publicados
|--------------------------------------------------------------------------
*/

$stmtExamenes = $conn->prepare("
    SELECT
        id,
        titulo,
        estado,
        mostrar_respuestas
    FROM examenes
    WHERE estado = 'publicado'
    ORDER BY id DESC
");

$stmtExamenes->execute();

$examenes = $stmtExamenes->get_result();
/*
|--------------------------------------------------------------------------
| Obtener examen seleccionado y sus resultados
|--------------------------------------------------------------------------
*/

$examenSeleccionado = null;
$resultados = null;

if ($examen_id > 0) {

    $stmtExamen = $conn->prepare("
        SELECT
            id,
            titulo,
            estado,
            mostrar_respuestas,
            criterio_nota
        FROM examenes
        WHERE id = ?
          AND estado = 'publicado'
        LIMIT 1
    ");

    $stmtExamen->bind_param(
        "i",
        $examen_id
    );

    $stmtExamen->execute();

    $resultadoExamen =
        $stmtExamen->get_result();

    $examenSeleccionado =
        $resultadoExamen->fetch_assoc();

    if (!$examenSeleccionado) {
        die("El Formulario no existe o ya no está publicado.");
    }


    /*
    |--------------------------------------------------------------------------
    | Criterio utilizado para determinar la nota válida
    |--------------------------------------------------------------------------
    */

    $criterio_nota =
        $examenSeleccionado['criterio_nota']
        ?? 'mejor_nota';


    /*
    |--------------------------------------------------------------------------
    | ÚLTIMO INTENTO
    |--------------------------------------------------------------------------
    */

    if ($criterio_nota === 'ultimo_intento') {

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


    /*
    |--------------------------------------------------------------------------
    | MEJOR NOTA
    |--------------------------------------------------------------------------
    */

    } else {

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


    $stmtResultados->bind_param(
        "i",
        $examen_id
    );

    $stmtResultados->execute();

    $resultados =
        $stmtResultados->get_result();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Resultados</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div
    class="container"
    style="max-width:1200px;"
>

    <div class="card">

        <h1>Resultados del Formulario</h1>

        <form
            method="GET"
            action="resultados.php"
        >

            <label for="examen_id">
                Seleccione un Formulario publicado
            </label>

            <select
                name="examen_id"
                id="examen_id"
                class="input"
                onchange="this.form.submit()"
            >

                <option value="">
                    Seleccione un Formulario
                </option>

                <?php while (
                    $filaExamen = $examenes->fetch_assoc()
                ): ?>

                    <option
                        value="<?= (int) $filaExamen['id'] ?>"
                        <?= $examen_id === (int) $filaExamen['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= htmlspecialchars(
                            $filaExamen['titulo']
                        ) ?>
                    </option>

                <?php endwhile; ?>

            </select>

        </form>

        <br>

        <?php if ($examenSeleccionado): ?>

            <h2>
                <?= htmlspecialchars(
                    $examenSeleccionado['titulo']
                ) ?>
            </h2>

            <?php if (
                (int) $examenSeleccionado['mostrar_respuestas'] === 1
            ): ?>

                <a
                    href="cambiar_mostrar_respuestas.php?examen_id=<?= $examen_id ?>&valor=0"
                    class="btn btn-salir"
                >
                    Ocultar respuestas a alumnos
                </a>

            <?php else: ?>

                <a
                    href="cambiar_mostrar_respuestas.php?examen_id=<?= $examen_id ?>&valor=1"
                    class="btn"
                >
                    Mostrar respuestas a alumnos
                </a>

            <?php endif; ?>

            <br><br>

            <a
                href="exportar_notas.php?examen_id=<?= $examen_id ?>"
                class="btn"
            >
                Exportar notas CSV
            </a>

            <br><br>

            <?php if (
    $resultados &&
    $resultados->num_rows > 0
): ?>

    <p>
        <strong>Criterio de nota:</strong>

        <?php if (
            ($examenSeleccionado['criterio_nota'] ?? 'mejor_nota')
            === 'ultimo_intento'
        ): ?>

            Último intento

        <?php else: ?>

            Mejor nota

        <?php endif; ?>
    </p>

    <br>

    <table class="tabla">

        <thead>

            <tr>
                <th>Alumno</th>
                <th>Correo</th>
                <th>Intentos</th>
                <th>Intento válido</th>
                <th>Nota válida</th>
                <th>Cambios</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Detalle</th>
            </tr>

        </thead>

        <tbody>

            <?php while (
                $fila = $resultados->fetch_assoc()
            ): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars(
                            $fila['nombre']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $fila['correo']
                        ) ?>
                    </td>

                    <td>
                        <?= (int) $fila['total_intentos'] ?>
                    </td>

                    <td>
                        Intento <?= (int) $fila['numero_intento'] ?>
                    </td>

                    <td>
                        <strong>
                            <?= number_format(
                                (float) $fila['nota'],
                                2
                            ) ?>
                        </strong>
                    </td>

                    <td>
                        <?= (int) $fila['cambios_pestana'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $fila['fecha_inicio']
                        ) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars(
                            $fila['fecha_fin']
                        ) ?>
                    </td>

                    <td>

                        <a
                            href="detalle_resultado.php?id=<?= (int) $fila['id'] ?>"
                            class="btn-mini"
                        >
                            Ver
                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

<?php else: ?>

    <div
        style="
            padding:12px;
            background:#f3f3f3;
            border-radius:6px;
        "
    >
        Este Formulario todavía no tiene resultados finalizados.
    </div>

<?php endif; ?>

        <?php else: ?>

            <div
                style="
                    padding:12px;
                    background:#f3f3f3;
                    border-radius:6px;
                "
            >
                Seleccione un Formulario publicado para consultar sus resultados.
            </div>

        <?php endif; ?>

        <br>

        <a
            href="dashboard.php"
            class="btn"
        >
            Volver
        </a>

    </div>

</div>

</body>
</html>