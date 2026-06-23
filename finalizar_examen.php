<?php
session_start();
require_once "conexion.php";
require_once "ollama.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_POST['intento_id'])) {
    die("Intento inválido.");
}

$intento_id = (int)$_POST['intento_id'];

$nota = 0;
$puntaje_total = 0;

$preguntas = $conn->query("SELECT * FROM preguntas");

while ($pregunta = $preguntas->fetch_assoc()) {

    $pregunta_id = $pregunta['id'];
    $puntaje = (float)$pregunta['puntaje'];

    $puntaje_total += $puntaje;

    $campo = "pregunta_" . $pregunta_id;

    if (!isset($_POST[$campo])) {
        continue;
    }

    if ($pregunta['tipo'] == 'codigo') {

        $respuesta_alumno = $_POST[$campo] ?? '';
        
$codigo = strtolower($respuesta_alumno);

if (str_contains(strtolower($pregunta['pregunta']), 'hola mundo')) {

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

    $puntaje_obtenido = ($puntos / 3) * $puntaje;

    $correcta = $puntaje_obtenido >= ($puntaje * 0.70) ? 1 : 0;

    $observacion = "Corrección automática por palabras clave.";

    $nota += $puntaje_obtenido;

    $stmt = $conn->prepare("
        INSERT INTO respuestas(
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido,
            observacion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_alumno,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmt->execute();

    continue;

}
if (
    str_contains(strtolower($pregunta['pregunta']), 'suma')
    &&
    str_contains(strtolower($pregunta['pregunta']), 'dos')
) {

    $puntos = 0;

    if (str_contains($codigo, 'cin'))
        $puntos++;

    if (str_contains($codigo, 'cout'))
        $puntos++;

    if (str_contains($codigo, '+'))
        $puntos++;

    if (str_contains($codigo, 'int'))
        $puntos++;

    $puntaje_obtenido = ($puntos / 4) * $puntaje;

    $correcta = $puntaje_obtenido >= ($puntaje * 0.70) ? 1 : 0;

    $observacion = "Corrección automática suma.";

    $nota += $puntaje_obtenido;

    $stmt = $conn->prepare("
        INSERT INTO respuestas(
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido,
            observacion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_alumno,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmt->execute();

    continue;
}

if (
    str_contains(strtolower($pregunta['pregunta']), 'positivo')
    ||
    str_contains(strtolower($pregunta['pregunta']), 'negativo')
) {

    $puntos = 0;

    if (str_contains($codigo, 'if'))
        $puntos++;

    if (str_contains($codigo, 'else'))
        $puntos++;

    if (str_contains($codigo, 'cin'))
        $puntos++;

    if (str_contains($codigo, 'cout'))
        $puntos++;

    if (str_contains($codigo, 'numero'))
        $puntos++;

    $puntaje_obtenido = ($puntos / 5) * $puntaje;

    $correcta = $puntaje_obtenido >= ($puntaje * 0.70) ? 1 : 0;

    $observacion = "Corrección automática positivo o negativo.";

    $nota += $puntaje_obtenido;

    $stmt = $conn->prepare("
        INSERT INTO respuestas(
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido,
            observacion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_alumno,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmt->execute();

    continue;
}
if (
    str_contains(strtolower($pregunta['pregunta']), 'par')
    ||
    str_contains(strtolower($pregunta['pregunta']), 'impar')
) {

    $puntos = 0;

    if (str_contains($codigo, 'if'))
        $puntos++;

    if (str_contains($codigo, 'else'))
        $puntos++;

    if (str_contains($codigo, '%'))
        $puntos++;

    if (str_contains($codigo, 'cin'))
        $puntos++;

    if (str_contains($codigo, 'cout'))
        $puntos++;

    $puntaje_obtenido = ($puntos / 5) * $puntaje;

    $correcta = $puntaje_obtenido >= ($puntaje * 0.70) ? 1 : 0;

    $observacion = "Corrección automática par o impar.";

    $nota += $puntaje_obtenido;

    $stmt = $conn->prepare("
        INSERT INTO respuestas(
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido,
            observacion
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisids",
        $intento_id,
        $pregunta_id,
        $respuesta_alumno,
        $correcta,
        $puntaje_obtenido,
        $observacion
    );

    $stmt->execute();

    continue;
}

        $resultadoIA = corregirConOllama(
            $pregunta['pregunta'],
            $respuesta_alumno,
            $pregunta['respuesta_correcta'],
            $puntaje
        );


        $correcta = $resultadoIA['correcta'] ? 1 : 0;
        $puntaje_obtenido = (float)$resultadoIA['puntaje'];
        $observacion = $resultadoIA['observacion'];

        if ($puntaje_obtenido > $puntaje) {
            $puntaje_obtenido = $puntaje;
        }

        if ($puntaje_obtenido < 0) {
            $puntaje_obtenido = 0;
        }

        $nota += $puntaje_obtenido;

        $stmt = $conn->prepare("
            INSERT INTO respuestas(
                intento_id,
                pregunta_id,
                respuesta,
                correcta,
                puntaje_obtenido,
                observacion
            )
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisids",
            $intento_id,
            $pregunta_id,
            $respuesta_alumno,
            $correcta,
            $puntaje_obtenido,
            $observacion
        );

        $stmt->execute();

        continue;
    }

    $opcion_id = (int)$_POST[$campo];

    $opcion = $conn->query("
        SELECT *
        FROM opciones
        WHERE id = $opcion_id
        LIMIT 1
    ")->fetch_assoc();

    $correcta = 0;
    $puntaje_obtenido = 0;
    $respuesta_texto = '';

    if ($opcion) {
        $respuesta_texto = $opcion['opcion_texto'];

        if ($opcion['es_correcta']) {
            $correcta = 1;
            $puntaje_obtenido = $puntaje;
            $nota += $puntaje;
        }
    }

    $stmt = $conn->prepare("
        INSERT INTO respuestas(
            intento_id,
            pregunta_id,
            respuesta,
            correcta,
            puntaje_obtenido
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "iisid",
        $intento_id,
        $pregunta_id,
        $respuesta_texto,
        $correcta,
        $puntaje_obtenido
    );

    $stmt->execute();
}

$nota_final = 0;

if ($puntaje_total > 0) {
    $nota_final = ($nota * 100) / $puntaje_total;
}

$cambios_pestana = isset($_POST['cambios_pestana'])
    ? (int)$_POST['cambios_pestana']
    : 0;

$stmt = $conn->prepare("
UPDATE intentos
SET
    fecha_fin = NOW(),
    nota = ?,
    finalizado = 1,
    cambios_pestana = ?
WHERE id = ?
");

$stmt->bind_param(
    "dii",
    $nota_final,
    $cambios_pestana,
    $intento_id
);

$stmt->execute();

$estado = $nota_final >= 51 ? "APROBADO" : "REPROBADO";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Examen Finalizado</h1>

        <p class="bienvenida">Nota obtenida:</p>

        <h2 style="text-align:center;">
            <?= number_format($nota_final, 2) ?> / 100
        </h2>

        <p style="text-align:center;">
            Puntaje interno:
            <?= number_format($nota, 2) ?>
            /
            <?= number_format($puntaje_total, 2) ?>
        </p>

        <p style="text-align:center;">
            Estado:
            <strong><?= $estado ?></strong>
        </p>

        <p style="text-align:center;">
            Cambios de pestaña:
            <strong><?= $cambios_pestana ?></strong>
        </p>

        <br>

        <a href="dashboard.php" class="btn">
            Volver al Dashboard
        </a>

    </div>
</div>

</body>
</html>