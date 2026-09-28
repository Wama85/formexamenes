<?php

session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Formulario</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<div class="container">
    <div class="card">

        <h1>Crear formulario</h1>

        <form action="guardar_examen.php" method="POST">

            <label>Título</label>

            <input
                type="text"
                name="titulo"
                class="input"
                required
            >


            <label>Descripción</label>

            <textarea
                name="descripcion"
                class="textarea-codigo"
                rows="4"
            ></textarea>


            <!-- ============================= -->
            <!-- CONTROL DE TIEMPO             -->
            <!-- ============================= -->

            <label>Control de tiempo</label>

            <select
                name="tipo_tiempo"
                id="tipo_tiempo"
                class="input"
                onchange="cambiarTipoTiempo()"
            >
                <option value="individual">
                    Tiempo individual
                </option>

                <option value="limite">
                    Hora límite para todos
                </option>
            </select>


            <!-- TIEMPO INDIVIDUAL -->

            <div id="bloque_individual">

                <label>Tiempo en minutos</label>

                <input
                    type="number"
                    name="tiempo_minutos"
                    id="tiempo_minutos"
                    class="input"
                    value="90"
                    min="1"
                >

                <small>
                    El tiempo comienza cuando el estudiante inicia el formulario.
                </small>

            </div>


            <!-- HORA LÍMITE -->

            <div
                id="bloque_limite"
                style="display:none;"
            >

                <label>Fecha y hora límite</label>

                <input
                    type="datetime-local"
                    name="fecha_hora_limite"
                    id="fecha_hora_limite"
                    class="input"
                >

                <small>
                    Todos los estudiantes terminarán a esta hora,
                    aunque hayan iniciado después.
                </small>

            </div>


            <br><br>


            <!-- ============================= -->
            <!-- PREGUNTAS                     -->
            <!-- ============================= -->

            <label>
                Cantidad de preguntas que verá cada estudiante
            </label>

            <input
                type="number"
                name="cantidad_preguntas"
                class="input"
                value="8"
                min="1"
                required
            >


            <!-- ============================= -->
            <!-- INTENTOS                      -->
            <!-- ============================= -->

            <label>
                Cantidad de intentos permitidos
            </label>

            <input
                type="number"
                name="cantidad_intentos"
                class="input"
                value="2"
                min="1"
                required
            >


            <!-- ============================= -->
            <!-- ESTADO                        -->
            <!-- ============================= -->

            <label>Estado</label>

            <select
                name="estado"
                class="input"
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


            <br><br>


            <button
                type="submit"
                class="btn"
            >
                Guardar formulario
            </button>

        </form>


        <br>


        <a
            href="admin_examen.php"
            class="btn btn-salir"
        >
            Volver
        </a>

    </div>
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


/*
Ejecutar al cargar la página
*/

cambiarTipoTiempo();

</script>


</body>
</html>