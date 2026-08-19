<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$examen = $conn->query("
    SELECT id, mostrar_respuestas
    FROM examenes
    WHERE estado='publicado'
    LIMIT 1
")->fetch_assoc();

if (!$examen) {
    die("No existe Formulario publicado.");
}

if ($examen['mostrar_respuestas'] == 0) {
    die("El docente todavía no habilitó la revisión de respuestas.");
}

$intento = $conn->query("
    SELECT id, nota
    FROM intentos
    WHERE usuario_id = $usuario_id
    AND examen_id = {$examen['id']}
    AND finalizado = 1
    ORDER BY id DESC
    LIMIT 1
")->fetch_assoc();

if (!$intento) {
    die("Todavía no finalizaste el Formulario.");
}

$respuestas = $conn->query("
    SELECT
        p.pregunta,
        p.tipo,
        r.respuesta,
        r.correcta,
        r.puntaje_obtenido,
        r.observacion
    FROM respuestas r
    INNER JOIN preguntas p
    ON r.pregunta_id = p.id
    WHERE r.intento_id = {$intento['id']}
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis respuestas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Mis respuestas</h1>

        <h2 style="text-align:center;">
            Nota: <?= number_format($intento['nota'], 2) ?> / 100
        </h2>

        <hr>

        <?php while($r = $respuestas->fetch_assoc()): ?>

            <div class="pregunta">

                <h3><?= nl2br(htmlspecialchars($r['pregunta'])) ?></h3>

                <p>
                    <strong>Tu respuesta:</strong><br>
                    <?= nl2br(htmlspecialchars($r['respuesta'])) ?>
                </p>

                <p>
                    <strong>Resultado:</strong>
                    <?php if($r['correcta']): ?>
                        <span style="color:green;">Correcta</span>
                    <?php else: ?>
                        <span style="color:red;">Incorrecta</span>
                    <?php endif; ?>
                </p>

                <p>
                    <strong>Puntaje:</strong>
                    <?= number_format($r['puntaje_obtenido'], 2) ?>
                </p>

                <?php if(!empty($r['observacion'])): ?>
                    <p>
                        <strong>Observación:</strong><br>
                        <?= htmlspecialchars($r['observacion']) ?>
                    </p>
                <?php endif; ?>

            </div>

            <hr>

        <?php endwhile; ?>

        <a href="dashboard.php" class="btn">
            Volver
        </a>

    </div>
</div>

</body>
</html>