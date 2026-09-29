<?php

declare(strict_types=1);

class FormularioController
{
    /*
    |--------------------------------------------------------------------------
    | Mostrar formularios
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Conexión actual del sistema
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
                descripcion,
                estado,
                activo,
                cantidad_preguntas,
                cantidad_intentos,
                tipo_tiempo,
                tiempo_minutos,
                fecha_hora_limite,
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
        | Cargar vista
        |--------------------------------------------------------------------------
        */

        require BASE_PATH .
            '/views/formularios/index.php';
    }
    /*
|--------------------------------------------------------------------------
| Nuevo formulario
|--------------------------------------------------------------------------
*/

public function nuevo(): void
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
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Cargar vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/formularios/nuevo.php';
}

/*
|--------------------------------------------------------------------------
| Guardar formulario
|--------------------------------------------------------------------------
*/

public function guardar(): void
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
        ($_SESSION['rol'] ?? '') !== 'docente'
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
    | Datos generales
    |--------------------------------------------------------------------------
    */

    $titulo =
        trim($_POST['titulo'] ?? '');

    $descripcion =
        trim($_POST['descripcion'] ?? '');

    $cantidad_preguntas =
        (int) (
            $_POST['cantidad_preguntas']
            ?? 1
        );

    $cantidad_intentos =
        (int) (
            $_POST['cantidad_intentos']
            ?? 1
        );

    $estado =
        $_POST['estado']
        ?? 'borrador';

    $tipo_tiempo =
        $_POST['tipo_tiempo']
        ?? 'individual';

    $criterio_nota =
        $_POST['criterio_nota']
        ?? 'mejor_nota';


    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    if ($titulo === '') {
        die(
            'Debe ingresar un título.'
        );
    }


    if ($cantidad_preguntas < 1) {
        $cantidad_preguntas = 1;
    }


    if ($cantidad_intentos < 1) {
        $cantidad_intentos = 1;
    }


    if (
        $tipo_tiempo !== 'individual' &&
        $tipo_tiempo !== 'limite'
    ) {
        $tipo_tiempo = 'individual';
    }


    if (
        $criterio_nota !== 'mejor_nota' &&
        $criterio_nota !== 'ultimo_intento'
    ) {
        $criterio_nota =
            'mejor_nota';
    }


    /*
    |--------------------------------------------------------------------------
    | Validar estado
    |--------------------------------------------------------------------------
    */

    $estadosPermitidos = [
        'borrador',
        'publicado',
        'cerrado'
    ];

    if (
        !in_array(
            $estado,
            $estadosPermitidos,
            true
        )
    ) {
        $estado = 'borrador';
    }


    /*
    |--------------------------------------------------------------------------
    | Control del tiempo
    |--------------------------------------------------------------------------
    */

    $tiempo_minutos = 0;

    $fecha_hora_limite = null;


    /*
    |--------------------------------------------------------------------------
    | Tiempo individual
    |--------------------------------------------------------------------------
    */

    if (
        $tipo_tiempo === 'individual'
    ) {

        $tiempo_minutos =
            (int) (
                $_POST['tiempo_minutos']
                ?? 0
            );


        if ($tiempo_minutos < 1) {

            die(
                'El tiempo individual debe ser de al menos 1 minuto.'
            );
        }


        $fecha_hora_limite = null;
    }


    /*
    |--------------------------------------------------------------------------
    | Hora límite
    |--------------------------------------------------------------------------
    */

    if (
        $tipo_tiempo === 'limite'
    ) {

        $fechaRecibida =
            trim(
                $_POST[
                    'fecha_hora_limite'
                ] ?? ''
            );


        if ($fechaRecibida === '') {

            die(
                'Debe seleccionar una fecha y hora límite.'
            );
        }


        $fechaObjeto =
            DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $fechaRecibida
            );


        if (!$fechaObjeto) {

            die(
                'La fecha y hora límite no son válidas.'
            );
        }


        $fecha_hora_limite =
            $fechaObjeto->format(
                'Y-m-d H:i:s'
            );


        $tiempo_minutos = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Estado activo
    |--------------------------------------------------------------------------
    */

    $activo =
        $estado === 'publicado'
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | Guardar formulario
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        INSERT INTO examenes(
            titulo,
            descripcion,
            tiempo_minutos,
            cantidad_preguntas,
            cantidad_intentos,
            activo,
            estado,
            tipo_tiempo,
            fecha_hora_limite,
            criterio_nota
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");


    if (!$stmt) {

        die(
            'Error al preparar la consulta: ' .
            $conn->error
        );
    }


    $stmt->bind_param(
        'ssiiiissss',
        $titulo,
        $descripcion,
        $tiempo_minutos,
        $cantidad_preguntas,
        $cantidad_intentos,
        $activo,
        $estado,
        $tipo_tiempo,
        $fecha_hora_limite,
        $criterio_nota
    );


    if (!$stmt->execute()) {

        die(
            'Error al guardar el formulario: ' .
            $stmt->error
        );
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Regresar a formularios
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/formularios'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| Editar formulario
|--------------------------------------------------------------------------
*/

public function editar(string $id): void
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
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar ID
    |--------------------------------------------------------------------------
    */

    $idFormulario = (int)$id;

    if ($idFormulario <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
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
    | Obtener formulario
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        SELECT *
        FROM examenes
        WHERE id = ?
        LIMIT 1
    ");

    if (!$stmt) {

        http_response_code(500);

        die(
            'Error al preparar la consulta: ' .
            $conn->error
        );
    }


    $stmt->bind_param(
        'i',
        $idFormulario
    );

    $stmt->execute();

    $resultado =
        $stmt->get_result();

    $examen =
        $resultado->fetch_assoc();

    $stmt->close();


    if (!$examen) {

        http_response_code(404);

        die('Formulario no encontrado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Tipo de tiempo
    |--------------------------------------------------------------------------
    */

    $tipo_tiempo =
        $examen['tipo_tiempo']
        ?? 'individual';

    if (
        $tipo_tiempo !== 'individual' &&
        $tipo_tiempo !== 'limite'
    ) {
        $tipo_tiempo = 'individual';
    }


    /*
    |--------------------------------------------------------------------------
    | Preparar fecha para datetime-local
    |--------------------------------------------------------------------------
    */

    $fecha_hora_limite = '';

    if (
        !empty(
            $examen['fecha_hora_limite']
        )
    ) {

        $timestamp =
            strtotime(
                $examen[
                    'fecha_hora_limite'
                ]
            );

        if ($timestamp !== false) {

            $fecha_hora_limite =
                date(
                    'Y-m-d\TH:i',
                    $timestamp
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Mostrar vista
    |--------------------------------------------------------------------------
    */

    require BASE_PATH .
        '/views/formularios/editar.php';
}

/*
|--------------------------------------------------------------------------
| Actualizar formulario
|--------------------------------------------------------------------------
*/

public function actualizar(string $id): void
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
        ($_SESSION['rol'] ?? '') !== 'docente'
    ) {

        http_response_code(403);

        die('Acceso denegado.');
    }


    /*
    |--------------------------------------------------------------------------
    | Validar ID
    |--------------------------------------------------------------------------
    */

    $idFormulario = (int)$id;

    if ($idFormulario <= 0) {

        http_response_code(400);

        die('Formulario inválido.');
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
    | Datos
    |--------------------------------------------------------------------------
    */

    $titulo =
        trim($_POST['titulo'] ?? '');

    $descripcion =
        trim($_POST['descripcion'] ?? '');

    $cantidad_preguntas =
        (int)($_POST['cantidad_preguntas'] ?? 1);

    $cantidad_intentos =
        (int)($_POST['cantidad_intentos'] ?? 1);

    $estado =
        $_POST['estado'] ?? 'borrador';

    $tipo_tiempo =
        $_POST['tipo_tiempo'] ?? 'individual';

    $criterio_nota =
        $_POST['criterio_nota'] ?? 'mejor_nota';

    $mostrar_respuestas =
        isset($_POST['mostrar_respuestas'])
            ? 1
            : 0;

    $mostrar_preguntas_antes =
        isset($_POST['mostrar_preguntas_antes'])
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | Validaciones
    |--------------------------------------------------------------------------
    */

    if ($titulo === '') {

        die(
            'Debe escribir un título.'
        );
    }

    if ($cantidad_preguntas < 1) {
        $cantidad_preguntas = 1;
    }

    if ($cantidad_intentos < 1) {
        $cantidad_intentos = 1;
    }


    /*
    |--------------------------------------------------------------------------
    | Estado
    |--------------------------------------------------------------------------
    */

    $estadosPermitidos = [
        'borrador',
        'publicado',
        'cerrado'
    ];

    if (
        !in_array(
            $estado,
            $estadosPermitidos,
            true
        )
    ) {
        $estado = 'borrador';
    }


    /*
    |--------------------------------------------------------------------------
    | Tipo de tiempo
    |--------------------------------------------------------------------------
    */

    $tiposTiempoPermitidos = [
        'individual',
        'limite'
    ];

    if (
        !in_array(
            $tipo_tiempo,
            $tiposTiempoPermitidos,
            true
        )
    ) {
        $tipo_tiempo = 'individual';
    }


    /*
    |--------------------------------------------------------------------------
    | Criterio de nota
    |--------------------------------------------------------------------------
    */

    $criteriosNotaPermitidos = [
        'mejor_nota',
        'ultimo_intento'
    ];

    if (
        !in_array(
            $criterio_nota,
            $criteriosNotaPermitidos,
            true
        )
    ) {
        $criterio_nota = 'mejor_nota';
    }


    /*
    |--------------------------------------------------------------------------
    | Control del tiempo
    |--------------------------------------------------------------------------
    */

    $tiempo_minutos = 0;

    $fecha_hora_limite = null;


    if ($tipo_tiempo === 'individual') {

        $tiempo_minutos =
            (int)($_POST['tiempo_minutos'] ?? 0);

        if ($tiempo_minutos < 1) {

            die(
                'El tiempo individual debe ser de al menos 1 minuto.'
            );
        }
    }


    if ($tipo_tiempo === 'limite') {

        $fechaRecibida =
            trim(
                $_POST['fecha_hora_limite']
                ?? ''
            );

        if ($fechaRecibida === '') {

            die(
                'Debe seleccionar una fecha y hora límite.'
            );
        }

        $fechaObjeto =
            DateTime::createFromFormat(
                'Y-m-d\TH:i',
                $fechaRecibida
            );

        if (!$fechaObjeto) {

            die(
                'La fecha y hora límite no son válidas.'
            );
        }

        $fecha_hora_limite =
            $fechaObjeto->format(
                'Y-m-d H:i:s'
            );

        $tiempo_minutos = 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Activo
    |--------------------------------------------------------------------------
    */

    $activo =
        $estado === 'publicado'
            ? 1
            : 0;


    /*
    |--------------------------------------------------------------------------
    | Actualizar
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE examenes
        SET
            titulo = ?,
            descripcion = ?,
            tiempo_minutos = ?,
            cantidad_preguntas = ?,
            cantidad_intentos = ?,
            activo = ?,
            estado = ?,
            mostrar_respuestas = ?,
            mostrar_preguntas_antes = ?,
            tipo_tiempo = ?,
            fecha_hora_limite = ?,
            criterio_nota = ?
        WHERE id = ?
    ");


    if (!$stmt) {

        die(
            'Error al preparar la consulta: ' .
            $conn->error
        );
    }


    $stmt->bind_param(
        'ssiiiisiisssi',
        $titulo,
        $descripcion,
        $tiempo_minutos,
        $cantidad_preguntas,
        $cantidad_intentos,
        $activo,
        $estado,
        $mostrar_respuestas,
        $mostrar_preguntas_antes,
        $tipo_tiempo,
        $fecha_hora_limite,
        $criterio_nota,
        $idFormulario
    );


    if (!$stmt->execute()) {

        die(
            'No se pudo actualizar el formulario: ' .
            $stmt->error
        );
    }


    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | Regresar
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/formularios'
    );

    exit;
}
}
