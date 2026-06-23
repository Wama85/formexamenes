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

$resultados = $conn->query("
SELECT
    i.id,
    u.nombre,
    u.correo,
    i.nota,
    i.fecha_inicio,
    i.fecha_fin,
    i.cambios_pestana
FROM intentos i
INNER JOIN usuarios u
ON i.usuario_id = u.id
WHERE i.finalizado = 1
ORDER BY i.id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container" style="max-width:1200px;">

    <div class="card">

        <h1>Resultados del Examen</h1>
        <?php
$examen = $conn->query("
    SELECT id, mostrar_respuestas
    FROM examenes
    WHERE estado='publicado'
    LIMIT 1
")->fetch_assoc();
?>

<?php if($examen): ?>

    <?php if($examen['mostrar_respuestas'] == 1): ?>
        <a href="cambiar_mostrar_respuestas.php?valor=0" class="btn btn-salir">
            Ocultar respuestas a alumnos
        </a>
    <?php else: ?>
        <a href="cambiar_mostrar_respuestas.php?valor=1" class="btn">
            Mostrar respuestas a alumnos
        </a>
    <?php endif; ?>

<?php endif; ?>
<br>
<a href="exportar_notas.php" class="btn">
    Exportar notas CSV
</a>
        <table class="tabla">

            <thead>
                <tr>
                    <th>Alumno</th>
                    <th>Correo</th>
                    <th>Nota</th>
                    <th>Cambios</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Detalle</th>
                </tr>
            </thead>

            <tbody>

                <?php while($fila = $resultados->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($fila['nombre']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($fila['correo']) ?>
                    </td>

                    <td>
                        <?= $fila['nota'] ?>
                    </td>

                    <td>
                        <?= $fila['cambios_pestana'] ?>
                    </td>

                    <td>
                        <?= $fila['fecha_inicio'] ?>
                    </td>

                    <td>
                        <?= $fila['fecha_fin'] ?>
                    </td>

                    <td>
                        <a
                        href="detalle_resultado.php?id=<?= $fila['id'] ?>"
                        class="btn-mini"
                        >
                            Ver
                        </a>
                    </td>

                </tr>

                <?php endwhile; ?>

            </tbody>

        </table>

        <br>

        <a href="dashboard.php" class="btn">
            Volver
        </a>

    </div>

</div>

</body>
</html>