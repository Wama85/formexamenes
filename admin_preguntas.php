<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}

$examen_id = (int)$_GET['examen_id'];

$examen = $conn->query("
    SELECT *
    FROM examenes
    WHERE id = $examen_id
")->fetch_assoc();

if (!$examen) {
    die("Examen no encontrado.");
}

$preguntas = $conn->query("
    SELECT *
    FROM preguntas
    WHERE examen_id = $examen_id
    ORDER BY id ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Preguntas</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container" style="max-width:1000px;">
    <div class="card">

        <h1>Preguntas</h1>

        <p class="correo">
            Examen: <?= htmlspecialchars($examen['titulo']) ?>
        </p>
<?php if (isset($_GET['reiniciado'])): ?>

    <div
        style="
            background:#d4edda;
            color:#155724;
            padding:10px;
            border-radius:6px;
            margin-bottom:5px;
        "
    >
        Los intentos del examen fueron reiniciados correctamente.
    </div>

<?php endif; ?>
        <a
    href="crear_pregunta.php?examen_id=<?= $examen_id ?>"
    class="btn"
>
    Agregar pregunta
</a>

<form
    action="reiniciar_examen.php"
    method="POST"
   
    onsubmit="return confirm(
        '¿Está seguro de reiniciar todos los intentos de este examen? ' +
        'Se eliminarán las respuestas y notas de los estudiantes.'
    );"
>

    <input
        type="hidden"
        name="examen_id"
        value="<?= $examen_id ?>"
    >

    <button
        type="submit"
        class="btn btn-salir"
        style="
            width:100%;
            margin-top:12px;
        "
    >
        Reiniciar intentos
    </button>

</form>

<br><br>

        <table class="tabla">
            <thead>
    <tr>

        <th width="5%">#</th>

        <th width="45%">Pregunta</th>

        <th width="15%">Tipo</th>

        <th width="15%">Corrección</th>

        <th width="10%">Puntaje</th>

        <th width="20%">Acciones</th>

    </tr>
</thead>
            <tbody>
                <?php $n = 1; ?>
                <?php while($p = $preguntas->fetch_assoc()): ?>
                    <tr>

    <td><?= $n ?></td>

    <td>
        <?= nl2br(htmlspecialchars($p['pregunta'])) ?>
    </td>

    <td>

        <?php

        switch($p['tipo']){

            case 'opcion_multiple':
                echo "Opción múltiple";
            break;

            case 'codigo':
                echo "Código fuente";
            break;

            case 'respuesta_texto':
                echo "Respuesta escrita";
            break;

            default:
                echo $p['tipo'];

        }

        ?>

    </td>

    <td>

        <?php

        switch($p['metodo_correccion']){

            case 'manual':
                echo "Manual";
            break;

            case 'palabras_clave':
                echo "Palabras clave";
            break;

            case 'ollama':
                echo "Ollama";
            break;

            default:
                echo "-";

        }

        ?>

    </td>

    <td>

        <?= $p['puntaje'] ?>

    </td>

    <td>

        <a
        href="editar_pregunta.php?id=<?= $p['id'] ?>&examen_id=<?= $examen_id ?>"
        class="btn-mini">
             Editar
        </a>

        <br><br>

        <a
        href="#"
        class="btn-mini">
             Duplicar
        </a>

        <br><br>

        <a
        href="#"
        class="btn-mini">
             Eliminar
        </a>

    </td>

</tr>
                    <?php $n++; ?>
                <?php endwhile; ?>
            </tbody>
        </table>

        <br>

        <a href="admin_examen.php" class="btn btn-salir">
            Volver
        </a>

    </div>
</div>

</body>
</html>