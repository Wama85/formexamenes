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
| Validar parámetros
|--------------------------------------------------------------------------
*/

$id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

$examen_id = isset($_GET['examen_id'])
    ? (int) $_GET['examen_id']
    : 0;

if ($id <= 0 || $examen_id <= 0) {
    die("Datos de la pregunta inválidos.");
}

/*
|--------------------------------------------------------------------------
| Obtener pregunta
|--------------------------------------------------------------------------
*/

$stmtPregunta = $conn->prepare("
    SELECT *
    FROM preguntas
    WHERE id = ?
      AND examen_id = ?
    LIMIT 1
");

$stmtPregunta->bind_param(
    "ii",
    $id,
    $examen_id
);

$stmtPregunta->execute();

$resultadoPregunta =
    $stmtPregunta->get_result();

$pregunta =
    $resultadoPregunta->fetch_assoc();

if (!$pregunta) {
    die("Pregunta no encontrada.");
}

/*
|--------------------------------------------------------------------------
| Obtener opciones
|--------------------------------------------------------------------------
*/

$stmtOpciones = $conn->prepare("
    SELECT
        id,
        opcion_texto,
        es_correcta
    FROM opciones
    WHERE pregunta_id = ?
    ORDER BY id ASC
");

$stmtOpciones->bind_param(
    "i",
    $id
);

$stmtOpciones->execute();

$resultadoOpciones =
    $stmtOpciones->get_result();

$listaOpciones = [];

while (
    $opcion = $resultadoOpciones->fetch_assoc()
) {
    $listaOpciones[] = $opcion;
}

/*
Si la pregunta de selección todavía no tiene opciones,
mostrar cuatro inicialmente.
*/

if (
    (
        $pregunta['tipo'] === 'opcion_multiple' ||
        $pregunta['tipo'] === 'seleccion_multiple'
    ) &&
    count($listaOpciones) === 0
) {

    for ($i = 0; $i < 4; $i++) {

        $listaOpciones[] = [
            'id' => 0,
            'opcion_texto' => '',
            'es_correcta' => 0
        ];
    }
}

$imagen_tipo_actual =
    $pregunta['imagen_tipo'] ?? '';

$imagen_actual =
    $pregunta['imagen'] ?? '';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar Pregunta</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Editar pregunta</h1>

        <form
            action="actualizar_pregunta.php"
            method="POST"
            enctype="multipart/form-data"
            id="formPregunta"
        >

            <input
                type="hidden"
                name="id"
                value="<?= (int)$pregunta['id'] ?>"
            >

            <input
                type="hidden"
                name="examen_id"
                value="<?= $examen_id ?>"
            >

            <input
                type="hidden"
                name="imagen_actual"
                value="<?= htmlspecialchars($imagen_actual) ?>"
            >

            <input
                type="hidden"
                name="imagen_tipo_actual"
                value="<?= htmlspecialchars($imagen_tipo_actual) ?>"
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

                <option
                    value="opcion_multiple"
                    <?= $pregunta['tipo'] === 'opcion_multiple'
                        ? 'selected'
                        : '' ?>
                >
                    Selección única
                </option>

                <option
                    value="seleccion_multiple"
                    <?= $pregunta['tipo'] === 'seleccion_multiple'
                        ? 'selected'
                        : '' ?>
                >
                    Selección múltiple
                </option>

                <option
                    value="respuesta_texto"
                    <?= $pregunta['tipo'] === 'respuesta_texto'
                        ? 'selected'
                        : '' ?>
                >
                    Respuesta escrita
                </option>

                <option
                    value="codigo"
                    <?= $pregunta['tipo'] === 'codigo'
                        ? 'selected'
                        : '' ?>
                >
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
            ><?= htmlspecialchars($pregunta['pregunta']) ?></textarea>

            <!-- IMAGEN ACTUAL -->

            <?php if ($imagen_actual !== ''): ?>

                <label>
                    Imagen actual
                </label>

                <div
                    style="
                        margin-bottom:15px;
                        text-align:center;
                    "
                >

                    <img
                        src="<?= htmlspecialchars($imagen_actual) ?>"
                        alt="Imagen de la pregunta"
                        style="
                            max-width:100%;
                            max-height:350px;
                            border-radius:6px;
                        "
                    >

                </div>

            <?php endif; ?>

            <label for="accion_imagen">
                Imagen de la pregunta
            </label>

            <select
                name="accion_imagen"
                id="accion_imagen"
                class="input"
            >

                <?php if ($imagen_actual !== ''): ?>

                    <option value="mantener">
                        Mantener imagen actual
                    </option>

                    <option value="eliminar">
                        Eliminar imagen
                    </option>

                <?php else: ?>

                    <option value="sin_imagen">
                        Sin imagen
                    </option>

                <?php endif; ?>

                <option value="archivo">
                    Subir nueva imagen
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
                    value="<?= $imagen_tipo_actual === 'url'
                        ? htmlspecialchars($imagen_actual)
                        : '' ?>"
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
                value="<?= htmlspecialchars($pregunta['puntaje']) ?>"
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

                    <option
                        value="manual"
                        <?= $pregunta['metodo_correccion'] === 'manual'
                            ? 'selected'
                            : '' ?>
                    >
                        Manual
                    </option>

                    <option
                        value="palabras_clave"
                        <?= $pregunta['metodo_correccion'] === 'palabras_clave'
                            ? 'selected'
                            : '' ?>
                    >
                        Palabras clave
                    </option>

                    <option
                        value="ollama"
                        <?= $pregunta['metodo_correccion'] === 'ollama'
                            ? 'selected'
                            : '' ?>
                    >
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
                ><?= htmlspecialchars($pregunta['respuesta_correcta']) ?></textarea>

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

                <div id="listaOpciones">

                    <?php foreach ($listaOpciones as $i => $opcion): ?>

                        <div class="bloque-opcion">

                            <input
                                type="hidden"
                                name="opcion_id[]"
                                value="<?= (int)$opcion['id'] ?>"
                            >

                            <label class="titulo-opcion">
                                Opción <?= $i + 1 ?>
                            </label>

                            <input
                                type="text"
                                name="opcion[]"
                                class="input texto-opcion"
                                value="<?= htmlspecialchars($opcion['opcion_texto']) ?>"
                                autocomplete="off"
                            >

                            <label class="opcion">

                                <input
                                    type="<?= $pregunta['tipo'] === 'seleccion_multiple'
                                        ? 'checkbox'
                                        : 'radio' ?>"
                                    class="selector-correcta"
                                    name="<?= $pregunta['tipo'] === 'seleccion_multiple'
                                        ? 'correctas[]'
                                        : 'correcta' ?>"
                                    value="<?= $i ?>"
                                    <?= (int)$opcion['es_correcta'] === 1
                                        ? 'checked'
                                        : '' ?>
                                >

                                <span class="texto-correcta">

                                    <?= $pregunta['tipo'] === 'seleccion_multiple'
                                        ? 'Es una respuesta correcta'
                                        : 'Es la respuesta correcta' ?>

                                </span>

                            </label>

                            <button
                                type="button"
                                class="btnEliminar"
                            >
                                Eliminar
                            </button>

                            <hr>

                        </div>

                    <?php endforeach; ?>

                </div>

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
                type="submit"
                class="btn"
            >
                Actualizar pregunta
            </button>

            <a
                href="admin_preguntas.php?examen_id=<?= $examen_id ?>"
                class="btn btn-salir"
            >
                Volver
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

const accionImagen =
    document.getElementById("accion_imagen");

const bloqueImagenArchivo =
    document.getElementById("bloqueImagenArchivo");

const bloqueImagenUrl =
    document.getElementById("bloqueImagenUrl");

const imagenArchivo =
    document.getElementById("imagen_archivo");

const imagenUrl =
    document.getElementById("imagen_url");

function obtenerTextosOpciones() {
    return document.querySelectorAll(".texto-opcion");
}

function obtenerSelectoresCorrectos() {
    return document.querySelectorAll(".selector-correcta");
}

function obtenerTextosCorrecta() {
    return document.querySelectorAll(".texto-correcta");
}

/*
|--------------------------------------------------------------------------
| Imagen
|--------------------------------------------------------------------------
*/

function actualizarImagen() {

    bloqueImagenArchivo.style.display =
        "none";

    bloqueImagenUrl.style.display =
        "none";

    imagenArchivo.required = false;
    imagenUrl.required = false;

    if (accionImagen.value === "archivo") {

        bloqueImagenArchivo.style.display =
            "block";

        imagenArchivo.required = true;

    } else if (accionImagen.value === "url") {

        bloqueImagenUrl.style.display =
            "block";

        imagenUrl.required = true;

    }

}

/*
|--------------------------------------------------------------------------
| Selección única
|--------------------------------------------------------------------------
*/

function configurarSeleccionUnica() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque una sola respuesta correcta.";

    const selectores =
        obtenerSelectoresCorrectos();

    const textos =
        obtenerTextosCorrecta();

    selectores.forEach(function (selector) {

        selector.type = "radio";
        selector.name = "correcta";

    });

    textos.forEach(function (texto) {

        texto.textContent =
            "Es la respuesta correcta";

    });

    const marcadas =
        Array.from(selectores)
            .filter(function (selector) {

                return selector.checked;

            });

    if (marcadas.length > 1) {

        marcadas.forEach(
            function (selector, indice) {

                if (indice > 0) {
                    selector.checked = false;
                }

            }
        );

    }

}

/*
|--------------------------------------------------------------------------
| Selección múltiple
|--------------------------------------------------------------------------
*/

function configurarSeleccionMultiple() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque todas las respuestas correctas.";

    const selectores =
        obtenerSelectoresCorrectos();

    const textos =
        obtenerTextosCorrecta();

    selectores.forEach(function (selector) {

        selector.type = "checkbox";
        selector.name = "correctas[]";

    });

    textos.forEach(function (texto) {

        texto.textContent =
            "Es una respuesta correcta";

    });

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

        ayudaPalabras.style.display =
            "none";

        respuestaCorrecta.required =
            false;

        return;

    }

    respuestaCorrecta.required =
        metodoCorreccion.value !== "manual";

    ayudaPalabras.style.display =
        metodoCorreccion.value === "palabras_clave"
            ? "block"
            : "none";

}

