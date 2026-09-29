<?php

declare(strict_types=1);

class ReporteController
{
    /*
    |--------------------------------------------------------------------------
    | Mostrar reportes
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validar sesión
        |--------------------------------------------------------------------------
        */

        if (!isset($_SESSION['usuario_id'])) {

            header(
                'Location: ' .
                BASE_URL .
                '/login'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Solo docentes
        |--------------------------------------------------------------------------
        */

        if (
            !isset($_SESSION['rol']) ||
            $_SESSION['rol'] !== 'docente'
        ) {

            http_response_code(403);

            die('Acceso denegado.');
        }


        /*
        |--------------------------------------------------------------------------
        | Conexión
        |--------------------------------------------------------------------------
        */

        require BASE_PATH .
            '/conexion.php';


        /*
        |--------------------------------------------------------------------------
        | Obtener formularios
        |--------------------------------------------------------------------------
        */

        $sql = "
            SELECT
                id,
                titulo,
                estado,
                criterio_nota
            FROM examenes
            ORDER BY id DESC
        ";


        $resultado =
            $conn->query($sql);


        if (!$resultado) {

            http_response_code(500);

            die(
                'Error al obtener los formularios.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Convertir resultado en arreglo
        |--------------------------------------------------------------------------
        */

        $formularios = [];


        while (
            $fila = $resultado->fetch_assoc()
        ) {

            $formularios[] = $fila;
        }


        /*
        |--------------------------------------------------------------------------
        | Mostrar vista
        |--------------------------------------------------------------------------
        */

        require BASE_PATH .
            '/views/reportes/index.php';
    }
    public function exportar(): void
{
    /*
    |--------------------------------------------------------------------------
    | Seguridad
    |--------------------------------------------------------------------------
    */

    if (!isset($_SESSION['usuario_id'])) {

        header(
            'Location: ' .
            BASE_URL .
            '/login'
        );

        exit;
    }

    if (
        !isset($_SESSION['rol']) ||
        $_SESSION['rol'] !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Formularios seleccionados
    |--------------------------------------------------------------------------
    */

    $formulariosIds =
        $_POST['formularios'] ?? [];

    if (!is_array($formulariosIds)) {
        $formulariosIds = [];
    }


    $formulariosIds =
        array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $formulariosIds
                    ),
                    static function ($id) {
                        return $id > 0;
                    }
                )
            )
        );


    if (empty($formulariosIds)) {

        die(
            'Debe seleccionar al menos un cuestionario.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Conexión
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/conexion.php';


    /*
    |--------------------------------------------------------------------------
    | Recuperar los formularios seleccionados
    |--------------------------------------------------------------------------
    */

    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($formulariosIds),
                '?'
            )
        );


    $tipos =
        str_repeat(
            'i',
            count($formulariosIds)
        );


    $stmtFormularios =
        $conn->prepare("
            SELECT
                id,
                titulo,
                criterio_nota
            FROM examenes
            WHERE id IN ($placeholders)
        ");


    if (!$stmtFormularios) {

        die(
            'No se pudo preparar la consulta de formularios.'
        );
    }


    $stmtFormularios->bind_param(
        $tipos,
        ...$formulariosIds
    );


    $stmtFormularios->execute();


    $resultadoFormularios =
        $stmtFormularios->get_result();


    $formulariosBD = [];


    while (
        $fila =
        $resultadoFormularios->fetch_assoc()
    ) {

        $formulariosBD[
            (int)$fila['id']
        ] = $fila;
    }


    $stmtFormularios->close();


    /*
    |--------------------------------------------------------------------------
    | Mantener el orden seleccionado
    |--------------------------------------------------------------------------
    */

    $formularios = [];


    foreach ($formulariosIds as $id) {

        if (isset($formulariosBD[$id])) {

            $formularios[] =
                $formulariosBD[$id];
        }
    }


    if (empty($formularios)) {

        die(
            'No se encontraron los cuestionarios seleccionados.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Construir reporte
    |--------------------------------------------------------------------------
    |
    | La clave será el correo del estudiante.
    |
    */

    $estudiantes = [];


    foreach ($formularios as $formulario) {

        $examenId =
            (int)$formulario['id'];

        $criterioNota =
            $formulario['criterio_nota']
            ?? 'mejor_nota';


        /*
        |--------------------------------------------------------------------------
        | Último intento
        |--------------------------------------------------------------------------
        */

        if (
            $criterioNota ===
            'ultimo_intento'
        ) {

            $stmtResultados =
                $conn->prepare("
                    SELECT
                        u.correo,
                        i.nota
                    FROM intentos i

                    INNER JOIN usuarios u
                        ON u.id = i.usuario_id

                    WHERE i.finalizado = 1
                      AND i.examen_id = ?

                      AND i.numero_intento = (
                            SELECT MAX(
                                i2.numero_intento
                            )
                            FROM intentos i2

                            WHERE
                                i2.usuario_id =
                                i.usuario_id

                                AND i2.examen_id =
                                i.examen_id

                                AND i2.finalizado = 1
                      )

                    ORDER BY u.correo ASC
                ");

        } else {

            /*
            |--------------------------------------------------------------------------
            | Mejor nota
            |--------------------------------------------------------------------------
            */

            $stmtResultados =
                $conn->prepare("
                    SELECT
                        u.correo,
                        i.nota
                    FROM intentos i

                    INNER JOIN usuarios u
                        ON u.id = i.usuario_id

                    WHERE i.finalizado = 1
                      AND i.examen_id = ?

                      AND i.id = (
                            SELECT i2.id
                            FROM intentos i2

                            WHERE
                                i2.usuario_id =
                                i.usuario_id

                                AND i2.examen_id =
                                i.examen_id

                                AND i2.finalizado = 1

                            ORDER BY
                                i2.nota DESC,
                                i2.numero_intento DESC,
                                i2.id DESC

                            LIMIT 1
                      )

                    ORDER BY u.correo ASC
                ");
        }


        if (!$stmtResultados) {

            die(
                'No se pudo preparar la consulta de resultados.'
            );
        }


        $stmtResultados->bind_param(
            'i',
            $examenId
        );


        $stmtResultados->execute();


        $resultado =
            $stmtResultados->get_result();


        while (
            $fila =
            $resultado->fetch_assoc()
        ) {

            $correo =
                trim(
                    (string)$fila['correo']
                );


            if ($correo === '') {
                continue;
            }


            if (
                !isset(
                    $estudiantes[$correo]
                )
            ) {

                $estudiantes[$correo] = [];
            }


            $estudiantes[$correo][
                $examenId
            ] = $fila['nota'];
        }


        $stmtResultados->close();
    }


    /*
    |--------------------------------------------------------------------------
    | Ordenar estudiantes por correo
    |--------------------------------------------------------------------------
    */

    ksort(
        $estudiantes,
        SORT_NATURAL |
        SORT_FLAG_CASE
    );


    /*
    |--------------------------------------------------------------------------
    | Generar CSV
    |--------------------------------------------------------------------------
    */

    $nombreArchivo =
        'reporte_formularios_' .
        date('Y-m-d_H-i-s') .
        '.csv';


    header(
        'Content-Type: text/csv; charset=UTF-8'
    );

    header(
        'Content-Disposition: attachment; filename="' .
        $nombreArchivo .
        '"'
    );

    header(
        'Pragma: no-cache'
    );

    header(
        'Expires: 0'
    );


    /*
    |--------------------------------------------------------------------------
    | BOM UTF-8 para Excel
    |--------------------------------------------------------------------------
    */

    echo "\xEF\xBB\xBF";


    $salida =
        fopen(
            'php://output',
            'w'
        );


    if ($salida === false) {

        die(
            'No se pudo generar el archivo CSV.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cabecera
    |--------------------------------------------------------------------------
    */

   $cabecera = [
    'correo'
];


/*
|--------------------------------------------------------------------------
| Nombre de columnas
|--------------------------------------------------------------------------
|
| Si se exporta un solo cuestionario:
|
| correo | nota
|
| Esto permite importar directamente el archivo
| al sistema académico.
|
| Si se exportan varios:
|
| correo | Cuestionario 1 | Cuestionario 2 | ...
|
*/

if (count($formularios) === 1) {

    $cabecera[] = 'nota';

} else {

    foreach (
        $formularios as $formulario
    ) {

        $cabecera[] =
            $formulario['titulo'];
    }
}
/*
|--------------------------------------------------------------------------
| Escribir cabecera
|--------------------------------------------------------------------------
*/

fputcsv(
    $salida,
    $cabecera
);

    /*
    |--------------------------------------------------------------------------
    | Filas
    |--------------------------------------------------------------------------
    */

    foreach (
        $estudiantes as
        $correo => $notas
    ) {

        $fila = [
            $correo
        ];


        foreach (
            $formularios as $formulario
        ) {

            $examenId =
                (int)$formulario['id'];


            $fila[] =
                array_key_exists(
                    $examenId,
                    $notas
                )
                    ? $notas[$examenId]
                    : '';
        }


        fputcsv(
            $salida,
            $fila
            
        );
    }


    fclose($salida);

    exit;
}
}