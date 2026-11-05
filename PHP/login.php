<?php
session_start();

if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - CT USM</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>

<div class="contenedor">
    <div class="tarjeta-login">
        <h2>Iniciar sesión</h2>
        <form action="validar_login.php" method="POST">
            <div class="campo">
                <label>Correo</label>
                <input
                    type="email"
                    name="correo"
                    placeholder="Ej: usuario@usm.cl"
                    required
                >
            </div>

            <div class="campo">
                <label>Contraseña</label>
                <input
                    type="password"
                    name="password"
                    placeholder="Ingrese su contraseña"
                    required
                >
            </div>

            <button type="submit">Ingresar</button>

        </form>
    </div>
</div>

</body>
</html>