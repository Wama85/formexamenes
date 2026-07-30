<?php

session_start();

require_once "conexion.php";
require_once "ollama.php";

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
| Validar intento recibido
|--------------------------------------------------------------------------
*/

$intento_id = isset($_POST['intento_id'])
    ? (int) $_POST['intento_id']
    : 0;

if ($intento_id <= 0) {
    die("Intento inválido.");
}

$cambios_pestana = isset($_POST['cambios_pestana'])
    ? (int) $_POST['cambios_pestana']
    : 0;

if ($cambios_pestana < 0) {
    $cambios_pestana = 0;
}

/*
El sistema finaliza al segundo cambio.
No necesitamos guardar valores mayores a 2.
*/
if ($cambios_pestana > 2) {
    $cambios_pestana = 2;
}

/*
|--------------------------------------------------------------------------
| Iniciar transacción
|--------------------------------------------------------------------------
|
| Si ocurre un error durante la corrección, no se guardarán resultados
| incompletos.
|
*/

$conn->begin_transaction();

try {

    /*
    |--------------------------------------------------------------------------
    | Verificar que el intento pertenezca al estudiante
    |--------------------------------------------------------------------------
    */

    $stmtVerificarIntento = $conn->prepare("
        SELECT
            id,
            examen_id,
            finalizado
        FROM intentos
        WHERE id = ?
          AND usuario_id = ?
        LIMIT 1
        FOR UPDATE
    ");

    $stmtVerificarIntento->bind_param(
        "ii",
        $intento_id,
        $usuario_id
    );

    $stmtVerificarIntento->execute();

    $resultadoIntento =
        $stmtVerificarIntento->get_result();

    $intento = $resultadoIntento->fetch_assoc();

    if (!$intento) {
        throw new Exception(
            "No tiene permiso para finalizar este intento."
        );
    }

    /*
    Evita volver a corregir el mismo examen al presionar dos veces
    el botón o recargar la página de resultados.
    */

    if ((int) $intento['finalizado'] === 1) {

        $conn->rollback();

        header("Location: dashboard.php");
        exit;
    }

    $examen_id = (int) $intento['examen_id'];

    /*
    |--------------------------------------------------------------------------
    | Verificar si ya existen respuestas
    |--------------------------------------------------------------------------
    |
    | Normalmente no deberían existir porque el intento está abierto.
    | Se eliminan únicamente por seguridad, por ejemplo, si una ejecución
    | anterior se interrumpió antes de finalizar.
    |
    */

    $stmtEliminarRespuestas = $conn->prepare("
        DELETE FROM respuestas
        WHERE intento_id = ?
    ");

    $stmtEliminarRespuestas->bind_param(
        "i",
        $intento_id
    );

    $stmtEliminarRespuestas->execute();

    /*
    |--------------------------------------------------------------------------
    | Obtener solamente las preguntas asignadas al intento
    |--------------------------------------------------------------------------
    */

    $stmtPreguntas = $conn->prepare("
        SELECT
            p.*
        FROM intento_preguntas ip
        INNER JOIN preguntas p
            ON p.id = ip.pregunta_id
        WHERE ip.intento_id = ?
          AND p.examen_id = ?
        ORDER BY ip.orden ASC
    ");

    $stmtPreguntas->bind_param(
        "ii",
        $intento_id,
        $examen_id
    );

    $stmtPreguntas->execute();

    $preguntas = $stmtPreguntas->get_result();

    if ($preguntas->num_rows === 0) {
        throw new Exception(
            "El intento no tiene preguntas asignadas."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Preparar inserción de respuestas
    |--------------------------------------------------------------------------
    */

    $stmtGuardarRespuesta = $conn->prepare("
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

    /*
    |--------------------------------------------------------------------------
    | Variables de calificación
    |--------------------------------------------------------------------------
    */

    $nota = 0;
    $puntaje_total = 0;

    /*
    |--------------------------------------------------------------------------
    | Recorrer las preguntas asignadas
    |--------------------------------------------------------------------------
    */

    while ($pregunta = $preguntas->fetch_assoc()) {

        $pregunta_id = (int) $pregunta['id'];
        $puntaje = (float) $pregunta['puntaje'];

        if ($puntaje < 0) {
            $puntaje = 0;
        }

        $puntaje_total += $puntaje;

        $campo = "pregunta_" . $pregunta_id;

        /*
        Si el estudiante no respondió, guardamos la pregunta con
        puntaje cero.
        */

        $respuesta_recibida = $_POST[$campo] ?? null;

$sin_responder = false;

if ($respuesta_recibida === null) {

    $sin_responder = true;

} elseif (is_array($respuesta_recibida)) {

    /*
    Para selección múltiple, la respuesta llega como arreglo.
    Eliminamos valores vacíos o inválidos.
    */

    $respuesta_recibida = array_filter(
        $respuesta_recibida,
        function ($valor) {
            return (int) $valor > 0;
        }
    );

    if (count($respuesta_recibida) === 0) {
        $sin_responder = true;
    }

} elseif (trim((string) $respuesta_recibida) === '') {

    $sin_responder = true;
}

if ($sin_responder) {

    $respuesta_alumno = "";
    $correcta = 0;
    $puntaje_obtenido = 0;
    $observacion = "Pregunta sin responder.";

    $stmtGuardarRespuesta->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_alumno,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmtGuardarRespuesta->execute();

    continue;
}

        /*
        |--------------------------------------------------------------------------
        | Preguntas de código
        |--------------------------------------------------------------------------
        */

        if ($pregunta['tipo'] === 'codigo') {

            $respuesta_alumno =
                trim((string) $_POST[$campo]);

            $codigo = strtolower($respuesta_alumno);

            $textoPregunta =
                strtolower((string) $pregunta['pregunta']);

            $correcta = 0;
            $puntaje_obtenido = 0;
            $observacion = "";

            /*
            --------------------------------------------------------------
            Ejercicio: Hola Mundo
            --------------------------------------------------------------
            */

            if (str_contains($textoPregunta, 'hola mundo')) {

                $puntos = 0;

                if (str_contains($codigo, 'cout')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'hola')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'mundo')) {
                    $puntos++;
                }

                $puntaje_obtenido =
                    ($puntos / 3) * $puntaje;

                $correcta =
                    $puntaje_obtenido >= ($puntaje * 0.70)
                    ? 1
                    : 0;

                $observacion =
                    "Corrección automática por palabras clave.";
            }

            /*
            --------------------------------------------------------------
            Ejercicio: suma de dos números
            --------------------------------------------------------------
            */

            elseif (
                str_contains($textoPregunta, 'suma') &&
                str_contains($textoPregunta, 'dos')
            ) {

                $puntos = 0;

                if (str_contains($codigo, 'cin')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'cout')) {
                    $puntos++;
                }

                if (str_contains($codigo, '+')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'int')) {
                    $puntos++;
                }

                $puntaje_obtenido =
                    ($puntos / 4) * $puntaje;

                $correcta =
                    $puntaje_obtenido >= ($puntaje * 0.70)
                    ? 1
                    : 0;

                $observacion =
                    "Corrección automática de suma.";
            }

            /*
            --------------------------------------------------------------
            Ejercicio: positivo o negativo
            --------------------------------------------------------------
            */

            elseif (
                str_contains($textoPregunta, 'positivo') ||
                str_contains($textoPregunta, 'negativo')
            ) {

                $puntos = 0;

                if (str_contains($codigo, 'if')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'else')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'cin')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'cout')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'numero')) {
                    $puntos++;
                }

                $puntaje_obtenido =
                    ($puntos / 5) * $puntaje;

                $correcta =
                    $puntaje_obtenido >= ($puntaje * 0.70)
                    ? 1
                    : 0;

                $observacion =
                    "Corrección automática: positivo o negativo.";
            }

            /*
            --------------------------------------------------------------
            Ejercicio: par o impar
            --------------------------------------------------------------
            */

            elseif (
                str_contains($textoPregunta, 'par') ||
                str_contains($textoPregunta, 'impar')
            ) {

                $puntos = 0;

                if (str_contains($codigo, 'if')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'else')) {
                    $puntos++;
                }

                if (str_contains($codigo, '%')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'cin')) {
                    $puntos++;
                }

                if (str_contains($codigo, 'cout')) {
                    $puntos++;
                }

                $puntaje_obtenido =
                    ($puntos / 5) * $puntaje;

                $correcta =
                    $puntaje_obtenido >= ($puntaje * 0.70)
                    ? 1
                    : 0;

                $observacion =
                    "Corrección automática: par o impar.";
            }

            /*
            --------------------------------------------------------------
            Código complejo: Ollama
            --------------------------------------------------------------
            */

            else {

                $resultadoIA = corregirConOllama(
                    $pregunta['pregunta'],
                    $respuesta_alumno,
                    $pregunta['respuesta_correcta'],
                    $puntaje
                );

                $correcta =
                    !empty($resultadoIA['correcta'])
                    ? 1
                    : 0;

                $puntaje_obtenido =
                    isset($resultadoIA['puntaje'])
                    ? (float) $resultadoIA['puntaje']
                    : 0;

                $observacion =
                    isset($resultadoIA['observacion'])
                    ? (string) $resultadoIA['observacion']
                    : "Corrección realizada con Ollama.";
            }

            /*
            Evitar puntajes fuera del rango permitido.
            */

            if ($puntaje_obtenido > $puntaje) {
                $puntaje_obtenido = $puntaje;
            }

            if ($puntaje_obtenido < 0) {
                $puntaje_obtenido = 0;
            }

            $nota += $puntaje_obtenido;

            $stmtGuardarRespuesta->bind_param(
                "iisids",
                $intento_id,
                $pregunta_id,
                $respuesta_alumno,
                $correcta,
                $puntaje_obtenido,
                $observacion
            );

            $stmtGuardarRespuesta->execute();

            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Preguntas de respuesta escrita
        |--------------------------------------------------------------------------
        */

        if ($pregunta['tipo'] === 'respuesta_texto') {

            $respuesta_alumno =
                trim((string) $_POST[$campo]);

            $correcta = 0;
            $puntaje_obtenido = 0;
            $observacion = "";

            $metodo_correccion =
                $pregunta['metodo_correccion'] ?? 'manual';

            /*
            --------------------------------------------------------------
            Corrección manual
            --------------------------------------------------------------
            */

            if ($metodo_correccion === 'manual') {

                $correcta = 0;
                $puntaje_obtenido = 0;

                $observacion =
                    "Respuesta pendiente de revisión manual.";
            }

            /*
            --------------------------------------------------------------
            Corrección por palabras clave
            --------------------------------------------------------------
            */

            elseif ($metodo_correccion === 'palabras_clave') {

                $respuesta_esperada =
                    strtolower(
                        trim(
                            (string)
                            $pregunta['respuesta_correcta']
                        )
                    );

                $respuesta_comparar =
                    strtolower($respuesta_alumno);

                /*
                Las palabras pueden guardarse separadas por:
                coma, punto y coma o salto de línea.
                */

                $palabras = preg_split(
                    '/[,;\r\n]+/',
                    $respuesta_esperada
                );

                $palabras_validas = [];

                foreach ($palabras as $palabra) {

                    $palabra = trim($palabra);

                    if ($palabra !== '') {
                        $palabras_validas[] = $palabra;
                    }
                }

                $total_palabras =
                    count($palabras_validas);

                $palabras_encontradas = 0;

                foreach ($palabras_validas as $palabra) {

                    if (
                        str_contains(
                            $respuesta_comparar,
                            $palabra
                        )
                    ) {
                        $palabras_encontradas++;
                    }
                }

                if ($total_palabras > 0) {

                    $puntaje_obtenido =
                        (
                            $palabras_encontradas /
                            $total_palabras
                        ) * $puntaje;
                }

                $correcta =
                    $puntaje_obtenido >= ($puntaje * 0.70)
                    ? 1
                    : 0;

                $observacion =
                    "Corrección automática por palabras clave.";
            }

            /*
            --------------------------------------------------------------
            Corrección con Ollama
            --------------------------------------------------------------
            */

            else {

                $resultadoIA = corregirConOllama(
                    $pregunta['pregunta'],
                    $respuesta_alumno,
                    $pregunta['respuesta_correcta'],
                    $puntaje
                );

                $correcta =
                    !empty($resultadoIA['correcta'])
                    ? 1
                    : 0;

                $puntaje_obtenido =
                    isset($resultadoIA['puntaje'])
                    ? (float) $resultadoIA['puntaje']
                    : 0;

                $observacion =
                    isset($resultadoIA['observacion'])
                    ? (string) $resultadoIA['observacion']
                    : "Corrección realizada con Ollama.";
            }

            if ($puntaje_obtenido > $puntaje) {
                $puntaje_obtenido = $puntaje;
            }

            if ($puntaje_obtenido < 0) {
                $puntaje_obtenido = 0;
            }

            $nota += $puntaje_obtenido;

            $stmtGuardarRespuesta->bind_param(
                "iisids",
                $intento_id,
                $pregunta_id,
                $respuesta_alumno,
                $correcta,
                $puntaje_obtenido,
                $observacion
            );

            $stmtGuardarRespuesta->execute();

            continue;
        }

        /*
|--------------------------------------------------------------------------
| Preguntas de selección múltiple
|--------------------------------------------------------------------------
*/

