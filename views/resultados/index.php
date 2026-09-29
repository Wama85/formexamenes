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
        Resultados
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
                Resultados
            </h1>

            <p>
                Formulario:
                <strong>
                    <?= htmlspecialchars(
                        $examen['titulo'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </strong>
            </p>

        </div>
     <a
    href="<?= BASE_URL ?>/formularios"
    class="btn-volver"
>
    Volver
</a>
    </div>


    <!-- =====================================================
         INFORMACIÓN DEL FORMULARIO
         ===================================================== -->

    <div class="preguntas-resumen">

        <div class="dato">

            <span class="dato-etiqueta">
                Estudiantes con resultados
            </span>

            <span class="dato-valor">
                <?= count($resultados) ?>
            </span>

        </div>


        <div class="dato">

            <span class="dato-etiqueta">
                Criterio de nota
            </span>

            <span class="dato-valor">

                <?php if (
                    ($examen['criterio_nota'] ?? 'mejor_nota')
                    === 'ultimo_intento'
                ): ?>

                    Último intento

                <?php else: ?>

                    Mejor nota

                <?php endif; ?>

            </span>

        </div>

    </div>


   


    <!-- =====================================================
         RESULTADOS
         ===================================================== -->

    <?php if (!empty($resultados)): ?>

        <div class="tabla-contenedor">

            <table class="tabla-preguntas">

                <thead>

                <tr>

                    <th>
                        Alumno
                    </th>

                    <th>
                        Correo
                    </th>

                    <th>
                        Intentos
                    </th>

                    <th>
                        Intento válido
                    </th>

                    <th>
                        Nota válida
                    </th>

                    <th>
                        Cambios
                    </th>

                    <th>
                        Inicio
                    </th>

                    <th>
                        Fin
                    </th>

                    <th>
                        Detalle
                    </th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($resultados as $fila): ?>

                    <tr>

                        <td>

                            <?= htmlspecialchars(
                                $fila['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $fila['correo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <?= (int)$fila['total_intentos'] ?>

                        </td>


                        <td>

                            Intento
                            <?= (int)$fila['numero_intento'] ?>

                        </td>


                        <td>

                            <strong>

                                <?= number_format(
                                    (float)$fila['nota'],
                                    2
                                ) ?>

                            </strong>

                        </td>


                        <td>

                            <?= (int)$fila['cambios_pestana'] ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                (string)$fila['fecha_inicio'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                (string)$fila['fecha_fin'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

    <a
        href="<?= BASE_URL ?>/resultados/intentos/<?= (int)$fila['id'] ?>"
        class="btn-tabla"
    >
        Ver
    </a>

</td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="sin-formularios">

            <h2>
                No hay resultados
            </h2>

            <p>
                Este formulario todavía no tiene
                resultados finalizados.
            </p>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         VOLVER
         ===================================================== -->

    <div class="acciones-inferiores">

        <a
            href="<?= BASE_URL ?>/formularios"
            class="btn-volver"
        >
            Volver a formularios
        </a>

    </div>

</div>

</body>

</html>