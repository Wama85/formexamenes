<?php

declare(strict_types=1);

$idFormulario = (int)$examen['id'];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nueva pregunta</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/public/assets/css/formularios.css?v=<?= filemtime(
            BASE_PATH . '/public/assets/css/formularios.css'
        ) ?>"
    >

</head>

<body>

<div class="contenedor">

    <div class="formulario-contenedor">

        <div class="formulario-cabecera">

            <div>

                <h1>
                    Agregar pregunta
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

        </div>


        <form
            action="<?= BASE_URL ?>/formularios/<?= $idFormulario ?>/preguntas"
            method="POST"
            enctype="multipart/form-data"
            id="formPregunta"
            class="formulario"
        >

            <!-- =============================================
                 TIPO
                 ============================================= -->

            <div class="campo">

                <label for="tipo">
                    Tipo de pregunta
                </label>

                <select
                    name="tipo"
                    id="tipo"
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

            </div>


            <!-- =============================================
                 PREGUNTA
                 ============================================= -->

            <div class="campo">

                <label for="pregunta">
                    Pregunta
                </label>

                <textarea
                    name="pregunta"
                    id="pregunta"
                    rows="5"
                    required
                ></textarea>

            </div>


            <!-- =============================================
                 IMAGEN
                 ============================================= -->

            <div class="campo">

                <label for="imagen_tipo">
                    Imagen de la pregunta (opcional)
                </label>

                <select
                    name="imagen_tipo"
                    id="imagen_tipo"
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

            </div>


            <div
                class="campo"
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
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >

                <small class="texto-ayuda">
                    Formatos permitidos: JPG, PNG y WEBP.
                    Máximo 5 MB.
                </small>

            </div>


            <div
                class="campo"
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
                    placeholder="https://ejemplo.com/imagen.jpg"
                >

            </div>


            <!-- =============================================
                 PUNTAJE
                 ============================================= -->

            <div class="campo">

                <label for="puntaje">
                    Puntaje
                </label>

                <input
                    type="number"
                    step="0.01"
                    min="0.01"
                    name="puntaje"
                    id="puntaje"
                    value="5"
                    required
                >

            </div>


            <!-- =============================================
                 MÉTODO DE CORRECCIÓN
                 ============================================= -->

            <div
                class="campo"
                id="metodo"
            >

                <label for="metodo_correccion">
                    Método de corrección
                </label>

                <select
                    name="metodo_correccion"
                    id="metodo_correccion"
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


            <!-- =============================================
                 RESPUESTA ESPERADA
                 ============================================= -->

            <div
                class="campo"
                id="respuesta"
            >

                <label for="respuesta_correcta">
                    Respuesta esperada
                </label>

                <textarea
                    name="respuesta_correcta"
                    id="respuesta_correcta"
                    rows="5"
                ></textarea>

                <small
                    id="ayudaPalabras"
                    class="texto-ayuda"
                    style="display:none;"
                >
                    Separe las palabras clave con comas.
                </small>

            </div>


            <!-- =============================================
                 OPCIONES
                 ============================================= -->

            <div
                id="opciones"
                class="bloque-opciones"
                style="display:none;"
            >

                <h3>
                    Opciones
                </h3>

                <p
                    id="instruccionOpciones"
                    class="texto-ayuda"
                ></p>

                <div id="listaOpciones"></div>

                <button
                    type="button"
                    class="btn-secundario"
                    id="btnAgregarOpcion"
                >
                    + Agregar opción
                </button>

            </div>


            <!-- =============================================
                 ACCIONES
                 ============================================= -->

            <div class="acciones-formulario">

                <button
                    type="submit"
                    class="btn-guardar"
                >
                    Guardar pregunta
                </button>

                <a
                    href="<?= BASE_URL ?>/formularios/<?= $idFormulario ?>/preguntas"
                    class="btn-volver"
                >
                    Cancelar
                </a>

            </div>

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

    return document.querySelectorAll(
        ".texto-opcion"
    );

}


function obtenerTextosCorrecta() {

    return document.querySelectorAll(
        ".texto-correcta"
    );

}


function obtenerSelectoresCorrectos() {

    return document.querySelectorAll(
        ".selector-correcta"
    );

}


/*
|--------------------------------------------------------------------------
| Imagen
|--------------------------------------------------------------------------
*/

function actualizarTipoImagen() {

    bloqueImagenArchivo.style.display =
        "none";

    bloqueImagenUrl.style.display =
        "none";

    imagenArchivo.required = false;
    imagenUrl.required = false;


    if (imagenTipo.value === "archivo") {

        bloqueImagenArchivo.style.display =
            "block";

        imagenArchivo.required = true;

        imagenUrl.value = "";

    } else if (
        imagenTipo.value === "url"
    ) {

        bloqueImagenUrl.style.display =
            "block";

        imagenUrl.required = true;

        imagenArchivo.value = "";

    } else {

        imagenArchivo.value = "";
        imagenUrl.value = "";

    }

}


/*
|--------------------------------------------------------------------------
| Limpiar respuestas correctas
|--------------------------------------------------------------------------
*/

function limpiarSeleccionCorrecta() {

    obtenerSelectoresCorrectos()
        .forEach(function (selector) {

            selector.checked = false;

        });

}


/*
|--------------------------------------------------------------------------
| Selección única
|--------------------------------------------------------------------------
*/

