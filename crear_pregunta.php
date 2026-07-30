<?php

session_start();
require_once "conexion.php";

/*
|--------------------------------------------------------------------------
| Validar sesión
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (
    !isset($_SESSION['rol']) ||
    $_SESSION['rol'] !== 'docente'
) {
    die("Acceso denegado.");
}

/*
|--------------------------------------------------------------------------
| Validar examen
|--------------------------------------------------------------------------
*/

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($examen_id <= 0) {
    die("Examen inválido.");
}

$stmtExamen = $conn->prepare("
    SELECT
        id,
        titulo
    FROM examenes
    WHERE id = ?
    LIMIT 1
");

$stmtExamen->bind_param(
    "i",
    $examen_id
);

$stmtExamen->execute();

$resultadoExamen = $stmtExamen->get_result();
$examen = $resultadoExamen->fetch_assoc();

if (!$examen) {
    die("Examen no encontrado.");
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

    <title>Crear Pregunta</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Agregar pregunta</h1>

        <p class="correo">

            Examen:

            <strong>
                <?= htmlspecialchars($examen['titulo']) ?>
            </strong>

        </p>

        <form
            action="guardar_pregunta.php"
            method="POST"
            id="formPregunta"
        >

            <input
                type="hidden"
                name="examen_id"
                value="<?= $examen_id ?>"
            >

            <label for="tipo">
                Tipo de pregunta
            </label>

            <select
                name="tipo"
                id="tipo"
                class="input"
                required
            >

                <option value="opcion_multiple">
                    Selección única
                </option>

                <option value="seleccion_multiple">
                    Selección múltiple
                </option>

                <option value="respuesta_texto">
                    Respuesta escrita
                </option>

                <option value="codigo">
                    Código fuente
                </option>

            </select>

            <label for="pregunta">
                Pregunta
            </label>

            <textarea
                name="pregunta"
                id="pregunta"
                class="textarea-codigo"
                rows="5"
                required
            ></textarea>

            <label for="puntaje">
                Puntaje
            </label>

            <input
                type="number"
                step="0.01"
                min="0.01"
                name="puntaje"
                id="puntaje"
                class="input"
                value="5"
                required
            >

            <div id="metodo">

                <label for="metodo_correccion">
                    Método de corrección
                </label>

                <select
                    name="metodo_correccion"
                    id="metodo_correccion"
                    class="input"
                >

                    <option value="manual">
                        Manual
                    </option>

                    <option value="palabras_clave">
                        Palabras clave
                    </option>

                    <option value="ollama">
                        Ollama
                    </option>

                </select>

            </div>

            <div id="respuesta">

                <label for="respuesta_correcta">
                    Respuesta esperada
                </label>

                <textarea
                    name="respuesta_correcta"
                    id="respuesta_correcta"
                    class="textarea-codigo"
                    rows="5"
                ></textarea>

                <p
                    id="ayudaPalabras"
                    class="correo"
                    style="display:none;"
                >
                    Separe las palabras clave con comas.
                </p>

            </div>

            <div
                id="opciones"
                style="display:none;"
            >

                <h3>Opciones</h3>

                <p
                    id="instruccionOpciones"
                    class="correo"
                ></p>

                <div id="listaOpciones"></div>

<button
    type="button"
    class="btn"
    id="btnAgregarOpcion"
>
    + Agregar opción
</button>

            </div>

            <br>

            <button
                class="btn"
                type="submit"
            >
                Guardar pregunta
            </button>

            <a
                href="admin_preguntas.php?examen_id=<?= $examen_id ?>"
                class="btn btn-salir"
            >
                Cancelar
            </a>

        </form>

    </div>

</div>

<script>

const formPregunta =
    document.getElementById("formPregunta");

const tipo =
    document.getElementById("tipo");

const opciones =
    document.getElementById("opciones");

const metodo =
    document.getElementById("metodo");

const respuesta =
    document.getElementById("respuesta");

const metodoCorreccion =
    document.getElementById("metodo_correccion");

const respuestaCorrecta =
    document.getElementById("respuesta_correcta");

const ayudaPalabras =
    document.getElementById("ayudaPalabras");

const instruccionOpciones =
    document.getElementById("instruccionOpciones");

function obtenerTextosOpciones() {
    return document.querySelectorAll(".texto-opcion");
}

function obtenerTextosCorrecta() {
    return document.querySelectorAll(".texto-correcta");
}

function obtenerSelectoresCorrectos() {
    return document.querySelectorAll(".selector-correcta");
}
let contadorOpciones = 0;
/*
|--------------------------------------------------------------------------
| Limpiar selección de respuestas correctas
|--------------------------------------------------------------------------
*/

function limpiarSeleccionCorrecta() {

    document.querySelectorAll(".selector-correcta").forEach(function (selector) {

        selector.checked = false;

    });

}

/*
|--------------------------------------------------------------------------
| Configurar selección única
|--------------------------------------------------------------------------
*/

function configurarSeleccionUnica() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque una sola respuesta correcta.";

    document.querySelectorAll(".selector-correcta").forEach(function (selector) {

        selector.type = "radio";
        selector.name = "correcta";

    });

    document.querySelectorAll(".texto-correcta").forEach(function (texto) {

        texto.textContent =
            "Es la respuesta correcta";

    });

}

/*
|--------------------------------------------------------------------------
| Configurar selección múltiple
|--------------------------------------------------------------------------
*/

function configurarSeleccionMultiple() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque todas las respuestas correctas.";

    document.querySelectorAll(".selector-correcta").forEach(function (selector) {

        selector.type = "checkbox";
        selector.name = "correctas[]";

    });

    document.querySelectorAll(".texto-correcta").forEach(function (texto) {

        texto.textContent =
            "Es una respuesta correcta";

    });

}

