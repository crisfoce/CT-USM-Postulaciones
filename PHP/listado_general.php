<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

function limpiar($dato){
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

function mostrar_estado($estado){
    if($estado== "En Revisión"){
        return "Enviada";
    }

    return $estado;
}

$rol= $_SESSION["rol"];
$id_usuario= (int)$_SESSION["id_usuario"];

$where= [];
$tipos= "";
$parametros= [];
$buscar= trim($_GET["buscar"] ?? "");

$where[]= "estado_postulacion <> 'Borrador'";

if($rol=="postulante"){
    $where[]= "id_usuario_responsable= ?";
    $tipos .= "i";
    $parametros[]= $id_usuario;
}

if($rol=="evaluador"){
    $where[]= "id_evaluador= ?";
    $tipos .= "i";
    $parametros[]= $id_usuario;
}

if($buscar!=""){
    $where[]= "(nombre_iniciativa LIKE ? OR nombre_empresa LIKE ? OR codigo_interno LIKE ?)"; 
    $tipos .= "sss";

    $buscar_like= "%" . $buscar . "%";

    $parametros[]= $buscar_like;
    $parametros[]= $buscar_like;
    $parametros[]= $buscar_like;
}

$sql= "SELECT numero_postulacion, codigo_interno, nombre_iniciativa, nombre_empresa, nombre_sede, region_ejecucion, region_impacto, presupuesto_total, estado_postulacion
       FROM vista_postulaciones
       WHERE " . implode(" AND ", $where) . "
       ORDER BY numero_postulacion ASC";

$stmt= $conn->prepare($sql);

if($tipos!=""){
    $stmt->bind_param($tipos, ...$parametros);
}

$stmt->execute();
$resultado= $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado General</title>
    <link rel="stylesheet" href="css/listado_general.css">
</head>

<body>

<div class="barra">
    <h1>CT-USM Postulaciones</h1>
    <a href="login.php?logout=1">Cerrar sesión</a>
</div>

<div class="contenido">

    <div class="menu">
        <a href="dashboard.php">Inicio</a>
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

    <h2>Listado General de Postulaciones Gestionadas</h2>
    <form method="GET" action="listado_general.php" class="form-busqueda">
        <input
            type="text"
            name="buscar"
            placeholder="Buscar por iniciativa, empresa o código"
            value="<?php echo limpiar($buscar); ?>"
        >

        <button type="submit">Buscar</button>

        <?php if ($buscar != ""): ?>
            <a href="listado_general.php" class="limpiar-busqueda">Limpiar</a>
        <?php endif; ?>
    </form>

    <table>
        <thead>
            <tr>
                <th>N° Postulación</th>
                <th class="col-codigo">Código</th>
                <th>Iniciativa</th>
                <th>Empresa</th>
                <th>Sede</th>
                <th>Región Ejecución</th>
                <th>Región Impacto</th>
                <th>Presupuesto</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <?php while ($fila = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo limpiar($fila["numero_postulacion"]); ?></td>
                        <td class="col-codigo"><?php echo limpiar($fila["codigo_interno"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_iniciativa"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_empresa"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_sede"]); ?></td>
                        <td><?php echo limpiar($fila["region_ejecucion"]); ?></td>
                        <td><?php echo limpiar($fila["region_impacto"]); ?></td>
                        <td class="presupuesto">
                            $<?php echo number_format((int)$fila["presupuesto_total"], 0, ",", "."); ?>
                        </td>
                        <td class="estado">
                            <?php echo limpiar(mostrar_estado($fila["estado_postulacion"])); ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="mensaje">
                        No hay postulaciones gestionadas para mostrar.
                    </td>
                </tr>

            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>