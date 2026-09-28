<?php

session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}

$id = isset($_GET['id'])
    ? (int)$_GET['id']
    : 0;

if ($id <= 0) {
    die("Formulario inválido.");
}


/*
|--------------------------------------------------------------------------
| OBTENER FORMULARIO
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT *
    FROM examenes
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $id);
$stmt->execute();

$resultado = $stmt->get_result();

$examen = $resultado->fetch_assoc();

if (!$examen) {
    die("Formulario no encontrado.");
}


/*
|--------------------------------------------------------------------------
| TIPO DE TIEMPO
|--------------------------------------------------------------------------
*/

$tipo_tiempo =
    $examen['tipo_tiempo'] ?? 'individual';

if (
    $tipo_tiempo !== 'individual' &&
    $tipo_tiempo !== 'limite'
) {
    $tipo_tiempo = 'individual';
}


/*
|--------------------------------------------------------------------------
| PREPARAR FECHA PARA datetime-local
|--------------------------------------------------------------------------
*/

$fecha_hora_limite = '';

if (!empty($examen['fecha_hora_limite'])) {

    $timestamp =
        strtotime(
            $examen['fecha_hora_limite']
        );

    if ($timestamp !== false) {

        $fecha_hora_limite =
            date(
                'Y-m-d\TH:i',
                $timestamp
            );
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>
        Editar Formulario
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>
            Editar Formulario
        </h1>


        <form
            action="actualizar_examen.php"
            method="POST"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$examen['id'] ?>"
            >


            <!-- ============================= -->
            <!-- TÍTULO                        -->
            <!-- ============================= -->

            <label>
                Título
            </label>

            <input
                type="text"
                name="titulo"
                class="input"
                value="<?= htmlspecialchars($examen['titulo']) ?>"
                required
            >


            <!-- ============================= -->
            <!-- DESCRIPCIÓN                   -->
            <!-- ============================= -->

            <label>
                Descripción
            </label>

            <textarea
                name="descripcion"
                class="textarea-codigo"
                rows="4"
            ><?= htmlspecialchars($examen['descripcion']) ?></textarea>


            <!-- ============================= -->
            <!-- CONTROL DE TIEMPO             -->
            <!-- ============================= -->

            <label>
                Control de tiempo
            </label>

            <select
                name="tipo_tiempo"
                id="tipo_tiempo"
                class="input"
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


            <!-- ============================= -->
            <!-- TIEMPO INDIVIDUAL             -->
            <!-- ============================= -->

            <div id="bloque_individual">

                <label>
                    Tiempo en minutos
                </label>

                <input
                    type="number"
                    name="tiempo_minutos"
                    id="tiempo_minutos"
                    class="input"
                    min="1"
                    value="<?= max(
                        1,
                        (int)$examen['tiempo_minutos']
                    ) ?>"
                >

                <small>
                    El tiempo comienza cuando el estudiante
                    inicia el formulario.
                </small>

            </div>


            <!-- ============================= -->
            <!-- HORA LÍMITE                   -->
            <!-- ============================= -->

            <div
                id="bloque_limite"
                style="display:none;"
            >

                <label>
                    Fecha y hora límite
                </label>

                <input
                    type="datetime-local"
                    name="fecha_hora_limite"
                    id="fecha_hora_limite"
                    class="input"
                    value="<?= htmlspecialchars(
                        $fecha_hora_limite
                    ) ?>"
                >

                <small>
                    Todos los estudiantes terminarán a esta
                    hora, aunque hayan iniciado después.
                </small>

            </div>


            <br><br>


            <!-- ============================= -->
            <!-- CANTIDAD DE PREGUNTAS         -->
            <!-- ============================= -->

            <label>
                Cantidad de preguntas por estudiante
            </label>

            <input
                type="number"
                name="cantidad_preguntas"
                class="input"
                min="1"
                value="<?= (int)$examen['cantidad_preguntas'] ?>"
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
                min="1"
                value="<?= max(
                    1,
                    (int)$examen['cantidad_intentos']
                ) ?>"
                required
            >
<!-- ============================= -->
<!-- CRITERIO DE NOTA              -->
<!-- ============================= -->

<label>
    Criterio para la nota final
</label>

<select
    name="criterio_nota"
    class="input"
    required
>
    <option
        value="mejor_nota"
        <?= ($examen['criterio_nota'] ?? 'mejor_nota') === 'mejor_nota'
            ? 'selected'
            : '' ?>
    >
        Mejor nota
    </option>

    <option
        value="ultimo_intento"
        <?= ($examen['criterio_nota'] ?? 'mejor_nota') === 'ultimo_intento'
            ? 'selected'
            : '' ?>
    >
        Último intento
    </option>
</select>

<small>
    Define qué calificación se tomará como nota válida
    cuando el estudiante tenga más de un intento.
</small>

<br><br>

            <!-- ============================= -->
            <!-- ESTADO                        -->
            <!-- ============================= -->

            <label>
                Estado
            </label>

            <select
                name="estado"
                class="input"
            >

                <option
                    value="borrador"
                    <?= $examen['estado'] === 'borrador'
                        ? 'selected'
                        : '' ?>
                >
                    Borrador
                </option>

                <option
                    value="publicado"
                    <?= $examen['estado'] === 'publicado'
                        ? 'selected'
                        : '' ?>
                >
                    Publicado
                </option>

                <option
                    value="cerrado"
                    <?= $examen['estado'] === 'cerrado'
                        ? 'selected'
                        : '' ?>
                >
                    Cerrado
                </option>

            </select>


            <br><br>


            <!-- ============================= -->
            <!-- VER PREGUNTAS ANTES           -->
            <!-- ============================= -->

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


            <br><br>


            <!-- ============================= -->
            <!-- MOSTRAR RESPUESTAS            -->
            <!-- ============================= -->

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


            <br><br>


            <!-- ============================= -->
            <!-- ACTUALIZAR                    -->
            <!-- ============================= -->

            <button
                type="submit"
                class="btn"
            >
                Actualizar Formulario
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
|--------------------------------------------------------------------------
| CONFIGURAR AL CARGAR
|--------------------------------------------------------------------------
*/

cambiarTipoTiempo();

</script>

</body>
</html>