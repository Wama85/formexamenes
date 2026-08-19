<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Formularios</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="card">

        <h1>Formularios</h1>

        <p class="bienvenida">
            Bienvenido,
            <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
        </p>

        <p class="correo">
            <?= htmlspecialchars($_SESSION['correo']) ?>
        </p>

        <div class="acciones">

       <?php if ($_SESSION['rol'] == 'docente'): ?>

    <a
        href="seleccionar_formulario_preguntas.php"
        class="btn"
    >
        Preguntas
    </a>

<?php endif; ?>

            <a href="seleccionar_formulario.php" class="btn">
                Resolver
            </a>

            <?php if ($_SESSION['rol'] == 'docente'): ?>
                <a href="admin_examen.php" class="btn">
                    Administrar
                </a>
            <?php endif; ?>
            
<?php if($_SESSION['rol'] == 'docente'): ?>

<a href="resultados.php" class="btn">
    Resultados
</a>

<?php endif; ?>
<?php if($_SESSION['rol'] == 'alumno'): ?>

    <a href="mis_respuestas.php" class="btn">
        Ver mis respuestas
    </a>

<?php endif; ?>
<?php if ($_SESSION['rol'] == 'docente'): ?>
    <a href="asistente_ia.php" class="btn">
        Asistente IA
    </a>
<?php endif; ?>
            <a href="logout.php" class="btn btn-salir">
                Cerrar Sesión
            </a>

        </div>

    </div>

</div>

</body>
</html>