<?php

declare(strict_types=1);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Resultado</title>

    <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/public/assets/css/resolver.css?v=<?= filemtime(
        BASE_PATH . '/public/assets/css/resolver.css'
    ) ?>"
>

</head>

<body>

<div class="container">

    <div class="card">

        <h1>
            Formulario Finalizado
        </h1>


        <?php if ($cambiosPestana >= 2): ?>

            <p
                class="bienvenida"
                style="text-align: center;"
            >
                El formulario fue enviado
                automáticamente por cambiar
                de pantalla dos veces.
            </p>

        <?php endif; ?>


        <p
            class="bienvenida"
            style="text-align: center;"
        >
            Nota obtenida:
        </p>


        <h2
            style="text-align: center;"
        >
            <?= number_format(
                $notaFinal,
                2
            ) ?>
            / 100
        </h2>


        <p
            style="text-align: center;"
        >
            Puntaje interno:

            <strong>
                <?= number_format(
                    $nota,
                    2
                ) ?>
                /
                <?= number_format(
                    $puntajeTotal,
                    2
                ) ?>
            </strong>
        </p>


        <p
            style="text-align: center;"
        >
            Estado:

            <strong>
                <?= htmlspecialchars(
                    $estado
                ) ?>
            </strong>
        </p>


        <p
            style="text-align: center;"
        >
            Cambios de pantalla:

            <strong>
                <?= (int)$cambiosPestana ?>
            </strong>
        </p>


        <br>


        <a
            href="<?= BASE_URL ?>/formularios/disponibles"
            class="btn"
        >
            Volver a Formularios
        </a>

    </div>

</div>

</body>
</html>