function configurarSeleccionUnica() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque una sola respuesta correcta.";

    obtenerSelectoresCorrectos()
        .forEach(function (selector) {

            selector.type = "radio";
            selector.name = "correcta";

        });


    obtenerTextosCorrecta()
        .forEach(function (texto) {

            texto.textContent =
                "Es la respuesta correcta";

        });

}


/*
|--------------------------------------------------------------------------
| Selección múltiple
|--------------------------------------------------------------------------
*/

function configurarSeleccionMultiple() {

    instruccionOpciones.textContent =
        "Escriba las opciones y marque todas las respuestas correctas.";

    obtenerSelectoresCorrectos()
        .forEach(function (selector) {

            selector.type = "checkbox";
            selector.name = "correctas[]";

        });


    obtenerTextosCorrecta()
        .forEach(function (texto) {

            texto.textContent =
                "Es una respuesta correcta";

        });

}


/*
|--------------------------------------------------------------------------
| Actualizar formulario según tipo
|--------------------------------------------------------------------------
*/

function actualizarFormulario() {

    const tipoSeleccionado =
        tipo.value;

    limpiarSeleccionCorrecta();


    if (
        tipoSeleccionado ===
            "opcion_multiple" ||
        tipoSeleccionado ===
            "seleccion_multiple"
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


        actualizarTiposCorrecta();


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
            tipoSeleccionado === "codigo"
        ) {

            metodoCorreccion.value =
                "ollama";

        } else if (
            tipoSeleccionado ===
                "respuesta_texto" &&
            metodoCorreccion.value ===
                "ollama"
        ) {

            metodoCorreccion.value =
                "manual";

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
        tipo.value ===
            "opcion_multiple" ||
        tipo.value ===
            "seleccion_multiple"
    ) {

        ayudaPalabras.style.display =
            "none";

        return;

    }


    if (
        metodoCorreccion.value ===
            "manual"
    ) {

        respuestaCorrecta.required =
            false;

    } else {

        respuestaCorrecta.required =
            true;

    }


    if (
        metodoCorreccion.value ===
            "palabras_clave"
    ) {

        ayudaPalabras.style.display =
            "block";

    } else {

        ayudaPalabras.style.display =
            "none";

    }

}


/*
|--------------------------------------------------------------------------
| Validación antes de guardar
|--------------------------------------------------------------------------
*/

formPregunta.addEventListener(
    "submit",
    function (evento) {

        const tipoSeleccionado =
            tipo.value;


        if (
            tipoSeleccionado !==
                "opcion_multiple" &&
            tipoSeleccionado !==
                "seleccion_multiple"
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
            Array.from(
                selectoresCorrectos
            ).filter(
                function (selector) {

                    return selector.checked;

                }
            );


        if (
            tipoSeleccionado ===
                "opcion_multiple" &&
            seleccionadas.length !== 1
        ) {

            evento.preventDefault();

            alert(
                "Debe seleccionar una respuesta correcta."
            );

            return;

        }


        if (
            tipoSeleccionado ===
                "seleccion_multiple" &&
            seleccionadas.length < 2
        ) {

            evento.preventDefault();

            alert(
                "Debe seleccionar por lo menos dos respuestas correctas."
            );

            return;

        }


        for (
            const selector
            of seleccionadas
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

    div.className =
        "bloque-opcion";

    div.innerHTML = `

        <div class="opcion-cabecera">

            <strong class="titulo-opcion">
                Opción ${contadorOpciones}
            </strong>

            <button
                type="button"
                class="btnEliminar"
            >
                Eliminar
            </button>

        </div>

        <input
            type="text"
            name="opcion[]"
            class="texto-opcion"
            placeholder="Escriba la opción"
        >

        <label class="marcar-correcta">

            <input
                class="selector-correcta"
                value="${contadorOpciones - 1}"
            >

            <span class="texto-correcta"></span>

        </label>
    `;


    document
        .getElementById(
            "listaOpciones"
        )
        .appendChild(div);


    actualizarTiposCorrecta();

}


/*
|--------------------------------------------------------------------------
| Configurar radio / checkbox
|--------------------------------------------------------------------------
*/

function actualizarTiposCorrecta() {

    const selectores =
        obtenerSelectoresCorrectos();

    const textos =
        obtenerTextosCorrecta();


    if (
        tipo.value ===
            "seleccion_multiple"
    ) {

        selectores.forEach(
            function (selector) {

                selector.type =
                    "checkbox";

                selector.name =
                    "correctas[]";

            }
        );


        textos.forEach(
            function (texto) {

                texto.textContent =
                    "Es una respuesta correcta";

            }
        );

    } else {

        selectores.forEach(
            function (selector) {

                selector.type =
                    "radio";

                selector.name =
                    "correcta";

            }
        );


        textos.forEach(
            function (texto) {

                texto.textContent =
                    "Es la respuesta correcta";

            }
        );

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
        .querySelectorAll(
            ".bloque-opcion"
        )
        .forEach(
            function (bloque) {

                contadorOpciones++;

                const titulo =
                    bloque.querySelector(
                        ".titulo-opcion"
                    );

                const selector =
                    bloque.querySelector(
                        ".selector-correcta"
                    );

                titulo.textContent =
                    "Opción " +
                    contadorOpciones;

                selector.value =
                    contadorOpciones - 1;

            }
        );

}


/*
|--------------------------------------------------------------------------
| Eventos
|--------------------------------------------------------------------------
*/

document
    .getElementById(
        "btnAgregarOpcion"
    )
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
            .closest(
                ".bloque-opcion"
            )
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