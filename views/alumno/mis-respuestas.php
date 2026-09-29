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

    <title>Mis respuestas</title>

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
            Mis respuestas
        </h1>


        <p
            class="bienvenida"
            style="text-align: center;"
        >
            <?= htmlspecialchars(
                $examen['titulo']
            ) ?>
        </p>


        <h2
            style="text-align: center;"
        >
            Nota:
            <?= number_format(
                (float)$intento['nota'],
                2
            ) ?>
            / 100
        </h2>


        <?php if (
            !empty($intento['numero_intento'])
        ): ?>

            <p style="text-align: center;">
                Intento:
                <strong>
                    <?= (int)$intento[
                        'numero_intento'
                    ] ?>
                </strong>
            </p>

        <?php endif; ?>


        <hr>


        <?php if (empty($respuestas)): ?>

            <p style="text-align: center;">
                No existen respuestas registradas
                para este intento.
            </p>

        <?php else: ?>

            <?php foreach (
                $respuestas as $respuesta
            ): ?>

                <div class="pregunta">

                    <h3>
                        <?= nl2br(
                            htmlspecialchars(
                                $respuesta[
                                    'pregunta'
                                ]
                            )
                        ) ?>
                    </h3>


                    <p>

                        <strong>
                            Tu respuesta:
                        </strong>

                        <br>

                        <?php if (
                            trim(
                                (string)$respuesta[
                                    'respuesta'
                                ]
                            ) !== ''
                        ): ?>

                            <?= nl2br(
                                htmlspecialchars(
                                    $respuesta[
                                        'respuesta'
                                    ]
                                )
                            ) ?>

                        <?php else: ?>

                            Sin respuesta

                        <?php endif; ?>

                    </p>


                    <p>

                        <strong>
                            Resultado:
                        </strong>

                        <?php if (
    (int)$respuesta[
        'correcta'
    ] === 1
): ?>

    <span class="resultado-correcto">
        Correcta
    </span>

<?php else: ?>

    <span class="resultado-incorrecto">
        Incorrecta
    </span>

<?php endif; ?>

                    </p>


                    <p>

                        <strong>
                            Puntaje:
                        </strong>

                        <?= number_format(
                            (float)$respuesta[
                                'puntaje_obtenido'
                            ],
                            2
                        ) ?>

                    </p>


                    <?php if (
                        !empty(
                            $respuesta[
                                'observacion'
                            ]
                        )
                    ): ?>

                        <p>

                            <strong>
                                Observación:
                            </strong>

                            <br>

                            <?= nl2br(
                                htmlspecialchars(
                                    $respuesta[
                                        'observacion'
                                    ]
                                )
                            ) ?>

                        </p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>


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