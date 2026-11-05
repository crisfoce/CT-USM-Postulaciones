<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"]!= "postulante") {
    header("Location: dashboard.php");
    exit();
}

require_once "conexion.php";

if (!isset($_GET["id"])) {
    header("Location: mis_postulaciones.php");
    exit();
}

$id_postulacion= (int)$_GET["id"];
$id_usuario= $_SESSION["id_usuario"];

$sql = "UPDATE postulacion
        SET estado_postulacion = 'En Revisión'
        WHERE id_postulacion = ?
        AND id_usuario_responsable = ?
        AND estado_postulacion = 'Borrador'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_postulacion, $id_usuario);
$stmt->execute();

header("Location: mis_postulaciones.php");
exit();
?>