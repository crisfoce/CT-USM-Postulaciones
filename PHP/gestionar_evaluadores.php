<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["rol"]!= "admin") {
    header("Location: dashboard.php");
    exit();
}

require_once "conexion.php";

function limpiar($dato){
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

$rol= $_SESSION["rol"];
$mensaje= "";

/* CREAR NUEVO EVALUADOR */
if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST["crear_evaluador"])) {

    $nombre_usuario= trim($_POST["nombre_usuario"]);
    $correo= trim($_POST["correo"]);
    $password_usuario= trim($_POST["password_usuario"]);

    if($nombre_usuario== "" || $correo== "" || $password_usuario== ""){
        $mensaje= "Debe completar todos los campos.";
    }
    else{
        try{
            $sql_insert= "INSERT INTO usuario(nombre_usuario, correo, password_usuario, rol, activo) VALUES (?, ?, ?, 'evaluador', TRUE)";

            $stmt_insert= $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $nombre_usuario, $correo, $password_usuario);

            $stmt_insert->execute();

            $mensaje= "Evaluador creado correctamente.";

        }
        catch (Exception $e){
            $mensaje= "No se pudo crear el evaluador. Revisa si el correo ya existe.";
        }
    }
}

/* ACTIVAR O DESACTIVAR EVALUADOR */
if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST["cambiar_estado"])) {

    $id_evaluador= (int)$_POST["id_evaluador"];
    $nuevo_estado= (int)$_POST["nuevo_estado"];

    if($nuevo_estado!=0 && $nuevo_estado!=1) {
        $mensaje= "Estado no válido.";
    }
    else{
        try{
            $sql_update= "UPDATE usuario
                          SET activo = ?
                          WHERE id_usuario = ?
                          AND rol = 'evaluador'";

            $stmt_update= $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $nuevo_estado, $id_evaluador);

            $stmt_update->execute();

            if($stmt_update->affected_rows>0){
                $mensaje= "Estado del evaluador actualizado correctamente.";
            }
            else{
                $mensaje= "No se realizó ningún cambio.";
            }

        }
        catch (Exception $e) {
            $mensaje= "Error al actualizar evaluador: " . $e->getMessage();
        }
    }
}

/* ASIGNAR EVALUADOR A POSTULACIÓN */
if ($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST["asignar_evaluador"])) {

    $id_postulacion= (int)$_POST["id_postulacion"];
    $id_evaluador= (int)$_POST["id_evaluador_asignado"];

    try{
        $sql_asignar = "UPDATE postulacion
                        SET id_evaluador= ?
                        WHERE id_postulacion= ?
                        AND estado_postulacion= 'En Revisión'";

        $stmt_asignar= $conn->prepare($sql_asignar);
        $stmt_asignar->bind_param("ii", $id_evaluador, $id_postulacion);

        $stmt_asignar->execute();

        if($stmt_asignar->affected_rows>0) {
            $mensaje= "Evaluador asignado correctamente.";
        }
        else{
            $mensaje= "No se realizó la asignación.";
        }

    }
    catch (Exception $e) {
        $mensaje= "Error al asignar evaluador: " . $e->getMessage();
    }
}

