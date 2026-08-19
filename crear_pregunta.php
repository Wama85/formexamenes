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
| Validar formulario
|--------------------------------------------------------------------------
*/

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($examen_id <= 0) {
    die("Formulario inválido.");
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
    die("Formulario no encontrado.");
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

            Formulario:

            <strong>
                <?= htmlspecialchars($examen['titulo']) ?>
            </strong>

        </p>

        <form
            action="guardar_pregunta.php"
            method="POST"
            enctype="multipart/form-data"
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

            <label for="imagen_tipo">
                Imagen de la pregunta (opcional)
            </label>

            <select
                name="imagen_tipo"
                id="imagen_tipo"
                class="input"
            >

                <option value="">
                    Sin imagen
                </option>

                <option value="archivo">
                    Subir imagen
                </option>

                <option value="url">
                    Usar imagen desde Internet
                </option>

            </select>

            <div
                id="bloqueImagenArchivo"
                style="display:none;"
            >

                <label for="imagen_archivo">
                    Seleccionar imagen
                </label>

                <input
                    type="file"
                    name="imagen_archivo"
                    id="imagen_archivo"
                    class="input"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <p class="correo">
                    Formatos permitidos: JPG, PNG y WEBP. Máximo 5 MB.
                </p>

            </div>

            <div
                id="bloqueImagenUrl"
                style="display:none;"
            >

                <label for="imagen_url">
                    Enlace de la imagen
                </label>

                <input
                    type="url"
                    name="imagen_url"
                    id="imagen_url"
                    class="input"
                    placeholder="https://ejemplo.com/imagen.jpg"
                >

            </div>

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

const imagenTipo =
    document.getElementById("imagen_tipo");

const bloqueImagenArchivo =
    document.getElementById("bloqueImagenArchivo");

const bloqueImagenUrl =
    document.getElementById("bloqueImagenUrl");

const imagenArchivo =
    document.getElementById("imagen_archivo");

const imagenUrl =
    document.getElementById("imagen_url");

let contadorOpciones = 0;

/*
|--------------------------------------------------------------------------
| Obtener elementos dinámicos
|--------------------------------------------------------------------------
*/

function obtenerTextosOpciones() {
    return document.querySelectorAll(".texto-opcion");
}

function obtenerTextosCorrecta() {
    return document.querySelectorAll(".texto-correcta");
}

function obtenerSelectoresCorrectos() {
    return document.querySelectorAll(".selector-correcta");
}

/*
|--------------------------------------------------------------------------
| Imagen
|--------------------------------------------------------------------------
*/

function actualizarTipoImagen() {

    bloqueImagenArchivo.style.display = "none";
    bloqueImagenUrl.style.display = "none";

    imagenArchivo.required = false;
    imagenUrl.required = false;

    if (imagenTipo.value === "archivo") {

        bloqueImagenArchivo.style.display = "block";
        imagenArchivo.required = true;
        imagenUrl.value = "";

    } else if (imagenTipo.value === "url") {

        bloqueImagenUrl.style.display = "block";
        imagenUrl.required = true;
        imagenArchivo.value = "";

    } else {

        imagenArchivo.value = "";
        imagenUrl.value = "";

    }

}

/*
|--------------------------------------------------------------------------
| Limpiar selección de respuestas correctas
|--------------------------------------------------------------------------
*/

function limpiarSeleccionCorrecta() {

    obtenerSelectoresCorrectos().forEach(function (selector) {

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

    obtenerSelectoresCorrectos().forEach(function (selector) {

        selector.type = "radio";
        selector.name = "correcta";

    });

    obtenerTextosCorrecta().forEach(function (texto) {

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

    obtenerSelectoresCorrectos().forEach(function (selector) {

        selector.type = "checkbox";
        selector.name = "correctas[]";

    });

    obtenerTextosCorrecta().forEach(function (texto) {

        texto.textContent =
            "Es una respuesta correcta";

    });

}

/*
|--------------------------------------------------------------------------
| Actualizar formulario
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

        obtenerTextosOpciones().forEach(function (input) {

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

        obtenerTextosOpciones().forEach(function (input) {

            input.required = false;

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
| Método de corrección
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
| Validación
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

        const textosOpciones =
            obtenerTextosOpciones();

        const selectoresCorrectos =
            obtenerSelectoresCorrectos();

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

        for (const selector of seleccionadas) {

            const bloque =
                selector.closest(".bloque-opcion");

            const textoOpcion =
                bloque.querySelector(".texto-opcion");

            if (
                !textoOpcion ||
                textoOpcion.value.trim() === ""
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

/*
|--------------------------------------------------------------------------
| Agregar opción
|--------------------------------------------------------------------------
*/

function agregarOpcion() {

    contadorOpciones++;

    const div =
        document.createElement("div");

    div.className = "bloque-opcion";

    div.innerHTML = `

        <label class="titulo-opcion">
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
                value="${contadorOpciones - 1}"
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

/*
|--------------------------------------------------------------------------
| Configurar radio o checkbox
|--------------------------------------------------------------------------
*/

function actualizarTiposCorrecta() {

    const selectores =
        obtenerSelectoresCorrectos();

    const textos =
        obtenerTextosCorrecta();

    if (tipo.value === "seleccion_multiple") {

        selectores.forEach(function (selector) {

            selector.type = "checkbox";
            selector.name = "correctas[]";

        });

        textos.forEach(function (texto) {

            texto.textContent =
                "Es una respuesta correcta";

        });

    } else {

        selectores.forEach(function (selector) {

            selector.type = "radio";
            selector.name = "correcta";

        });

        textos.forEach(function (texto) {

            texto.textContent =
                "Es la respuesta correcta";

        });

    }

}

/*
|--------------------------------------------------------------------------
| Renumerar opciones
|--------------------------------------------------------------------------
*/

function renumerarOpciones() {

    contadorOpciones = 0;

    document
        .querySelectorAll(".bloque-opcion")
        .forEach(function (bloque) {

            contadorOpciones++;

            const titulo =
                bloque.querySelector(".titulo-opcion");

            const selector =
                bloque.querySelector(".selector-correcta");

            titulo.textContent =
                "Opción " + contadorOpciones;

            selector.value =
                contadorOpciones - 1;

        });

}

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

document
    .getElementById("btnAgregarOpcion")
    .addEventListener(
        "click",
        agregarOpcion
    );

document.addEventListener(
    "click",
    function (e) {

        if (
            !e.target.classList.contains(
                "btnEliminar"
            )
        ) {
            return;
        }

        const bloques =
            document.querySelectorAll(
                ".bloque-opcion"
            );

        if (bloques.length <= 2) {

            alert(
                "Debe existir al menos dos opciones."
            );

            return;

        }

        e.target
            .closest(".bloque-opcion")
            .remove();

        renumerarOpciones();
        actualizarTiposCorrecta();

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

imagenTipo.addEventListener(
    "change",
    actualizarTipoImagen
);

/*
|--------------------------------------------------------------------------
| Inicializar
|--------------------------------------------------------------------------
*/

agregarOpcion();
agregarOpcion();
agregarOpcion();
agregarOpcion();

actualizarFormulario();
actualizarTipoImagen();

</script>

</body>
</html>