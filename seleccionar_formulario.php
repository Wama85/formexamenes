<?php

session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Obtener formularios publicados
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        id,
        titulo,
        mostrar_preguntas_antes
    FROM examenes
    WHERE estado = 'publicado'
    ORDER BY id DESC
");

$stmt->execute();

$formularios = $stmt->get_result();

$total = $formularios->num_rows;

/*
|--------------------------------------------------------------------------
| Si no existe ningún formulario
|--------------------------------------------------------------------------
*/

if ($total === 0) {
    die("No existen formularios publicados.");
}

/*
|--------------------------------------------------------------------------
| Guardar formularios en un arreglo
|--------------------------------------------------------------------------
*/

$listaFormularios = [];

while ($fila = $formularios->fetch_assoc()) {
    $listaFormularios[] = $fila;
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

        <?php if ($total === 1): ?>

            <?php
            $formulario = $listaFormularios[0];
            ?>

            <p class="correo">

                Formulario:

                <strong>
                    <?= htmlspecialchars($formulario['titulo']) ?>
                </strong>

            </p>

            <a
                href="resolver_examen.php?examen_id=<?= (int)$formulario['id'] ?>"
                class="btn"
            >
                Iniciar formulario
            </a>

            <?php if (
                (int)$formulario['mostrar_preguntas_antes'] === 1
            ): ?>

                <br><br>

                <a
                    href="ver_preguntas.php?examen_id=<?= (int)$formulario['id'] ?>"
                    class="btn"
                >
                    Ver preguntas
                </a>

            <?php endif; ?>

        <?php else: ?>

            <form
                method="GET"
                action="resolver_examen.php"
                id="formSeleccion"
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
                        Seleccione...
                    </option>

                    <?php foreach ($listaFormularios as $formulario): ?>

                        <option
                            value="<?= (int)$formulario['id'] ?>"
                            data-ver="<?= (int)$formulario['mostrar_preguntas_antes'] ?>"
                        >
                            <?= htmlspecialchars($formulario['titulo']) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <br><br>

                <button
                    class="btn"
                    type="submit"
                >
                    Iniciar formulario
                </button>

                <br><br>

                <button
                    class="btn"
                    type="button"
                    id="btnVerPreguntas"
                    style="display:none;"
                >
                    Ver preguntas
                </button>

            </form>

        <?php endif; ?>

        <br><br>

        <a
            href="dashboard.php"
            class="btn btn-salir"
        >
            Volver
        </a>

    </div>

</div>

<?php if ($total > 1): ?>

<script>

const selector =
    document.getElementById("examen_id");

const btnVerPreguntas =
    document.getElementById("btnVerPreguntas");

selector.addEventListener(
    "change",
    function () {

        const opcion =
            selector.options[
                selector.selectedIndex
            ];

        if (
            opcion.value !== "" &&
            opcion.dataset.ver === "1"
        ) {

            btnVerPreguntas.style.display =
                "block";

        } else {

            btnVerPreguntas.style.display =
                "none";

        }

    }
);

btnVerPreguntas.addEventListener(
    "click",
    function () {

        const examenId =
            selector.value;

        if (examenId === "") {
            return;
        }

        window.location.href =
            "ver_preguntas.php?examen_id=" +
            examenId;

    }
);

</script>

<?php endif; ?>

</body>
</html>