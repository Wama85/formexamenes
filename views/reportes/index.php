<?php

if (!isset($formularios)) {
    $formularios = [];
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

    <title>
        Reportes | SISFORMULARIOS
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/public/assets/css/reportes.css?v=<?= filemtime(
            BASE_PATH . '/public/assets/css/reportes.css'
        ) ?>"
    >

</head>

<body>

<main class="contenedor">

    <!-- =====================================================
         CABECERA
         ===================================================== -->

    <div class="cabecera-pagina">

        <div>

            <h1>
                Reportes
            </h1>

            <p>
                Selecciona uno o varios formularios
                para generar el archivo CSV.
            </p>

        </div>

        <div class="acciones-cabecera">

            <a
                href="<?= BASE_URL ?>/formularios"
                class="btn-volver"
            >
                Volver a formularios
            </a>

        </div>

    </div>


    <!-- =====================================================
         SIN FORMULARIOS
         ===================================================== -->

    <?php if (empty($formularios)): ?>

        <div class="panel-reportes">

            <div class="sin-formularios">

                No existen formularios disponibles
                para generar reportes.

            </div>

        </div>


    <?php else: ?>


        <!-- =================================================
             FORMULARIO DE REPORTE
             ================================================= -->

        <form
            method="POST"
            action="<?= BASE_URL ?>/formularios/reportes/exportar"
            id="formReporte"
        >

            <div class="panel-reportes">

                <h2>
                    Seleccionar formularios
                </h2>

                <p>
                    Marca los formularios que deseas
                    incluir en el reporte.
                </p>


                <!-- =========================================
                     CONTROLES
                     ========================================= -->

                <div class="controles-seleccion">

    <button
        type="button"
        id="seleccionarTodos"
        class="btn-seleccion"
    >
        Seleccionar todos
    </button>

    <button
        type="submit"
        class="btn-exportar"
    >
        Exportar CSV
    </button>

</div>


                <!-- =========================================
                     LISTA
                     ========================================= -->

                <div class="lista-formularios">

                    <?php foreach (
                        $formularios as $formulario
                    ): ?>

                        <label class="reporte-item">

                            <input
                                type="checkbox"
                                name="formularios[]"
                                value="<?= (int) $formulario['id'] ?>"
                                class="checkbox-formulario"
                            >

                            <div class="reporte-info">

                                <span class="reporte-titulo">

                                    <?= htmlspecialchars(
                                        $formulario['titulo'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>

                                </span>


                                <div class="reporte-detalles">

                                    <span>
                                        Estado:
                                        <strong>
                                            <?= htmlspecialchars(
                                                ucfirst(
                                                    $formulario['estado']
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </strong>
                                    </span>


                                    <span>
                                        Criterio:
                                        <strong>

                                            <?=
                                                $formulario[
                                                    'criterio_nota'
                                                ] === 'ultimo_intento'
                                                    ? 'Último intento'
                                                    : 'Mejor nota'
                                            ?>

                                        </strong>
                                    </span>

                                </div>

                            </div>

                        </label>

                    <?php endforeach; ?>

                </div>


                <!-- =========================================
                     EXPORTAR
                     ========================================= -->

                <div class="acciones-reporte">

                    <button
                        type="submit"
                        class="btn-exportar"
                    >
                        Exportar CSV
                    </button>

                </div>

            </div>

        </form>

    <?php endif; ?>

</main>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const botonTodos =
            document.getElementById(
                'seleccionarTodos'
            );

        const formulario =
            document.getElementById(
                'formReporte'
            );

        const checkboxes =
            document.querySelectorAll(
                '.checkbox-formulario'
            );


        /*
        |--------------------------------------------------------------------------
        | Seleccionar / deseleccionar todos
        |--------------------------------------------------------------------------
        */

        if (botonTodos) {

            botonTodos.addEventListener(
                'click',
                function () {

                    const todosSeleccionados =
                        Array.from(
                            checkboxes
                        ).every(
                            checkbox =>
                                checkbox.checked
                        );


                    checkboxes.forEach(
                        function (checkbox) {

                            checkbox.checked =
                                !todosSeleccionados;

                        }
                    );


                    botonTodos.textContent =
                        todosSeleccionados
                            ? 'Seleccionar todos'
                            : 'Deseleccionar todos';

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Validar selección
        |--------------------------------------------------------------------------
        */

        if (formulario) {

            formulario.addEventListener(
                'submit',
                function (event) {

                    const seleccionados =
                        document.querySelectorAll(
                            '.checkbox-formulario:checked'
                        );


                    if (
                        seleccionados.length === 0
                    ) {

                        event.preventDefault();

                        alert(
                            'Seleccione al menos un formulario.'
                        );

                    }

                }
            );

        }

    }
);

</script>

</body>

</html>