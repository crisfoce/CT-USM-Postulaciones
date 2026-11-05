<?php
session_start();

if(!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])){
    header("Location: login.php");
    exit();
}

require_once "conexion.php";

function limpiar($dato){
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

function seleccionado($valor_actual, $valor_opcion) {
    return $valor_actual==$valor_opcion ? "selected" : "";
}

function mostrar_estado($estado) {
    if ($estado=="En Revisión") {
        return "Enviada";
    }

    return $estado;
}

$rol= $_SESSION["rol"];
$id_usuario= (int)$_SESSION["id_usuario"];

$region_ejecucion= $_GET["region_ejecucion"] ?? "";
$region_impacto= $_GET["region_impacto"] ?? "";
$nombre_sede= $_GET["nombre_sede"] ?? "";
$tipo_iniciativa= $_GET["tipo_iniciativa"] ?? "";
$tamano_empresa= $_GET["tamano_empresa"] ?? "";
$conv_marco_usm= $_GET["conv_marco_usm"] ?? "";
$estado_postulacion= $_GET["estado_postulacion"] ?? "";
$id_evaluador= $_GET["id_evaluador"] ?? "";

$hay_busqueda= count($_GET) > 0;
$resultado= null;

$where= [];
$tipos= "";
$parametros= [];

if($rol=="postulante"){
    $where[]= "p.id_usuario_responsable= ?";
    $tipos .= "i";
    $parametros[]= $id_usuario;
}

if($rol=="evaluador"){
    $where[]= "p.id_evaluador= ?";
    $tipos .= "i";
    $parametros[]= $id_usuario;
}


$sql = "SELECT p.codigo_interno, p.nombre_iniciativa, e.nombre_empresa, COALESCE(GROUP_CONCAT(DISTINCT s.nombre_sede SEPARATOR ', '), 'Sin sede') AS nombre_sede, p.region_ejecucion, p.region_impacto, p.estado_postulacion
        FROM postulacion p
        INNER JOIN empresa e ON p.rut_empresa = e.rut_empresa
        LEFT JOIN sede s ON p.id_postulacion = s.id_postulacion";

if ($region_ejecucion!= ""){
    $where[]= "p.region_ejecucion = ?";
    $tipos .= "s";
    $parametros[]= $region_ejecucion;
}

if ($region_impacto!= "") {
    $where[]= "p.region_impacto = ?";
    $tipos .= "s";
    $parametros[]= $region_impacto;
}

if ($nombre_sede!= "") {
    $where[]= "s.nombre_sede = ?";
    $tipos .= "s";
    $parametros[]= $nombre_sede;
}

if ($tipo_iniciativa!= "") {
    $where[]= "p.tipo_iniciativa = ?";
    $tipos .= "s";
    $parametros[]= $tipo_iniciativa;
}

if ($tamano_empresa!= "") {
    $where[]= "e.tamano_empresa = ?";
    $tipos .= "s";
    $parametros[]= $tamano_empresa;
}

if ($conv_marco_usm!= "") {
    $where[]= "e.conv_marco_usm = ?";
    $tipos .= "i";
    $parametros[]= (int)$conv_marco_usm;
}

if ($estado_postulacion != "") {
    $where[]= "p.estado_postulacion = ?";
    $tipos .= "s";
    $parametros[]= $estado_postulacion;
}

if ($id_evaluador!= "") {
    $where[]= "p.id_evaluador = ?";
    $tipos .= "i";
    $parametros[]= (int)$id_evaluador;
}

if (count($where)>0) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    GROUP BY p.id_postulacion, p.codigo_interno, p.nombre_iniciativa, e.nombre_empresa, p.region_ejecucion, p.region_impacto, p.estado_postulacion
    ORDER BY p.codigo_interno ASC";

if ($hay_busqueda) {
    $stmt= $conn->prepare($sql);

    if ($tipos!= "") {
        $stmt->bind_param($tipos, ...$parametros);
    }

    $stmt->execute();
    $resultado= $stmt->get_result();
}

$evaluadores = $conn->query("
    SELECT id_usuario, nombre_usuario
    FROM usuario
    WHERE rol= 'evaluador'
    AND activo= TRUE
    ORDER BY nombre_usuario ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Búsqueda Avanzada</title>
    <link rel="stylesheet" href="css/busqueda_avanzada.css">
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

    <h2>Búsqueda Avanzada</h2>

    <form method="GET" action="busqueda_avanzada.php" class="formulario">

        <div class="campo">
            <label>Región de ejecución</label>
            <select name="region_ejecucion">
                <option value="">Todas</option>
                <option value="Region de Arica y Parinacota" <?php echo seleccionado($region_ejecucion, "Region de Arica y Parinacota"); ?>>Region de Arica y Parinacota</option>
                <option value="Region de Tarapaca" <?php echo seleccionado($region_ejecucion, "Region de Tarapaca"); ?>>Region de Tarapaca</option>
                <option value="Region de Antofagasta" <?php echo seleccionado($region_ejecucion, "Region de Antofagasta"); ?>>Region de Antofagasta</option>
                <option value="Region de Atacama" <?php echo seleccionado($region_ejecucion, "Region de Atacama"); ?>>Region de Atacama</option>
                <option value="Region de Coquimbo" <?php echo seleccionado($region_ejecucion, "Region de Coquimbo"); ?>>Region de Coquimbo</option>
                <option value="Region de Valparaiso" <?php echo seleccionado($region_ejecucion, "Region de Valparaiso"); ?>>Region de Valparaiso</option>
                <option value="Region Metropolitana de Santiago" <?php echo seleccionado($region_ejecucion, "Region Metropolitana de Santiago"); ?>>Region Metropolitana de Santiago</option>
                <option value="Region del Libertador General Bernardo O'Higgins" <?php echo seleccionado($region_ejecucion, "Region del Libertador General Bernardo O'Higgins"); ?>>Region del Libertador General Bernardo O'Higgins</option>
                <option value="Region del Maule" <?php echo seleccionado($region_ejecucion, "Region del Maule"); ?>>Region del Maule</option>
                <option value="Region de Nuble" <?php echo seleccionado($region_ejecucion, "Region de Nuble"); ?>>Region de Nuble</option>
                <option value="Region del Biobio" <?php echo seleccionado($region_ejecucion, "Region del Biobio"); ?>>Region del Biobio</option>
                <option value="Region de La Araucania" <?php echo seleccionado($region_ejecucion, "Region de La Araucania"); ?>>Region de La Araucania</option>
                <option value="Region de Los Rios" <?php echo seleccionado($region_ejecucion, "Region de Los Rios"); ?>>Region de Los Rios</option>
                <option value="Region de Los Lagos" <?php echo seleccionado($region_ejecucion, "Region de Los Lagos"); ?>>Region de Los Lagos</option>
                <option value="Region de Aysen" <?php echo seleccionado($region_ejecucion, "Region de Aysen"); ?>>Region de Aysen</option>
                <option value="Region de Magallanes" <?php echo seleccionado($region_ejecucion, "Region de Magallanes"); ?>>Region de Magallanes</option>
            </select>
        </div>

        <div class="campo">
            <label>Región de impacto</label>
            <select name="region_impacto">
                <option value="">Todas</option>
                <option value="Region de Arica y Parinacota" <?php echo seleccionado($region_impacto, "Region de Arica y Parinacota"); ?>>Region de Arica y Parinacota</option>
                <option value="Region de Tarapaca" <?php echo seleccionado($region_impacto, "Region de Tarapaca"); ?>>Region de Tarapaca</option>
                <option value="Region de Antofagasta" <?php echo seleccionado($region_impacto, "Region de Antofagasta"); ?>>Region de Antofagasta</option>
                <option value="Region de Atacama" <?php echo seleccionado($region_impacto, "Region de Atacama"); ?>>Region de Atacama</option>
                <option value="Region de Coquimbo" <?php echo seleccionado($region_impacto, "Region de Coquimbo"); ?>>Region de Coquimbo</option>
                <option value="Region de Valparaiso" <?php echo seleccionado($region_impacto, "Region de Valparaiso"); ?>>Region de Valparaiso</option>
                <option value="Region Metropolitana de Santiago" <?php echo seleccionado($region_impacto, "Region Metropolitana de Santiago"); ?>>Region Metropolitana de Santiago</option>
                <option value="Region del Libertador General Bernardo O'Higgins" <?php echo seleccionado($region_impacto, "Region del Libertador General Bernardo O'Higgins"); ?>>Region del Libertador General Bernardo O'Higgins</option>
                <option value="Region del Maule" <?php echo seleccionado($region_impacto, "Region del Maule"); ?>>Region del Maule</option>
                <option value="Region de Nuble" <?php echo seleccionado($region_impacto, "Region de Nuble"); ?>>Region de Nuble</option>
                <option value="Region del Biobio" <?php echo seleccionado($region_impacto, "Region del Biobio"); ?>>Region del Biobio</option>
                <option value="Region de La Araucania" <?php echo seleccionado($region_impacto, "Region de La Araucania"); ?>>Region de La Araucania</option>
                <option value="Region de Los Rios" <?php echo seleccionado($region_impacto, "Region de Los Rios"); ?>>Region de Los Rios</option>
                <option value="Region de Los Lagos" <?php echo seleccionado($region_impacto, "Region de Los Lagos"); ?>>Region de Los Lagos</option>
                <option value="Region de Aysen" <?php echo seleccionado($region_impacto, "Region de Aysen"); ?>>Region de Aysen</option>
                <option value="Region de Magallanes" <?php echo seleccionado($region_impacto, "Region de Magallanes"); ?>>Region de Magallanes</option>
            </select>
        </div>

        <div class="campo">
            <label>Sede / campus</label>
            <select name="nombre_sede">
                <option value="">Todas</option>
                <option value="Campus Casa Central Valparaiso" <?php echo seleccionado($nombre_sede, "Campus Casa Central Valparaiso"); ?>>Campus Casa Central Valparaiso</option>
                <option value="Campus San Joaquin" <?php echo seleccionado($nombre_sede, "Campus San Joaquin"); ?>>Campus San Joaquin</option>
                <option value="Campus Vitacura" <?php echo seleccionado($nombre_sede, "Campus Vitacura"); ?>>Campus Vitacura</option>
                <option value="Sede Vina del Mar" <?php echo seleccionado($nombre_sede, "Sede Vina del Mar"); ?>>Sede Vina del Mar</option>
                <option value="Sede Concepcion" <?php echo seleccionado($nombre_sede, "Sede Concepcion"); ?>>Sede Concepcion</option>
            </select>
        </div>

        <div class="campo">
            <label>Tipo de iniciativa</label>
            <select name="tipo_iniciativa">
                <option value="">Todas</option>
                <option value="Nueva" <?php echo seleccionado($tipo_iniciativa, "Nueva"); ?>>Nueva</option>
                <option value="Existente" <?php echo seleccionado($tipo_iniciativa, "Existente"); ?>>Existente</option>
            </select>
        </div>

        <div class="campo">
            <label>Tamaño de empresa</label>
            <select name="tamano_empresa">
                <option value="">Todos</option>
                <option value="Microempresa" <?php echo seleccionado($tamano_empresa, "Microempresa"); ?>>Microempresa</option>
                <option value="Mediana" <?php echo seleccionado($tamano_empresa, "Mediana"); ?>>Mediana</option>
                <option value="Grande" <?php echo seleccionado($tamano_empresa, "Grande"); ?>>Grande</option>
            </select>
        </div>

        <div class="campo">
            <label>Convenio marco USM</label>
            <select name="conv_marco_usm">
                <option value="">Todos</option>
                <option value="1" <?php echo seleccionado($conv_marco_usm, "1"); ?>>Sí</option>
                <option value="0" <?php echo seleccionado($conv_marco_usm, "0"); ?>>No</option>
            </select>
        </div>

        <div class="campo">
            <label>Estado de postulación</label>
            <select name="estado_postulacion">
                <option value="">Todos</option>
                <option value="Borrador" <?php echo seleccionado($estado_postulacion, "Borrador"); ?>>Borrador</option>
                <option value="En Revisión" <?php echo seleccionado($estado_postulacion, "En Revisión"); ?>>Enviada</option>
                <option value="Aprobada" <?php echo seleccionado($estado_postulacion, "Aprobada"); ?>>Aprobada</option>
                <option value="Rechazada" <?php echo seleccionado($estado_postulacion, "Rechazada"); ?>>Rechazada</option>
                <option value="Cerrada" <?php echo seleccionado($estado_postulacion, "Cerrada"); ?>>Cerrada</option>
            </select>
        </div>

        <div class="campo">
            <label>Evaluador asignado</label>
            <select name="id_evaluador">
                <option value="">Todos</option>

                <?php if ($evaluadores && $evaluadores->num_rows > 0): ?>
                    <?php while ($ev = $evaluadores->fetch_assoc()): ?>
                        <option value="<?php echo limpiar($ev["id_usuario"]); ?>" <?php echo seleccionado($id_evaluador, $ev["id_usuario"]); ?>>
                            <?php echo limpiar($ev["nombre_usuario"]); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="acciones">
            <button type="submit">Buscar</button>
            <a class="limpiar" href="busqueda_avanzada.php">Limpiar filtros</a>
        </div>

    </form>

    <?php if ($hay_busqueda): ?>

        <h3>Resultados</h3>

        <table>
            <thead>
                <tr>
                    <th class="col-codigo">Código</th>
                    <th>Iniciativa</th>
                    <th>Empresa</th>
                    <th>Sede</th>
                    <th>Región Ejecución</th>
                    <th>Región Impacto</th>
                    <th>Estado</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>
                    <?php while ($fila = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td class="col-codigo"><?php echo limpiar($fila["codigo_interno"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_iniciativa"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_empresa"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_sede"]); ?></td>
                            <td><?php echo limpiar($fila["region_ejecucion"]); ?></td>
                            <td><?php echo limpiar($fila["region_impacto"]); ?></td>
                            <td class="estado"><?php echo limpiar(mostrar_estado($fila["estado_postulacion"])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="mensaje">
                            No se encontraron resultados con los filtros seleccionados.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

</body>
</html>