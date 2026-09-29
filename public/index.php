<?php

declare(strict_types=1);

session_start();

/*
|--------------------------------------------------------------------------
| Configuración principal
|--------------------------------------------------------------------------
*/

define(
    'BASE_PATH',
    dirname(__DIR__)
);

define(
    'BASE_URL',
    '/formexamenes'
);

/*
|--------------------------------------------------------------------------
| Cargar núcleo
|--------------------------------------------------------------------------
*/

require_once BASE_PATH .
    '/app/Core/Router.php';

/*
|--------------------------------------------------------------------------
| Cargar Controllers
|--------------------------------------------------------------------------
*/

require_once BASE_PATH .
    '/app/Controllers/FormularioController.php';

require_once BASE_PATH .
    '/app/Controllers/PreguntaController.php';

require_once BASE_PATH .
    '/app/Controllers/IntentoController.php';
require_once BASE_PATH .
    '/app/Controllers/ResultadoController.php';
require_once BASE_PATH .
    '/app/Controllers/AlumnoController.php';
require_once BASE_PATH .
    '/app/Services/CorreccionService.php';
require_once BASE_PATH .
    '/app/Controllers/ReporteController.php';
/*
|--------------------------------------------------------------------------
| Crear Router
|--------------------------------------------------------------------------
*/

$router = new Router();

/*
|--------------------------------------------------------------------------
| Cargar rutas
|--------------------------------------------------------------------------
*/

require BASE_PATH .
    '/routes/web.php';

/*
|--------------------------------------------------------------------------
| Obtener ruta solicitada
|--------------------------------------------------------------------------
*/

$uri = parse_url(
    $_SERVER['REQUEST_URI'],
    PHP_URL_PATH
);

$uri = $uri ?? '/';

/*
|--------------------------------------------------------------------------
| Quitar ruta base del proyecto
|--------------------------------------------------------------------------
*/

if (
    BASE_URL !== '' &&
    str_starts_with($uri, BASE_URL)
) {
    $uri = substr(
        $uri,
        strlen(BASE_URL)
    );
}

/*
|--------------------------------------------------------------------------
| Página principal
|--------------------------------------------------------------------------
*/

$uriNormalizada =
    '/' . trim($uri, '/');

if ($uriNormalizada === '/') {

    header(
        'Location: ' .
        BASE_URL .
        '/formularios'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Ejecutar Router
|--------------------------------------------------------------------------
*/

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $uri
);