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

function limpiar($dato) {
    return htmlspecialchars((string)$dato, ENT_QUOTES, "UTF-8");
}

$rol= $_SESSION["rol"];
$mensaje= "";

$tipos_iniciativa= ["Nueva", "Existente"];

$regiones= ["Region de Arica y Parinacota", "Region de Tarapaca", "Region de Antofagasta", "Region de Atacama", "Region de Coquimbo", "Region de Valparaiso", "Region Metropolitana de Santiago",
            "Region del Libertador General Bernardo O'Higgins", "Region del Maule", "Region de Nuble", "Region del Biobio", "Region de La Araucania", "Region de Los Rios", "Region de Los Lagos",
            "Region de Aysen", "Region de Magallanes"];

$sedes= ["Campus Casa Central Valparaiso", "Campus San Joaquin", "Campus Vitacura", "Sede Vina del Mar", "Sede Concepcion"];

$empresas= $conn->query("SELECT rut_empresa, nombre_empresa
                         FROM empresa
                         ORDER BY nombre_empresa ASC");


if ($_SERVER["REQUEST_METHOD"]== "POST"){
    $id_usuario_responsable = $_SESSION["id_usuario"];

    $rut_empresa= $_POST["rut_empresa"];
    $tipo= $_POST["tipo"];
    $region_ejecucion= $_POST["region_ejecucion"];
    $region_impacto= $_POST["region_impacto"];
    $nombre_sede= $_POST["nombre_sede"];

    $resp1= trim($_POST["resp1"]);
    $resp2= trim($_POST["resp2"]);
    $nombre= trim($_POST["nombre"]);
    $objetivo= trim($_POST["objetivo"]);
    $descripcion= trim($_POST["descripcion"]);
    $resultados= trim($_POST["resultados"]);
    $presupuesto= (int)$_POST["presupuesto"];
    $estado_postulacion= "Borrador";

    if(!in_array($tipo, $tipos_iniciativa)){
        $mensaje = "Tipo de iniciativa no válido.";
    }

    elseif(!in_array($region_ejecucion, $regiones)){
        $mensaje = "Región de ejecución no válida.";
    }
    
    elseif(!in_array($region_impacto, $regiones)){
        $mensaje = "Región de impacto no válida.";
    }
    
    elseif(!in_array($nombre_sede, $sedes)){
        $mensaje = "Sede no válida.";
    }
    
    elseif($resp1== "" || $resp2== "" || $nombre== "" || $objetivo== "" || $descripcion== "" || $resultados== ""){
        $mensaje= "Debe completar todos los campos obligatorios de la postulación.";
    }

    elseif($presupuesto<0) {
        $mensaje= "El presupuesto no puede ser negativo.";
    }
    
    else{
        try{
            $conn->begin_transaction();

            $sql_numero= "SELECT COALESCE(MAX(numero_postulacion), 0) + 1 AS siguiente_numero
                          FROM postulacion";

            $resultado_numero= $conn->query($sql_numero);
            $fila_numero= $resultado_numero->fetch_assoc();

            $numero= (int)$fila_numero["siguiente_numero"];

            $ano= date("Y");
            $codigo= "CT-" . $ano . "-" . str_pad($numero, 4, "0", STR_PAD_LEFT);

            $sql_postulacion = "INSERT INTO postulacion(rut_empresa, codigo_interno, fecha_postulacion, tipo_iniciativa, region_ejecucion, region_impacto, estado_postulacion, nombre_responsable_1, nombre_responsable_2,
                                                        numero_postulacion, nombre_iniciativa, objetivo_iniciativa, descripcion_iniciativa, resultados_iniciativa, presupuesto_total, id_usuario_responsable)
                                VALUES(?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_postulacion= $conn->prepare($sql_postulacion);

            $stmt_postulacion->bind_param("ssssssssissssii", $rut_empresa, $codigo, $tipo, $region_ejecucion, $region_impacto, $estado_postulacion, $resp1,
                                          $resp2, $numero, $nombre, $objetivo, $descripcion, $resultados, $presupuesto, $id_usuario_responsable);

            $stmt_postulacion->execute();
            $id_postulacion= $conn->insert_id;

            $sql_sede= "INSERT INTO sede(id_postulacion, nombre_sede) VALUES(?, ?)";

            $stmt_sede = $conn->prepare($sql_sede);
            $stmt_sede->bind_param("is", $id_postulacion, $nombre_sede);
            $stmt_sede->execute();

            if (!empty($_POST["otros_documentos"])) {
                $otros_documentos = trim($_POST["otros_documentos"]);

                if ($otros_documentos!= "") {
                    $sql_documento = "CALL sp_agregar_documento(?, ?)";
                    $stmt_documento = $conn->prepare($sql_documento);
                    $stmt_documento->bind_param("is", $id_postulacion, $otros_documentos);
                    $stmt_documento->execute();
                }
            }

            $cantidad_integrantes= 0;

            if (isset($_POST["rut_candidato"]) && is_array($_POST["rut_candidato"])) {

                for ($i=0; $i<count($_POST["rut_candidato"]); $i++) {

                    $rut_candidato= trim($_POST["rut_candidato"][$i]);
                    $nombre_candidato= trim($_POST["nombre_candidato"][$i]);
                    $departamento= trim($_POST["departamento"][$i]);
                    $sede_candidato= trim($_POST["sede_candidato"][$i]);
                    $mail_candidato= trim($_POST["mail_candidato"][$i]);
                    $telefono_candidato= trim($_POST["telefono_candidato"][$i]);
                    $rol_que_cumple= trim($_POST["rol_que_cumple"][$i]);

                    if($rut_candidato == "" && $nombre_candidato == "" && $departamento == "" && $sede_candidato == "" && $mail_candidato == "" && $telefono_candidato == "" && $rol_que_cumple == ""){
                        continue;
                    }

                    if($rut_candidato == "" || $nombre_candidato == "" || $departamento == "" || $sede_candidato == "" || $mail_candidato == "" || $rol_que_cumple == ""){
                        throw new Exception("Si agregas un integrante, debes completar todos sus campos obligatorios.");
                    }

                    $sql_candidato = "
                        INSERT INTO candidato(rut_candidato,nombre_candidato, departamento, sede_candidato, mail_candidato, telefono_candidato) VALUES (?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE nombre_candidato = VALUES(nombre_candidato), departamento = VALUES(departamento), sede_candidato = VALUES(sede_candidato), mail_candidato = VALUES(mail_candidato), telefono_candidato = VALUES(telefono_candidato)";

                    $stmt_candidato= $conn->prepare($sql_candidato);
                    $stmt_candidato->bind_param("ssssss", $rut_candidato, $nombre_candidato, $departamento, $sede_candidato, $mail_candidato, $telefono_candidato);
                    $stmt_candidato->execute();

                    $sql_participacion= "INSERT INTO participacion(id_postulacion, rut_candidato, rol_que_cumple) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE rol_que_cumple = VALUES(rol_que_cumple)";

                    $stmt_participacion= $conn->prepare($sql_participacion);
                    $stmt_participacion->bind_param("iss", $id_postulacion, $rut_candidato, $rol_que_cumple);
                    $stmt_participacion->execute();

                    $cantidad_integrantes++;
                }
            }

            if($cantidad_integrantes==0){
                throw new Exception("Debe ingresar al menos un integrante en el equipo de trabajo.");
            }

            $cantidad_etapas= 0;

            if(isset($_POST["nombre_etapa"]) && is_array($_POST["nombre_etapa"])){

                for ($i=0; $i<count($_POST["nombre_etapa"]); $i++) {

                    $nombre_etapa= trim($_POST["nombre_etapa"][$i]);
                    $plazo_semanas= (int)$_POST["plazo_semanas"][$i];
                    $entregable= trim($_POST["entregable"][$i]);

                    if($nombre_etapa== "" && $plazo_semanas== 0 && $entregable== ""){
                        continue;
                    }

                    if($nombre_etapa== "" || $plazo_semanas<=0 || $entregable== ""){
                        throw new Exception("Si agregas una etapa del cronograma, debes completar todos sus campos obligatorios.");
                    }

                    $sql_etapa= "INSERT INTO etapa_cronograma(id_postulacion, nombre_etapa, plazo_semanas, entregable) VALUES(?, ?, ?, ?)";

                    $stmt_etapa= $conn->prepare($sql_etapa);
                    $stmt_etapa->bind_param("isis", $id_postulacion, $nombre_etapa, $plazo_semanas, $entregable);
                    $stmt_etapa->execute();

                    $cantidad_etapas++;
                }
            }

            if($cantidad_etapas == 0){
                throw new Exception("Debe ingresar al menos una etapa del cronograma.");
            }

            $conn->commit();

            $mensaje= "Postulación creada correctamente como Borrador. Código interno generado: " . $codigo . ". Número de postulación: " . $numero . ".";

        }
        catch (Exception $e){
            $conn->rollback();
            $mensaje= "Error al crear la postulación: " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Postulación</title>
    <link rel="stylesheet" href="css/crear_postulacion.css">

    <script>
        function actualizarTitulosIntegrantes(){
            const bloques= document.querySelectorAll(".bloque-integrante:not(#plantilla-integrante)");
            bloques.forEach((bloque, index) =>{
                const titulo= bloque.querySelector(".titulo-integrante");
                titulo.textContent= "Integrante " + (index + 1);
            });
        }

        function actualizarTitulosEtapas() {
            const bloques= document.querySelectorAll(".bloque-etapa:not(#plantilla-etapa)");
            bloques.forEach((bloque, index) => {
                const titulo= bloque.querySelector(".titulo-etapa");
                titulo.textContent= "Etapa " + (index + 1);
            });
        }

        function agregarIntegrante() {
            const contenedor= document.getElementById("contenedor-integrantes");
            const plantilla= document.getElementById("plantilla-integrante");

            const nuevo= plantilla.cloneNode(true);
            nuevo.removeAttribute("id");
            nuevo.style.display= "block";

            const inputs= nuevo.querySelectorAll("input");
            inputs.forEach(input =>{
                input.value= "";

                if (input.dataset.obligatorio == "1"){
                    input.required= true;
                }
            });

            contenedor.appendChild(nuevo);
            actualizarTitulosIntegrantes();
        }

        function eliminarIntegrante(boton){
            const bloques= document.querySelectorAll(".bloque-integrante:not(#plantilla-integrante)");

            if (bloques.length<=1) {
                alert("Debe existir al menos un integrante.");
                return;
            }

            boton.closest(".bloque-integrante").remove();
            actualizarTitulosIntegrantes();
        }

        function agregarEtapa() {
            const contenedor= document.getElementById("contenedor-etapas");
            const plantilla= document.getElementById("plantilla-etapa");

            const nuevo= plantilla.cloneNode(true);
            nuevo.removeAttribute("id");
            nuevo.style.display= "block";

            const inputs = nuevo.querySelectorAll("input");
            inputs.forEach(input => {
                input.value = "";

                if (input.dataset.obligatorio== "1"){
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

    <h2>Crear Postulación | Los campos marcados con <span class="obligatorio">*</span> son obligatorios.</h2>

    <?php if ($mensaje != ""): ?>
        <div class="mensaje">
            <strong><?php echo limpiar($mensaje); ?></strong>
        </div>
    <?php endif; ?>

    <form method="POST" class="formulario">

        <h3>Datos principales</h3>

        <div class="campo">
            <label>Empresa <span class="obligatorio">*</span></label>
            <select name="rut_empresa" required>
                <option value="">Seleccione una empresa</option>

                <?php while ($empresa = $empresas->fetch_assoc()): ?>
                    <option value="<?php echo limpiar($empresa["rut_empresa"]); ?>">
                        <?php echo limpiar($empresa["nombre_empresa"]); ?> -
                        <?php echo limpiar($empresa["rut_empresa"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="campo">
            <label>Tipo iniciativa <span class="obligatorio">*</span></label>
            <select name="tipo" required>
                <option value="">Seleccione</option>
                <option value="Nueva">Nueva</option>
                <option value="Existente">Existente</option>
            </select>
        </div>

        <div class="campo">
            <label>Región ejecución <span class="obligatorio">*</span></label>
            <select name="region_ejecucion" required>
                <option value="">Seleccione región</option>

                <?php foreach ($regiones as $region): ?>
                    <option value="<?php echo limpiar($region); ?>">
                        <?php echo limpiar($region); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label>Región impacto <span class="obligatorio">*</span></label>
            <select name="region_impacto" required>
                <option value="">Seleccione región</option>

                <?php foreach ($regiones as $region): ?>
                    <option value="<?php echo limpiar($region); ?>">
                        <?php echo limpiar($region); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label>Sede / campus <span class="obligatorio">*</span></label>
            <select name="nombre_sede" required>
                <option value="">Seleccione sede</option>

                <?php foreach ($sedes as $sede): ?>
                    <option value="<?php echo limpiar($sede); ?>">
                        <?php echo limpiar($sede); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campo">
            <label>Responsable 1 <span class="obligatorio">*</span></label>
            <input
                type="text"
                name="resp1"
                maxlength="100"
                placeholder="Ej: Prof. Juan Pérez"
                required
            >
            <span class="ayuda">Ingrese el nombre del responsable académico principal.</span>
        </div>

        <div class="campo">
            <label>Responsable 2 <span class="obligatorio">*</span></label>
            <input
                type="text"
                name="resp2"
                maxlength="100"
                placeholder="Ej: Coordinador CT-USM"
                required
            >
            <span class="ayuda">Ingrese el nombre del segundo responsable o coordinador.</span>
        </div>

        <div class="campo">
            <label>Nombre iniciativa <span class="obligatorio">*</span></label>
            <input
                type="text"
                name="nombre"
                maxlength="150"
                placeholder="Ej: Plataforma de monitoreo energético"
                required
            >
            <span class="ayuda">Ingrese un nombre claro y breve para la iniciativa.</span>
        </div>

        <div class="campo">
            <label>Objetivo <span class="obligatorio">*</span></label>
            <textarea
                name="objetivo"
                maxlength="255"
                placeholder="Ej: Desarrollar una solución tecnológica para optimizar el consumo energético."
                required
            ></textarea>
            <span class="ayuda">Máximo 255 caracteres.</span>
        </div>

        <div class="campo">
            <label>Descripción <span class="obligatorio">*</span></label>
            <textarea
                name="descripcion"
                maxlength="255"
                placeholder="Ej: La iniciativa consiste en implementar sensores y una plataforma web para visualizar datos en tiempo real."
                required
            ></textarea>
            <span class="ayuda">Máximo 255 caracteres.</span>
        </div>

        <div class="campo">
            <label>Resultados <span class="obligatorio">*</span></label>
            <textarea
                name="resultados"
                maxlength="255"
                placeholder="Ej: Informe técnico, prototipo funcional y validación con usuarios."
                required
            ></textarea>
            <span class="ayuda">Máximo 255 caracteres.</span>
        </div>

        <div class="campo">
            <label>Presupuesto <span class="obligatorio">*</span></label>
            <input
                type="number"
                name="presupuesto"
                min="0"
                step="1"
                placeholder="Ej: 5000000"
                required
            >
            <span class="ayuda">Ingrese solo números sin puntos.</span>
        </div>

        <div class="campo">
            <label>Otros documentos</label>
            <input
                type="text"
                name="otros_documentos"
                maxlength="255"
                placeholder="Ej: Texto de apoyo"
            >
        </div>

        <h3>Equipo de trabajo | Debe ingresar al menos un integrante.</h3>

        <div id="contenedor-integrantes">

            <div class="bloque bloque-integrante">
                <h4 class="titulo-integrante">Integrante 1</h4>

                <div class="campo">
                    <label>RUT candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="rut_candidato[]" maxlength="12" placeholder="Ej: 12.345.678-9" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Nombre candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="nombre_candidato[]" maxlength="100" placeholder="Ej: María González" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Departamento <span class="obligatorio">*</span></label>
                    <input type="text" name="departamento[]" maxlength="100" placeholder="Ej: Informática" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Sede candidato <span class="obligatorio">*</span></label>
                    <input type="text" name="sede_candidato[]" maxlength="100" placeholder="Ej: Campus Casa Central Valparaiso" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Mail candidato <span class="obligatorio">*</span></label>
                    <input type="email" name="mail_candidato[]" maxlength="120" placeholder="Ej: integrante@usm.cl" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Teléfono candidato</label>
                    <input type="text" name="telefono_candidato[]" maxlength="20" placeholder="Ej: +56912345678">
                </div>

                <div class="campo">
                    <label>Rol que cumple <span class="obligatorio">*</span></label>
                    <input type="text" name="rol_que_cumple[]" maxlength="100" placeholder="Ej: Director, Coordinador, Desarrollador" data-obligatorio="1" required>
                </div>
            </div>

        </div>

        <button type="button" class="boton-agregar" onclick="agregarIntegrante()">
            Añadir integrante
        </button>

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

        <h3>Cronograma | Debe ingresar al menos una etapa.</h3>

        <div id="contenedor-etapas">

            <div class="bloque bloque-etapa">
                <h4 class="titulo-etapa">Etapa 1</h4>

                <div class="campo">
                    <label>Nombre etapa <span class="obligatorio">*</span></label>
                    <input type="text" name="nombre_etapa[]" maxlength="100" placeholder="Ej: Diagnóstico inicial" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Plazo semanas <span class="obligatorio">*</span></label>
                    <input type="number" name="plazo_semanas[]" min="1" step="1" placeholder="Ej: 4" data-obligatorio="1" required>
                </div>

                <div class="campo">
                    <label>Entregable <span class="obligatorio">*</span></label>
                    <input type="text" name="entregable[]" maxlength="255" placeholder="Ej: Informe de diagnóstico" data-obligatorio="1" required>
                </div>
            </div>

        </div>

        <button type="button" class="boton-agregar" onclick="agregarEtapa()">
            Añadir etapa
        </button>

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
            <button type="submit" name="accion" value="Guardar">
                Guardar
            </button>
        </div>

    </form>

</div>

</body>
</html>