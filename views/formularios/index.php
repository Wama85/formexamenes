<?php

/*
|--------------------------------------------------------------------------
| Vista principal de formularios
|--------------------------------------------------------------------------
*/

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
        Formularios | SISFORMULARIOS
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/public/assets/css/formularios.css"
    >

</head>

<body>

    <main class="contenedor">

        <!-- =========================================================
             CABECERA
             ========================================================= -->

        <div class="cabecera-pagina">

            <div>

                <h1>
                    Mis formularios
                </h1>

                <p>
                    Administra tus formularios, preguntas y resultados.
                </p>

            </div>


<div class="acciones-cabecera">

    <a
        href="<?= BASE_URL ?>/formularios/nuevo"
        class="btn-nuevo"
    >
        + Nuevo formulario
    </a>

    <a
        href="<?= BASE_URL ?>/formularios/reportes"
        class="btn-nuevo"
    >
        Reportes
    </a>

    <a
        href="<?= BASE_URL ?>/logout"
        class="btn-cerrar-sesion"
    >
        Cerrar sesión
    </a>

</div>
            

        </div>


        <!-- =========================================================
             FORMULARIOS
             ========================================================= -->

        <?php if (empty($formularios)): ?>

            <div class="sin-formularios">

                <h2>
                    No existen formularios
                </h2>

                <p>
                    Crea tu primer formulario para comenzar.
                </p>

            </div>

        <?php else: ?>


            <div class="grid-formularios">


                <?php foreach ($formularios as $formulario): ?>


                    <?php

                    /*
                    |--------------------------------------------------------------------------
                    | Estado
                    |--------------------------------------------------------------------------
                    */

                    $estado =
                        strtolower(
                            $formulario['estado'] ?? ''
                        );

                    if ($estado === 'publicado') {

                        $claseEstado =
                            'estado-publicado';

                        $textoEstado =
                            'Publicado';

                    } elseif ($estado === 'borrador') {

                        $claseEstado =
                            'estado-borrador';

                        $textoEstado =
                            'Borrador';

                    } else {

                        $claseEstado =
                            'estado-otro';

                        $textoEstado =
                            ucfirst($estado);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Tipo de tiempo
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ($formulario['tipo_tiempo'] ?? 'individual')
                        === 'limite'
                    ) {

                        $textoTiempo =
                            'Hora límite';

                    } else {

                        $textoTiempo =
                            (int) $formulario['tiempo_minutos']
                            . ' min';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Criterio de nota
                    |--------------------------------------------------------------------------
                    */

                    if (
                        ($formulario['criterio_nota'] ?? 'mejor_nota')
                        === 'ultimo_intento'
                    ) {

                        $textoCriterio =
                            'Último intento';

                    } else {

                        $textoCriterio =
                            'Mejor nota';
                    }

                    ?>


                    <article class="formulario-card">


                        <!-- ============================= -->
                        <!-- TÍTULO Y ESTADO               -->
                        <!-- ============================= -->

                        <div class="card-superior">

                            <h2 class="card-titulo">

                                <?= htmlspecialchars(
                                    $formulario['titulo']
                                ) ?>

                            </h2>


                            <span
                                class="
                                    estado
                                    <?= $claseEstado ?>
                                "
                            >

                                <?= htmlspecialchars(
                                    $textoEstado
                                ) ?>

                            </span>

                        </div>


                        <!-- ============================= -->
                        <!-- DESCRIPCIÓN                   -->
                        <!-- ============================= -->

                        <p class="card-descripcion">

                            <?php if (
                                !empty(
                                    $formulario['descripcion']
                                )
                            ): ?>

                                <?= htmlspecialchars(
                                    $formulario['descripcion']
                                ) ?>

                            <?php else: ?>

                                Sin descripción.

                            <?php endif; ?>

                        </p>


                        <!-- ============================= -->
                        <!-- INFORMACIÓN                   -->
                        <!-- ============================= -->

                        <div class="card-datos">


                            <div class="dato">

                                <span class="dato-etiqueta">
                                    Preguntas
                                </span>

                                <span class="dato-valor">

                                    <?= (int)
                                        $formulario[
                                            'cantidad_preguntas'
                                        ] ?>

                                </span>

                            </div>


                            <div class="dato">

                                <span class="dato-etiqueta">
                                    Intentos
                                </span>

                                <span class="dato-valor">

                                    <?= (int)
                                        $formulario[
                                            'cantidad_intentos'
                                        ] ?>

                                </span>

                            </div>


                            <div class="dato">

                                <span class="dato-etiqueta">
                                    Tiempo
                                </span>

                                <span class="dato-valor">

                                    <?= htmlspecialchars(
                                        $textoTiempo
                                    ) ?>

                                </span>

                            </div>


                            <div class="dato">

                                <span class="dato-etiqueta">
                                    Nota válida
                                </span>

                                <span class="dato-valor">

                                    <?= htmlspecialchars(
                                        $textoCriterio
                                    ) ?>

                                </span>

                            </div>


                        </div>


                        <!-- ============================= -->
                        <!-- ACCIONES                      -->
                        <!-- ============================= -->

                        <div class="card-acciones">


                            <a
                                href="<?= BASE_URL ?>/formularios/<?= (int) $formulario['id'] ?>/preguntas"
                                class="accion-principal"
                            >
                                Preguntas
                            </a>


                           

                                <a
                                    href="<?= BASE_URL ?>/formularios/<?= (int) $formulario['id'] ?>/resultados"
                                >
                                    Resultados
                                </a>

                            


                            <a
                                href="<?= BASE_URL ?>/formularios/<?= (int) $formulario['id'] ?>/editar"
                            >
                                Editar
                            </a>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </main>

</body>

</html>