<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Rutas de SISFORMULARIOS
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| Listar formularios
|--------------------------------------------------------------------------
*/

$router->get(
    '/formularios',
    [
        FormularioController::class,
        'index'
    ]
);


/*
|--------------------------------------------------------------------------
| Nuevo formulario
|--------------------------------------------------------------------------
*/

$router->get(
    '/formularios/nuevo',
    [
        FormularioController::class,
        'nuevo'
    ]
);


/*
|--------------------------------------------------------------------------
| Guardar formulario
|--------------------------------------------------------------------------
*/

$router->post(
    '/formularios',
    [
        FormularioController::class,
        'guardar'
    ]
);


/*
|--------------------------------------------------------------------------
| Editar formulario
|--------------------------------------------------------------------------
*/

$router->get(
    '/formularios/{id}/editar',
    [
        FormularioController::class,
        'editar'
    ]
);


/*
|--------------------------------------------------------------------------
| Actualizar formulario
|--------------------------------------------------------------------------
*/

$router->post(
    '/formularios/{id}/actualizar',
    [
        FormularioController::class,
        'actualizar'
    ]
);

/*
|--------------------------------------------------------------------------
| Preguntas
|--------------------------------------------------------------------------
*/

$router->get(
    '/formularios/{id}/preguntas',
    [
        PreguntaController::class,
        'preguntas'
    ]
);

$router->get(
    '/formularios/{id}/preguntas/nueva',
    [
        PreguntaController::class,
        'nuevaPregunta'
    ]
);

$router->post(
    '/formularios/{id}/preguntas',
    [
        PreguntaController::class,
        'guardarPregunta'
    ]
);

$router->get(
    '/formularios/{id}/preguntas/{preguntaId}/editar',
    [
        PreguntaController::class,
        'editarPregunta'
    ]
);

$router->post(
    '/formularios/{id}/preguntas/{preguntaId}/actualizar',
    [
        PreguntaController::class,
        'actualizarPregunta'
    ]
);

$router->post(
    '/formularios/{id}/preguntas/{preguntaId}/eliminar',
    [
        PreguntaController::class,
        'eliminarPregunta'
    ]
);
/*
|--------------------------------------------------------------------------
| Intentos
|--------------------------------------------------------------------------
*/

$router->post(
    '/formularios/{id}/reiniciar-intentos',
    [
        IntentoController::class,
        'reiniciar'
    ]
);
/*
|--------------------------------------------------------------------------
| Resultados
|--------------------------------------------------------------------------
*/

$router->get(
    '/formularios/{id}/resultados',
    [
        ResultadoController::class,
        'index'
    ]
);
$router->get(
    '/resultados/intentos/{id}',
    [
        ResultadoController::class,
        'detalle'
    ]
);
$router->get(
    '/formularios/{id}/resolver',
    [
        AlumnoController::class,
        'resolver'
    ]
);
$router->post(
    '/autoguardar-respuesta',
    [
        AlumnoController::class,
        'autoguardar'
    ]
);
$router->post(
    '/finalizar-examen',
    [
        AlumnoController::class,
        'finalizar'
    ]
);
$router->get(
    '/formularios/disponibles',
    [
        AlumnoController::class,
        'disponibles'
    ]
);
$router->get(
    '/formularios/{id}/mis-respuestas',
    [
        AlumnoController::class,
        'misRespuestas'
    ]
);
$router->get(
    '/logout',
    [
        AlumnoController::class,
        'logout'
    ]
);
$router->get(
    '/login',
    [
        AlumnoController::class,
        'login'
    ]
);
$router->get(
    '/formularios/reportes',
    [
        ReporteController::class,
        'index'
    ]
);

$router->post(
    '/formularios/reportes/exportar',
    [
        ReporteController::class,
        'exportar'
    ]
);