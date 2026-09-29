<?php

declare(strict_types=1);

class Router
{
    /*
    |--------------------------------------------------------------------------
    | Rutas registradas
    |--------------------------------------------------------------------------
    */

    private array $routes = [];


    /*
    |--------------------------------------------------------------------------
    | Registrar ruta GET
    |--------------------------------------------------------------------------
    */

    public function get(
        string $path,
        array $action
    ): void {

        $this->addRoute(
            'GET',
            $path,
            $action
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Registrar ruta POST
    |--------------------------------------------------------------------------
    */

    public function post(
        string $path,
        array $action
    ): void {

        $this->addRoute(
            'POST',
            $path,
            $action
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Agregar ruta
    |--------------------------------------------------------------------------
    */

    private function addRoute(
        string $method,
        string $path,
        array $action
    ): void {

        $path = $this->normalizePath($path);

        $this->routes[] = [
            'method' => $method,
            'path'   => $path,
            'action' => $action
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Ejecutar Router
    |--------------------------------------------------------------------------
    */

    public function dispatch(
        string $method,
        string $uri
    ): void {

        $uri = $this->normalizePath($uri);

        $routeExists = false;


        foreach ($this->routes as $route) {

            /*
            |--------------------------------------------------------------------------
            | Convertir parámetros dinámicos
            |--------------------------------------------------------------------------
            |
            | /formularios/{id}/editar
            |
            | se convierte en:
            |
            | /formularios/([^/]+)/editar
            |
            */

            $pattern = preg_replace(
                '#\{[a-zA-Z_][a-zA-Z0-9_]*\}#',
                '([^/]+)',
                $route['path']
            );

            $pattern =
                '#^' . $pattern . '$#';


            /*
            |--------------------------------------------------------------------------
            | Comprobar URL
            |--------------------------------------------------------------------------
            */

            if (
                !preg_match(
                    $pattern,
                    $uri,
                    $matches
                )
            ) {
                continue;
            }


            $routeExists = true;


            /*
            |--------------------------------------------------------------------------
            | Comprobar método HTTP
            |--------------------------------------------------------------------------
            */

            if (
                $route['method'] !== $method
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Quitar coincidencia completa
            |--------------------------------------------------------------------------
            */

            array_shift($matches);


            /*
            |--------------------------------------------------------------------------
            | Controller y método
            |--------------------------------------------------------------------------
            */

            [
                $controllerClass,
                $controllerMethod
            ] = $route['action'];


            if (
                !class_exists(
                    $controllerClass
                )
            ) {

                http_response_code(500);

                die(
                    'Controller no encontrado.'
                );
            }


            $controller =
                new $controllerClass();


            if (
                !method_exists(
                    $controller,
                    $controllerMethod
                )
            ) {

                http_response_code(500);

                die(
                    'Método del Controller no encontrado.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Ejecutar Controller
            |--------------------------------------------------------------------------
            */

            $controller->$controllerMethod(
                ...$matches
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Ruta existente, método incorrecto
        |--------------------------------------------------------------------------
        */

        if ($routeExists) {

            http_response_code(405);

            echo '<h1>405</h1>';
            echo '<p>Método no permitido.</p>';

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | 404
        |--------------------------------------------------------------------------
        */

        http_response_code(404);

        echo '<h1>404</h1>';
        echo '<p>Página no encontrada.</p>';
    }


    /*
    |--------------------------------------------------------------------------
    | Normalizar rutas
    |--------------------------------------------------------------------------
    */

    private function normalizePath(
        string $path
    ): string {

        $path = parse_url(
            $path,
            PHP_URL_PATH
        ) ?? '/';

        $path = trim(
            $path,
            '/'
        );

        if ($path === '') {
            return '/';
        }

        return '/' . $path;
    }
}