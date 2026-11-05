<?php

$conn= new mysqli("localhost", "root", "", "ct_usm_postulaciones");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$conexion= $conn;
?>