<?php
session_start();
require_once "conexion.php";

if ($_SESSION['rol'] != 'docente') {
    die("Acceso denegado.");
}

$intento_id = (int)$_GET['id'];

$respuestas = $conn->query("
SELECT
    p.pregunta,
    r.respuesta,
    r.correcta,
    r.puntaje_obtenido
FROM respuestas r
INNER JOIN preguntas p
ON r.pregunta_id = p.id
WHERE r.intento_id = $intento_id
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Detalle</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="container">

<div class="card">

<h1>Detalle del Intento</h1>

<?php while($r = $respuestas->fetch_assoc()): ?>

<div class="pregunta">

<h3><?= htmlspecialchars($r['pregunta']) ?></h3>

<p>
Respuesta:
<strong>
<?= htmlspecialchars($r['respuesta']) ?>
</strong>
</p>

<p>

<?php if($r['correcta']): ?>

<span style="color:green;">
Correcta
</span>

<?php else: ?>

<span style="color:red;">
Incorrecta
</span>

<?php endif; ?>

</p>

</div>

<hr>

<?php endwhile; ?>

<a href="resultados.php" class="btn">
Volver
</a>

</div>

</div>

</body>
</html>