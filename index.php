<?php
session_start();

if(isset($_SESSION['usuario_id'])){
    header("Location: dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sistema de Exámenes</title>
</head>
<body style="text-align:center; font-family:Arial">

    <h1>Plataforma de Exámenes</h1>

    <p>Acceso exclusivo para estudiantes @cesanrafael.org</p>

    <a href="login.php">
        <button style="padding:10px 20px; font-size:16px;">
            Iniciar con Google
        </button>
    </a>

</body>
</html>