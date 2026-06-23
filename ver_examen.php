<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "
SELECT
    p.id,
    p.tipo,
    p.pregunta,
    p.puntaje
FROM preguntas p
INNER JOIN examenes e
ON p.examen_id = e.id
WHERE e.estado='publicado'
ORDER BY p.id
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vista Previa Examen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="card">

        <h1>Vista Previa del Examen</h1>

        <?php if($result->num_rows > 0): ?>

            <?php $numero = 1; ?>

            <?php while($pregunta = $result->fetch_assoc()): ?>

                <div class="pregunta">

                    <h3>
                        <?= $numero ?>.
                        <?= htmlspecialchars($pregunta['pregunta']) ?>
                    </h3>

                    <p class="puntaje">
                        Puntaje:
                        <?= $pregunta['puntaje'] ?>
                    </p>

                    <?php
                    $idPregunta = $pregunta['id'];

                    $opciones = $conn->query("
                        SELECT *
                        FROM opciones
                        WHERE pregunta_id = $idPregunta
                    ");
                    ?>

                    <?php if($opciones->num_rows > 0): ?>

                        <?php while($opcion = $opciones->fetch_assoc()): ?>

                            <div class="opcion">
                                <input type="radio" disabled>
                                <?= htmlspecialchars($opcion['opcion_texto']) ?>
                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <textarea
                            rows="3"
                            disabled
                            placeholder="Respuesta del estudiante..."
                        ></textarea>

                    <?php endif; ?>

                </div>

                <hr>

                <?php $numero++; ?>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No existe un examen publicado.</p>

        <?php endif; ?>

        <br>

        <a href="dashboard.php" class="btn">
            Volver
        </a>

    </div>

</div>

</body>
</html>