/* OBTENER SOLO EVALUADORES */
$evaluadores= $conn->query("SELECT id_usuario, nombre_usuario, correo, activo
                            FROM usuario
                            WHERE rol= 'evaluador'
                            ORDER BY nombre_usuario ASC");

/* OBTENER EVALUADORES ACTIVOS PARA ASIGNAR */
$evaluadores_activos= $conn->query("SELECT id_usuario, nombre_usuario
                                    FROM usuario
                                    WHERE rol= 'evaluador'
                                    AND activo= TRUE
                                    ORDER BY nombre_usuario ASC");

/* OBTENER POSTULACIONES EN REVISIÓN */
$postulaciones_en_revision= $conn->query("SELECT p.id_postulacion, p.numero_postulacion, p.codigo_interno, p.nombre_iniciativa, p.estado_postulacion, u.nombre_usuario AS nombre_evaluador
                                         FROM postulacion p
                                         LEFT JOIN usuario u
                                         ON p.id_evaluador= u.id_usuario
                                         WHERE p.estado_postulacion= 'En Revisión'
                                         ORDER BY p.numero_postulacion ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Evaluadores</title>
    <link rel="stylesheet" href="css/gestionar_evaluadores.css">
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

        <?php if ($rol == "postulante"): ?>
            <a href="crear_postulacion.php">Crear Postulación</a>
            <a href="mis_postulaciones.php">Mis Postulaciones</a>
        <?php endif; ?>

        <?php if ($rol == "evaluador"): ?>
            <a href="evaluar_postulaciones.php">Evaluar Postulaciones</a>
        <?php endif; ?>

        <?php if ($rol == "admin"): ?>
            <a href="gestionar_evaluadores.php">Gestionar Evaluadores</a>
        <?php endif; ?>
    </div>

    <h2>Gestionar Evaluadores</h2>

    <?php if ($mensaje != ""): ?>
        <div class="mensaje">
            <strong><?php echo limpiar($mensaje); ?></strong>
        </div>
    <?php endif; ?>

    <h3>Crear nuevo evaluador</h3>

    <form method="POST" class="formulario">

        <div class="campo">
            <label>Nombre</label>
            <input
                type="text"
                name="nombre_usuario"
                maxlength="100"
                placeholder="Ej: Evaluador CT-USM"
                required
            >
        </div>

        <div class="campo">
            <label>Correo</label>
            <input
                type="email"
                name="correo"
                maxlength="120"
                placeholder="Ej: evaluador2@usm.cl"
                required
            >
        </div>

        <div class="campo">
            <label>Contraseña</label>
            <input
                type="password"
                name="password_usuario"
                maxlength="100"
                placeholder="Ej: 1234"
                required
            >
        </div>

        <button type="submit" name="crear_evaluador">
            Crear evaluador
        </button>

    </form>

    <h3>Evaluadores del sistema</h3>

    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acción</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($evaluadores && $evaluadores->num_rows > 0): ?>

                <?php while ($evaluador = $evaluadores->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo limpiar($evaluador["nombre_usuario"]); ?></td>

                        <td><?php echo limpiar($evaluador["correo"]); ?></td>

                        <td>
                            <?php if ($evaluador["activo"]): ?>
                                <span class="estado-activo">Activo</span>
                            <?php else: ?>
                                <span class="estado-inactivo">Inactivo</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($evaluador["activo"]): ?>

                                <form method="POST" style="display:inline;">
                                    <input
                                        type="hidden"
                                        name="id_evaluador"
                                        value="<?php echo limpiar($evaluador["id_usuario"]); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="nuevo_estado"
                                        value="0"
                                    >

                                    <button
                                        type="submit"
                                        name="cambiar_estado"
                                        class="boton-peligro"
                                        onclick="return confirm('¿Seguro que deseas desactivar este evaluador?');"
                                    >
                                        Desactivar
                                    </button>
                                </form>

                            <?php else: ?>

                                <form method="POST" style="display:inline;">
                                    <input
                                        type="hidden"
                                        name="id_evaluador"
                                        value="<?php echo limpiar($evaluador["id_usuario"]); ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="nuevo_estado"
                                        value="1"
                                    >

                                    <button
                                        type="submit"
                                        name="cambiar_estado"
                                        class="boton-secundario"
                                        onclick="return confirm('¿Seguro que deseas activar este evaluador?');"
                                    >
                                        Activar
                                    </button>
                                </form>

                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4">
                        No hay evaluadores registrados.
                    </td>
                </tr>

            <?php endif; ?>
        </tbody>
    </table>

    <h3>Asignar evaluador a postulación enviada</h3>

    <form method="POST" class="formulario">

        <div class="campo">
            <label>Postulación</label>
            <select name="id_postulacion" required>
                <option value="">Seleccione una postulación</option>

                <?php if ($postulaciones_en_revision && $postulaciones_en_revision->num_rows > 0): ?>
                    <?php while ($postulacion = $postulaciones_en_revision->fetch_assoc()): ?>
                        <option value="<?php echo limpiar($postulacion["id_postulacion"]); ?>">
                            <?php
                                echo limpiar(
                                    "N° " . $postulacion["numero_postulacion"] .
                                    " - " . $postulacion["codigo_interno"] .
                                    " - " . $postulacion["nombre_iniciativa"] .
                                    " - Evaluador actual: " . ($postulacion["nombre_evaluador"] ?? "Sin asignar")
                                );
                            ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="campo">
            <label>Evaluador</label>
            <select name="id_evaluador_asignado" required>
                <option value="">Seleccione un evaluador</option>

                <?php if ($evaluadores_activos && $evaluadores_activos->num_rows > 0): ?>
                    <?php while ($evaluador_activo = $evaluadores_activos->fetch_assoc()): ?>
                        <option value="<?php echo limpiar($evaluador_activo["id_usuario"]); ?>">
                            <?php echo limpiar($evaluador_activo["nombre_usuario"]); ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>

        <button
            type="submit"
            name="asignar_evaluador"
            onclick="return confirm('¿Seguro que deseas asignar este evaluador?');"
        >
            Asignar evaluador
        </button>

    </form>

</div>

</body>
</html>