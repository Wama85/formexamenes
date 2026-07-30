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

$usuario_id = (int) $_SESSION['usuario_id'];

/*
|--------------------------------------------------------------------------
| Buscar examen publicado
|--------------------------------------------------------------------------
*/

$stmtExamen = $conn->prepare("
    SELECT *
    FROM examenes
    WHERE estado = 'publicado'
    ORDER BY id DESC
    LIMIT 1
");

$stmtExamen->execute();

$resultadoExamen = $stmtExamen->get_result();
$examen = $resultadoExamen->fetch_assoc();

if (!$examen) {
    die("No existe un examen publicado.");
}

$examen_id = (int) $examen['id'];

/*
|--------------------------------------------------------------------------
| Buscar un intento abierto
|--------------------------------------------------------------------------
|
| Si el estudiante recarga la página o vuelve a ingresar, continúa con el
| mismo intento y conserva las preguntas que ya le fueron asignadas.
|
*/

$stmtIntentoAbierto = $conn->prepare("
    SELECT
        id,
        fecha_inicio,
        cambios_pestana
    FROM intentos
    WHERE usuario_id = ?
      AND examen_id = ?
      AND finalizado = 0
    ORDER BY id DESC
    LIMIT 1
");

$stmtIntentoAbierto->bind_param(
    "ii",
    $usuario_id,
    $examen_id
);

$stmtIntentoAbierto->execute();

$resultadoIntentoAbierto = $stmtIntentoAbierto->get_result();
$intentoAbierto = $resultadoIntentoAbierto->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Continuar intento o crear uno nuevo
|--------------------------------------------------------------------------
*/

if ($intentoAbierto) {

    /*
    El estudiante ya tiene un intento abierto.
    No se crea otro intento.
    */

    $intento_id = (int) $intentoAbierto['id'];
    $fecha_inicio = $intentoAbierto['fecha_inicio'];

    $cambios_guardados = isset($intentoAbierto['cambios_pestana'])
        ? (int) $intentoAbierto['cambios_pestana']
        : 0;

} else {

    /*
    Contar todos los intentos utilizados.
    */

    $stmtCantidadIntentos = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM intentos
        WHERE usuario_id = ?
          AND examen_id = ?
          AND finalizado IN (0, 1)
    ");

    $stmtCantidadIntentos->bind_param(
        "ii",
        $usuario_id,
        $examen_id
    );

    $stmtCantidadIntentos->execute();

    $resultadoCantidadIntentos = $stmtCantidadIntentos->get_result();
    $datosIntentos = $resultadoCantidadIntentos->fetch_assoc();

    $totalIntentos = (int) $datosIntentos['total'];

    /*
    Después de dos intentos, ya no puede resolver nuevamente.
    */

    if ($totalIntentos >= 2) {
        die("
            Ya utilizó los 2 intentos permitidos para este examen.
            Ahora solo puede consultar su nota.
        ");
    }

    /*
    Crear un nuevo intento.
    */

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $navegador = $_SERVER['HTTP_USER_AGENT'] ?? '';

    $stmtCrearIntento = $conn->prepare("
        INSERT INTO intentos (
            usuario_id,
            examen_id,
            fecha_inicio,
            ip_publica,
            navegador,
            finalizado
        )
        VALUES (?, ?, NOW(), ?, ?, 0)
    ");

    $stmtCrearIntento->bind_param(
        "iiss",
        $usuario_id,
        $examen_id,
        $ip,
        $navegador
    );

    if (!$stmtCrearIntento->execute()) {
        die("No se pudo crear el intento.");
    }

    $intento_id = (int) $stmtCrearIntento->insert_id;
    $fecha_inicio = date("Y-m-d H:i:s");
    $cambios_guardados = 0;
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

$stmtCantidadAsignadas->bind_param(
    "i",
    $intento_id
);

$stmtCantidadAsignadas->execute();

$resultadoCantidadAsignadas = $stmtCantidadAsignadas->get_result();
$datosCantidadAsignadas = $resultadoCantidadAsignadas->fetch_assoc();

$totalAsignadas = (int) $datosCantidadAsignadas['total'];

/*
|--------------------------------------------------------------------------
| Asignar preguntas aleatorias solo una vez
|--------------------------------------------------------------------------
|
| Si el estudiante recarga la página, este bloque no vuelve a ejecutarse
| porque el intento ya tiene preguntas asignadas.
|
*/

if ($totalAsignadas === 0) {

    $cantidad_preguntas = isset($examen['cantidad_preguntas'])
        ? (int) $examen['cantidad_preguntas']
        : 8;

    if ($cantidad_preguntas < 1) {
        $cantidad_preguntas = 8;
    }

    /*
    Verificar cuántas preguntas existen realmente.
    */

    $stmtTotalPreguntas = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM preguntas
        WHERE examen_id = ?
    ");

    $stmtTotalPreguntas->bind_param(
        "i",
        $examen_id
    );

    $stmtTotalPreguntas->execute();

    $resultadoTotalPreguntas = $stmtTotalPreguntas->get_result();
    $datosTotalPreguntas = $resultadoTotalPreguntas->fetch_assoc();

    $totalDisponibles = (int) $datosTotalPreguntas['total'];

    if ($totalDisponibles === 0) {

        /*
        Eliminamos el intento vacío para que no consuma uno.
        */

        $stmtEliminarIntento = $conn->prepare("
            DELETE FROM intentos
            WHERE id = ?
              AND usuario_id = ?
              AND finalizado = 0
        ");

        $stmtEliminarIntento->bind_param(
            "ii",
            $intento_id,
            $usuario_id
        );

        $stmtEliminarIntento->execute();

        die("Este examen todavía no tiene preguntas.");
    }

    /*
    Si el examen solicita más preguntas de las disponibles,
    se utilizarán todas las preguntas existentes.
    */

    if ($cantidad_preguntas > $totalDisponibles) {
        $cantidad_preguntas = $totalDisponibles;
    }

    /*
    Seleccionar preguntas aleatorias.
    */

    $stmtPreguntasAleatorias = $conn->prepare("
        SELECT id
        FROM preguntas
        WHERE examen_id = ?
        ORDER BY RAND()
        LIMIT ?
    ");

    $stmtPreguntasAleatorias->bind_param(
        "ii",
        $examen_id,
        $cantidad_preguntas
    );

    $stmtPreguntasAleatorias->execute();

    $resultadoPreguntasAleatorias =
        $stmtPreguntasAleatorias->get_result();

    /*
    Guardar las preguntas asignadas al intento.
    */

    $stmtGuardarPregunta = $conn->prepare("
        INSERT INTO intento_preguntas (
            intento_id,
            pregunta_id,
            orden
        )
        VALUES (?, ?, ?)
    ");

    $orden = 1;

    while (
        $filaPregunta =
        $resultadoPreguntasAleatorias->fetch_assoc()
    ) {

        $pregunta_id = (int) $filaPregunta['id'];

        $stmtGuardarPregunta->bind_param(
            "iii",
            $intento_id,
            $pregunta_id,
            $orden
        );

        if (!$stmtGuardarPregunta->execute()) {
            die("No se pudieron asignar las preguntas del examen.");
        }

        $orden++;
    }
}

/*
|--------------------------------------------------------------------------
| Cargar las preguntas asignadas
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

$stmtPreguntas->bind_param(
    "i",
    $intento_id
);

$stmtPreguntas->execute();

$preguntas = $stmtPreguntas->get_result();

if ($preguntas->num_rows === 0) {
    die("No existen preguntas asignadas a este intento.");
}

/*
|--------------------------------------------------------------------------
| Calcular tiempo restante
|--------------------------------------------------------------------------
|
| Al recargar la página, el tiempo continúa desde fecha_inicio.
|
*/

$tiempo_minutos = (int) $examen['tiempo_minutos'];

if ($tiempo_minutos < 1) {
    $tiempo_minutos = 1;
}

$tiempo_total_segundos = $tiempo_minutos * 60;

$inicio_timestamp = strtotime($fecha_inicio);

if ($inicio_timestamp === false) {
    $inicio_timestamp = time();
}

$segundos_transcurridos = time() - $inicio_timestamp;

$tiempo_restante = $tiempo_total_segundos - $segundos_transcurridos;

if ($tiempo_restante < 0) {
    $tiempo_restante = 0;
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

    <title>Resolver Examen</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>
            <?= htmlspecialchars($examen['titulo']) ?>
        </h1>

        <p class="correo">
            Tiempo:
            <?= $tiempo_minutos ?>
            minutos
        </p>

        <p class="correo">
            Intento:
            <?php

            $stmtNumeroIntento = $conn->prepare("
                SELECT COUNT(*) AS numero
                FROM intentos
                WHERE usuario_id = ?
                  AND examen_id = ?
                  AND id <= ?
            ");

            $stmtNumeroIntento->bind_param(
                "iii",
                $usuario_id,
                $examen_id,
                $intento_id
            );

            $stmtNumeroIntento->execute();

            $resultadoNumeroIntento =
                $stmtNumeroIntento->get_result();

            $datosNumeroIntento =
                $resultadoNumeroIntento->fetch_assoc();

            echo (int) $datosNumeroIntento['numero'];

            ?>
            de 2
        </p>

        <div
            id="temporizador"
            class="temporizador"
        ></div>

        <form
            id="formExamen"
            method="POST"
            action="finalizar_examen.php"
            onsubmit="enviado = true;"
        >

            <input
                type="hidden"
                name="intento_id"
                value="<?= $intento_id ?>"
            >

            <input
                type="hidden"
                name="examen_id"
                value="<?= $examen_id ?>"
            >

            <input
                type="hidden"
                name="cambios_pestana"
                id="cambios_pestana"
                value="<?= $cambios_guardados ?>"
            >

            <?php $numero = 1; ?>

            <?php while ($pregunta = $preguntas->fetch_assoc()): ?>

                <?php
                $idPregunta = (int) $pregunta['id'];
                ?>

                <div class="pregunta">

                    <h3>

                        <?= $numero ?>.

                        <?= nl2br(
                            htmlspecialchars($pregunta['pregunta'])
                        ) ?>

                    </h3>

                    <?php if ($pregunta['tipo'] === 'codigo'): ?>

                        <textarea
                            name="pregunta_<?= $idPregunta ?>"
                            rows="12"
                            class="textarea-codigo"
                            placeholder="Escriba aquí su código..."
                        ></textarea>

                    <?php elseif (
                        $pregunta['tipo'] === 'respuesta_texto'
                    ): ?>

                        <textarea
                            name="pregunta_<?= $idPregunta ?>"
                            rows="5"
                            class="textarea-codigo"
                            placeholder="Escriba aquí su respuesta..."
                        ></textarea>

                    <?php else: ?>

                        <?php

                        $stmtOpciones = $conn->prepare("
                            SELECT
                                id,
                                opcion_texto
                            FROM opciones
                            WHERE pregunta_id = ?
                            ORDER BY id ASC
                        ");

                        $stmtOpciones->bind_param(
                            "i",
                            $idPregunta
                        );

                        $stmtOpciones->execute();

                        $opciones =
                            $stmtOpciones->get_result();

                        ?>

                        <?php while ($opcion = $opciones->fetch_assoc()): ?>

    <label class="opcion">

        <?php if ($pregunta['tipo'] === 'seleccion_multiple'): ?>

            <input
                type="checkbox"
                name="pregunta_<?= $idPregunta ?>[]"
                value="<?= (int) $opcion['id'] ?>"
            >

        <?php else: ?>

            <input
                type="radio"
                name="pregunta_<?= $idPregunta ?>"
                value="<?= (int) $opcion['id'] ?>"
            >

        <?php endif; ?>

        <?= htmlspecialchars($opcion['opcion_texto']) ?>

    </label>

<?php endwhile; ?>

                    <?php endif; ?>

                </div>

                <hr>

                <?php $numero++; ?>

            <?php endwhile; ?>

            <button
                type="submit"
                class="btn"
            >
                Finalizar Examen
            </button>

        </form>

    </div>

</div>

<script>

let cambios = <?= $cambios_guardados ?>;
let enviado = false;
let tiempo = <?= $tiempo_restante ?>;

/*
Evita que visibilitychange y blur cuenten dos veces
el mismo cambio de ventana.
*/
let ultimoCambio = 0;

function enviarExamen(mensaje) {

    if (enviado) {
        return;
    }

    enviado = true;

    alert(mensaje);

    document
        .getElementById("formExamen")
        .submit();
}

function actualizarTemporizador() {

    const temporizador =
        document.getElementById("temporizador");

    if (tiempo <= 0) {

        temporizador.innerHTML =
            "Tiempo restante: 0:00";

        enviarExamen(
            "El tiempo terminó. " +
            "El examen será enviado automáticamente."
        );

        return;
    }

    const minutos = Math.floor(tiempo / 60);
    const segundos = tiempo % 60;

    temporizador.innerHTML =
        "Tiempo restante: " +
        minutos +
        ":" +
        (segundos < 10 ? "0" : "") +
        segundos;

    tiempo--;
}

actualizarTemporizador();

const intervaloTemporizador = setInterval(
    actualizarTemporizador,
    1000
);

function registrarCambioPantalla() {

    if (enviado) {
        return;
    }

    const ahora = Date.now();

    /*
    Los eventos blur y visibilitychange pueden activarse
    casi al mismo tiempo. Se ignoran eventos repetidos
    dentro de un segundo.
    */
    if (ahora - ultimoCambio < 1000) {
        return;
    }

    ultimoCambio = ahora;

    cambios++;

    document
        .getElementById("cambios_pestana")
        .value = cambios;

    if (cambios === 1) {

        alert(
            "Advertencia: no debe cambiar de pantalla. " +
            "Si vuelve a hacerlo, el examen finalizará."
        );
    }

    if (cambios >= 2) {

        enviarExamen(
            "Examen finalizado automáticamente " +
            "por cambiar de pantalla."
        );
    }
}

document.addEventListener(
    "visibilitychange",
    function () {

        if (document.hidden) {
            registrarCambioPantalla();
        }
    }
);

window.addEventListener(
    "blur",
    function () {
        registrarCambioPantalla();
    }
);

/*
Bloquear botón atrás.
*/

history.pushState(
    null,
    "",
    location.href
);

window.addEventListener(
    "popstate",
    function () {

        history.pushState(
            null,
            "",
            location.href
        );

        alert(
            "No puede volver atrás durante el examen."
        );
    }
);

/*
Bloquear teclas y combinaciones.
*/

document.addEventListener(
    "keydown",
    function (e) {

        const tecla = e.key.toLowerCase();

        if (
            e.key === "F5" ||
            e.key === "F12" ||
            (e.ctrlKey && tecla === "r") ||
            (e.ctrlKey && tecla === "c") ||
            (e.ctrlKey && tecla === "v") ||
            (e.ctrlKey && tecla === "x") ||
            (e.ctrlKey && tecla === "a") ||
            (e.ctrlKey && tecla === "u") ||
            (
                e.ctrlKey &&
                e.shiftKey &&
                tecla === "i"
            )
        ) {

            e.preventDefault();
        }
    }
);

/*
Bloquear menú contextual, copiar, cortar,
pegar y seleccionar.
*/

document.addEventListener(
    "contextmenu",
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    "copy",
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    "cut",
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    "paste",
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    "selectstart",
    function (e) {
        e.preventDefault();
    }
);

/*
Advertencia al intentar cerrar o abandonar.
*/

window.addEventListener(
    "beforeunload",
    function (e) {

        if (!enviado) {

            e.preventDefault();
            e.returnValue = "";
        }
    }
);

</script>

</body>
</html>