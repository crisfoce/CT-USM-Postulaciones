<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"]!= "evaluador") {
    header("Location: dashboard.php");
    exit();
}

require_once "conexion.php";

function limpiar($dato){
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

function mostrar_estado($estado){
    if ($estado== "En Revisión"){
        return "Enviada";
    }
    return $estado;
}

$id_evaluador= $_SESSION["id_usuario"];
$nombre_evaluador= $_SESSION["usuario"] ?? "Evaluador";
$rol= $_SESSION["rol"];
$mensaje= "";

$estados_validos= ["Aprobada", "Rechazada", "Cerrada"];

/* REGISTRAR EVALUACIÓN */
if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST["registrar_evaluacion"])){

    $id_postulacion= (int)$_POST["id_postulacion"];
    $comentario= trim($_POST["comentario"]);
    $estado_nuevo= $_POST["estado_nuevo"];

    if($comentario== ""){
        $mensaje= "Debe ingresar un comentario para registrar la evaluación.";
    }
    
    elseif(!in_array($estado_nuevo, $estados_validos)){
        $mensaje= "Debe seleccionar un estado válido.";
    }
    
    else{
        try{
            $conn->begin_transaction();

            $sql_validar= "SELECT id_postulacion
                           FROM postulacion
                           WHERE id_postulacion= ?
                           AND id_evaluador= ?
                           AND estado_postulacion= 'En Revisión'";

            $stmt_validar= $conn->prepare($sql_validar);
            $stmt_validar->bind_param("ii", $id_postulacion, $id_evaluador);
            $stmt_validar->execute();

            $resultado_validar= $stmt_validar->get_result();

            if (!$resultado_validar || $resultado_validar->num_rows== 0){
                throw new Exception("No puedes evaluar esta postulación.");
            }

            $sql_evaluacion= "INSERT INTO evaluacion(id_postulacion, id_evaluador, comentario, estado_nuevo) VALUES(?, ?, ?, ?)";

            $stmt_evaluacion= $conn->prepare($sql_evaluacion);
            $stmt_evaluacion->bind_param("iiss", $id_postulacion, $id_evaluador, $comentario, $estado_nuevo);
            $stmt_evaluacion->execute();

            $sql_update= "UPDATE postulacion
                          SET estado_postulacion= ?
                          WHERE id_postulacion= ?
                          AND id_evaluador= ?
                          AND estado_postulacion= 'En Revisión'";

            $stmt_update= $conn->prepare($sql_update);
            $stmt_update->bind_param("sii", $estado_nuevo, $id_postulacion, $id_evaluador);
            $stmt_update->execute();

            $conn->commit();

            $mensaje= "Evaluación registrada correctamente.";

        }
        catch (Exception $e){
            $conn->rollback();
            $mensaje= "Error al registrar evaluación: " . $e->getMessage();
        }
    }
}

/* OBTENER POSTULACIONES ASIGNADAS AL EVALUADOR */
$sql=  "SELECT p.id_postulacion, p.codigo_interno, p.numero_postulacion, p.nombre_iniciativa, p.nombre_responsable_1, p.nombre_responsable_2, p.region_ejecucion, p.region_impacto, p.presupuesto_total, p.estado_postulacion, e.nombre_empresa,
        COALESCE(GROUP_CONCAT(DISTINCT s.nombre_sede SEPARATOR ', '), 'Sin sede') AS nombre_sede
        FROM postulacion p
        INNER JOIN empresa e ON p.rut_empresa = e.rut_empresa
        LEFT JOIN sede s ON p.id_postulacion = s.id_postulacion
        WHERE p.id_evaluador = ?
        AND p.estado_postulacion = 'En Revisión'
        GROUP BY p.id_postulacion, p.codigo_interno, p.numero_postulacion, p.nombre_iniciativa, p.nombre_responsable_1, p.nombre_responsable_2, p.region_ejecucion, p.region_impacto, p.presupuesto_total, p.estado_postulacion, e.nombre_empresa
        ORDER BY p.numero_postulacion ASC";

$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $id_evaluador);
$stmt->execute();
$resultado= $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluar Postulaciones</title>
    <link rel="stylesheet" href="css/evaluar_postulaciones.css">
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

        <?php if ($rol== "evaluador"): ?>
            <a href="evaluar_postulaciones.php">Evaluar Postulaciones</a>
        <?php endif; ?>
    </div>

    <h2>Evaluar Postulaciones</h2>

    <div class="datos">
        <p>
            <strong>Evaluador:</strong>
            <?php echo limpiar($nombre_evaluador); ?>
        </p>
    </div>

    <?php if ($mensaje!= ""): ?>
        <div class="mensaje">
            <strong><?php echo limpiar($mensaje); ?></strong>
        </div>
    <?php endif; ?>

    <h3>Postulaciones asignadas en revisión</h3>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th class="col-codigo">Código</th>
                <th>Iniciativa</th>
                <th>Empresa</th>
                <th>Sede</th>
                <th>Responsable 1</th>
                <th>Responsable 2</th>
                <th>Región Ejecución</th>
                <th>Región Impacto</th>
                <th>Presupuesto</th>
                <th>Estado</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>

                <?php while ($fila= $resultado->fetch_assoc()): ?>

                    <tr>
                        <td><?php echo limpiar($fila["numero_postulacion"]); ?></td>
                        <td class="col-codigo"><?php echo limpiar($fila["codigo_interno"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_iniciativa"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_empresa"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_sede"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_responsable_1"]); ?></td>
                        <td><?php echo limpiar($fila["nombre_responsable_2"]); ?></td>
                        <td><?php echo limpiar($fila["region_ejecucion"]); ?></td>
                        <td><?php echo limpiar($fila["region_impacto"]); ?></td>

                        <td class="presupuesto">
                            $<?php echo number_format((int)$fila["presupuesto_total"], 0, ",", "."); ?>
                        </td>

                        <td class="estado">
                            <?php echo limpiar(mostrar_estado($fila["estado_postulacion"])); ?>
                        </td>
                    </tr>

                    <tr class="fila-evaluacion">
                        <td colspan="11">

                            <form method="POST" class="form-evaluacion">

                                <input
                                    type="hidden"
                                    name="id_postulacion"
                                    value="<?php echo limpiar($fila["id_postulacion"]); ?>"
                                >

                                <div class="campo-evaluacion">
                                    <label>Nuevo estado</label>
                                    <select name="estado_nuevo" required>
                                        <option value="">Seleccione</option>
                                        <option value="Aprobada">Aprobada</option>
                                        <option value="Rechazada">Rechazada</option>
                                        <option value="Cerrada">Cerrada</option>
                                    </select>
                                </div>

                                <div class="campo-evaluacion">
                                    <label>Comentario</label>
                                    <textarea
                                        name="comentario"
                                        maxlength="500"
                                        required
                                    ></textarea>
                                </div>

                                <div class="campo-evaluacion">
                                    <button
                                        type="submit"
                                        name="registrar_evaluacion"
                                        onclick="return confirm('¿Seguro que deseas registrar esta evaluación?');"
                                    >
                                        Registrar evaluación
                                    </button>
                                </div>

                            </form>

                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>
                <tr>
                    <td colspan="11" class="sin-datos">
                        No tienes postulaciones asignadas en revisión.
                    </td>
                </tr>

            <?php endif; ?>
        </tbody>
    </table>

</div>

</body>
</html>