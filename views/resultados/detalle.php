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
        Detalle del intento
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/public/assets/css/formularios.css?v=<?= filemtime(
            BASE_PATH . '/public/assets/css/formularios.css'
        ) ?>"
    >

</head>

<body>

<div class="contenedor">

    <!-- =====================================================
         CABECERA
         ===================================================== -->

    <div class="cabecera-pagina">

        <div>

            <h1>
                Detalle del intento
            </h1>

            <p>
                Formulario:
                <strong>
                    <?= htmlspecialchars(
                        $intento['titulo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

        </div>

    </div>


    <!-- =====================================================
         INFORMACIÓN DEL INTENTO
         ===================================================== -->

    <div class="preguntas-resumen">

        <div class="dato">

            <span class="dato-etiqueta">
                Estudiante
            </span>

            <span class="dato-valor">
                <?= htmlspecialchars(
                    $intento['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


        <div class="dato">

            <span class="dato-etiqueta">
                Correo
            </span>

            <span class="dato-valor">
                <?= htmlspecialchars(
                    $intento['correo'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

        </div>


        <div class="dato">

            <span class="dato-etiqueta">
                Intento
            </span>

            <span class="dato-valor">
                <?= (int)$intento['numero_intento'] ?>
            </span>

        </div>


        <div class="dato">

            <span class="dato-etiqueta">
                Nota
            </span>

            <span class="dato-valor">
                <?= number_format(
                    (float)$intento['nota'],
                    2
                ) ?>
            </span>

        </div>

    </div>


    <!-- =====================================================
         RESPUESTAS
         ===================================================== -->

    <?php if (!empty($respuestas)): ?>

        <?php foreach ($respuestas as $indice => $respuesta): ?>

            <div class="formulario-card">

                <div class="card-superior">

                    <h2 class="card-titulo">
                        Pregunta <?= $indice + 1 ?>
                    </h2>

                </div>


                <div class="card-descripcion">

                    <?= nl2br(
                        htmlspecialchars(
                            $respuesta['pregunta'],
                            ENT_QUOTES,
                            'UTF-8'
                        )
                    ) ?>

                </div>


                <div class="card-datos">

                    <div class="dato">

                        <span class="dato-etiqueta">
                            Respuesta
                        </span>

                        <span class="dato-valor">

                            <?php if (
                                trim(
                                    (string)$respuesta['respuesta']
                                ) !== ''
                            ): ?>

                                <?= nl2br(
                                    htmlspecialchars(
                                        (string)$respuesta['respuesta'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                ) ?>

                            <?php else: ?>

                                Sin respuesta

                            <?php endif; ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-etiqueta">
                            Resultado
                        </span>

                        <span class="dato-valor">

                            <?php if (
                                (int)$respuesta['correcta'] === 1
                            ): ?>

                                Correcta

                            <?php else: ?>

                                Incorrecta

                            <?php endif; ?>

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-etiqueta">
                            Puntaje obtenido
                        </span>

                        <span class="dato-valor">

                            <?= number_format(
                                (float)$respuesta['puntaje_obtenido'],
                                2
                            ) ?>

                        </span>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="sin-formularios">

            <h2>
                No hay respuestas
            </h2>

            <p>
                Este intento no tiene respuestas registradas.
            </p>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         VOLVER
         ===================================================== -->

    <div class="acciones-inferiores">

        <a
            href="<?= BASE_URL ?>/formularios/<?= (int)$intento['examen_id'] ?>/resultados"
            class="btn-volver"
        >
            Volver a resultados
        </a>

    </div>

</div>

</body>

</html>