<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

$examen = $conn->query("
SELECT *
FROM examenes
WHERE estado='publicado'
LIMIT 1
")->fetch_assoc();

if (!$examen) {
    die("No existe examen publicado.");
}

$examen_id = $examen['id'];

$ip = $_SERVER['REMOTE_ADDR'];
$navegador = $_SERVER['HTTP_USER_AGENT'];

$stmt = $conn->prepare("
INSERT INTO intentos(
usuario_id,
examen_id,
fecha_inicio,
ip_publica,
navegador
)
VALUES(?, ?, NOW(), ?, ?)
");

$stmt->bind_param("iiss", $usuario_id, $examen_id, $ip, $navegador);
$stmt->execute();

$intento_id = $stmt->insert_id;

$preguntas = $conn->query("
SELECT *
FROM preguntas
WHERE examen_id = $examen_id
ORDER BY RAND()
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolver Examen</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

    <div class="card">

        <h1><?= htmlspecialchars($examen['titulo']) ?></h1>

        <p class="correo">
            Tiempo: <?= $examen['tiempo_minutos'] ?> minutos
        </p>

        <div id="temporizador" class="temporizador"></div>

        <form id="formExamen" method="POST" action="finalizar_examen.php" onsubmit="enviado=true;">

            <input type="hidden" name="intento_id" value="<?= $intento_id ?>">
            <input type="hidden" name="cambios_pestana" id="cambios_pestana" value="0">

            <?php $numero = 1; ?>

            <?php while ($pregunta = $preguntas->fetch_assoc()): ?>

                <div class="pregunta">

                    <h3>
                        <?= $numero ?>.
                        <?= nl2br(htmlspecialchars($pregunta['pregunta'])) ?>
                    </h3>

                    <?php
                    $idPregunta = $pregunta['id'];
                    ?>

                    <?php if ($pregunta['tipo'] == 'codigo'): ?>

                        <textarea
                            name="pregunta_<?= $idPregunta ?>"
                            rows="12"
                            class="textarea-codigo"
                            placeholder="Escriba aquí su código..."
                        ></textarea>

                    <?php else: ?>

                        <?php
                        $opciones = $conn->query("
                            SELECT *
                            FROM opciones
                            WHERE pregunta_id = $idPregunta
                        ");
                        ?>

                        <?php while ($opcion = $opciones->fetch_assoc()): ?>

                            <label class="opcion">
                                <input
                                    type="radio"
                                    name="pregunta_<?= $idPregunta ?>"
                                    value="<?= $opcion['id'] ?>"
                                >

                                <?= htmlspecialchars($opcion['opcion_texto']) ?>
                            </label>

                        <?php endwhile; ?>

                    <?php endif; ?>

                </div>

                <hr>

                <?php $numero++; ?>

            <?php endwhile; ?>

            <button type="submit" class="btn">
                Finalizar Examen
            </button>

        </form>

    </div>

</div>

<script>
let cambios = 0;
let enviado = false;

let tiempo = <?= (int)$examen['tiempo_minutos'] ?> * 60;

function enviarExamen(mensaje) {
    if (enviado) return;

    enviado = true;
    alert(mensaje);
    document.getElementById("formExamen").submit();
}

function actualizarTemporizador() {
    let minutos = Math.floor(tiempo / 60);
    let segundos = tiempo % 60;

    document.getElementById("temporizador").innerHTML =
        "Tiempo restante: " +
        minutos + ":" + (segundos < 10 ? "0" : "") + segundos;

    if (tiempo <= 0) {
        enviarExamen("El tiempo terminó. El examen será enviado automáticamente.");
    }

    tiempo--;
}

actualizarTemporizador();
setInterval(actualizarTemporizador, 1000);

function registrarCambioPantalla() {
    if (enviado) return;

    cambios++;
    document.getElementById("cambios_pestana").value = cambios;

    if (cambios === 1) {
        alert("Advertencia: No debe cambiar de pantalla.");
    }

    if (cambios >= 2) {
        enviarExamen("Examen finalizado automáticamente por cambiar de pantalla.");
    }
}

document.addEventListener("visibilitychange", function () {
    if (document.hidden) {
        registrarCambioPantalla();
    }
});

window.addEventListener("blur", function () {
    registrarCambioPantalla();
});

history.pushState(null, null, location.href);

window.onpopstate = function () {
    history.go(1);
    alert("No puede volver atrás durante el examen.");
};

document.addEventListener("keydown", function(e){

    if (
        e.key === "F5" ||
        e.key === "F12" ||
        (e.ctrlKey && e.key.toLowerCase() === "r") ||
        (e.ctrlKey && e.key.toLowerCase() === "c") ||
        (e.ctrlKey && e.key.toLowerCase() === "v") ||
        (e.ctrlKey && e.key.toLowerCase() === "x") ||
        (e.ctrlKey && e.key.toLowerCase() === "a") ||
        (e.ctrlKey && e.key.toLowerCase() === "u") ||
        (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === "i")
    ){
        e.preventDefault();
    }

});

document.addEventListener("contextmenu", function(e){
    e.preventDefault();
});

document.addEventListener("copy", function(e){
    e.preventDefault();
});

document.addEventListener("cut", function(e){
    e.preventDefault();
});

document.addEventListener("paste", function(e){
    e.preventDefault();
});

document.addEventListener("selectstart", function(e){
    e.preventDefault();
});

window.addEventListener("beforeunload", function(e){
    if (!enviado) {
        e.preventDefault();
        e.returnValue = "";
    }
});
</script>

</body>
</html>