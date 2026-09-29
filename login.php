<?php
session_start();
require_once "conexion.php";

$env = parse_ini_file(".env");

$client_id = $env["GOOGLE_CLIENT_ID"];
$client_secret = $env["GOOGLE_CLIENT_SECRET"];
$redirect_uri = $env["GOOGLE_REDIRECT_URI"];

// 1. REDIRECCIÓN A GOOGLE
if (!isset($_GET['code'])) {

    $url = "https://accounts.google.com/o/oauth2/v2/auth?"
        . "client_id=" . urlencode($client_id)
        . "&redirect_uri=" . urlencode($redirect_uri)
        . "&response_type=code"
        . "&scope=email%20profile"
        . "&prompt=select_account";

    header("Location: $url");
    exit;
}

// 2. PEDIR TOKEN
$token_url = "https://oauth2.googleapis.com/token";

$data = [
    "code" => $_GET['code'],
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
];

$options = [
    "http" => [
        "header" => "Content-Type: application/x-www-form-urlencoded",
        "method" => "POST",
        "content" => http_build_query($data)
    ]
];

$response = file_get_contents($token_url, false, stream_context_create($options));
$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("Error al iniciar sesión con Google.");
}

$access_token = $token['access_token'];

// 3. OBTENER DATOS DEL USUARIO
$user_info = file_get_contents(
    "https://www.googleapis.com/oauth2/v2/userinfo?access_token=" . $access_token
);

$user = json_decode($user_info, true);

if (!isset($user['email'])) {
    die("No se pudo obtener el correo del usuario.");
}

$correo = $user['email'];
$nombre = $user['name'] ?? "Sin nombre";

// 4. VALIDAR DOMINIO
if (!str_ends_with($correo, "@cesanrafael.org")) {
    die("Solo pueden ingresar cuentas institucionales @cesanrafael.org");
}

// 5. BUSCAR USUARIO
$stmt = $conn->prepare("SELECT id, rol FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {

    $rol = "alumno";

    $stmt = $conn->prepare("INSERT INTO usuarios(nombre, correo, rol) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $correo, $rol);
    $stmt->execute();

    $user_id = $stmt->insert_id;

} else {

    $row = $result->fetch_assoc();

    $user_id = $row['id'];
    $rol = $row['rol'];
}

// 6. CREAR SESIÓN
$_SESSION['usuario_id'] = $user_id;
$_SESSION['nombre'] = $nombre;
$_SESSION['correo'] = $correo;
$_SESSION['rol'] = $rol;

// 7. REDIRECCIÓN SEGÚN ROL

if ($rol === 'docente') {

    header(
        'Location: /formexamenes/formularios'
    );

    exit;
}

header(
    'Location: /formexamenes/formularios/disponibles'
);

exit;
?>