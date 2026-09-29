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

    <title>
        Resolver Formulario
    </title>

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
            <?= htmlspecialchars(
                $examen['titulo']
            ) ?>
        </h1>

        <p class="correo">
            Intento:
            <?= (int)$numeroIntento ?>
            de
            <?= (int)$cantidadIntentos ?>
        </p>


        <!-- TEMPORIZADOR -->

        <div
            id="temporizador"
            class="temporizador"
        ></div>


        <!-- FORMULARIO -->

        <form
            id="formExamen"
            method="POST"
            action="<?= BASE_URL ?>/finalizar-examen"
        >

            <input
                type="hidden"
                name="intento_id"
                value="<?= (int)$intentoId ?>"
            >

            <input
                type="hidden"
                name="examen_id"
                value="<?= (int)$examenId ?>"
            >

            <input
                type="hidden"
                name="cambios_pestana"
                id="cambios_pestana"
                value="<?= (int)$cambiosGuardados ?>"
            >


            <?php $numero = 1; ?>


            <?php foreach (
                $preguntas as $pregunta
            ): ?>

                <?php

                $idPregunta =
                    (int)$pregunta['id'];

                $respuestaGuardada =
                    $respuestasGuardadas[
                        $idPregunta
                    ] ?? '';

                ?>


                <div class="pregunta">

                    <h3>

                        <?= $numero ?>.

                        <?= nl2br(
                            htmlspecialchars(
                                $pregunta['pregunta']
                            )
                        ) ?>

                    </h3>


                    <!-- IMAGEN -->

                    <?php if (
                        !empty($pregunta['imagen'])
                    ): ?>

                        <div
                            class="imagen-pregunta"
                            style="
                                margin: 15px 0 20px 0;
                                text-align: center;
                            "
                        >

                            <img
                                src="<?= htmlspecialchars(
                                    $pregunta['imagen']
                                ) ?>"
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


                    <!-- CÓDIGO -->

                    <?php if (
                        $pregunta['tipo'] ===
                        'codigo'
                    ): ?>

                        <textarea
                            name="pregunta_<?= $idPregunta ?>"
                            rows="12"
                            class="textarea-codigo"
                            data-pregunta-id="<?= $idPregunta ?>"
                            placeholder="Escriba aquí su código..."
                        ><?= htmlspecialchars(
                            $respuestaGuardada
                        ) ?></textarea>


                    <!-- RESPUESTA DE TEXTO -->

                    <?php elseif (
                        $pregunta['tipo'] ===
                        'respuesta_texto'
                    ): ?>

                        <textarea
                            name="pregunta_<?= $idPregunta ?>"
                            rows="5"
                            class="textarea-codigo"
                            data-pregunta-id="<?= $idPregunta ?>"
                            placeholder="Escriba aquí su respuesta..."
                        ><?= htmlspecialchars(
                            $respuestaGuardada
                        ) ?></textarea>


                    <!-- OPCIONES -->

                    <?php else: ?>

                        <?php

                        $stmtOpciones =
                            $conn->prepare("
                                SELECT
                                    id,
                                    opcion_texto
                                FROM opciones
                                WHERE pregunta_id = ?
                                ORDER BY id ASC
                            ");

                        $stmtOpciones->bind_param(
                            'i',
                            $idPregunta
                        );

                        $stmtOpciones->execute();

                        $resultadoOpciones =
                            $stmtOpciones->get_result();


                        /*
                        |--------------------------------------------------------------------------
                        | Recuperar selección múltiple guardada
                        |--------------------------------------------------------------------------
                        */

                        $seleccionesGuardadas = [];

                        if (
                            $pregunta['tipo'] ===
                            'seleccion_multiple'
                        ) {

                            $decodificada =
                                json_decode(
                                    $respuestaGuardada,
                                    true
                                );

                            if (
                                is_array(
                                    $decodificada
                                )
                            ) {

                                $seleccionesGuardadas =
                                    array_map(
                                        'intval',
                                        $decodificada
                                    );
                            }
                        }

                        ?>


                        <?php while (
                            $opcion =
                            $resultadoOpciones->fetch_assoc()
                        ): ?>

                            <?php

                            $opcionId =
                                (int)$opcion['id'];

                            ?>


                            <label class="opcion">


                                <?php if (
                                    $pregunta['tipo'] ===
                                    'seleccion_multiple'
                                ): ?>

                                    <input
                                        type="checkbox"
                                        name="pregunta_<?= $idPregunta ?>[]"
                                        value="<?= $opcionId ?>"
                                        data-pregunta-id="<?= $idPregunta ?>"
                                        <?= in_array(
                                            $opcionId,
                                            $seleccionesGuardadas,
                                            true
                                        )
                                            ? 'checked'
                                            : '' ?>
                                    >


                                <?php else: ?>

                                    <input
                                        type="radio"
                                        name="pregunta_<?= $idPregunta ?>"
                                        value="<?= $opcionId ?>"
                                        data-pregunta-id="<?= $idPregunta ?>"
                                        <?= (
                                            (string)$respuestaGuardada ===
                                            (string)$opcionId
                                        )
                                            ? 'checked'
                                            : '' ?>
                                    >

                                <?php endif; ?>


                                <?= htmlspecialchars(
                                    $opcion['opcion_texto']
                                ) ?>


                            </label>

                        <?php endwhile; ?>


                        <?php
                        $stmtOpciones->close();
                        ?>


                    <?php endif; ?>


                </div>

                <hr>

                <?php $numero++; ?>


            <?php endforeach; ?>


            <button
                type="submit"
                class="btn"
            >
                Finalizar Examen
            </button>


        </form>

    </div>

</div>


<!-- CONFIGURACIÓN PARA JAVASCRIPT -->

<div
    id="configuracion-examen"
    data-intento-id="<?= (int)$intentoId ?>"
    data-cambios="<?= (int)$cambiosGuardados ?>"
    data-tiempo="<?= (int)$tiempoRestante ?>"
    data-url-autoguardado="<?= BASE_URL ?>/autoguardar-respuesta"
    hidden
></div>


<!-- JAVASCRIPT -->

<script
    src="<?= BASE_URL ?>/public/assets/js/resolver.js?v=<?= filemtime(
        BASE_PATH . '/public/assets/js/resolver.js'
    ) ?>"
></script>

</body>
</html>

</body>

</html>