/*
|--------------------------------------------------------------------------
| Actualizar formulario según el tipo de pregunta
|--------------------------------------------------------------------------
*/

function actualizarFormulario() {

    const tipoSeleccionado = tipo.value;

    limpiarSeleccionCorrecta();

    if (
        tipoSeleccionado === "opcion_multiple" ||
        tipoSeleccionado === "seleccion_multiple"
    ) {

        opciones.style.display = "block";
        metodo.style.display = "none";
        respuesta.style.display = "none";

        metodoCorreccion.disabled = true;
        respuestaCorrecta.disabled = true;

        document.querySelectorAll(".texto-opcion").forEach(function (input) {

            input.required = true;

        });

        actualizarTiposCorrecta();

        if (tipoSeleccionado === "seleccion_multiple") {

            configurarSeleccionMultiple();

        } else {

            configurarSeleccionUnica();

        }

    } else {

        opciones.style.display = "none";
        metodo.style.display = "block";
        respuesta.style.display = "block";

        metodoCorreccion.disabled = false;
        respuestaCorrecta.disabled = false;

        document.querySelectorAll(".texto-opcion").forEach(function (input) {

            input.required = false;
            input.value = "";

        });

        if (tipoSeleccionado === "codigo") {

            metodoCorreccion.value = "ollama";

        } else if (
            tipoSeleccionado === "respuesta_texto" &&
            metodoCorreccion.value === "ollama"
        ) {

            metodoCorreccion.value = "manual";

        }

    }

    actualizarMetodoCorreccion();

}
/*
|--------------------------------------------------------------------------
| Mostrar ayuda según el método de corrección
|--------------------------------------------------------------------------
*/

function actualizarMetodoCorreccion() {

    if (
        tipo.value === "opcion_multiple" ||
        tipo.value === "seleccion_multiple"
    ) {

        ayudaPalabras.style.display = "none";
        return;
    }

    if (metodoCorreccion.value === "manual") {

        respuestaCorrecta.required = false;

    } else {

        respuestaCorrecta.required = true;
    }

    if (metodoCorreccion.value === "palabras_clave") {

        ayudaPalabras.style.display = "block";

    } else {

        ayudaPalabras.style.display = "none";
    }
}

