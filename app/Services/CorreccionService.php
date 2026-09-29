<?php

declare(strict_types=1);

class CorreccionService
{
    /*
    |--------------------------------------------------------------------------
    | Corregir pregunta de código
    |--------------------------------------------------------------------------
    */

    public function corregirCodigo(
        array $pregunta,
        string $respuestaAlumno
    ): array {

        $respuestaAlumno =
            trim($respuestaAlumno);

        $codigo =
            strtolower($respuestaAlumno);

        $textoPregunta =
            strtolower(
                (string)$pregunta['pregunta']
            );

        $puntaje =
            (float)$pregunta['puntaje'];

        if ($puntaje < 0) {
            $puntaje = 0;
        }

        $correcta = 0;

        $puntajeObtenido = 0;

        $observacion = '';


        /*
        |--------------------------------------------------------------------------
        | Hola Mundo
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $textoPregunta,
                'hola mundo'
            )
        ) {

            $puntos = 0;

            if (
                str_contains(
                    $codigo,
                    'cout'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'hola'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'mundo'
                )
            ) {
                $puntos++;
            }

            $puntajeObtenido =
                ($puntos / 3) *
                $puntaje;

            $correcta =
                $puntajeObtenido >=
                ($puntaje * 0.70)
                    ? 1
                    : 0;

            $observacion =
                'Corrección automática por palabras clave.';
        }


        /*
        |--------------------------------------------------------------------------
        | Suma de dos números
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains(
                $textoPregunta,
                'suma'
            ) &&
            str_contains(
                $textoPregunta,
                'dos'
            )
        ) {

            $puntos = 0;

            if (
                str_contains(
                    $codigo,
                    'cin'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'cout'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    '+'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'int'
                )
            ) {
                $puntos++;
            }

            $puntajeObtenido =
                ($puntos / 4) *
                $puntaje;

            $correcta =
                $puntajeObtenido >=
                ($puntaje * 0.70)
                    ? 1
                    : 0;

            $observacion =
                'Corrección automática de suma.';
        }


        /*
        |--------------------------------------------------------------------------
        | Positivo o negativo
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains(
                $textoPregunta,
                'positivo'
            ) ||
            str_contains(
                $textoPregunta,
                'negativo'
            )
        ) {

            $puntos = 0;

            if (
                str_contains(
                    $codigo,
                    'if'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'else'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'cin'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'cout'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'numero'
                )
            ) {
                $puntos++;
            }

            $puntajeObtenido =
                ($puntos / 5) *
                $puntaje;

            $correcta =
                $puntajeObtenido >=
                ($puntaje * 0.70)
                    ? 1
                    : 0;

            $observacion =
                'Corrección automática: positivo o negativo.';
        }


        /*
        |--------------------------------------------------------------------------
        | Par o impar
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains(
                $textoPregunta,
                'par'
            ) ||
            str_contains(
                $textoPregunta,
                'impar'
            )
        ) {

            $puntos = 0;

            if (
                str_contains(
                    $codigo,
                    'if'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'else'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    '%'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'cin'
                )
            ) {
                $puntos++;
            }

            if (
                str_contains(
                    $codigo,
                    'cout'
                )
            ) {
                $puntos++;
            }

            $puntajeObtenido =
                ($puntos / 5) *
                $puntaje;

            $correcta =
                $puntajeObtenido >=
                ($puntaje * 0.70)
                    ? 1
                    : 0;

            $observacion =
                'Corrección automática: par o impar.';
        }


        /*
        |--------------------------------------------------------------------------
        | Código complejo - Ollama
        |--------------------------------------------------------------------------
        */

        else {

            $resultadoIA =
                corregirConOllama(
                    (string)$pregunta['pregunta'],
                    $respuestaAlumno,
                    (string)$pregunta['respuesta_correcta'],
                    $puntaje
                );

            $correcta =
                !empty(
                    $resultadoIA['correcta']
                )
                    ? 1
                    : 0;

            $puntajeObtenido =
                isset(
                    $resultadoIA['puntaje']
                )
                    ? (float)$resultadoIA['puntaje']
                    : 0;

            $observacion =
                isset(
                    $resultadoIA['observacion']
                )
                    ? (string)$resultadoIA['observacion']
                    : 'Corrección realizada con Ollama.';
        }


        /*
        |--------------------------------------------------------------------------
        | Limitar puntaje
        |--------------------------------------------------------------------------
        */

        if (
            $puntajeObtenido >
            $puntaje
        ) {
            $puntajeObtenido =
                $puntaje;
        }

        if ($puntajeObtenido < 0) {
            $puntajeObtenido = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        return [
            'respuesta' =>
                $respuestaAlumno,

            'correcta' =>
                $correcta,

            'puntaje' =>
                $puntajeObtenido,

            'observacion' =>
                $observacion
        ];
    }
    public function corregirRespuestaTexto(
    array $pregunta,
    string $respuestaAlumno
): array {

    $respuestaAlumno =
        trim($respuestaAlumno);

    $puntaje =
        (float)$pregunta['puntaje'];

    if ($puntaje < 0) {
        $puntaje = 0;
    }

    $correcta = 0;

    $puntajeObtenido = 0;

    $observacion = '';

    $metodoCorreccion =
        $pregunta['metodo_correccion']
        ?? 'manual';


    /*
    |--------------------------------------------------------------------------
    | Corrección manual
    |--------------------------------------------------------------------------
    */

    if (
        $metodoCorreccion ===
        'manual'
    ) {

        $correcta = 0;

        $puntajeObtenido = 0;

        $observacion =
            'Respuesta pendiente de revisión manual.';
    }


    /*
    |--------------------------------------------------------------------------
    | Corrección por palabras clave
    |--------------------------------------------------------------------------
    */

    elseif (
        $metodoCorreccion ===
        'palabras_clave'
    ) {

        $respuestaEsperada =
            strtolower(
                trim(
                    (string)$pregunta[
                        'respuesta_correcta'
                    ]
                )
            );

        $respuestaComparar =
            strtolower(
                $respuestaAlumno
            );

        /*
        | Las palabras pueden estar separadas por:
        | coma, punto y coma o salto de línea.
        */

        $palabras =
            preg_split(
                '/[,;\r\n]+/',
                $respuestaEsperada
            );

        $palabrasValidas = [];

        foreach (
            $palabras as $palabra
        ) {

            $palabra =
                trim($palabra);

            if ($palabra !== '') {

                $palabrasValidas[] =
                    $palabra;
            }
        }

        $totalPalabras =
            count($palabrasValidas);

        $palabrasEncontradas = 0;

        foreach (
            $palabrasValidas
            as $palabra
        ) {

            if (
                str_contains(
                    $respuestaComparar,
                    $palabra
                )
            ) {

                $palabrasEncontradas++;
            }
        }

        if ($totalPalabras > 0) {

            $puntajeObtenido =
                (
                    $palabrasEncontradas /
                    $totalPalabras
                ) *
                $puntaje;
        }

        $correcta =
            $puntajeObtenido >=
            ($puntaje * 0.70)
                ? 1
                : 0;

        $observacion =
            'Corrección automática por palabras clave.';
    }


    /*
    |--------------------------------------------------------------------------
    | Corrección con Ollama
    |--------------------------------------------------------------------------
    */

    else {

        $resultadoIA =
            corregirConOllama(
                (string)$pregunta[
                    'pregunta'
                ],
                $respuestaAlumno,
                (string)$pregunta[
                    'respuesta_correcta'
                ],
                $puntaje
            );

        $correcta =
            !empty(
                $resultadoIA['correcta']
            )
                ? 1
                : 0;

        $puntajeObtenido =
            isset(
                $resultadoIA['puntaje']
            )
                ? (float)$resultadoIA[
                    'puntaje'
                ]
                : 0;

        $observacion =
            isset(
                $resultadoIA['observacion']
            )
                ? (string)$resultadoIA[
                    'observacion'
                ]
                : 'Corrección realizada con Ollama.';
    }


    /*
    |--------------------------------------------------------------------------
    | Limitar puntaje
    |--------------------------------------------------------------------------
    */

    if (
        $puntajeObtenido >
        $puntaje
    ) {

        $puntajeObtenido =
            $puntaje;
    }

    if ($puntajeObtenido < 0) {

        $puntajeObtenido = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Resultado
    |--------------------------------------------------------------------------
    */

    return [
        'respuesta' =>
            $respuestaAlumno,

        'correcta' =>
            $correcta,

        'puntaje' =>
            $puntajeObtenido,

        'observacion' =>
            $observacion
    ];
}
public function corregirSeleccionMultiple(
    mysqli $conn,
    array $pregunta,
    array $opcionesMarcadas
): array {

    $preguntaId =
        (int)$pregunta['id'];

    $puntaje =
        (float)$pregunta['puntaje'];

    if ($puntaje < 0) {
        $puntaje = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalizar opciones recibidas
    |--------------------------------------------------------------------------
    */

    $opcionesMarcadas =
        array_map(
            'intval',
            $opcionesMarcadas
        );

    $opcionesMarcadas =
        array_filter(
            $opcionesMarcadas,
            function ($id) {
                return $id > 0;
            }
        );

    $opcionesMarcadas =
        array_values(
            array_unique(
                $opcionesMarcadas
            )
        );


    /*
    |--------------------------------------------------------------------------
    | Obtener opciones correctas
    |--------------------------------------------------------------------------
    */

    $stmtCorrectas =
        $conn->prepare("
            SELECT id
            FROM opciones
            WHERE pregunta_id = ?
              AND es_correcta = 1
            ORDER BY id ASC
        ");

    if (!$stmtCorrectas) {

        throw new Exception(
            'No se pudieron obtener ' .
            'las opciones correctas.'
        );
    }

    $stmtCorrectas->bind_param(
        'i',
        $preguntaId
    );

    $stmtCorrectas->execute();

    $resultadoCorrectas =
        $stmtCorrectas->get_result();

    $opcionesCorrectas = [];

    while (
        $filaCorrecta =
        $resultadoCorrectas->fetch_assoc()
    ) {

        $opcionesCorrectas[] =
            (int)$filaCorrecta['id'];
    }

    $stmtCorrectas->close();


    /*
    |--------------------------------------------------------------------------
    | Validar opciones seleccionadas
    |--------------------------------------------------------------------------
    */

    $opcionesValidas = [];

    if (
        count($opcionesMarcadas) > 0
    ) {

        $marcadores =
            implode(
                ',',
                array_fill(
                    0,
                    count(
                        $opcionesMarcadas
                    ),
                    '?'
                )
            );

        $tipos =
            str_repeat(
                'i',
                count(
                    $opcionesMarcadas
                )
            );

        $sqlOpcionesValidas = "
            SELECT
                id,
                opcion_texto
            FROM opciones
            WHERE pregunta_id = ?
              AND id IN ($marcadores)
            ORDER BY id ASC
        ";

        $stmtOpcionesValidas =
            $conn->prepare(
                $sqlOpcionesValidas
            );

        if (!$stmtOpcionesValidas) {

            throw new Exception(
                'No se pudieron validar ' .
                'las opciones seleccionadas.'
            );
        }

        $parametros =
            array_merge(
                [$preguntaId],
                $opcionesMarcadas
            );

        $tiposParametros =
            'i' . $tipos;

        $stmtOpcionesValidas->bind_param(
            $tiposParametros,
            ...$parametros
        );

        $stmtOpcionesValidas->execute();

        $resultadoOpcionesValidas =
            $stmtOpcionesValidas
                ->get_result();

        while (
            $filaOpcion =
            $resultadoOpcionesValidas
                ->fetch_assoc()
        ) {

            $opcionesValidas[] = [
                'id' =>
                    (int)$filaOpcion['id'],

                'texto' =>
                    (string)$filaOpcion[
                        'opcion_texto'
                    ]
            ];
        }

        $stmtOpcionesValidas->close();
    }


    /*
    |--------------------------------------------------------------------------
    | Construir respuesta válida
    |--------------------------------------------------------------------------
    */

    $idsValidos = [];

    $textosSeleccionados = [];

    foreach (
        $opcionesValidas
        as $opcionValida
    ) {

        $idsValidos[] =
            $opcionValida['id'];

        $textosSeleccionados[] =
            $opcionValida['texto'];
    }


    /*
    |--------------------------------------------------------------------------
    | Comparación exacta
    |--------------------------------------------------------------------------
    */

    sort($opcionesCorrectas);

    sort($idsValidos);

    $correcta = 0;

    $puntajeObtenido = 0;

    $respuestaTexto =
        implode(
            ' | ',
            $textosSeleccionados
        );

    if (
        count($opcionesCorrectas) > 0 &&
        $opcionesCorrectas ===
            $idsValidos
    ) {

        $correcta = 1;

        $puntajeObtenido =
            $puntaje;

        $observacion =
            'Todas las opciones ' .
            'seleccionadas son correctas.';

    } else {

        $observacion =
            'La combinación de opciones ' .
            'seleccionadas es incorrecta.';
    }


    /*
    |--------------------------------------------------------------------------
    | Resultado
    |--------------------------------------------------------------------------
    */

    return [
        'respuesta' =>
            $respuestaTexto,

        'correcta' =>
            $correcta,

        'puntaje' =>
            $puntajeObtenido,

        'observacion' =>
            $observacion
    ];
}
public function corregirSeleccionUnica(
    mysqli $conn,
    array $pregunta,
    int $opcionId
): array {

    $preguntaId =
        (int)$pregunta['id'];

    $puntaje =
        (float)$pregunta['puntaje'];

    if ($puntaje < 0) {
        $puntaje = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Buscar y validar opción
    |--------------------------------------------------------------------------
    */

    $stmtOpcion =
        $conn->prepare("
            SELECT
                id,
                opcion_texto,
                es_correcta
            FROM opciones
            WHERE id = ?
              AND pregunta_id = ?
            LIMIT 1
        ");

    if (!$stmtOpcion) {

        throw new Exception(
            'No se pudo validar la opción seleccionada.'
        );
    }

    $stmtOpcion->bind_param(
        'ii',
        $opcionId,
        $preguntaId
    );

    $stmtOpcion->execute();

    $resultadoOpcion =
        $stmtOpcion->get_result();

    $opcion =
        $resultadoOpcion->fetch_assoc();

    $stmtOpcion->close();


    /*
    |--------------------------------------------------------------------------
    | Valores iniciales
    |--------------------------------------------------------------------------
    */

    $correcta = 0;

    $puntajeObtenido = 0;

    $respuestaTexto = '';

    $observacion = '';


    /*
    |--------------------------------------------------------------------------
    | Corregir
    |--------------------------------------------------------------------------
    */

    if ($opcion) {

        $respuestaTexto =
            (string)$opcion[
                'opcion_texto'
            ];

        if (
            (int)$opcion[
                'es_correcta'
            ] === 1
        ) {

            $correcta = 1;

            $puntajeObtenido =
                $puntaje;

            $observacion =
                'Respuesta correcta.';

        } else {

            $observacion =
                'Respuesta incorrecta.';
        }

    } else {

        $observacion =
            'La opción seleccionada no es válida.';
    }


    /*
    |--------------------------------------------------------------------------
    | Resultado
    |--------------------------------------------------------------------------
    */

    return [
        'respuesta' =>
            $respuestaTexto,

        'correcta' =>
            $correcta,

        'puntaje' =>
            $puntajeObtenido,

        'observacion' =>
            $observacion
    ];
}
}