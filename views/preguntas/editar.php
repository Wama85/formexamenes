<?php

declare(strict_types=1);

$idFormulario = (int)$examen['id'];
$idPregunta = (int)$pregunta['id'];

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Editar pregunta</title>

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

            <h1>
                Editar pregunta
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


        <form
            action="<?= BASE_URL ?>/formularios/<?= $idFormulario ?>/preguntas/<?= $idPregunta ?>/actualizar"
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
                ><?= htmlspecialchars(
                    $pregunta['pregunta'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>


            <!-- =============================================
                 IMAGEN ACTUAL
                 ============================================= -->

            <?php if ($imagenActual !== ''): ?>

                <div class="campo">

                    <label>
                        Imagen actual
                    </label>

                    <div class="imagen-pregunta-actual">

                        <?php if (
                            $imagenTipoActual === 'url'
                        ): ?>

                            <img
                                src="<?= htmlspecialchars(
                                    $imagenActual,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="Imagen de la pregunta"
                            >

                        <?php else: ?>

                            <img
                                src="<?= BASE_URL ?>/<?= htmlspecialchars(
                                    $imagenActual,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                alt="Imagen de la pregunta"
                            >

                        <?php endif; ?>

                    </div>

                </div>

            <?php endif; ?>


            <!-- =============================================
                 ACCIÓN DE IMAGEN
                 ============================================= -->

            <div class="campo">

                <label for="accion_imagen">
                    Imagen de la pregunta
                </label>

                <select
                    name="accion_imagen"
                    id="accion_imagen"
                >

                    <?php if ($imagenActual !== ''): ?>

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
                    value="<?= $imagenTipoActual === 'url'
                        ? htmlspecialchars(
                            $imagenActual,
                            ENT_QUOTES,
                            'UTF-8'
                        )
                        : '' ?>"
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
                    value="<?= htmlspecialchars(
                        (string)$pregunta['puntaje'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
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
                ><?= htmlspecialchars(
                    (string)$pregunta['respuesta_correcta'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

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


                <div id="listaOpciones">

                    <?php foreach (
                        $listaOpciones
                        as $indice => $opcion
                    ): ?>

                        <div class="bloque-opcion">

                            <input
                                type="hidden"
                                name="opcion_id[]"
                                value="<?= (int)$opcion['id'] ?>"
                            >


                            <div class="opcion-cabecera">

                                <strong class="titulo-opcion">
                                    Opción <?= $indice + 1 ?>
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
                                value="<?= htmlspecialchars(
                                    $opcion['opcion_texto'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                autocomplete="off"
                            >


                            <label class="marcar-correcta">

                                <input
                                    type="<?= $pregunta['tipo'] === 'seleccion_multiple'
                                        ? 'checkbox'
                                        : 'radio' ?>"
                                    class="selector-correcta"
                                    name="<?= $pregunta['tipo'] === 'seleccion_multiple'
                                        ? 'correctas[]'
                                        : 'correcta' ?>"
                                    value="<?= $indice ?>"
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

                        </div>

                    <?php endforeach; ?>

                </div>


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
                    Actualizar pregunta
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


/*
|--------------------------------------------------------------------------
| Elementos dinámicos
|--------------------------------------------------------------------------
*/

function obtenerTextosOpciones() {

    return document.querySelectorAll(
        ".texto-opcion"
    );

}


function obtenerSelectoresCorrectos() {

    return document.querySelectorAll(
        ".selector-correcta"
    );

}


function obtenerTextosCorrecta() {

    return document.querySelectorAll(
        ".texto-correcta"
    );

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


    if (
        accionImagen.value ===
        "archivo"
    ) {

        bloqueImagenArchivo.style.display =
            "block";

        imagenArchivo.required = true;

    } else if (
        accionImagen.value ===
        "url"
    ) {

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


    selectores.forEach(
        function (selector) {

            selector.type = "radio";
            selector.name = "correcta";

        }
    );


    textos.forEach(
        function (texto) {

            texto.textContent =
                "Es la respuesta correcta";

        }
    );


    const marcadas =
        Array.from(selectores)
            .filter(
                function (selector) {

                    return selector.checked;

                }
            );


    if (marcadas.length > 1) {

        marcadas.forEach(
            function (
                selector,
                indice
            ) {

                if (indice > 0) {

                    selector.checked =
                        false;
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

        respuestaCorrecta.required =
            false;

        return;
    }


    respuestaCorrecta.required =
        metodoCorreccion.value !==
        "manual";


    ayudaPalabras.style.display =
        metodoCorreccion.value ===
            "palabras_clave"
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
            .forEach(
                function (input) {

                    input.required =
                        true;

                }
            );


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
            .forEach(
                function (input) {

                    input.required =
                        false;

                }
            );


        if (
            tipoSeleccionado ===
                "codigo" &&
            metodoCorreccion.value !==
                "palabras_clave" &&
            metodoCorreccion.value !==
                "ollama"
        ) {

            metodoCorreccion.value =
                "ollama";
        }

    }


    actualizarMetodoCorreccion();

}


/*
|--------------------------------------------------------------------------
| Actualizar radio / checkbox
|--------------------------------------------------------------------------
*/

function actualizarTiposCorrecta() {

    const multiple =
        tipo.value ===
        "seleccion_multiple";


    document
        .querySelectorAll(
            ".bloque-opcion"
        )
        .forEach(
            function (
                bloque,
                indice
            ) {

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
                    "Opción " +
                    (indice + 1);


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

        <div class="opcion-cabecera">

            <strong class="titulo-opcion">
                Opción ${indice + 1}
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
            >

            <span
                class="texto-correcta"
            ></span>

        </label>
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
            .closest(
                ".bloque-opcion"
            )
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
    .getElementById(
        "btnAgregarOpcion"
    )
    .addEventListener(
        "click",
        agregarOpcion
    );


/*
|--------------------------------------------------------------------------
| Validación
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
                    input.value.trim() !==
                    ""
                ) {

                    opcionesCompletas++;
                }

            }
        );


        if (
            opcionesCompletas < 2
        ) {

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
                textoOpcion.value.trim()
                    === ""
            ) {

                evento.preventDefault();

                alert(
                    "Una opción marcada como correcta está vacía."
                );

                return;
            }

        }


        if (
            tipoSeleccionado ===
                "seleccion_multiple" &&
            seleccionadas.length ===
                opcionesCompletas
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