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

function limpiar($dato){
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

function seleccionado($valor_actual, $valor_opcion){
    return $valor_actual== $valor_opcion ? "selected" : "";
}

function mostrar_estado($estado) {
    if ($estado== "En Revisión") {
        return "Enviada";
    }

    return $estado;
}

$rol= $_SESSION["rol"];
$mensaje= "";
$id_usuario= (int)$_SESSION["id_usuario"];
$modo_editar= false;
$postulacion= null;
$integrantes_edicion= [];
$etapas_edicion= [];

$tipos_iniciativa= ["Nueva", "Existente"];

$regiones= ["Region de Arica y Parinacota", "Region de Tarapaca", "Region de Antofagasta", "Region de Atacama", "Region de Coquimbo", "Region de Valparaiso", "Region Metropolitana de Santiago", "Region del Libertador General Bernardo O'Higgins",
            "Region del Maule", "Region de Nuble", "Region del Biobio", "Region de La Araucania", "Region de Los Rios", "Region de Los Lagos", "Region de Aysen", "Region de Magallanes"];

$sedes= ["Campus Casa Central Valparaiso", "Campus San Joaquin", "Campus Vitacura", "Sede Vina del Mar", "Sede Concepcion"];

if($_SERVER["REQUEST_METHOD"]== "POST" && isset($_POST["accion"]) && $_POST["accion"]== "eliminar_borrador"){
    $id_postulacion= (int)($_POST["id_postulacion"] ?? 0);

    if($id_postulacion>0) {
        $sql_eliminar= "DELETE FROM postulacion WHERE id_postulacion= ? AND id_usuario_responsable= ? AND estado_postulacion = 'Borrador'";

        $stmt_eliminar= $conn->prepare($sql_eliminar);
        $stmt_eliminar->bind_param("ii", $id_postulacion, $id_usuario);
        $stmt_eliminar->execute();

        if($stmt_eliminar->affected_rows>0) {
            $mensaje= "Borrador eliminado correctamente.";
        }
        else {
            $mensaje= "No se pudo eliminar el borrador.";
        }
    }
}
function validar_postulacion_borrador($conn, $id_postulacion, $id_usuario){
    $sql= "SELECT id_postulacion
           FROM postulacion
           WHERE id_postulacion= ?
           AND id_usuario_responsable= ?
           AND estado_postulacion= 'Borrador'";

    $stmt= $conn->prepare($sql);
    $stmt->bind_param("ii", $id_postulacion, $id_usuario);
    $stmt->execute();

    $resultado= $stmt->get_result();

    return $resultado && $resultado->num_rows>0;
}

/* GUARDAR EDICIÓN COMPLETA */
if(isset($_POST["guardar"])){

    $id= (int)$_POST["id"];

    $rut_empresa= $_POST["rut_empresa"];
    $tipo= $_POST["tipo_iniciativa"];
    $region_ejecucion= $_POST["region_ejecucion"];
    $region_impacto= $_POST["region_impacto"];
    $nombre_sede= $_POST["nombre_sede"];

    $resp1= trim($_POST["nombre_responsable_1"]);
    $resp2= trim($_POST["nombre_responsable_2"]);
    $nombre_iniciativa= trim($_POST["nombre_iniciativa"]);
    $objetivo= trim($_POST["objetivo_iniciativa"]);
    $descripcion= trim($_POST["descripcion_iniciativa"]);
    $resultados= trim($_POST["resultados_iniciativa"]);
    $presupuesto= (int)$_POST["presupuesto_total"];

    if(!validar_postulacion_borrador($conn, $id, $id_usuario)){
        $mensaje= "Solo puedes editar postulaciones propias que estén en estado Borrador.";
    }
    
    elseif(!in_array($tipo, $tipos_iniciativa)){
        $mensaje= "Tipo de iniciativa no válido.";
    }
    
    elseif(!in_array($region_ejecucion, $regiones)){
        $mensaje= "Región de ejecución no válida.";
    }
    
    elseif(!in_array($region_impacto, $regiones)){
        $mensaje= "Región de impacto no válida.";
    }
    
    elseif(!in_array($nombre_sede, $sedes)){
        $mensaje= "Sede no válida.";
    }
    
    elseif ($resp1== "" || $resp2== "" || $nombre_iniciativa== "" || $objetivo== "" || $descripcion== "" || $resultados== ""){
        $mensaje= "Debe completar todos los campos obligatorios de la postulación.";
    }
    
    elseif ($presupuesto<0){
        $mensaje= "El presupuesto no puede ser negativo.";
    }
    
    else{
        try{
            $conn->begin_transaction();
            $sql_update= "UPDATE postulacion SET rut_empresa= ?, tipo_iniciativa = ?, region_ejecucion= ?, region_impacto= ?, nombre_responsable_1= ?, nombre_responsable_2= ?, nombre_iniciativa= ?,
                          objetivo_iniciativa= ?, descripcion_iniciativa= ?, resultados_iniciativa= ?, presupuesto_total= ?
                          WHERE id_postulacion= ?
                          AND id_usuario_responsable= ?
                          AND estado_postulacion= 'Borrador'";

            $stmt_update= $conn->prepare($sql_update);

            $stmt_update->bind_param("ssssssssssiii", $rut_empresa, $tipo, $region_ejecucion, $region_impacto, $resp1, $resp2, $nombre_iniciativa, $objetivo, $descripcion, $resultados, $presupuesto, $id, $id_usuario);
            $stmt_update->execute();

            $sql_delete_sede= "DELETE FROM sede WHERE id_postulacion= ?";

            $stmt_delete_sede= $conn->prepare($sql_delete_sede);
            $stmt_delete_sede->bind_param("i", $id);
            $stmt_delete_sede->execute();

            $sql_insert_sede = "INSERT INTO sede(id_postulacion, nombre_sede) VALUES(?, ?)";

            $stmt_insert_sede= $conn->prepare($sql_insert_sede);
            $stmt_insert_sede->bind_param("is", $id, $nombre_sede);
            $stmt_insert_sede->execute();

            $sql_delete_participacion= "DELETE FROM participacion WHERE id_postulacion= ?";

            $stmt_delete_participacion= $conn->prepare($sql_delete_participacion);
            $stmt_delete_participacion->bind_param("i", $id);
            $stmt_delete_participacion->execute();

            $cantidad_integrantes= 0;

            if(isset($_POST["rut_candidato"]) && is_array($_POST["rut_candidato"])){

                for($i= 0; $i<count($_POST["rut_candidato"]); $i++){

                    $rut_candidato= trim($_POST["rut_candidato"][$i]);
                    $nombre_candidato= trim($_POST["nombre_candidato"][$i]);
                    $departamento= trim($_POST["departamento"][$i]);
                    $sede_candidato= trim($_POST["sede_candidato"][$i]);
                    $mail_candidato= trim($_POST["mail_candidato"][$i]);
                    $telefono_candidato= trim($_POST["telefono_candidato"][$i]);
                    $rol_que_cumple= trim($_POST["rol_que_cumple"][$i]);

                    if($rut_candidato== "" && $nombre_candidato== "" && $departamento== "" && $sede_candidato== "" && $mail_candidato== "" && $telefono_candidato== "" && $rol_que_cumple== ""){
                        continue;
                    }

                    if($rut_candidato== "" || $nombre_candidato== "" || $departamento== "" || $sede_candidato== "" || $mail_candidato== "" || $rol_que_cumple== ""){
                        throw new Exception("Si agregas un integrante, debes completar todos sus campos obligatorios.");
                    }

                    $sql_candidato= "INSERT INTO candidato(rut_candidato, nombre_candidato, departamento, sede_candidato, mail_candidato, telefono_candidato) VALUES(?, ?, ?, ?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE nombre_candidato= VALUES(nombre_candidato), departamento= VALUES(departamento), sede_candidato= VALUES(sede_candidato), 
                                                             mail_candidato= VALUES(mail_candidato), telefono_candidato= VALUES(telefono_candidato)";

                    $stmt_candidato = $conn->prepare($sql_candidato);
                    $stmt_candidato->bind_param("ssssss", $rut_candidato, $nombre_candidato, $departamento, $sede_candidato, $mail_candidato, $telefono_candidato);
                    $stmt_candidato->execute();

                    $sql_participacion= "INSERT INTO participacion(id_postulacion, rut_candidato, rol_que_cumple) VALUES(?, ?, ?)";

                    $stmt_participacion= $conn->prepare($sql_participacion);
                    $stmt_participacion->bind_param("iss", $id, $rut_candidato, $rol_que_cumple);
                    $stmt_participacion->execute();

                    $cantidad_integrantes++;
                }
            }

            if($cantidad_integrantes==0){
                throw new Exception("Debe mantener al menos un integrante en el equipo de trabajo.");
            }

            $sql_delete_etapas= "DELETE FROM etapa_cronograma WHERE id_postulacion= ?";

            $stmt_delete_etapas= $conn->prepare($sql_delete_etapas);
            $stmt_delete_etapas->bind_param("i", $id);
            $stmt_delete_etapas->execute();

            $cantidad_etapas= 0;

            if(isset($_POST["nombre_etapa"]) && is_array($_POST["nombre_etapa"])){

                for ($i=0; $i<count($_POST["nombre_etapa"]); $i++) {

                    $nombre_etapa= trim($_POST["nombre_etapa"][$i]);
                    $plazo_semanas= (int)$_POST["plazo_semanas"][$i];
                    $entregable= trim($_POST["entregable"][$i]);

                    if($nombre_etapa== "" && $plazo_semanas== 0 && $entregable== ""){
                        continue;
                    }

                    if($nombre_etapa== "" || $plazo_semanas<=0 || $entregable== "") {
                        throw new Exception("Si agregas una etapa del cronograma, debes completar todos sus campos obligatorios.");
                    }

                    $sql_etapa = "INSERT INTO etapa_cronograma(id_postulacion, nombre_etapa, plazo_semanas, entregable) VALUES(?, ?, ?, ?)";

                    $stmt_etapa= $conn->prepare($sql_etapa);
                    $stmt_etapa->bind_param("isis", $id, $nombre_etapa, $plazo_semanas, $entregable);
                    $stmt_etapa->execute();

                    $cantidad_etapas++;
                }
            }

            if($cantidad_etapas==0){
                throw new Exception("Debe mantener al menos una etapa del cronograma.");
            }

            $conn->commit();

            header("Location: mis_postulaciones.php");
            exit();

        }
        catch(Exception $e){
            $conn->rollback();
            $mensaje= "Error al editar la postulación: " . $e->getMessage();
        }
    }
}

/* CARGAR FORMULARIO DE EDICIÓN */
if(isset($_GET["id"])){

    $modo_editar= true;
    $id= (int)$_GET["id"];

    $sql_editar= "SELECT p.*,COALESCE((SELECT s.nombre_sede FROM sede s WHERE s.id_postulacion = p.id_postulacion LIMIT 1), '') AS nombre_sede
                  FROM postulacion p
                  WHERE p.id_postulacion= ?
                  AND p.id_usuario_responsable= ?
                  AND p.estado_postulacion= 'Borrador'";

    $stmt_editar= $conn->prepare($sql_editar);
    $stmt_editar->bind_param("ii", $id, $id_usuario);
    $stmt_editar->execute();

    $resultado_editar= $stmt_editar->get_result();

    if($resultado_editar && $resultado_editar->num_rows>0){
        $postulacion= $resultado_editar->fetch_assoc();

        $sql_integrantes= "SELECT pa.rol_que_cumple, c.rut_candidato, c.nombre_candidato, c.departamento, c.sede_candidato, c.mail_candidato, c.telefono_candidato
                           FROM participacion pa
                           INNER JOIN candidato c
                           ON pa.rut_candidato = c.rut_candidato
                           WHERE pa.id_postulacion = ?
                           ORDER BY pa.id_participacion ASC";

        $stmt_integrantes= $conn->prepare($sql_integrantes);
        $stmt_integrantes->bind_param("i", $id);
        $stmt_integrantes->execute();
        $resultado_integrantes= $stmt_integrantes->get_result();

        while($fila_integrante= $resultado_integrantes->fetch_assoc()){
            $integrantes_edicion[]= $fila_integrante;
        }

        $sql_etapas= "SELECT nombre_etapa, plazo_semanas, entregable
                     FROM etapa_cronograma
                     WHERE id_postulacion = ?
                     ORDER BY id_etapa_cronograma ASC";

        $stmt_etapas= $conn->prepare($sql_etapas);
        $stmt_etapas->bind_param("i", $id);
        $stmt_etapas->execute();
        $resultado_etapas= $stmt_etapas->get_result();

        while($fila_etapa= $resultado_etapas->fetch_assoc()){
            $etapas_edicion[]= $fila_etapa;
        }

        if(count($integrantes_edicion)==0){
            $integrantes_edicion[]= ["rut_candidato" => "", "nombre_candidato" => "", "departamento" => "", "sede_candidato" => "", "mail_candidato" => "", "telefono_candidato" => "", "rol_que_cumple" => ""];
        }

        if(count($etapas_edicion)==0){
            $etapas_edicion[]= ["nombre_etapa" => "", "plazo_semanas" => "", "entregable" => ""];
        }

    }
    else{
        $mensaje= "Solo puedes editar postulaciones propias que estén en estado Borrador.";
        $modo_editar= false;
    }
}

/* LISTADO MIS POSTULACIONES */
$sql= "SELECT p.id_postulacion,p.codigo_interno,p.numero_postulacion,p.fecha_postulacion, p.nombre_iniciativa,e.nombre_empresa,COALESCE(GROUP_CONCAT(DISTINCT s.nombre_sede SEPARATOR ', '), 'Sin sede') AS nombre_sede,
        p.tipo_iniciativa,p.region_ejecucion,p.region_impacto,p.estado_postulacion,p.presupuesto_total,ev.comentario,ev.fecha_evaluacion
       FROM postulacion p
       INNER JOIN empresa e ON p.rut_empresa = e.rut_empresa
       LEFT JOIN sede s ON p.id_postulacion = s.id_postulacion
       LEFT JOIN evaluacion ev ON p.id_postulacion = ev.id_postulacion
       WHERE p.id_usuario_responsable = ?
       GROUP BY p.id_postulacion, p.codigo_interno, p.numero_postulacion, p.fecha_postulacion, p.nombre_iniciativa, e.nombre_empresa, p.tipo_iniciativa, p.region_ejecucion, p.region_impacto, p.estado_postulacion, p.presupuesto_total
       ORDER BY p.id_postulacion ASC";

$stmt= $conn->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado= $stmt->get_result();

$empresas= $conn->query("SELECT rut_empresa, nombre_empresa FROM empresa ORDER BY nombre_empresa ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Postulaciones</title>
    <link rel="stylesheet" href="css/mis_postulaciones.css">

    <script>
        function actualizarTitulosIntegrantes(){
            const bloques= document.querySelectorAll(".bloque-integrante:not(#plantilla-integrante)");
            bloques.forEach((bloque, index) => {
                const titulo= bloque.querySelector(".titulo-integrante");
                titulo.textContent= "Integrante " + (index + 1);
            });
        }

        function actualizarTitulosEtapas(){
            const bloques= document.querySelectorAll(".bloque-etapa:not(#plantilla-etapa)");
            bloques.forEach((bloque, index) => {
                const titulo= bloque.querySelector(".titulo-etapa");
                titulo.textContent= "Etapa " + (index + 1);
            });
        }

        function agregarIntegrante(){
            const contenedor= document.getElementById("contenedor-integrantes");
            const plantilla= document.getElementById("plantilla-integrante");

            const nuevo= plantilla.cloneNode(true);
            nuevo.removeAttribute("id");
            nuevo.style.display= "block";

            const inputs= nuevo.querySelectorAll("input");
            inputs.forEach(input => {
                input.value= "";

                if(input.dataset.obligatorio=="1"){
                    input.required= true;
                }
            });

            contenedor.appendChild(nuevo);
            actualizarTitulosIntegrantes();
        }

        function eliminarIntegrante(boton){
            const bloques= document.querySelectorAll(".bloque-integrante:not(#plantilla-integrante)");

            if(bloques.length<=1){
                alert("Debe existir al menos un integrante.");
                return;
            }

            boton.closest(".bloque-integrante").remove();
            actualizarTitulosIntegrantes();
        }

        function agregarEtapa(){
            const contenedor= document.getElementById("contenedor-etapas");
            const plantilla= document.getElementById("plantilla-etapa");

            const nuevo= plantilla.cloneNode(true);
            nuevo.removeAttribute("id");
            nuevo.style.display= "block";

            const inputs= nuevo.querySelectorAll("input");
            inputs.forEach(input => {
                input.value= "";

                if(input.dataset.obligatorio=="1"){
                    input.required= true;
                }
            });

            contenedor.appendChild(nuevo);
            actualizarTitulosEtapas();
        }

        function eliminarEtapa(boton){
            const bloques= document.querySelectorAll(".bloque-etapa:not(#plantilla-etapa)");

            if (bloques.length<=1){
                alert("Debe existir al menos una etapa.");
                return;
            }

            boton.closest(".bloque-etapa").remove();
            actualizarTitulosEtapas();
        }
    </script>
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
        <a href="crear_postulacion.php">Crear Postulación</a>
        <a href="mis_postulaciones.php">Mis Postulaciones</a>
    </div>

    <h2>Mis Postulaciones</h2>

    <?php if ($mensaje!=""): ?>
        <div class="mensaje">
            <strong><?php echo limpiar($mensaje); ?></strong>
        </div>
    <?php endif; ?>

    <?php if ($modo_editar && $postulacion): ?>

        <p class="nota">
            Los campos marcados con <span class="obligatorio">*</span> son obligatorios.
        </p>

        <p class="nota">
            El código interno y el número de postulación son generados automáticamente por el sistema y no se pueden modificar.
        </p>

        <form method="POST" class="formulario">

            <input type="hidden" name="id" value="<?php echo limpiar($postulacion["id_postulacion"]); ?>">

            <h3>Datos principales</h3>

            <div class="campo">
                <label>Código interno</label>
                <input
                    type="text"
                    value="<?php echo limpiar($postulacion["codigo_interno"]); ?>"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Número postulación</label>
                <input
                    type="text"
                    value="<?php echo limpiar($postulacion["numero_postulacion"]); ?>"
                    disabled
                >
            </div>

            <div class="campo">
                <label>Empresa <span class="obligatorio">*</span></label>
                <select name="rut_empresa" required>
                    <?php while($empresa= $empresas->fetch_assoc()): ?>
                        <option value="<?php echo limpiar($empresa["rut_empresa"]); ?>"
                            <?php echo seleccionado($postulacion["rut_empresa"], $empresa["rut_empresa"]); ?>>
                            <?php echo limpiar($empresa["nombre_empresa"]); ?> -
                            <?php echo limpiar($empresa["rut_empresa"]); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label>Tipo iniciativa <span class="obligatorio">*</span></label>
                <select name="tipo_iniciativa" required>
                    <?php foreach($tipos_iniciativa as $tipo): ?>
                        <option value="<?php echo limpiar($tipo); ?>"
                            <?php echo seleccionado($postulacion["tipo_iniciativa"], $tipo); ?>>
                            <?php echo limpiar($tipo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Región ejecución <span class="obligatorio">*</span></label>
                <select name="region_ejecucion" required>
                    <?php foreach($regiones as $region): ?>
                        <option value="<?php echo limpiar($region); ?>"
                            <?php echo seleccionado($postulacion["region_ejecucion"], $region); ?>>
                            <?php echo limpiar($region); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Región impacto <span class="obligatorio">*</span></label>
                <select name="region_impacto" required>
                    <?php foreach($regiones as $region): ?>
                        <option value="<?php echo limpiar($region); ?>"
                            <?php echo seleccionado($postulacion["region_impacto"], $region); ?>>
                            <?php echo limpiar($region); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Sede / campus <span class="obligatorio">*</span></label>
                <select name="nombre_sede" required>
                    <?php foreach($sedes as $sede): ?>
                        <option value="<?php echo limpiar($sede); ?>"
                            <?php echo seleccionado($postulacion["nombre_sede"], $sede); ?>>
                            <?php echo limpiar($sede); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label>Responsable 1 <span class="obligatorio">*</span></label>
                <input
                    type="text"
                    name="nombre_responsable_1"
                    maxlength="100"
                    value="<?php echo limpiar($postulacion["nombre_responsable_1"]); ?>"
                    required
                >
            </div>

            <div class="campo">
                <label>Responsable 2 <span class="obligatorio">*</span></label>
                <input
                    type="text"
                    name="nombre_responsable_2"
                    maxlength="100"
                    value="<?php echo limpiar($postulacion["nombre_responsable_2"]); ?>"
                    required
                >
            </div>

            <div class="campo">
                <label>Nombre iniciativa <span class="obligatorio">*</span></label>
                <input
                    type="text"
                    name="nombre_iniciativa"
                    maxlength="150"
                    value="<?php echo limpiar($postulacion["nombre_iniciativa"]); ?>"
                    required
                >
            </div>

            <div class="campo">
                <label>Objetivo <span class="obligatorio">*</span></label>
                <textarea
                    name="objetivo_iniciativa"
                    maxlength="255"
                    required
                ><?php echo limpiar($postulacion["objetivo_iniciativa"]); ?></textarea>
            </div>

            <div class="campo">
                <label>Descripción <span class="obligatorio">*</span></label>
                <textarea
                    name="descripcion_iniciativa"
                    maxlength="255"
                    required
                ><?php echo limpiar($postulacion["descripcion_iniciativa"]); ?></textarea>
            </div>

            <div class="campo">
                <label>Resultados <span class="obligatorio">*</span></label>
                <textarea
                    name="resultados_iniciativa"
                    maxlength="255"
                    required
                ><?php echo limpiar($postulacion["resultados_iniciativa"]); ?></textarea>
            </div>

            <div class="campo">
                <label>Presupuesto <span class="obligatorio">*</span></label>
                <input
                    type="number"
                    name="presupuesto_total"
                    min="0"
                    step="1"
                    value="<?php echo intval($postulacion["presupuesto_total"]); ?>"
                    required
                >
                <span class="ayuda">Ingrese solo números, sin puntos ni signo peso.</span>
            </div>

            <h3>Equipo de trabajo</h3>

            <p class="nota">
                Debe mantener al menos un integrante. Para eliminar uno, presiona “Eliminar integrante” y luego guarda los cambios.
            </p>

            <div id="contenedor-integrantes">

                <?php foreach ($integrantes_edicion as $index => $integrante): ?>
                    <div class="bloque bloque-integrante">
                        <h4 class="titulo-integrante">Integrante <?php echo $index + 1; ?></h4>

                        <div class="campo">
                            <label>RUT candidato <span class="obligatorio">*</span></label>
                            <input type="text" name="rut_candidato[]" maxlength="12" value="<?php echo limpiar($integrante["rut_candidato"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Nombre candidato <span class="obligatorio">*</span></label>
                            <input type="text" name="nombre_candidato[]" maxlength="100" value="<?php echo limpiar($integrante["nombre_candidato"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Departamento <span class="obligatorio">*</span></label>
                            <input type="text" name="departamento[]" maxlength="100" value="<?php echo limpiar($integrante["departamento"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Sede candidato <span class="obligatorio">*</span></label>
                            <input type="text" name="sede_candidato[]" maxlength="100" value="<?php echo limpiar($integrante["sede_candidato"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Mail candidato <span class="obligatorio">*</span></label>
                            <input type="email" name="mail_candidato[]" maxlength="120" value="<?php echo limpiar($integrante["mail_candidato"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Teléfono candidato</label>
                            <input type="text" name="telefono_candidato[]" maxlength="20" value="<?php echo limpiar($integrante["telefono_candidato"]); ?>">
                        </div>

                        <div class="campo">
                            <label>Rol que cumple <span class="obligatorio">*</span></label>
                            <input type="text" name="rol_que_cumple[]" maxlength="100" value="<?php echo limpiar($integrante["rol_que_cumple"]); ?>" data-obligatorio="1" required>
                        </div>

                        <button type="button" class="boton-eliminar" onclick="eliminarIntegrante(this)">
                            Eliminar integrante
                        </button>
                    </div>
                <?php endforeach; ?>

            </div>

            <button type="button" class="boton-agregar" onclick="agregarIntegrante()">Añadir integrante</button>

            <div id="plantilla-integrante" class="bloque bloque-integrante" style="display:none;">
                <h4 class="titulo-integrante">Integrante</h4>

                <div class="campo">
                    <label>RUT candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="rut_candidato[]" maxlength="12" placeholder="Ej: 12.345.678-9" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Nombre candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="nombre_candidato[]" maxlength="100" placeholder="Ej: María González" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Departamento <span class="obligatorio">*</span></label>
                    <input type="text" name="departamento[]" maxlength="100" placeholder="Ej: Informática" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Sede candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="sede_candidato[]" maxlength="100" placeholder="Ej: Campus Casa Central Valparaiso" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Mail candidato <span class="obligatorio">*</span></label>
                    <input type="email" name="mail_candidato[]" maxlength="120" placeholder="Ej: integrante@usm.cl" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Teléfono candidato</label>
                    <input type="text" name="telefono_candidato[]" maxlength="20" placeholder="Ej: +56912345678">
                </div>

                <div class="campo">
                    <label>Rol que cumple <span class="obligatorio">*</span></label>
                    <input type="text" name="rol_que_cumple[]" maxlength="100" placeholder="Ej: Director, Coordinador, Desarrollador" data-obligatorio="1">
                </div>

                <button type="button" class="boton-eliminar" onclick="eliminarIntegrante(this)">
                    Eliminar integrante
                </button>
            </div>

            <h3>Cronograma</h3>

            <p class="nota">
                Debe mantener al menos una etapa. Para eliminar una, presiona “Eliminar etapa” y luego guarda los cambios.
            </p>

            <div id="contenedor-etapas">

                <?php foreach ($etapas_edicion as $index => $etapa): ?>
                    <div class="bloque bloque-etapa">
                        <h4 class="titulo-etapa">Etapa <?php echo $index + 1; ?></h4>

                        <div class="campo">
                            <label>Nombre etapa <span class="obligatorio">*</span></label>
                            <input type="text" name="nombre_etapa[]" maxlength="100" value="<?php echo limpiar($etapa["nombre_etapa"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Plazo semanas <span class="obligatorio">*</span></label>
                            <input type="number" name="plazo_semanas[]" min="1" step="1" value="<?php echo limpiar($etapa["plazo_semanas"]); ?>" data-obligatorio="1" required>
                        </div>

                        <div class="campo">
                            <label>Entregable <span class="obligatorio">*</span></label>
                            <input type="text" name="entregable[]" maxlength="255" value="<?php echo limpiar($etapa["entregable"]); ?>" data-obligatorio="1" required>
                        </div>

                        <button type="button" class="boton-eliminar" onclick="eliminarEtapa(this)">
                            Eliminar etapa
                        </button>
                    </div>
                <?php endforeach; ?>

            </div>

            <button type="button" class="boton-agregar" onclick="agregarEtapa()">Añadir etapa</button>

            <div id="plantilla-etapa" class="bloque bloque-etapa" style="display:none;">
                <h4 class="titulo-etapa">Etapa</h4>

                <div class="campo">
                    <label>Nombre etapa <span class="obligatorio">*</span></label>
                    <input type="text" name="nombre_etapa[]" maxlength="100" placeholder="Ej: Diagnóstico inicial" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Plazo semanas <span class="obligatorio">*</span></label>
                    <input type="number" name="plazo_semanas[]" min="1" step="1" placeholder="Ej: 4" data-obligatorio="1">
                </div>

                <div class="campo">
                    <label>Entregable <span class="obligatorio">*</span></label>
                    <input type="text" name="entregable[]" maxlength="255" placeholder="Ej: Informe de diagnóstico" data-obligatorio="1">
                </div>

                <button type="button" class="boton-eliminar" onclick="eliminarEtapa(this)">
                    Eliminar etapa
                </button>
            </div>

            <div class="acciones-finales">
                <button type="submit" name="guardar">Guardar cambios</button>
            </div>

        </form>

    <?php endif; ?>

    <?php if (!$modo_editar): ?>

        <table>
            <thead>
                <tr>
                    <th>N° Postulación</th>
                    <th class="col-codigo">Código</th>
                    <th>Fecha</th>
                    <th>Iniciativa</th>
                    <th>Empresa</th>
                    <th>Sede</th>
                    <th>Tipo</th>
                    <th>Región Ejecución</th>
                    <th>Región Impacto</th>
                    <th>Estado</th>
                    <th>Presupuesto</th>
                    <th>Comentario</th>
                    <th>Fecha Evaluación</th>
                    <th>Acciones</th>
                    
                </tr>
            </thead>

            <tbody>
                <?php if ($resultado && $resultado->num_rows > 0): ?>

                    <?php while ($fila = $resultado->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo limpiar($fila["numero_postulacion"]); ?></td>
                            <td class="col-codigo"><?php echo limpiar($fila["codigo_interno"]); ?></td>
                            <td><?php echo limpiar($fila["fecha_postulacion"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_iniciativa"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_empresa"]); ?></td>
                            <td><?php echo limpiar($fila["nombre_sede"]); ?></td>
                            <td><?php echo limpiar($fila["tipo_iniciativa"]); ?></td>
                            <td><?php echo limpiar($fila["region_ejecucion"]); ?></td>
                            <td><?php echo limpiar($fila["region_impacto"]); ?></td>

                            <td class="estado">
                                <?php echo limpiar(mostrar_estado($fila["estado_postulacion"])); ?>
                            </td>

                            <td class="presupuesto">
                                $<?php echo number_format((int)$fila["presupuesto_total"], 0, ",", "."); ?>
                            </td>

                            <td>
                                <?php echo limpiar($fila["comentario"]); ?>
                            </td>
                            <td>
                                <?php echo limpiar($fila["fecha_evaluacion"]); ?>
                            </td>
                            <td class="accion">
                                <?php if ($fila["estado_postulacion"] == "Borrador"): ?>

                                    <a href="mis_postulaciones.php?id=<?php echo limpiar($fila["id_postulacion"]); ?>">
                                        Editar
                                    </a>

                                    |

                                    <a
                                        href="enviar_postulacion.php?id=<?php echo limpiar($fila["id_postulacion"]); ?>"
                                        onclick="return confirm('¿Seguro que deseas enviar esta postulación? Pasará a Enviada y no podrás editarla.');"
                                    >
                                        Enviar
                                    </a>

                                    <form
                                        method="POST"
                                        action="mis_postulaciones.php"
                                        class="form-eliminar-borrador"
                                        onsubmit="return confirm('¿Seguro que deseas eliminar este borrador?');"
                                    >
                                        <input type="hidden" name="accion" value="eliminar_borrador">
                                        <input type="hidden" name="id_postulacion" value="<?php echo limpiar($fila["id_postulacion"]); ?>">
                                        <button type="submit" class="boton-eliminar-borrador">Eliminar</button>
                                    </form>

                                <?php elseif ($fila["estado_postulacion"] == "En Revisión"): ?>

                                    Enviada

                                <?php else: ?>

                                    No editable

                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="12">
                            No tienes postulaciones registradas.
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>

</div>

</body>
</html>