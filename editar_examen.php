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

$id = (int)$_GET['id'];

$examen = $conn->query("
    SELECT *
    FROM examenes
    WHERE id = $id
")->fetch_assoc();

if (!$examen) {
    die("Examen no encontrado.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Formulario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">
    <div class="card">

        <h1>Editar Formulario</h1>

        <form action="actualizar_examen.php" method="POST">

            <input type="hidden" name="id" value="<?= $examen['id'] ?>">

            <label>Título</label>
            <input type="text" name="titulo" class="input"
                   value="<?= htmlspecialchars($examen['titulo']) ?>" required>

            <label>Descripción</label>
            <textarea name="descripcion" class="textarea-codigo" rows="4"><?= htmlspecialchars($examen['descripcion']) ?></textarea>

            <label>Tiempo en minutos</label>
            <input type="number" name="tiempo_minutos" class="input"
                   value="<?= $examen['tiempo_minutos'] ?>" required>
<label>Cantidad de preguntas por estudiante</label>

<input
    type="number"
    name="cantidad_preguntas"
    class="input"
    min="1"
    value="<?= $examen['cantidad_preguntas'] ?>"
    required
>
<label>Cantidad de intentos permitidos</label>

<input
    type="number"
    name="cantidad_intentos"
    class="input"
    min="1"
    value="<?= (int) $examen['cantidad_intentos'] ?>"
    required
>
            <label>Estado</label>
            <select name="estado" class="input">
                <option value="borrador" <?= $examen['estado']=='borrador' ? 'selected' : '' ?>>Borrador</option>
                <option value="publicado" <?= $examen['estado']=='publicado' ? 'selected' : '' ?>>Publicado</option>
                <option value="cerrado" <?= $examen['estado']=='cerrado' ? 'selected' : '' ?>>Cerrado</option>
            </select>
<label>
   <label>
    <input
        type="checkbox"
        name="mostrar_preguntas_antes"
        value="1"
        <?= !empty($examen['mostrar_preguntas_antes'])
            ? 'checked'
            : '' ?>
    >

    Permitir que los alumnos vean las preguntas antes de iniciar
</label> <br><br>
            <label>
                <input type="checkbox" name="mostrar_respuestas" value="1"
                    <?= $examen['mostrar_respuestas'] ? 'checked' : '' ?>>
                Permitir que los alumnos vean sus respuestas
            </label>

            <br><br>

            <button type="submit" class="btn">
                Actualizar Formulario
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