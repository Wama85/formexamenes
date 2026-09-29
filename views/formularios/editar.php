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
        Editar formulario
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
                Editar formulario
            </h1>

            <p>
                Modifica la configuración del formulario.
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

    <form
        action="<?= BASE_URL ?>/formularios/<?= (int)$examen['id'] ?>/actualizar"
        method="POST"
        class="formulario-panel"
    >

        <input
            type="hidden"
            name="id"
            value="<?= (int)$examen['id'] ?>"
        >


        <!-- =================================================
             DATOS GENERALES
             ================================================= -->

        <div class="seccion-titulo">

            <h2>
                Datos generales
            </h2>

            <p>
                Información principal del formulario.
            </p>

        </div>


        <div class="campo campo-completo">

            <label for="titulo">
                Título
            </label>

            <input
                type="text"
                name="titulo"
                id="titulo"
                class="input-formulario"
                value="<?= htmlspecialchars(
                    $examen['titulo'] ?? '',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
                required
            >

        </div>


        <div class="campo campo-completo">

            <label for="descripcion">
                Descripción
            </label>

            <textarea
                name="descripcion"
                id="descripcion"
                class="input-formulario"
                rows="4"
            ><?= htmlspecialchars(
                $examen['descripcion'] ?? '',
                ENT_QUOTES,
                'UTF-8'
            ) ?></textarea>

        </div>


        <!-- =================================================
             TIEMPO
             ================================================= -->

        <div class="seccion-formulario">

            <div class="seccion-titulo">

                <h2>
                    Control de tiempo
                </h2>

                <p>
                    Define cómo se controlará la duración
                    del formulario.
                </p>

            </div>


            <div class="campo campo-completo">

                <label for="tipo_tiempo">
                    Tipo de tiempo
                </label>

                <select
                    name="tipo_tiempo"
                    id="tipo_tiempo"
                    class="input-formulario"
                    onchange="cambiarTipoTiempo()"
                >

                    <option
                        value="individual"
                        <?= $tipo_tiempo === 'individual'
                            ? 'selected'
                            : '' ?>
                    >
                        Tiempo individual
                    </option>

                    <option
                        value="limite"
                        <?= $tipo_tiempo === 'limite'
                            ? 'selected'
                            : '' ?>
                    >
                        Hora límite para todos
                    </option>

                </select>

            </div>


            <!-- TIEMPO INDIVIDUAL -->

            <div
                id="bloque_individual"
                class="campo campo-completo"
            >

                <label for="tiempo_minutos">
                    Tiempo en minutos
                </label>

                <input
                    type="number"
                    name="tiempo_minutos"
                    id="tiempo_minutos"
                    class="input-formulario"
                    min="1"
                    value="<?= max(
                        1,
                        (int)($examen['tiempo_minutos'] ?? 1)
                    ) ?>"
                >

                <small>
                    El tiempo comienza cuando el estudiante
                    inicia el formulario.
                </small>

            </div>


            <!-- HORA LÍMITE -->

            <div
                id="bloque_limite"
                class="campo campo-completo"
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
                    value="<?= htmlspecialchars(
                        $fecha_hora_limite,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <small>
                    Todos los estudiantes terminarán a esta
                    hora, aunque hayan iniciado después.
                </small>

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
                    Define preguntas, intentos y criterio
                    para la calificación.
                </p>

            </div>


            <div class="grid-campos">

                <div class="campo">

                    <label for="cantidad_preguntas">
                        Preguntas por estudiante
                    </label>

                    <input
                        type="number"
                        name="cantidad_preguntas"
                        id="cantidad_preguntas"
                        class="input-formulario"
                        min="1"
                        value="<?= max(
                            1,
                            (int)($examen['cantidad_preguntas'] ?? 1)
                        ) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="cantidad_intentos">
                        Intentos permitidos
                    </label>

                    <input
                        type="number"
                        name="cantidad_intentos"
                        id="cantidad_intentos"
                        class="input-formulario"
                        min="1"
                        value="<?= max(
                            1,
                            (int)($examen['cantidad_intentos'] ?? 1)
                        ) ?>"
                        required
                    >

                </div>


                <div class="campo">

                    <label for="criterio_nota">
                        Criterio para la nota final
                    </label>

                    <select
                        name="criterio_nota"
                        id="criterio_nota"
                        class="input-formulario"
                        required
                    >

                        <option
                            value="mejor_nota"
                            <?= (
                                $examen['criterio_nota']
                                ?? 'mejor_nota'
                            ) === 'mejor_nota'
                                ? 'selected'
                                : '' ?>
                        >
                            Mejor nota
                        </option>

                        <option
                            value="ultimo_intento"
                            <?= (
                                $examen['criterio_nota']
                                ?? 'mejor_nota'
                            ) === 'ultimo_intento'
                                ? 'selected'
                                : '' ?>
                        >
                            Último intento
                        </option>

                    </select>

                    <small>
                        Define qué calificación se utilizará
                        cuando existan varios intentos.
                    </small>

                </div>


                <div class="campo">

                    <label for="estado">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                        class="input-formulario"
                    >

                        <option
                            value="borrador"
                            <?= ($examen['estado'] ?? '') === 'borrador'
                                ? 'selected'
                                : '' ?>
                        >
                            Borrador
                        </option>

                        <option
                            value="publicado"
                            <?= ($examen['estado'] ?? '') === 'publicado'
                                ? 'selected'
                                : '' ?>
                        >
                            Publicado
                        </option>

                        <option
                            value="cerrado"
                            <?= ($examen['estado'] ?? '') === 'cerrado'
                                ? 'selected'
                                : '' ?>
                        >
                            Cerrado
                        </option>

                    </select>

                </div>

            </div>

        </div>


        <!-- =================================================
             VISIBILIDAD PARA EL ESTUDIANTE
             ================================================= -->

        <div class="seccion-formulario">

            <div class="seccion-titulo">

                <h2>
                    Visibilidad para el estudiante
                </h2>

                <p>
                    Controla qué información podrá consultar
                    el estudiante.
                </p>

            </div>


            <div class="campo campo-completo">

                <label>

                    <input
                        type="checkbox"
                        name="mostrar_preguntas_antes"
                        value="1"
                        <?= !empty(
                            $examen['mostrar_preguntas_antes']
                        )
                            ? 'checked'
                            : '' ?>
                    >

                    Permitir que los alumnos vean las preguntas
                    antes de iniciar

                </label>

            </div>


            <div class="campo campo-completo">

                <label>

                    <input
                        type="checkbox"
                        name="mostrar_respuestas"
                        value="1"
                        <?= !empty(
                            $examen['mostrar_respuestas']
                        )
                            ? 'checked'
                            : '' ?>
                    >

                    Permitir que los alumnos vean sus respuestas

                </label>

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
                Actualizar formulario
            </button>

        </div>

    </form>

</div>


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
            'flex';

        bloqueLimite.style.display =
            'none';

        tiempoMinutos.required = true;

        fechaLimite.required = false;

    } else {

        bloqueIndividual.style.display =
            'none';

        bloqueLimite.style.display =
            'flex';

        tiempoMinutos.required = false;

        fechaLimite.required = true;
    }
}


cambiarTipoTiempo();

</script>

</body>

</html>