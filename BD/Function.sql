USE ct_usm_postulaciones;

DROP FUNCTION IF EXISTS fn_total_semanas;

DELIMITER //

CREATE FUNCTION fn_total_semanas(p_id_postulacion INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;
    SELECT COALESCE(SUM(plazo_semanas), 0)
    INTO total
    FROM etapa_cronograma
    WHERE id_postulacion= p_id_postulacion;
    RETURN total;
END //

DELIMITER ;

/*
Para probarla:
SELECT fn_total_semanas(1) AS total_semanas;

Para todas las postulaciones:
SELECT id_postulacion, codigo_interno, nombre_iniciativa, fn_total_semanas(id_postulacion) AS total_semanas
FROM postulacion;
*/