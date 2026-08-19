<?php

session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| Obtener formulario
|--------------------------------------------------------------------------
*/

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($examen_id <= 0) {
    die("Formulario inválido.");
}

$stmtExamen = $conn->prepare("
    SELECT
        id,
        titulo,
        descripcion,
        mostrar_preguntas_antes
    FROM examenes
    WHERE id = ?
      AND estado = 'publicado'
    LIMIT 1
");

$stmtExamen->bind_param(
    "i",
    $examen_id
);

$stmtExamen->execute();

$examen = $stmtExamen
    ->get_result()
    ->fetch_assoc();

if (!$examen) {
    die("El formulario no existe o ya no está disponible.");
}

/*
|--------------------------------------------------------------------------
| Verificar permiso
|--------------------------------------------------------------------------
*/

if ((int)$examen['mostrar_preguntas_antes'] !== 1) {
    die("No está permitido ver las preguntas de este formulario.");
}

/*
|--------------------------------------------------------------------------
| Obtener preguntas
|--------------------------------------------------------------------------
*/

$stmtPreguntas = $conn->prepare("
    SELECT
        id,
        pregunta,
        tipo,
        imagen_tipo,
        imagen
    FROM preguntas
    WHERE examen_id = ?
    ORDER BY id ASC
");

$stmtPreguntas->bind_param(
    "i",
    $examen_id
);

$stmtPreguntas->execute();

$preguntas = $stmtPreguntas->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Ver preguntas</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>
            <?= htmlspecialchars($examen['titulo']) ?>
        </h1>

        <?php if (!empty($examen['descripcion'])): ?>

            <p class="correo">
                <?= nl2br(
                    htmlspecialchars($examen['descripcion'])
                ) ?>
            </p>

        <?php endif; ?>

        <p class="correo">
            Vista previa de las preguntas.
            No se ha iniciado ningún intento.
        </p>

        <?php if ($preguntas->num_rows === 0): ?>

            <p>
                Este formulario todavía no tiene preguntas.
            </p>

        <?php else: ?>

            <?php $numero = 1; ?>

            <?php while ($pregunta = $preguntas->fetch_assoc()): ?>

                <div class="pregunta">

                    <h3>
                        <?= $numero ?>.
                        <?= nl2br(
                            htmlspecialchars($pregunta['pregunta'])
                        ) ?>
                                       </h3>

                    <?php if (!empty($pregunta['imagen'])): ?>

                        <div
                            class="imagen-pregunta"
                            style="
                                margin: 15px 0 20px 0;
                                text-align: center;
                            "
                        >

                            <img
                                src="<?= htmlspecialchars($pregunta['imagen']) ?>"
                                alt="Imagen de la pregunta"
                                style="
                                    max-width: 100%;
                                    max-height: 450px;
                                    width: auto;
                                    height: auto;
                                    object-fit: contain;
                                    border-radius: 6px;
                                "
                            >

                        </div>

                    <?php endif; ?>

                    <?php

                    /*
                    Mostrar opciones solamente como texto.
                    No mostramos cuál es correcta.
                    */

                    if (
                        $pregunta['tipo'] === 'opcion_multiple' ||
                        $pregunta['tipo'] === 'seleccion_multiple'
                    ):

                        $pregunta_id =
                            (int)$pregunta['id'];

                        $stmtOpciones = $conn->prepare("
                            SELECT opcion_texto
                            FROM opciones
                            WHERE pregunta_id = ?
                            ORDER BY id ASC
                        ");

                        $stmtOpciones->bind_param(
                            "i",
                            $pregunta_id
                        );

                        $stmtOpciones->execute();

                        $opciones =
                            $stmtOpciones->get_result();

                    ?>

                        <?php while ($opcion = $opciones->fetch_assoc()): ?>

                            <p>
                                • <?= htmlspecialchars(
                                    $opcion['opcion_texto']
                                ) ?>
                            </p>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </div>

                <hr>

                <?php $numero++; ?>

            <?php endwhile; ?>

        <?php endif; ?>

        <br>

        <a
            href="resolver_examen.php?examen_id=<?= $examen_id ?>"
            class="btn"
        >
            Iniciar formulario
        </a>

        <a
            href="seleccionar_formulario.php"
            class="btn btn-salir"
        >
            Volver
        </a>

    </div>

</div>

</body>
</html>