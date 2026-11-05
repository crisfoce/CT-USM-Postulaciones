<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit();
}

$usuario= $_SESSION["usuario"] ?? "Usuario";
$rol= $_SESSION["rol"];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inicio CT-USM</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>

<div class="barra">
    <h1>CT-USM Postulaciones</h1>
    <a href="login.php?logout=1">Cerrar sesión</a>
</div>

<div class="contenido">
    <h2>Bienvenido/a</h2>
    <div class="datos">
        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($usuario); ?></p>
        <p><strong>Rol:</strong> <?php echo htmlspecialchars($rol); ?></p>
    </div>

    <h3>Menú</h3>

    <div class="botones">
        <a href="listado_general.php">Listado General</a>
        <a href="busqueda_avanzada.php">Búsqueda Avanzada</a>

        <?php if ($rol== "postulante"): ?>
            <a href="crear_postulacion.php">Crear Postulación</a>
            <a href="mis_postulaciones.php">Mis Postulaciones</a>
        <?php endif; ?>

        <?php if ($rol== "evaluador"): ?>
            <a href="evaluar_postulaciones.php">Evaluar Postulaciones</a>
        <?php endif; ?>

        <?php if ($rol== "admin"): ?>
            <a href="gestionar_evaluadores.php">Gestionar Evaluadores</a>
        <?php endif; ?>

    </div>
</div>

</body>
</html>