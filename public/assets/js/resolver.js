'use strict';

/*
|--------------------------------------------------------------------------
| Configuración inicial
|--------------------------------------------------------------------------
*/

const configuracion =
    document.getElementById('configuracion-examen');

const formulario =
    document.getElementById('formExamen');

const temporizador =
    document.getElementById('temporizador');

const campoCambios =
    document.getElementById('cambios_pestana');

if (
    !configuracion ||
    !formulario ||
    !temporizador ||
    !campoCambios
) {
    throw new Error(
        'No se pudo inicializar el formulario.'
    );
}

let cambios =
    parseInt(
        configuracion.dataset.cambios || '0',
        10
    );

let tiempo =
    parseInt(
        configuracion.dataset.tiempo || '0',
        10
    );

const intentoId =
    parseInt(
        configuracion.dataset.intentoId || '0',
        10
    );

const urlAutoguardado =
    configuracion.dataset.urlAutoguardado;

let enviado = false;

let ultimoCambio = 0;

const temporizadoresGuardado = {};


/*
|--------------------------------------------------------------------------
| Marcar envío normal
|--------------------------------------------------------------------------
*/

formulario.addEventListener(
    'submit',
    function () {
        enviado = true;
    }
);


/*
|--------------------------------------------------------------------------
| Autoguardar respuesta
|--------------------------------------------------------------------------
*/

function autoguardarRespuesta(
    preguntaId,
    respuesta
) {

    if (enviado) {
        return;
    }

    const datos = new FormData();

    datos.append(
        'intento_id',
        intentoId
    );

    datos.append(
        'pregunta_id',
        preguntaId
    );

    if (Array.isArray(respuesta)) {

        respuesta.forEach(
            function (valor) {

                datos.append(
                    'respuesta[]',
                    valor
                );
            }
        );

    } else {

        datos.append(
            'respuesta',
            respuesta
        );
    }

    fetch(
        urlAutoguardado,
        {
            method: 'POST',
            body: datos
        }
    )
        .then(function (respuestaServidor) {
            return respuestaServidor.json();
        })
        .then(function (resultado) {

            if (!resultado.ok) {

                console.error(
                    'No se pudo autoguardar:',
                    resultado.mensaje
                );
            }
        })
        .catch(function (error) {

            console.error(
                'Error de autoguardado:',
                error
            );
        });
}


/*
|--------------------------------------------------------------------------
| Textarea
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'textarea[data-pregunta-id]'
    )
    .forEach(function (campo) {

        campo.addEventListener(
            'input',
            function () {

                const preguntaId =
                    this.dataset.preguntaId;

                clearTimeout(
                    temporizadoresGuardado[
                        preguntaId
                    ]
                );

                const campoActual = this;

                temporizadoresGuardado[
                    preguntaId
                ] = setTimeout(
                    function () {

                        autoguardarRespuesta(
                            preguntaId,
                            campoActual.value
                        );

                    },
                    700
                );
            }
        );
    });


/*
|--------------------------------------------------------------------------
| Radio
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'input[type="radio"][data-pregunta-id]'
    )
    .forEach(function (campo) {

        campo.addEventListener(
            'change',
            function () {

                autoguardarRespuesta(
                    this.dataset.preguntaId,
                    this.value
                );
            }
        );
    });


/*
|--------------------------------------------------------------------------
| Checkbox
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll(
        'input[type="checkbox"][data-pregunta-id]'
    )
    .forEach(function (campo) {

        campo.addEventListener(
            'change',
            function () {

                const preguntaId =
                    this.dataset.preguntaId;

                const seleccionadas = [];

                document
                    .querySelectorAll(
                        'input[type="checkbox"]' +
                        '[data-pregunta-id="' +
                        preguntaId +
                        '"]:checked'
                    )
                    .forEach(
                        function (check) {

                            seleccionadas.push(
                                check.value
                            );
                        }
                    );

                autoguardarRespuesta(
                    preguntaId,
                    seleccionadas
                );
            }
        );
    });


/*
|--------------------------------------------------------------------------
| Enviar formulario automáticamente
|--------------------------------------------------------------------------
*/

