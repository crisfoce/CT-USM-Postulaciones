USE ct_usm_postulaciones;

DROP VIEW IF EXISTS vista_postulaciones;

CREATE VIEW vista_postulaciones AS SELECT p.id_postulacion, p.id_usuario_responsable, p.id_evaluador, u.nombre_usuario AS nombre_evaluador, p.codigo_interno, p.numero_postulacion, p.fecha_postulacion, 
    p.nombre_responsable_1, p.nombre_responsable_2, p.nombre_iniciativa, p.tipo_iniciativa, p.region_ejecucion, p.region_impacto, p.estado_postulacion, p.presupuesto_total, e.rut_empresa, e.nombre_empresa,
    e.tamano_empresa, e.conv_marco_usm,
    COALESCE(GROUP_CONCAT(DISTINCT s.nombre_sede SEPARATOR ', '), 'Sin sede') AS nombre_sede,
    COUNT(DISTINCT pa.id_participacion) AS cantidad_integrantes,
    COUNT(DISTINCT d.id_documento) AS cantidad_documentos,
    fn_total_semanas(p.id_postulacion) AS total_semanas

FROM postulacion p
INNER JOIN empresa e ON p.rut_empresa= e.rut_empresa
LEFT JOIN usuario u ON p.id_evaluador= u.id_usuario
LEFT JOIN sede s ON p.id_postulacion= s.id_postulacion
LEFT JOIN participacion pa ON p.id_postulacion= pa.id_postulacion
LEFT JOIN documento d ON p.id_postulacion= d.id_postulacion

GROUP BY p.id_postulacion, p.id_usuario_responsable, p.id_evaluador, u.nombre_usuario, p.codigo_interno, p.numero_postulacion, p.fecha_postulacion, p.nombre_responsable_1, p.nombre_responsable_2,
    p.nombre_iniciativa, p.tipo_iniciativa, p.region_ejecucion, p.region_impacto, p.estado_postulacion, p.presupuesto_total, e.rut_empresa, e.nombre_empresa, e.tamano_empresa, e.conv_marco_usm;



/*
Para probarla:
SELECT *
FROM vista_postulaciones;

También se pueden seleccionar algunas columnas:
SELECT codigo_interno, nombre_iniciativa, nombre_empresa, nombre_sede, estado_postulacion
FROM vista_postulaciones;
*/
/*
Para probarla:
SELECT *
FROM vista_postulaciones;

Tambien se pueden seleccionar algunas columnas con 
SELECT columnas
FROM vista_postulaciones;
*/