/*
|--------------------------------------------------------------------------
| Formulario según tipo
|--------------------------------------------------------------------------
*/

function actualizarFormulario() {

    const tipoSeleccionado =
        tipo.value;

    if (
        tipoSeleccionado === "opcion_multiple" ||
        tipoSeleccionado === "seleccion_multiple"
    ) {

        opciones.style.display =
            "block";

        metodo.style.display =
            "none";

        respuesta.style.display =
            "none";

        metodoCorreccion.disabled =
            true;

        respuestaCorrecta.disabled =
            true;

        obtenerTextosOpciones()
            .forEach(function (input) {

                input.required = true;

            });

        if (
            tipoSeleccionado ===
            "seleccion_multiple"
        ) {

            configurarSeleccionMultiple();

        } else {

            configurarSeleccionUnica();

        }

    } else {

        opciones.style.display =
            "none";

        metodo.style.display =
            "block";

        respuesta.style.display =
            "block";

        metodoCorreccion.disabled =
            false;

        respuestaCorrecta.disabled =
            false;

        obtenerTextosOpciones()
            .forEach(function (input) {

                input.required = false;

            });

        if (
            tipoSeleccionado === "codigo" &&
            metodoCorreccion.value !== "palabras_clave" &&
            metodoCorreccion.value !== "ollama"
        ) {

            metodoCorreccion.value =
                "ollama";

        }

    }

    actualizarMetodoCorreccion();

}

