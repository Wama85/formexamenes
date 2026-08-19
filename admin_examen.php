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

$examenes = $conn->query("
    SELECT *
    FROM examenes
    ORDER BY id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Exámenes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container" style="max-width:1000px;">
    <div class="card">

        <h1>Administrar Formularios</h1>

        <a href="crear_examen.php" class="btn">
            Crear nuevo formulario
        </a>

        <br><br>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Tiempo</th>
                    <th>Estado</th>
                    <th>Ver respuestas</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                <?php while($examen = $examenes->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($examen['titulo']) ?></td>

                        <td><?= $examen['tiempo_minutos'] ?> min</td>

                        <td><?= $examen['estado'] ?></td>

                        <td>
                            <?= $examen['mostrar_respuestas'] ? 'Sí' : 'No' ?>
                        </td>

                        <td>
                            <a class="btn-mini" href="editar_examen.php?id=<?= $examen['id'] ?>">
                                Editar
                            </a>
<a
    href="eliminar_formulario.php?id=<?= (int)$examen['id'] ?>"
    class="btn-mini"
    onclick="return confirm(
        '¿Está seguro de eliminar este formulario? ' +
        'Se eliminarán sus preguntas, respuestas, intentos e imágenes.'
    );"
>
     Eliminar
</a></br><br>
                            <a class="btn-mini" href="admin_preguntas.php?examen_id=<?= $examen['id'] ?>">
                                Preguntas
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <br>

        <a href="dashboard.php" class="btn btn-salir">
            Volver
        </a>

    </div>
</div>

</body>
</html>