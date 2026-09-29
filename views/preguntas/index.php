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
        Preguntas
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
                Preguntas
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
            href="<?= BASE_URL ?>/formularios/<?= (int)$examen['id'] ?>/preguntas/nueva"
            class="btn-nuevo"
        >
            + Agregar pregunta
        </a>

    </div>


    <!-- =====================================================
         MENSAJE DE REINICIO
         ===================================================== -->

    <?php if (isset($_GET['reiniciado'])): ?>

        <div class="mensaje-exito">

            Los intentos del formulario fueron
            reiniciados correctamente.

        </div>

    <?php endif; ?>


    <!-- =====================================================
         INFORMACIÓN
         ===================================================== -->

    <div class="preguntas-resumen">

        <div class="dato">

            <span class="dato-etiqueta">
                Preguntas registradas
            </span>

            <span class="dato-valor">
                <?= count($preguntas) ?>
            </span>

        </div>

        <div class="dato">

            <span class="dato-etiqueta">
                Preguntas por estudiante
            </span>

            <span class="dato-valor">
                <?= (int)$examen['cantidad_preguntas'] ?>
            </span>

        </div>

    </div>


    <!-- =====================================================
         TABLA
         ===================================================== -->

    <?php if (!empty($preguntas)): ?>

        <div class="tabla-contenedor">

            <table class="tabla-preguntas">

                <thead>

                <tr>

                    <th>#</th>

                    <th>
                        Pregunta
                    </th>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Corrección
                    </th>

                    <th>
                        Puntaje
                    </th>

                    <th>
                        Acciones
                    </th>

                </tr>

                </thead>


                <tbody>

                <?php foreach ($preguntas as $indice => $pregunta): ?>

                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Nombre del tipo
                    |--------------------------------------------------------------------------
                    */

                    switch ($pregunta['tipo']) {

                        case 'opcion_multiple':
                            $nombreTipo =
                                'Opción múltiple';
                            break;

                        case 'seleccion_multiple':
                            $nombreTipo =
                                'Selección múltiple';
                            break;

                        case 'codigo':
                            $nombreTipo =
                                'Código fuente';
                            break;

                        case 'respuesta_texto':
                            $nombreTipo =
                                'Respuesta escrita';
                            break;

                        default:
                            $nombreTipo =
                                $pregunta['tipo'];
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Método de corrección
                    |--------------------------------------------------------------------------
                    */

                    switch (
                        $pregunta['metodo_correccion']
                        ?? ''
                    ) {

                        case 'manual':
                            $nombreCorreccion =
                                'Manual';
                            break;

                        case 'palabras_clave':
                            $nombreCorreccion =
                                'Palabras clave';
                            break;

                        case 'ollama':
                            $nombreCorreccion =
                                'Ollama';
                            break;

                        default:
                            $nombreCorreccion =
                                '-';
                    }

                    ?>

                    <tr>

                        <td>
                            <?= $indice + 1 ?>
                        </td>


                        <td class="pregunta-texto">

                            <?= nl2br(
                                htmlspecialchars(
                                    $pregunta['pregunta'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                )
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $nombreTipo,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                $nombreCorreccion,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <?= htmlspecialchars(
                                (string)$pregunta['puntaje'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </td>


                        <td>

                            <div class="acciones-pregunta">

                                <a
                                    href="<?= BASE_URL ?>/formularios/<?= (int)$examen['id'] ?>/preguntas/<?= (int)$pregunta['id'] ?>/editar"
                                    class="btn-tabla"
                                >
                                    Editar
                                </a>


                               


                                <form
    action="<?= BASE_URL ?>/formularios/<?= (int)$examen['id'] ?>/preguntas/<?= (int)$pregunta['id'] ?>/eliminar"
    method="POST"
    style="display: inline;"
    onsubmit="
        return confirm(
            '¿Está seguro de eliminar esta pregunta?'
        );
    "
>
    <button
        type="submit"
        class="btn-tabla btn-eliminar"
    >
        Eliminar
    </button>
</form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>


    <?php else: ?>

        <div class="sin-formularios">

            <h2>
                No hay preguntas registradas
            </h2>

            <p>
                Agrega la primera pregunta para comenzar
                a construir este formulario.
            </p>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ACCIONES INFERIORES
         ===================================================== -->

    <div class="acciones-inferiores">

        <a
            href="<?= BASE_URL ?>/formularios"
            class="btn-volver"
        >
            Volver a formularios
        </a>


        <form
            action="<?= BASE_URL ?>/formularios/<?= (int)$examen['id'] ?>/reiniciar-intentos"
            method="POST"
            onsubmit="
                return confirm(
                    '¿Está seguro de reiniciar todos los intentos de este formulario? Se eliminarán las respuestas y notas de los estudiantes.'
                );
            "
        >

            <button
                type="submit"
                class="btn-reiniciar"
            >
                Reiniciar intentos
            </button>

        </form>

    </div>

</div>

</body>

</html>