/*
|--------------------------------------------------------------------------
| Actualizar radios / checkbox
|--------------------------------------------------------------------------
*/

function actualizarTiposCorrecta() {

    const multiple =
        tipo.value === "seleccion_multiple";

    document
        .querySelectorAll(".bloque-opcion")
        .forEach(
            function (bloque, indice) {

                const selector =
                    bloque.querySelector(
                        ".selector-correcta"
                    );

                const texto =
                    bloque.querySelector(
                        ".texto-correcta"
                    );

                const titulo =
                    bloque.querySelector(
                        ".titulo-opcion"
                    );

                selector.value =
                    indice;

                titulo.textContent =
                    "Opción " + (indice + 1);

                if (multiple) {

                    selector.type =
                        "checkbox";

                    selector.name =
                        "correctas[]";

                    texto.textContent =
                        "Es una respuesta correcta";

                } else {

                    selector.type =
                        "radio";

                    selector.name =
                        "correcta";

                    texto.textContent =
                        "Es la respuesta correcta";

                }

            }
        );

}

/*
|--------------------------------------------------------------------------
| Agregar opción
|--------------------------------------------------------------------------
*/

function agregarOpcion() {

    const contenedor =
        document.getElementById(
            "listaOpciones"
        );

    const indice =
        contenedor.children.length;

    const div =
        document.createElement("div");

    div.className =
        "bloque-opcion";

    div.innerHTML = `

        <input
            type="hidden"
            name="opcion_id[]"
            value="0"
        >

        <label class="titulo-opcion">
            Opción ${indice + 1}
        </label>

        <input
            type="text"
            name="opcion[]"
            class="input texto-opcion"
        >

        <label class="opcion">

            <input
                class="selector-correcta"
            >

            <span
                class="texto-correcta"
            ></span>

        </label>

        <button
            type="button"
            class="btnEliminar"
        >
            Eliminar
        </button>

        <hr>
    `;

    contenedor.appendChild(div);

    actualizarTiposCorrecta();

}

