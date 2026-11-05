<?php
session_start();
require_once "conexion.php";

$correo= $_POST["correo"];
$password= $_POST["password"];

$sql= "SELECT id_usuario, nombre_usuario, correo, password_usuario, rol
       FROM usuario
       WHERE correo= ?
       AND password_usuario= ?
       AND activo= TRUE";

$stmt= $conn->prepare($sql);
$stmt->bind_param("ss", $correo, $password);
$stmt->execute();

$resultado= $stmt->get_result();

if($resultado->num_rows== 1){
    $usuario= $resultado->fetch_assoc();

    $_SESSION["id_usuario"]= $usuario["id_usuario"];
    $_SESSION["usuario"]= $usuario["nombre_usuario"];
    $_SESSION["correo"]= $usuario["correo"];
    $_SESSION["rol"]= $usuario["rol"];

    header("Location: dashboard.php");
    exit();
}
else{
    echo "Correo o contraseña incorrectos.";
    echo "<br><a href='login.php'>Volver</a>";
}
?>