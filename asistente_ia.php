<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asistente IA</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Asistente IA</h1>

        <form action="generar_examen_ia.php" method="POST">

            <label>Describe el examen que quieres crear</label>

            <textarea
                name="instruccion"
                class="textarea-codigo"
                rows="10"
                required
                placeholder="Ejemplo: Crea un examen de Excel básico para primero de secundaria, con 10 preguntas de opción múltiple, 5 preguntas abiertas, duración 60 minutos, nivel fácil."
            ></textarea>

            <br><br>

            <button type="submit" class="btn">
                Generar examen con IA
            </button>

        </form>

        <br>

        <a href="dashboard.php" class="btn btn-salir">
            Volver
        </a>

    </div>
</div>

</body>
</html>