/*
|--------------------------------------------------------------------------
| Eliminar opción
|--------------------------------------------------------------------------
*/

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

        actualizarTiposCorrecta();

    }
);

/*
|--------------------------------------------------------------------------
| Agregar opción
|--------------------------------------------------------------------------
*/

document
    .getElementById("btnAgregarOpcion")
    .addEventListener(
        "click",
        agregarOpcion
    );

/*
|--------------------------------------------------------------------------
| Validar
|--------------------------------------------------------------------------
*/

formPregunta.addEventListener(
    "submit",
    function (evento) {

        const tipoSeleccionado =
            tipo.value;

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

        textosOpciones.forEach(
            function (input) {

                if (
                    input.value.trim() !== ""
                ) {
                    opcionesCompletas++;
                }

            }
        );

        if (opcionesCompletas < 2) {

            evento.preventDefault();

            alert(
                "Debe escribir por lo menos dos opciones."
            );

            return;
        }

        const seleccionadas =
            Array.from(selectoresCorrectos)
                .filter(function (selector) {

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

        for (
            const selector of seleccionadas
        ) {

            const bloque =
                selector.closest(
                    ".bloque-opcion"
                );

            const textoOpcion =
                bloque.querySelector(
                    ".texto-opcion"
                );

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

        if (
            tipoSeleccionado === "seleccion_multiple" &&
            seleccionadas.length === opcionesCompletas
        ) {

            evento.preventDefault();

            alert(
                "Debe existir por lo menos una opción incorrecta."
            );

        }

    }
);

/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

tipo.addEventListener(
    "change",
    actualizarFormulario
);

metodoCorreccion.addEventListener(
    "change",
    actualizarMetodoCorreccion
);

accionImagen.addEventListener(
    "change",
    actualizarImagen
);

/*
|--------------------------------------------------------------------------
| Inicializar
|--------------------------------------------------------------------------
*/

actualizarFormulario();
actualizarImagen();

</script>

</body>
</html>