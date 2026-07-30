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
    <title>Crear Examen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Crear Examen</h1>

        <form action="guardar_examen.php" method="POST">

            <label>Título</label>
            <input type="text" name="titulo" class="input" required>

            <label>Descripción</label>
            <textarea name="descripcion" class="textarea-codigo" rows="4"></textarea>

            <label>Tiempo en minutos</label>
            <input type="number" name="tiempo_minutos" class="input" value="90" required>
<label>Cantidad de preguntas que verá cada estudiante</label>

<input
    type="number"
    name="cantidad_preguntas"
    class="input"
    value="8"
    min="1"
    required
>
            <label>Estado</label>
            <select name="estado" class="input">
                <option value="borrador">Borrador</option>
                <option value="publicado">Publicado</option>
                <option value="cerrado">Cerrado</option>
            </select>

            <br><br>

            <button type="submit" class="btn">
                Guardar examen
            </button>

        </form>

        <br>

        <a href="admin_examen.php" class="btn btn-salir">
            Volver
        </a>

    </div>
</div>

</body>
</html>