if ($pregunta['tipo'] === 'seleccion_multiple') {

    /*
    Las opciones marcadas llegan como arreglo:
    pregunta_15[] = [2, 4, 6]
    */

    $opciones_marcadas = isset($_POST[$campo])
        && is_array($_POST[$campo])
        ? $_POST[$campo]
        : [];

    /*
    Convertir todos los valores a enteros válidos.
    */

    $opciones_marcadas = array_map(
        'intval',
        $opciones_marcadas
    );

    $opciones_marcadas = array_filter(
        $opciones_marcadas,
        function ($id) {
            return $id > 0;
        }
    );

    /*
    Eliminar posibles opciones repetidas.
    */

    $opciones_marcadas = array_values(
        array_unique($opciones_marcadas)
    );

    /*
    Obtener todas las opciones correctas de la pregunta.
    */

    $stmtCorrectas = $conn->prepare("
        SELECT id
        FROM opciones
        WHERE pregunta_id = ?
          AND es_correcta = 1
        ORDER BY id ASC
    ");

    $stmtCorrectas->bind_param(
        "i",
        $pregunta_id
    );

    $stmtCorrectas->execute();

    $resultadoCorrectas =
        $stmtCorrectas->get_result();

    $opciones_correctas = [];

    while (
        $filaCorrecta =
        $resultadoCorrectas->fetch_assoc()
    ) {

        $opciones_correctas[] =
            (int) $filaCorrecta['id'];
    }

    /*
    Verificar que todas las opciones seleccionadas
    realmente pertenezcan a la pregunta.
    */

    $opciones_validas = [];

    if (count($opciones_marcadas) > 0) {

        $marcadores = implode(
            ',',
            array_fill(
                0,
                count($opciones_marcadas),
                '?'
            )
        );

        $tipos = str_repeat(
            'i',
            count($opciones_marcadas)
        );

        $sqlOpcionesValidas = "
            SELECT
                id,
                opcion_texto
            FROM opciones
            WHERE pregunta_id = ?
              AND id IN ($marcadores)
            ORDER BY id ASC
        ";

        $stmtOpcionesValidas =
            $conn->prepare($sqlOpcionesValidas);

        $parametros = array_merge(
            [$pregunta_id],
            $opciones_marcadas
        );

        $tiposParametros =
            'i' . $tipos;

        $stmtOpcionesValidas->bind_param(
            $tiposParametros,
            ...$parametros
        );

        $stmtOpcionesValidas->execute();

        $resultadoOpcionesValidas =
            $stmtOpcionesValidas->get_result();

        while (
            $filaOpcion =
            $resultadoOpcionesValidas->fetch_assoc()
        ) {

            $opciones_validas[] = [
                'id' =>
                    (int) $filaOpcion['id'],

                'texto' =>
                    (string) $filaOpcion['opcion_texto']
            ];
        }
    }

    /*
    Construir el arreglo de IDs realmente válidos
    y el texto que se guardará en respuestas.
    */

    $ids_validos = [];
    $textos_seleccionados = [];

    foreach ($opciones_validas as $opcion_valida) {

        $ids_validos[] =
            $opcion_valida['id'];

        $textos_seleccionados[] =
            $opcion_valida['texto'];
    }

    /*
    Ordenar ambos arreglos para realizar
    una comparación exacta.
    */

    sort($opciones_correctas);
    sort($ids_validos);

    $correcta = 0;
    $puntaje_obtenido = 0;

    $respuesta_texto = implode(
        " | ",
        $textos_seleccionados
    );

    /*
    La respuesta es correcta únicamente si:
    - seleccionó todas las correctas;
    - no seleccionó ninguna incorrecta;
    - no faltó ninguna opción correcta.
    */

    if (
        count($opciones_correctas) > 0 &&
        $opciones_correctas === $ids_validos
    ) {

        $correcta = 1;
        $puntaje_obtenido = $puntaje;
        $nota += $puntaje;

        $observacion =
            "Todas las opciones seleccionadas son correctas.";

    } else {

        $observacion =
            "La combinación de opciones seleccionadas es incorrecta.";
    }

    $stmtGuardarRespuesta->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_texto,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmtGuardarRespuesta->execute();

    continue;
}

/*
|--------------------------------------------------------------------------
| Preguntas de selección única
|--------------------------------------------------------------------------
|
| Este bloque mantiene la compatibilidad con el tipo:
| opcion_multiple
|
*/

$opcion_id = (int) $_POST[$campo];

/*
Además del ID de la opción, verificamos que la opción
realmente pertenezca a la pregunta que está siendo corregida.
*/

$stmtOpcion = $conn->prepare("
    SELECT
        id,
        opcion_texto,
        es_correcta
    FROM opciones
    WHERE id = ?
      AND pregunta_id = ?
    LIMIT 1
");

$stmtOpcion->bind_param(
    "ii",
    $opcion_id,
    $pregunta_id
);

$stmtOpcion->execute();

$resultadoOpcion =
    $stmtOpcion->get_result();

$opcion = $resultadoOpcion->fetch_assoc();

$correcta = 0;
$puntaje_obtenido = 0;
$respuesta_texto = "";
$observacion = "";

if ($opcion) {

    $respuesta_texto =
        (string) $opcion['opcion_texto'];

    if ((int) $opcion['es_correcta'] === 1) {

        $correcta = 1;
        $puntaje_obtenido = $puntaje;
        $nota += $puntaje;

        $observacion =
            "Respuesta correcta.";

    } else {

        $observacion =
            "Respuesta incorrecta.";
    }

} else {

    $observacion =
        "La opción seleccionada no es válida.";
}

$stmtGuardarRespuesta->bind_param(
    "iisids",
    $intento_id,
    $pregunta_id,
    $respuesta_texto,
    $correcta,
    $puntaje_obtenido,
    $observacion
);

$stmtGuardarRespuesta->execute();
    }
    /*
    |--------------------------------------------------------------------------
    | Calcular nota sobre 100
    |--------------------------------------------------------------------------
    */

    $nota_final = 0;

    if ($puntaje_total > 0) {

        $nota_final =
            ($nota * 100) / $puntaje_total;
    }

    if ($nota_final > 100) {
        $nota_final = 100;
    }

    if ($nota_final < 0) {
        $nota_final = 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Finalizar intento
    |--------------------------------------------------------------------------
    */

    $stmtFinalizar = $conn->prepare("
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

    $stmtFinalizar->bind_param(
        "diii",
        $nota_final,
        $cambios_pestana,
        $intento_id,
        $usuario_id
    );

    $stmtFinalizar->execute();

    if ($stmtFinalizar->affected_rows !== 1) {
        throw new Exception(
            "No se pudo finalizar el intento."
        );
    }

    /*
    Guardar todos los cambios.
    */

    $conn->commit();

} catch (Throwable $error) {

    $conn->rollback();

    die(
        "Ocurrió un error al finalizar el examen: " .
        htmlspecialchars($error->getMessage())
    );
}

/*
|--------------------------------------------------------------------------
| Estado final
|--------------------------------------------------------------------------
*/

$estado =
    $nota_final >= 51
    ? "APROBADO"
    : "REPROBADO";

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Resultado</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Examen Finalizado</h1>

        <?php if ($cambios_pestana >= 2): ?>

            <p
                class="bienvenida"
                style="text-align:center;"
            >
                El examen fue enviado automáticamente por cambiar
                de pantalla dos veces.
            </p>

        <?php endif; ?>

        <p class="bienvenida">
            Nota obtenida:
        </p>

        <h2 style="text-align:center;">

            <?= number_format($nota_final, 2) ?>
            / 100

        </h2>

        <p style="text-align:center;">

            Puntaje interno:

            <?= number_format($nota, 2) ?>

            /

            <?= number_format($puntaje_total, 2) ?>

        </p>

        <p style="text-align:center;">

            Estado:

            <strong>
                <?= htmlspecialchars($estado) ?>
            </strong>

        </p>

        <p style="text-align:center;">

            Cambios de pestaña:

            <strong>
                <?= $cambios_pestana ?>
            </strong>

        </p>

        <br>

        <a
            href="dashboard.php"
            class="btn"
        >
            Volver al Dashboard
        </a>

    </div>

</div>

</body>
</html>