/*
|--------------------------------------------------------------------------
| Validar formulario antes de enviarlo
|--------------------------------------------------------------------------
*/

formPregunta.addEventListener(
    "submit",
    function (evento) {

        const tipoSeleccionado = tipo.value;

        if (
            tipoSeleccionado !== "opcion_multiple" &&
            tipoSeleccionado !== "seleccion_multiple"
        ) {
            return;
        }

        let opcionesCompletas = 0;

        textosOpciones.forEach(function (input) {

            if (input.value.trim() !== "") {
                opcionesCompletas++;
            }
        });

        if (opcionesCompletas < 2) {

            evento.preventDefault();

            alert(
                "Debe escribir por lo menos dos opciones."
            );

            return;
        }

        const seleccionadas = Array.from(
            selectoresCorrectos
        ).filter(function (selector) {

            return selector.checked;
        });

        if (
            tipoSeleccionado === "opcion_multiple" &&
            seleccionadas.length !== 1
        ) {

            evento.preventDefault();

            alert(
                "Debe seleccionar una respuesta correcta."
            );

            return;
        }

        if (
            tipoSeleccionado === "seleccion_multiple" &&
            seleccionadas.length < 2
        ) {

            evento.preventDefault();

            alert(
                "Debe seleccionar por lo menos dos respuestas correctas."
            );

            return;
        }

        /*
        Verificar que las respuestas marcadas como correctas
        tengan texto.
        */

        for (const selector of seleccionadas) {

            const indice = parseInt(selector.value);

            if (
                !textosOpciones[indice] ||
                textosOpciones[indice].value.trim() === ""
            ) {

                evento.preventDefault();

                alert(
                    "Una opción marcada como correcta está vacía."
                );

                return;
            }
        }
    }
);

tipo.addEventListener(
    "change",
    actualizarFormulario
);

metodoCorreccion.addEventListener(
    "change",
    actualizarMetodoCorreccion
);






/*
|--------------------------------------------------------------------------
| Agregar nueva opción
|--------------------------------------------------------------------------*/
function agregarOpcion() {

    contadorOpciones++;

    const div = document.createElement("div");

    div.className = "bloque-opcion";

    div.innerHTML = `
        <label>
            Opción ${contadorOpciones}
        </label>

        <input
            type="text"
            name="opcion[]"
            class="input texto-opcion"
        >

        <label class="opcion">

            <input
                class="selector-correcta"
                value="${contadorOpciones-1}"
            >

            <span class="texto-correcta"></span>

        </label>

        <button
            type="button"
            class="btnEliminar"
        >
            Eliminar
        </button>

        <hr>
    `;

    document
        .getElementById("listaOpciones")
        .appendChild(div);

    actualizarTiposCorrecta();

}
/*|--------------------------------------------------------------------------
| Actualizar tipos de selección correcta
|--------------------------------------------------------------------------*/
function actualizarTiposCorrecta(){

    const selectores =
        document.querySelectorAll(".selector-correcta");

    const textos =
        document.querySelectorAll(".texto-correcta");

    if(tipo.value=="seleccion_multiple"){

        selectores.forEach(s=>{

            s.type="checkbox";
            s.name="correctas[]";

        });

        textos.forEach(t=>{

            t.textContent="Es una respuesta correcta";

        });

    }else{

        selectores.forEach(s=>{

            s.type="radio";
            s.name="correcta";

        });

        textos.forEach(t=>{

            t.textContent="Es la respuesta correcta";

        });

    }

}
document
.getElementById("btnAgregarOpcion")
.addEventListener(
    "click",
    agregarOpcion
);

agregarOpcion();
agregarOpcion();
agregarOpcion();
agregarOpcion();
actualizarFormulario();
</script>

</body>
</html>