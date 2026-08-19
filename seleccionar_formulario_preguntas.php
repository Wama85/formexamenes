<?php

session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'docente'
) {
    die("Acceso denegado.");
}

/*
|--------------------------------------------------------------------------
| Obtener formularios publicados
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        titulo
    FROM examenes
    WHERE estado = 'publicado'
    ORDER BY id DESC
");

$stmt->execute();

$formularios = $stmt->get_result();

$totalFormularios = $formularios->num_rows;

/*
|--------------------------------------------------------------------------
| Si no existe ningún formulario publicado
|--------------------------------------------------------------------------
*/

if ($totalFormularios === 0) {
    die("No existe ningún formulario publicado.");
}

/*
|--------------------------------------------------------------------------
| Si existe uno solo, entrar directamente
|--------------------------------------------------------------------------
*/

if ($totalFormularios === 1) {

    $formulario = $formularios->fetch_assoc();

    header(
        "Location: admin_preguntas.php?examen_id=" .
        (int) $formulario['id']
    );

    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Seleccionar formulario</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Seleccionar formulario</h1>

        <p>
            Seleccione el formulario cuyas preguntas desea administrar.
        </p>

        <form
            method="GET"
            action="admin_preguntas.php"
        >

            <label for="examen_id">
                Formulario
            </label>

            <select
                name="examen_id"
                id="examen_id"
                class="input"
                required
            >

                <option value="">
                    Seleccione un formulario
                </option>

                <?php while (
                    $formulario = $formularios->fetch_assoc()
                ): ?>

                    <option
                        value="<?= (int) $formulario['id'] ?>"
                    >
                        <?= htmlspecialchars(
                            $formulario['titulo']
                        ) ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <br><br>

            <button
                type="submit"
                class="btn"
            >
                Ver preguntas
            </button>

        </form>

        <br>

        <a
            href="dashboard.php"
            class="btn btn-salir"
        >
            Volver
        </a>

    </div>

</div>

</body>
</html>