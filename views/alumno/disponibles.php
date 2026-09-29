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

    <title>Formularios disponibles</title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>/public/assets/css/disponibles.css?v=<?= filemtime(
            BASE_PATH . '/public/assets/css/disponibles.css'
        ) ?>"
    >

</head>

<body>

<div class="pagina-formularios">

    <div class="encabezado-formularios">

    <div>

        <h1>
            Formularios disponibles
        </h1>

        <p>
            Bienvenido,
            <strong>
                <?= htmlspecialchars(
                    $_SESSION['nombre'] ?? ''
                ) ?>
            </strong>
        </p>

    </div>


    <a
        href="<?= BASE_URL ?>/logout"
        class="btn-cerrar-sesion"
    >
        Cerrar sesión
    </a>

</div>


    <?php if (empty($formularios)): ?>

        <div class="estado-vacio">

            <h2>
                No hay formularios disponibles
            </h2>

            <p>
                Actualmente no existe ningún
                formulario publicado.
            </p>

        </div>

    <?php else: ?>

        <div class="grid-formularios">

            <?php foreach ($formularios as $formulario): ?>

                <article class="card-formulario">

                    <div class="card-formulario-header">

                        <div>

                            <h2>
                                <?= htmlspecialchars(
                                    $formulario['titulo']
                                ) ?>
                            </h2>

                            <?php if (
                                !empty(
                                    $formulario['descripcion']
                                )
                            ): ?>

                                <p>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $formulario[
                                                'descripcion'
                                            ]
                                        )
                                    ) ?>
                                </p>

                            <?php endif; ?>

                        </div>

                    </div>


                    <div class="card-formulario-datos">

                        <div>

                            <span>
                                Preguntas
                            </span>

                            <strong>
                                <?= (int)$formulario[
                                    'cantidad_preguntas'
                                ] ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Intentos
                            </span>

                            <strong>
                                <?= (int)$formulario[
                                    'cantidad_intentos'
                                ] ?>
                            </strong>

                        </div>


                        <div>

                            <span>
                                Tiempo
                            </span>

                            <strong>

                                <?php if (
                                    $formulario[
                                        'tipo_tiempo'
                                    ] === 'limite'
                                ): ?>

                                    Hasta fecha límite

                                <?php else: ?>

                                    <?= (int)$formulario[
                                        'tiempo_minutos'
                                    ] ?>
                                    min

                                <?php endif; ?>

                            </strong>

                        </div>

                    </div>


                    <?php if (
                        $formulario['tipo_tiempo']
                        === 'limite' &&
                        !empty(
                            $formulario[
                                'fecha_hora_limite'
                            ]
                        )
                    ): ?>

                        <p>
                            <strong>
                                Fecha límite:
                            </strong>

                            <?= htmlspecialchars(
                                date(
                                    'd/m/Y H:i',
                                    strtotime(
                                        $formulario[
                                            'fecha_hora_limite'
                                        ]
                                    )
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>


                    <div class="acciones-card">

    <?php

    $intentosUtilizados =
        (int)$formulario['intentos_utilizados'];

    $cantidadIntentos =
        (int)$formulario['cantidad_intentos'];

    $intentosAgotados =
        $intentosUtilizados >= $cantidadIntentos;
    
        $tiempoFinalizado = false;

if (
    $formulario['tipo_tiempo'] === 'limite' &&
    !empty($formulario['fecha_hora_limite'])
) {

    $fechaLimite =
        strtotime(
            $formulario['fecha_hora_limite']
        );

    if (
        $fechaLimite !== false &&
        time() >= $fechaLimite
    ) {
        $tiempoFinalizado = true;
    }
}

    ?>



    <?php if ($tiempoFinalizado): ?>

    <button
        type="button"
        class="btn-card btn-card-principal btn-formulario-bloqueado"
        data-tipo="tiempo"
        data-titulo="<?= htmlspecialchars(
            $formulario['titulo'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        data-fecha="<?= htmlspecialchars(
            date(
                'd/m/Y H:i',
                strtotime(
                    $formulario['fecha_hora_limite']
                )
            ),
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >
        Resolver
    </button>


<?php elseif ($intentosAgotados): ?>

    <button
        type="button"
        class="btn-card btn-card-principal btn-formulario-bloqueado"
        data-tipo="intentos"
        data-titulo="<?= htmlspecialchars(
            $formulario['titulo'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
        data-utilizados="<?= $intentosUtilizados ?>"
        data-maximos="<?= $cantidadIntentos ?>"
    >
        Resolver
    </button>


<?php else: ?>

    <a
        href="<?= BASE_URL ?>/formularios/<?= (int)$formulario['id'] ?>/resolver"
        class="btn-card btn-card-principal"
    >
        Resolver
    </a>

<?php endif; ?>


    <?php if (
        (int)$formulario['mostrar_respuestas'] === 1 &&
        $intentosUtilizados > 0
    ): ?>

        <a
            href="<?= BASE_URL ?>/formularios/<?= (int)$formulario['id'] ?>/mis-respuestas"
            class="btn-card btn-card-secundario"
        >
            Ver respuestas
        </a>

    <?php endif; ?>

</div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>
<div
    id="modalIntentos"
    class="modal-intentos"
    aria-hidden="true"
>
    <div class="modal-intentos-contenido">

        <div class="modal-intentos-icono">
            !
        </div>

        <h2>
            Intentos agotados
        </h2>

        <p id="modalIntentosTitulo"></p>

        <p id="modalIntentosMensaje"></p>

        <button
            type="button"
            id="cerrarModalIntentos"
            class="btn-modal-entendido"
        >
            Entendido
        </button>

    </div>
</div>

<script>
document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById(
                'modalIntentos'
            );

        const tituloModal =
            document.querySelector(
                '#modalIntentos h2'
            );

        const tituloFormulario =
            document.getElementById(
                'modalIntentosTitulo'
            );

        const mensaje =
            document.getElementById(
                'modalIntentosMensaje'
            );

        const cerrar =
            document.getElementById(
                'cerrarModalIntentos'
            );

        const botones =
            document.querySelectorAll(
                '.btn-formulario-bloqueado'
            );


        botones.forEach(function (boton) {

            boton.addEventListener(
                'click',
                function () {

                    const tipo =
                        this.dataset.tipo;

                    const nombre =
                        this.dataset.titulo;


                    tituloFormulario.textContent =
                        nombre;


                    if (tipo === 'tiempo') {

                        const fecha =
                            this.dataset.fecha;

                        tituloModal.textContent =
                            'Tiempo finalizado';

                        mensaje.textContent =
                            'La fecha límite de este formulario ya terminó. ' +
                            'La fecha límite era el ' +
                            fecha +
                            '.';

                    } else {

                        const utilizados =
                            this.dataset.utilizados;

                        const maximos =
                            this.dataset.maximos;

                        tituloModal.textContent =
                            'Intentos agotados';

                        mensaje.textContent =
                            'Ya utilizaste los ' +
                            utilizados +
                            ' de ' +
                            maximos +
                            ' intentos permitidos para este formulario.';
                    }


                    modal.classList.add(
                        'activo'
                    );

                    modal.setAttribute(
                        'aria-hidden',
                        'false'
                    );
                }
            );

        });


        function cerrarModal() {

            modal.classList.remove(
                'activo'
            );

            modal.setAttribute(
                'aria-hidden',
                'true'
            );
        }


        cerrar.addEventListener(
            'click',
            cerrarModal
        );


        modal.addEventListener(
            'click',
            function (event) {

                if (event.target === modal) {
                    cerrarModal();
                }
            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Escape' &&
                    modal.classList.contains(
                        'activo'
                    )
                ) {
                    cerrarModal();
                }
            }
        );

    }
);
</script>
</body>
</html>