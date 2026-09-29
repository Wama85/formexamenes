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
        Nuevo formulario | SISFORMULARIOS
    </title>

   <link
    rel="stylesheet"
    href="<?= BASE_URL ?>/public/assets/css/formularios.css?v=<?= filemtime(
        BASE_PATH . '/public/assets/css/formularios.css'
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
                Nuevo formulario
            </h1>

            <p>
                Configura el formulario y sus condiciones de aplicación.
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
         FORMULARIO
         ===================================================== -->

    <div class="formulario-panel">

        <form
            action="<?= BASE_URL ?>/formularios"
            method="POST"
        >

            <!-- TÍTULO -->

            <div class="campo campo-completo">

                <label for="titulo">
                    Título
                </label>

                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    class="input-formulario"
                    placeholder="Ej. Repaso de redes"
                    required
                >

            </div>


            <!-- DESCRIPCIÓN -->

            <div class="campo campo-completo">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="input-formulario"
                    rows="4"
                    placeholder="Descripción del formulario..."
                ></textarea>

            </div>


            <!-- =================================================
                 CONTROL DE TIEMPO
                 ================================================= -->

            <div class="seccion-formulario">

                <div class="seccion-titulo">

                    <h2>
                        Control de tiempo
                    </h2>

                    <p>
                        Define cómo se controlará el tiempo del formulario.
                    </p>

                </div>


                <div class="grid-campos">

                    <div class="campo">

                        <label for="tipo_tiempo">
                            Tipo de tiempo
                        </label>

                        <select
                            name="tipo_tiempo"
                            id="tipo_tiempo"
                            class="input-formulario"
                            onchange="cambiarTipoTiempo()"
                        >

                            <option value="individual">
                                Tiempo individual
                            </option>

                            <option value="limite">
                                Hora límite para todos
                            </option>

                        </select>

                    </div>


                    <!-- TIEMPO INDIVIDUAL -->

                    <div
                        class="campo"
                        id="bloque_individual"
                    >

                        <label for="tiempo_minutos">
                            Tiempo en minutos
                        </label>

                        <input
                            type="number"
                            name="tiempo_minutos"
                            id="tiempo_minutos"
                            class="input-formulario"
                            value="90"
                            min="1"
                        >

                        <small>
                            Comienza cuando el estudiante inicia.
                        </small>

                    </div>


                    <!-- HORA LÍMITE -->

                    <div
                        class="campo"
                        id="bloque_limite"
                        style="display:none;"
                    >

                        <label for="fecha_hora_limite">
                            Fecha y hora límite
                        </label>

                        <input
                            type="datetime-local"
                            name="fecha_hora_limite"
                            id="fecha_hora_limite"
                            class="input-formulario"
                        >

                        <small>
                            Todos finalizarán al llegar esta hora.
                        </small>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONFIGURACIÓN
                 ================================================= -->

            <div class="seccion-formulario">

                <div class="seccion-titulo">

                    <h2>
                        Configuración
                    </h2>

                    <p>
                        Define las condiciones del formulario.
                    </p>

                </div>


                <div class="grid-campos">


                    <!-- PREGUNTAS -->

                    <div class="campo">

                        <label for="cantidad_preguntas">
                            Preguntas por estudiante
                        </label>

                        <input
                            type="number"
                            id="cantidad_preguntas"
                            name="cantidad_preguntas"
                            class="input-formulario"
                            value="8"
                            min="1"
                            required
                        >

                    </div>


                    <!-- INTENTOS -->

                    <div class="campo">

                        <label for="cantidad_intentos">
                            Intentos permitidos
                        </label>

                        <input
                            type="number"
                            id="cantidad_intentos"
                            name="cantidad_intentos"
                            class="input-formulario"
                            value="2"
                            min="1"
                            required
                        >

                    </div>


                    <!-- CRITERIO -->

                    <div class="campo">

                        <label for="criterio_nota">
                            Nota final
                        </label>

                        <select
                            id="criterio_nota"
                            name="criterio_nota"
                            class="input-formulario"
                            required
                        >

                            <option
                                value="mejor_nota"
                                selected
                            >
                                Mejor nota
                            </option>

                            <option value="ultimo_intento">
                                Último intento
                            </option>

                        </select>

                        <small>
                            Se aplicará cuando existan varios intentos.
                        </small>

                    </div>


                    <!-- ESTADO -->

                    <div class="campo">

                        <label for="estado">
                            Estado
                        </label>

                        <select
                            id="estado"
                            name="estado"
                            class="input-formulario"
                        >

                            <option value="borrador">
                                Borrador
                            </option>

                            <option value="publicado">
                                Publicado
                            </option>

                            <option value="cerrado">
                                Cerrado
                            </option>

                        </select>

                    </div>

                </div>

            </div>


            <!-- =================================================
                 ACCIONES
                 ================================================= -->

            <div class="acciones-formulario">

                <a
                    href="<?= BASE_URL ?>/formularios"
                    class="btn-cancelar"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar formulario
                </button>

            </div>

        </form>

    </div>

</main>


<script>

function cambiarTipoTiempo() {

    const tipo =
        document.getElementById(
            'tipo_tiempo'
        ).value;

    const bloqueIndividual =
        document.getElementById(
            'bloque_individual'
        );

    const bloqueLimite =
        document.getElementById(
            'bloque_limite'
        );

    const tiempoMinutos =
        document.getElementById(
            'tiempo_minutos'
        );

    const fechaLimite =
        document.getElementById(
            'fecha_hora_limite'
        );


    if (tipo === 'individual') {

        bloqueIndividual.style.display =
            'block';

        bloqueLimite.style.display =
            'none';

        tiempoMinutos.required = true;
        fechaLimite.required = false;

    } else {

        bloqueIndividual.style.display =
            'none';

        bloqueLimite.style.display =
            'block';

        tiempoMinutos.required = false;
        fechaLimite.required = true;
    }
}


cambiarTipoTiempo();

</script>

</body>

</html>