function enviarExamen(mensaje) {

    if (enviado) {
        return;
    }

    enviado = true;

    alert(mensaje);

    formulario.submit();
}


/*
|--------------------------------------------------------------------------
| Temporizador
|--------------------------------------------------------------------------
*/

function actualizarTemporizador() {

    if (tiempo <= 0) {

        temporizador.textContent =
            'Tiempo restante: 0:00';

        enviarExamen(
            'El tiempo terminó. ' +
            'El examen será enviado automáticamente.'
        );

        return;
    }

    const minutos =
        Math.floor(tiempo / 60);

    const segundos =
        tiempo % 60;

    temporizador.textContent =
        'Tiempo restante: ' +
        minutos +
        ':' +
        (
            segundos < 10
                ? '0'
                : ''
        ) +
        segundos;

    tiempo--;
}

actualizarTemporizador();

const intervaloTemporizador =
    setInterval(
        actualizarTemporizador,
        1000
    );


/*
|--------------------------------------------------------------------------
| Cambio de pantalla
|--------------------------------------------------------------------------
*/

function registrarCambioPantalla() {

    if (enviado) {
        return;
    }

    const ahora = Date.now();

    /*
     * blur y visibilitychange pueden ejecutarse
     * prácticamente al mismo tiempo.
     */

    if (
        ahora - ultimoCambio <
        1000
    ) {
        return;
    }

    ultimoCambio = ahora;

    cambios++;

    campoCambios.value =
        cambios;

    if (cambios === 1) {

        alert(
            'Advertencia: no debe cambiar de pantalla. ' +
            'Si vuelve a hacerlo, el formulario finalizará.'
        );
    }

    if (cambios >= 2) {

        enviarExamen(
            'Formulario finalizado automáticamente ' +
            'por cambiar de pantalla.'
        );
    }
}


document.addEventListener(
    'visibilitychange',
    function () {

        if (document.hidden) {
            registrarCambioPantalla();
        }
    }
);


window.addEventListener(
    'blur',
    function () {
        registrarCambioPantalla();
    }
);


/*
|--------------------------------------------------------------------------
| Bloquear botón atrás
|--------------------------------------------------------------------------
*/

history.pushState(
    null,
    '',
    location.href
);

window.addEventListener(
    'popstate',
    function () {

        history.pushState(
            null,
            '',
            location.href
        );

        alert(
            'No puede volver atrás durante el formulario.'
        );
    }
);


/*
|--------------------------------------------------------------------------
| Bloquear determinadas teclas
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (e) {

        const tecla =
            e.key.toLowerCase();

        if (
            e.key === 'F5' ||
            e.key === 'F12' ||
            (e.ctrlKey && tecla === 'r') ||
            (e.ctrlKey && tecla === 'c') ||
            (e.ctrlKey && tecla === 'v') ||
            (e.ctrlKey && tecla === 'x') ||
            (e.ctrlKey && tecla === 'a') ||
            (e.ctrlKey && tecla === 'u') ||
            (
                e.ctrlKey &&
                e.shiftKey &&
                tecla === 'i'
            )
        ) {
            e.preventDefault();
        }
    }
);


/*
|--------------------------------------------------------------------------
| Bloquear menú contextual y portapapeles
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'contextmenu',
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    'copy',
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    'cut',
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    'paste',
    function (e) {
        e.preventDefault();
    }
);

document.addEventListener(
    'selectstart',
    function (e) {
        e.preventDefault();
    }
);


/*
|--------------------------------------------------------------------------
| Advertir al cerrar o abandonar
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'beforeunload',
    function (e) {

        if (!enviado) {

            e.preventDefault();

            e.returnValue = '';
        }
    }
);